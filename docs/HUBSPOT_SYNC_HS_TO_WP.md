# HubSpot → WordPress Sync
## Ampliación del plugin `mgmit-hubspot-bridge` v1.5.0

**Fecha:** 2026-05-12
**Estado:** Pendiente de aprobación — Fase 1

---

## 1. Contexto y problema

El plugin `mgmit-hubspot-bridge` v1.5.0 opera en modo **unidireccional WP→HubSpot**:
- Escucha eventos SWPM (registro/edición de perfil)
- Envía datos a HubSpot Forms API via `wp_remote_post`

**Gap:** Cuando se realizan cambios en HubSpot (Contacts, Deals, Products), WordPress no los recibe, generando desincronización de datos.

**Objetivo:** Añadir sincronización **HubSpot→WP** sin modificar el flujo existente.

---

## 2. Principios de diseño

1. **Zero-touch al código existente** — Los archivos actuales no se modifican, salvo un único `require_once` en `init_hooks()`.
2. **Fases incrementales** — Ninguna fase nueva se inicia hasta que la anterior esté testeada y funcionando.
3. **PHP 7.4 estricto** — Sin constructor promotion, union types, named arguments, match expressions, nullsafe operator.
4. **Solo actualiza, nunca crea** — Si el registro no existe en WP, se loguea; no se crean entidades automáticamente.
5. **Seguridad primero** — Toda petición entrante es validada con firma HMAC-SHA256 antes de procesarse.

---

## 3. Arquitectura del flujo

```
HubSpot (cambio en Contact / Deal / Product)
        │
        │  POST webhook (JSON firmado con HMAC-SHA256)
        │  Header: X-HubSpot-Signature-v3
        ▼
WordPress REST API
  POST /wp-json/mgmit-hs/v1/webhook
        │
        ├─ Valida firma HMAC (rechaza 401 si inválida)
        ├─ Parsea payload JSON
        ├─ Identifica tipo de objeto (contact | deal | product)
        ├─ Responde 200 OK inmediatamente
        │
        ├─► Processor Contacts  → WP Users / wp_usermeta
        ├─► Processor Deals     → WooCommerce Orders
        └─► Processor Products  → WooCommerce Products
```

**Mecanismo:** Webhooks de HubSpot (tiempo real).
HubSpot envía un POST firmado en cada cambio. WP valida la firma y procesa de forma asíncrona.

---

## 4. Estructura de archivos

```
mgmit-hubspot-bridge/
├── mgmit-hubspot-bridge.php                    ← 1 línea nueva al final de init_hooks()
├── includes/
│   ├── class-mgmit-admin-ui.php               ← NO SE TOCA
│   ├── class-mgmit-webhook-receiver.php        [NUEVO — Fase 1]
│   ├── class-mgmit-sync-contacts.php           [NUEVO — Fase 2]
│   ├── class-mgmit-sync-orders.php             [NUEVO — Fase 3]
│   └── class-mgmit-sync-products.php           [NUEVO — Fase 4]
└── assets/js/
    ├── hubspot_map.js                          ← NO SE TOCA
    └── admin-mapper.js                         ← NO SE TOCA
```

### Único cambio en archivo existente

En `mgmit-hubspot-bridge.php`, al final de `init_hooks()`:

```php
// Módulo recepción webhooks HubSpot→WP (v2.0)
require_once MGMIT_HS_BRIDGE_PATH . 'includes/class-mgmit-webhook-receiver.php';
new MGMIT_Webhook_Receiver();
```

---

## 5. Fases de implementación

---

### FASE 1 — Receptor de Webhooks (infraestructura base)

**Objetivo:** Endpoint REST que recibe, valida y loguea los POSTs de HubSpot.

**Archivos nuevos:**
- `includes/class-mgmit-webhook-receiver.php`

**Funcionalidades:**
- Registra `POST /wp-json/mgmit-hs/v1/webhook`
- Valida firma HMAC-SHA256 (header `X-HubSpot-Signature-v3`)
- Parsea el payload JSON e identifica el tipo de objeto
- Responde `200 OK` inmediatamente (requisito de HubSpot: <5s)
- Despacha al procesador correcto según tipo (stub vacío en Fase 1)
- Sistema de logging: últimos 50 eventos en `wp_options['mgmit_hs_webhook_log']`

**Criterios de verificación (TODOS deben pasar antes de Fase 2):**
- [ ] `curl -X POST` con payload válido → respuesta `200 OK`
- [ ] `curl -X POST` con firma inválida → respuesta `401 Unauthorized`
- [ ] El log registra el evento en `wp_options`
- [ ] `php -l includes/class-mgmit-webhook-receiver.php` sin errores
- [ ] Formulario SWPM sigue enviando datos a HubSpot correctamente (regresión)
- [ ] Sin errores en `wp-content/debug.log`

**No procesa datos todavía** — solo recibe, valida y loguea.

---

### FASE 2 — Sincronización de Contactos

**Objetivo:** Actualizar datos de usuario WP cuando un Contact cambia en HubSpot.

**Archivos nuevos:**
- `includes/class-mgmit-sync-contacts.php`

**Funcionalidades:**
- Recibe el payload de Contact desde el Receiver (Fase 1)
- Campo pivote de búsqueda: `email`
- Busca el WP User por email (`get_user_by('email', ...)`)
- Si no existe: loguea "usuario no encontrado", no crea, no lanza error
- Si existe: actualiza `wp_usermeta` con los campos configurados
- Los campos a mapear se definen en configuración (proporcionados por cliente)

**Criterios de verificación:**
- [ ] Cambiar un Contact en HubSpot → `wpgr_usermeta` actualizado en BD
- [ ] Email inexistente en WP → log "usuario no encontrado", sin error PHP
- [ ] Fase 1 sigue funcionando (endpoint y log operativos)
- [ ] `php -l` sin errores
- [ ] Flujo WP→HubSpot sin regresiones

---

### FASE 3 — Sincronización de Pedidos

**Objetivo:** Actualizar pedidos WooCommerce cuando un Deal cambia en HubSpot.

**Archivos nuevos:**
- `includes/class-mgmit-sync-orders.php`

**Funcionalidades:**
- Recibe el payload de Deal desde el Receiver
- Campo pivote: número de pedido WC o meta custom (a definir con cliente)
- Solo actualiza campos configurados — nunca sobreescribe datos no mapeados
- Actualiza estado, metadatos o campos de dirección según mapeo

**Criterios de verificación:**
- [ ] Cambiar un Deal en HubSpot → pedido WC correspondiente actualizado
- [ ] Deal sin pedido WC asociado → log "pedido no encontrado", sin error
- [ ] Fases 1 y 2 siguen funcionando
- [ ] `php -l` sin errores

---

### FASE 4 — Sincronización de Productos

**Objetivo:** Actualizar productos WooCommerce cuando un Product cambia en HubSpot.

**Archivos nuevos:**
- `includes/class-mgmit-sync-products.php`

**Funcionalidades:**
- Recibe el payload de Product desde el Receiver
- Campo pivote: SKU o meta custom (a definir con cliente)
- Actualiza campos configurados: precio, nombre, descripción, stock
- Nunca sobreescribe campos no mapeados

**Criterios de verificación:**
- [ ] Cambiar un Product en HubSpot → producto WC actualizado
- [ ] Product sin SKU WC asociado → log "producto no encontrado", sin error
- [ ] Fases 1, 2 y 3 siguen funcionando
- [ ] `php -l` sin errores

---

### FASE 5 — Panel de administración (opcional, post fases 1-4)

**Objetivo:** UI en el admin del plugin para gestionar la sincronización entrante.

**Funcionalidades planificadas:**
- Tab nuevo en `HubSpot Bridge`: "Sincronización HS→WP"
- Visualizador del log de webhooks recibidos (últimos 50)
- Configuración de mapeos de campos por entidad (Contacts / Deals / Products)
- Toggle para activar/desactivar sincronización por tipo de entidad
- Botón de test de conexión (envía ping al endpoint propio)

---

## 6. Seguridad

| Capa | Mecanismo |
|---|---|
| Autenticación del webhook | HMAC-SHA256 con Client Secret de HubSpot |
| Header de firma | `X-HubSpot-Signature-v3` |
| Sanitización de datos | `sanitize_text_field()` en todos los campos de entrada |
| Permisos de escritura | Solo se actualiza lo que está en el mapeo configurado |
| Logs | Almacenados en `wp_options`, solo legibles por admins |

**Pendiente confirmar:** ¿El Client Secret para validar la firma del webhook es el mismo `MGMIT_HS_ACCESS_TOKEN_SECRET` de `wp-config.php`, o hay un secret separado en la configuración del webhook en HubSpot?

---

## 7. Mapeos de campos

Los mapeos específicos (qué propiedad de HubSpot mapea a qué campo de WP/WC) serán proporcionados por el cliente antes de iniciar Fase 2.

**Formato esperado por entidad:**

```
Contacts:
  HubSpot property  →  WP campo
  firstname         →  first_name (usermeta)
  lastname          →  last_name (usermeta)
  ...

Deals:
  HubSpot property  →  WC campo
  dealstage         →  order status
  ...

Products:
  HubSpot property  →  WC campo
  price             →  _price (postmeta)
  ...
```

---

## 8. Historial de versiones del plan

| Versión | Fecha | Descripción |
|---|---|---|
| 1.0 | 2026-05-12 | Plan inicial — pendiente aprobación Fase 1 |
