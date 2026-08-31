# Blueprint: Storno / Credit Note en el flujo de webhooks a HubSpot

**Autor:** Claude (Sonnet 5)
**Fecha:** 2026-08-31
**Versión:** 0.3.0 (implementado en local, pendiente de subir a producción)
**Estado:** 🟢 Fases 1-3 completadas y probadas en local. Fase 4 en curso.

---

## 0. Corrección respecto a la v0.1.0

La versión anterior de este plan diseñaba el flujo contra **Solve** (`admin.mgmsolve.concloo.net`) como sistema a reemplazar. **Esto es incorrecto**: Solve está abandonado, nadie trabaja con él, y no debe usarse como referencia de arquitectura.

El flujo real en producción es otro, ya implementado en `basel-child` y verificado en código:

> Al actualizarse el estado de un pedido, WooCommerce dispara su **webhook nativo** (`order.updated`). El tema `basel-child` intercepta ese payload y le **inyecta la URL firmada del PDF de la factura**. HubSpot (a través de un receptor externo, "vpsbridge") recibe ese payload y ahí obtiene el enlace a la factura.

Este plan v0.2.0 reutiliza exactamente ese mismo mecanismo para el Storno/nota de abono, en lugar de crear una pieza nueva desconectada.

---

## 1. Arquitectura actual (verificada en código)

Módulo: `wp-content/themes/basel-child/inc/woocommerce/`

```
inc/woocommerce/
├── loader.php            # require de config.php + invoice-endpoint.php + webhook-filters.php
├── config.php            # constantes: WEBHOOK_NEW_ORDER_TTL (120s), INVOICE_SECRET (HMAC key)
├── invoice-endpoint.php  # genera/sirve el PDF de factura vía URL firmada
└── webhook-filters.php   # inyecta esa URL en el payload del webhook WooCommerce
```

**Flujo actual (solo factura):**

```
Pedido → cambio de estado
        │
        ▼
WooCommerce dispara webhook nativo "order.updated"
        │
        ▼
woocommerce_webhook_payload (filtro)
  → mgmit_add_invoice_url_to_webhook_payload()
  → llama a mgmit_get_invoice_pdf_url($order_id)
     → URL firmada: admin-ajax.php?action=mgmit_invoice_pdf&order_id=X&token=HMAC-SHA256(order_id, INVOICE_SECRET)
  → añade $payload['invoice_pdf_url']
        │
        ▼
WooCommerce hace POST del payload (con invoice_pdf_url) al endpoint configurado en
WooCommerce → Ajustes → Avanzado → Webhooks ("vpsbridge" → HubSpot)
        │
        ▼
Cuando HubSpot/vpsbridge visita esa URL:
  mgmit_serve_invoice_pdf() valida el token (hash_equals) y llama a
  wcpdf_get_document( 'invoice', $order, true )->get_pdf()
  → PDF generado on-demand por WPO WCPDF, sin sesión ni login
```

**Nota importante:** la numeración de factura que usa HubSpot hoy ya sale de **WPO WCPDF** (`wcpdf_get_document('invoice', ...)`), no de Solve. Esto significa que el corte con Solve, al menos para la factura estándar, **ya se hizo de facto** en algún momento al construirse este flujo — Solve puede haber quedado como mero espejo/log histórico.

---

## 2. Objetivo del cambio

Replicar el mismo patrón para el caso `cancelled`: cuando un pedido pasa a cancelado, el payload del webhook `order.updated` debe incluir también un enlace firmado al **PDF de la nota de abono (credit-note/Storno)**, generado on-demand igual que la factura — sin tocar el mecanismo de entrega a HubSpot, que ya funciona y no se toca.

**No se necesita:**
- Ningún plugin nuevo aislado. Se añade como módulo más de `inc/woocommerce/`, seleccionando el mismo patrón de archivos ya existente.
- Ninguna referencia a Solve en tiempo de ejecución.

**Ampliación posterior (2026-08-31):** además del enlace en el webhook a HubSpot, el usuario pidió que el cliente reciba también un email con la nota de abono adjunta, tanto al cancelar como al reembolsar un pedido. Ver §3.6.

---

## 3. Diseño técnico

### 3.1 Archivos creados/tocados (estado real, no el planeado inicialmente)

```
inc/woocommerce/
├── loader.php                  # (editado) requires de todo lo nuevo
├── class-credit-note.php       # (nuevo) clase WPO\IPS\Documents\CreditNote
├── credit-note-document.php    # (nuevo) registra la clase en WPO WCPDF
├── credit-note-endpoint.php    # (nuevo) mgmit_get_storno_pdf_url() / mgmit_serve_storno_pdf()
├── credit-note-mailer.php      # (nuevo) email al cliente al cancelar (ver §3.6)
└── webhook-filters.php         # (editado) sobrescribe invoice_pdf_url si status === 'cancelled'

woocommerce/pdf/MeGeMit/
└── credit-note.php             # (nuevo) plantilla PDF, ver §3.7 (formato final)
```

### 3.2 `class-credit-note.php` + `credit-note-document.php` — registro del tipo de documento
- **Corrección respecto al plan original:** el filtro real de WPO WCPDF para registrar un tipo de documento propio es `wpo_wcpdf_document_classes` (array `class_name => instancia`), **no** `wpo_wcpdf_document_types` como se escribió inicialmente en este plan por error. Verificado leyendo `includes/Documents.php` del plugin.
- `CreditNote` extiende `WPO\IPS\Documents\OrderDocumentMethods` (misma clase base que usan `Invoice` y `PackingSlip` de forma nativa) — reutiliza toda la infraestructura de numeración con lock (`initiate_number()`/Semaphore), settings API, generación de PDF (mPDF) y el mecanismo de adjuntar documentos a emails nativos de WooCommerce (`attach_to_email_ids`, ver §3.6). No requiere la extensión Professional de pago.
- Numeración correlativa propia, independiente de la de "Factura", configurable desde `Facturas PDF → Documentos → Credit Note` una vez registrado el tipo.
- Incluye `get_related_invoice_number()` para referenciar la factura original (`wcpdf_get_document('invoice', $order)->get_number()`) en la plantilla PDF.

### 3.3 `credit-note-endpoint.php` — calco de `invoice-endpoint.php`
```php
function mgmit_get_storno_pdf_url( $order_id ) {
    // idéntico a mgmit_get_invoice_pdf_url(), cambiando action a 'mgmit_storno_pdf'
}

add_action( 'wp_ajax_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );
add_action( 'wp_ajax_nopriv_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );

function mgmit_serve_storno_pdf() {
    // idéntico a mgmit_serve_invoice_pdf(), pero:
    // wcpdf_get_document( 'credit-note', $order, true )
}
```
Reutiliza la misma constante `INVOICE_SECRET` para la firma HMAC (mismo modelo de seguridad, sin necesidad de un secreto adicional).

### 3.4 `webhook-filters.php` — código real implementado
```php
function mgmit_add_invoice_url_to_webhook_payload( $payload, $resource, $resource_id, $webhook_id ) {
    // ... código original sin cambios: calcula $invoice_url y lo mete en invoice_pdf_url ...

    // Decisión del usuario (2026-08-31): reutilizar el mismo campo que ya
    // procesa HubSpot/vpsbridge en vez de crear uno nuevo (credit_note_pdf_url).
    if ( 'cancelled' === $order->get_status() && function_exists( 'mgmit_get_storno_pdf_url' ) ) {
        $storno_url = mgmit_get_storno_pdf_url( $order->get_id() );
        if ( $storno_url ) {
            $payload['invoice_pdf_url'] = $storno_url; // sobrescribe, no añade campo nuevo
        }
    }

    return $payload;
}
```
Un único filtro, una única función editada — no se crea un hook nuevo para esto, se aprovecha que el webhook `order.updated` ya se dispara en cualquier cambio de estado, incluido `cancelled`.

### 3.5 `loader.php`
```php
require_once __DIR__ . '/credit-note-document.php';
require_once __DIR__ . '/credit-note-endpoint.php';
require_once __DIR__ . '/credit-note-mailer.php';
```

### 3.6 Email al cliente con la nota de abono adjunta

Ampliación pedida por el usuario tras probar el flujo: además del enlace en el webhook, el cliente debe recibir un email con la nota de abono adjunta tanto al **cancelar** como al **reembolsar** un pedido. La solución es distinta en cada caso:

| Caso | Causa raíz investigada | Solución |
|---|---|---|
| **Reembolso** (`refunded`) | WooCommerce **sí** tiene un email nativo al cliente: `WC_Email_Customer_Refunded_Order` (id `customer_refunded_order`, `customer_email = true`, se dispara en reembolso total y parcial). WPO WCPDF ya adjunta cualquier documento registrado a cualquier email nativo de forma genérica, vía su propio filtro `woocommerce_email_attachments` (`includes/Main.php::attach_document_to_email()`), usando el ajuste `attach_to_email_ids` de cada documento. | **Ningún código nuevo.** Solo hay que marcar la casilla "Pedido reembolsado" en `wp-admin → Facturas PDF → Documentos → Credit Note → Attach to:`. |
| **Cancelación** (`cancelled`) | WooCommerce **no** tiene ningún email nativo al cliente para pedidos cancelados. El único email de ese estado, `WC_Email_Cancelled_Order` (id `cancelled_order`), es exclusivamente para el administrador (`recipient` por defecto = `admin_email`, sin flag `customer_email`). Verificado leyendo `includes/emails/class-wc-email-cancelled-order.php` de WooCommerce. | Se creó `credit-note-mailer.php`: hookea `woocommerce_order_status_cancelled`, genera la nota de abono, la adjunta y envía por `wp_mail()` (mismo patrón que `swpm-expiry-reminders`, ver `docs/plugins-custom.md`) al email de facturación del cliente, con **BCC** a `get_option('admin_email')` **+** `doris.hagleitner@megemit.org` (equivalente al `Storno-Rechnung an` de Solve). Marca un meta `_mgmit_credit_note_email_sent` para no reenviar dos veces, y deja nota en el pedido con el resultado. |

### 3.7 Plantilla PDF (`credit-note.php`) — formato final, calcado del histórico de Solve

El usuario aportó un PDF real generado por Solve (`Stornorechnung S50148 zur Rechnung M12853...pdf`) como referencia de formato exacto. Cambios aplicados tras compararlo:

1. **Todos los importes en negativo** — línea de artículo (Einzelpreis/Gesamtpreis), subtotal, total, "(netto ...)" e IVA. Implementación: para `order_total`/`netto` se recalculan los importes numéricos y se formatean con `wc_price(-1 * $importe, ...)` (que ya coloca el signo "-" correctamente); para el resto de filas (`get_woocommerce_totals()`, `get_tax_totals()`, precios de línea) se antepone `'-'` como texto plano a la cadena ya formateada por WPO WCPDF, sin recalcular redondeo/IVA.
2. **Cabecera de números** en una sola línea, formato Solve: `Storno-Rechnungsnummer: S50148 zu Rechnung: M12853`, con `Bestellnummer: 22192` en la línea siguiente. Antes eran 3 párrafos separados con etiquetas en inglés/genéricas.
3. **Sin aviso de Storno ni caja legal al final** — el documento termina justo después de la tabla de totales, igual que el original de Solve. Se quitaron el párrafo "Hiermit stornieren wir..." y el `.legal-box` que se habían añadido en el borrador inicial.

---

## 4. Requisitos previos a validar (bloqueantes)

1. **Confirmar con contabilidad/fiscal (normativa DE/AT, GoBD)** el formato exigido para la numeración de notas de abono (secuencia sin huecos, inmutabilidad, etc.) antes de fijar el esquema del contador en WPO WCPDF.
2. ~~Decidir si importa la continuidad numérica con el histórico de Solve~~ — **✅ Resuelto (2026-08-31).** Se comprobó en vivo que el contador de Solve (`Nächste Storno-Rechnungsnummer`) sigue incrementándose en producción por inercia (pasó de `50149` a `50151` en menos de una hora entre dos comprobaciones), pero el usuario confirmó que **esos números no se usan ni se reportan a nadie**. Al no haber ninguna numeración de Storno "vigente" real hoy (WordPress/producción no emite ninguna todavía), **se arranca el contador de `credit-note` en WPO WCPDF en 1** (valor por defecto), sin necesidad de coordinar un corte exacto con Solve. Los números `S50xxx` de Solve quedan como referencia histórica únicamente, sin continuidad con la nueva secuencia.
3. ~~Confirmar con quien gestiona "vpsbridge"/HubSpot" qué campo espera~~ — **✅ Resuelto (2026-08-31).** El usuario indicó que no hace falta un campo nuevo: cuando el pedido se cancela, se reutiliza el mismo campo `invoice_pdf_url` que HubSpot/vpsbridge ya procesa hoy, apuntando al PDF de la nota de abono en vez de al de la factura. Sin coordinación adicional necesaria — HubSpot no distingue entre ambos casos, solo recibe "el PDF vigente para este pedido".
4. Confirmar si WooCommerce está configurado para dsiparar el webhook `order.updated` en el topic correcto al pasar a `cancelled` (revisar en `WooCommerce → Ajustes → Avanzado → Webhooks` qué webhook activo apunta a vpsbridge y qué "Topic" tiene asignado).

---

## 5. Fases de implementación

### Fase 1: Registro del documento + numeración ✅ COMPLETADA (local, 2026-08-31)
1. Creado `inc/woocommerce/class-credit-note.php` (clase `WPO\IPS\Documents\CreditNote`) + `credit-note-document.php` (registro vía filtro `wpo_wcpdf_document_classes`).
2. Verificado en `wp-admin → WooCommerce → Facturas PDF → Documentos`: "Credit Note" aparece junto a "Factura" con numeración propia.

### Fase 2: Endpoint firmado ✅ COMPLETADA (local, 2026-08-31)
1. Creada la plantilla `woocommerce/pdf/MeGeMit/credit-note.php` (calco de `invoice.php`, con referencia a la factura original).
2. Creado `inc/woocommerce/credit-note-endpoint.php` (`mgmit_get_storno_pdf_url()` / `mgmit_serve_storno_pdf()`), calco de `invoice-endpoint.php` con token HMAC propio (prefijo `storno-`).

### Fase 3: Inyección en el webhook ✅ COMPLETADA (local, 2026-08-31)
1. Editado `webhook-filters.php`: cuando `$order->get_status() === 'cancelled'`, **reemplaza** el valor de `invoice_pdf_url` (mismo campo que ya procesa HubSpot/vpsbridge) por el enlace firmado a la nota de abono, en lugar de añadir un campo nuevo. Decisión del usuario (2026-08-31): no hace falta distinguir campos, HubSpot solo necesita "el PDF vigente del pedido".

### Fase 4: Validación end-to-end — 🟡 EN CURSO
1. ✅ Prueba local vía `wp eval-file` (pedidos #21722, #21723): factura → cancelado → nota de abono generada. PDF revisado por el usuario.
2. ✅ Payload simulado confirmado: para el pedido cancelado, `invoice_pdf_url` apunta al PDF de la nota de abono (`action=mgmit_storno_pdf`).
3. ✅ Numeración de arranque decidida: **1** (ver bloqueante §4.2 resuelto).
4. ✅ Campo del payload decidido: se reutiliza `invoice_pdf_url`, sin coordinación adicional con HubSpot (bloqueante §4.3 resuelto).
5. ✅ Email al cliente en cancelación implementado y probado (`credit-note-mailer.php`) — ver §3.6.
6. ✅ Ajuste de BCC corregido (2026-08-31): el usuario reportó que ni el admin ni Doris recibían el email. Causa: solo se enviaba a `admin_email`, nunca se había añadido a Doris explícitamente. Corregido para incluir ambas direcciones en BCC. **Confirmado por el usuario (2026-08-31): ya llegan correctamente a ambas.**
7. ✅ Formato del PDF ajustado al histórico de Solve (importes en negativo, cabecera, sin aviso/legal-box) — ver §3.7.
8. ⏳ Pendiente: marcar la casilla "Pedido reembolsado" en `Credit Note → Attach to:` en producción y confirmar que el reembolso adjunta la nota de abono.
9. ⏳ Pendiente: cancelar un pedido real de prueba en producción y verificar en HubSpot que el enlace llega y es descargable.
10. ⏳ Pendiente: documentar el cambio en `CHANGELOG.md` y `plugins-custom.md` antes del despliegue a producción (PHP 7.4).

---

## 6. Ventajas de este enfoque frente al plan v0.1.0

| Aspecto | v0.1.0 (descartado) | v0.2.0 (este plan) |
|---|---|---|
| Referencia arquitectónica | Solve (abandonado) | Flujo real de webhooks ya en producción |
| Envío | Email SMTP propio | Reutiliza el canal ya validado hacia HubSpot |
| Archivos nuevos | Plugin completo aislado | 2 archivos nuevos + 2 ediciones, mismo patrón que ya existe |
| Riesgo de duplicar lógica | Alto (nuevo mailer, nuevo hook) | Bajo (calco literal de `invoice-endpoint.php`) |
| Coordinación necesaria | Ninguna (autocontenido, pero desconectado del flujo real) | Con el responsable de vpsbridge/HubSpot (necesaria de todos modos para que el dato sirva de algo) |

---

## 7. Pendiente / próximos pasos

- [x] Validar este plan con el usuario antes de escribir código.
- [x] Resolver los bloqueantes §4.2 y §4.3.
- [x] Fases 1-4 (parcial) implementadas y probadas en local (PHP 8.3), verificadas con el binario real de PHP 7.4.33 sin errores de sintaxis.
- [ ] Bloqueante §4.1 sigue abierto: validar con contabilidad/fiscal el formato GoBD de numeración antes de emitir notas de abono reales.
- [x] Email con BCC (admin + Doris) confirmado funcionando (2026-08-31).
- [ ] Probar en producción: reembolso con casilla "Attach to" marcada, y cancelación real verificando el enlace en HubSpot.
- [ ] Documentar en `CHANGELOG.md` y `plugins-custom.md` antes de desplegar a producción.

**Archivos listos para subir a producción (8):**
`inc/woocommerce/class-credit-note.php`, `credit-note-document.php`, `credit-note-endpoint.php`, `credit-note-mailer.php`, `loader.php` *(mod.)*, `webhook-filters.php` *(mod.)*, `woocommerce/pdf/MeGeMit/credit-note.php`.
Recordar activar manualmente "Credit Note" (y su "Attach to: Pedido reembolsado") en `wp-admin → Facturas PDF → Documentos` tras subir el código — queda desactivado por defecto.
