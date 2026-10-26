# Changelog — Proyecto MeGeMIT

Registro único de todas las modificaciones técnicas del proyecto (tema, plugins propios, mu-plugins e integración HubSpot/SWPM/Stripe/Moodle). Ordenado por fecha, de más reciente a más antigua.

**Convenciones**
- Este es el **único** changelog del proyecto. No crear otros archivos de changelog (ni en `docs/` ni dentro de los plugins propios).
- Cada fecha agrupa sus cambios. Las entradas de un componente concreto llevan su nombre y versión como título (`### hubspot-mapper 2.5.0`).
- Las entradas heredadas de los antiguos archivos separados indican su origen en cursiva.
- Los changelog de plugins de terceros (WooCommerce, Yoast, SWPM, etc.) no forman parte de este registro.

---

## 2026-10-26

### Añadido
- Aviso excepcional de normalización de pagos (`inc/hubspot-swpm-stripe-bridge/flow-exceptional-renewal.php`, plantilla `email-templates/body-exceptional-renewal.php`, cargado desde `loader.php`). Email único, en alemán, a los socios del nivel de pago cuya membresía se gestionó manualmente hasta el 13/10/2026: les informa de que deben suscribirse ellos desde la web (URL configurable). **Apagado por defecto y sin URL no envía nada.**
  - Destinatarios: nivel 3 con `subscription_starts` anterior al 13/10/2026 (la fecha de corte y el resto son configurables). Quedan fuera quienes tengan alta desde el 13/10 (flujo normal de SWPM) y, como red de seguridad, quien tenga un pago en `swpm_payments_tbl` o un `subscr_id` `sub_`. No exige estado activo (a la fecha de envío estarán `expired`).
  - Envío: **una única fecha para todos, 31/01/2027, sin excepciones** y sin depender de la fecha de alta ni de «alta + 1 año» (la fecha es configurable). Cron cada 15 min por lotes (50 por defecto) con bloqueo, un único envío por socio (option `mgmit_exc_renewal_sent`). El email no menciona fecha de fin de la membresía anterior.
  - Ajustes → «Aviso excepcional»: configuración, estado, vista previa de destinatarios, envío de prueba y ejecución manual de un lote.
- Cron: el envío usa WP-Cron (`mgmit_exc_renewal_cron`, cada 15 min). En producción `DISABLE_WP_CRON` es `false`, así que se dispara con las visitas a la web; no requiere actualizar nada. Para asegurar la hora, se usará un agente externo (servicio tipo cron-job.org / UptimeRobot) que haga un `GET` a `https://megemit.org/wp-cron.php` cada 15 minutos. El 31/01/2027 es domingo: no depender de un equipo local encendido.
- Pendiente (para poder seguir más adelante):
  1. Facilitar la URL de suscripción y guardarla en Ajustes → «Aviso excepcional» (sin URL el interruptor no se puede activar).
  2. Aprobar el texto del email (alemán, `email-templates/body-exceptional-renewal.php`).
  3. Subir a producción `flow-exceptional-renewal.php`, `loader.php` y `email-templates/body-exceptional-renewal.php` (en `inc/hubspot-swpm-stripe-bridge/`).
  4. Configurar el agente externo de cron (cada 15 min).
  5. Enviar el email de prueba desde el panel y revisar la vista previa de destinatarios (esperados ~860 según el backup del 21/10/2026: nivel 3 con alta anterior al 13/10/2026).
  6. Activar el interruptor antes del 31/01/2027 y comprobar ese día que el contador de enviados sube de 50 en 50.

### Diagnóstico abierto: nivel de membresía 3 → 2 tras comprar
- Con el backup de producción (21/10/2026 22:00) se confirmó que 4 socios con compra nueva (7803, 8006, 8450, 8623) están en nivel 2: SWPM los subió a 3 al pagar (log) y después volvió a 2. Núcleo de SWPM 4.8.5, Form Builder y tema de producción sin modificar frente al código oficial/repositorio; la causa sigue sin identificar.
- Herramienta de diagnóstico preparada fuera del repositorio (`~/Desktop/webs/plugins/mgmit-level-audit.php`, mu-plugin): registra cada UPDATE de `membership_level` con su pila de llamadas y envía un email si un socio pasa de 3 a 2. Pendiente de subir a `wp-content/mu-plugins/` y de hacer una compra de prueba.
- Seguridad (a resolver fuera del repositorio): `hufeland.php` y `cncl.php` en la raíz web de producción; el primero lleva la contraseña de la base de datos en claro (rotar y borrar/proteger).

### Cambiado — Documentación: changelog unificado
- Los 4 changelog propios que convivían se han fusionado en este único `CHANGELOG.md` (raíz): el raíz, `docs/CHANGELOG.md`, `wp-content/plugins/hubspot-mapper/CHANGELOG.md` y `wp-content/plugins/wc-moodle-sync/CHANGELOG.md`. Ordenado por fecha (más reciente primero); las entradas heredadas indican su origen. Verificado que se conservan las 541 líneas de contenido y todas las fechas.
- Eliminados `docs/CHANGELOG.md` y los changelog internos de `hubspot-mapper` y `wc-moodle-sync` (el historial sigue en este archivo y en git).
- `docs/README.md`, `docs/00-START-HERE.md`, `docs/GETTING_STARTED.md` y `docs/INDEX.md`: enlaces actualizados a `../CHANGELOG.md`. `docs/INDEX.md` ya no indica copiar el changelog entre `docs/` y la raíz (origen de la duplicación).
- Pendiente de decidir: en el checkout principal existen sin versionar `wp-content/plugins/hubspot-mapper-backup/CHANGELOG.md` y `wp-content/plugins/simple-hubspot-swpm-stripe-bridge/CHANGELOG.md` (copias antiguas, fuera de esta rama), y un `wc-moodle-sync/CHANGELOG.md` más reciente (1.8.0 del 22/10/2026, 1.7.x) que habrá que integrar aquí al fusionar esa rama.
- Los changelog de plugins de terceros (WooCommerce, Yoast, SWPM, etc.) no se tocan.

---

## 2026-10-12

### Eliminado
- Propiedad `title` de HubSpot: ya no se lee ni se usa. El saludo y la cabecera de los emails, la factura PDF de alta/renovación y las facturas/notas de abono de WooCommerce usan solo `courtesy_de` y `job_title_de` (`class-salutation-resolver.php`, `email-membership.php`, `email-renewal-confirmed.php`, `vorschreibung-pdf.php`, `invoice.php`, `credit-note.php`, `hs-connection-test.php`). `job_title_de` se muestra tal cual viene de HubSpot (sigue el filtro previo que descarta los valores exactos Herr/Frau).

### Añadido
- Aviso interno por cada alta nueva de membresía (`email-membership.php`): `mgmit_bridge_send_admin_new_subscription_email()`, enganchada a `swpm_payment_ipn_processed` con prioridad 20 (después del email al cliente y sin depender de él). Llega a `info@megemit.org` (cambiable con el filtro `mgmit_bridge_admin_notification_email`), en alemán, con nombre, email, nº de socio, nivel de membresía, importe, forma de pago, nº de factura de Stripe, fecha, id de la suscripción y enlaces al socio en WordPress y a la suscripción en Stripe; si el pago es SEPA, avisa de que puede estar pendiente. Lleva adjunta la misma factura PDF que recibe el cliente (el email de bienvenida deja una copia en `uploads/mgmit-bridge-tmp/admin-copy/` y el aviso la adjunta y la borra siempre, incluso si se omite o falla). Una sola notificación por pago (transitorio de 24 h) y el reenvío manual no la dispara. Deja traza `admin_new_subscription: …` en el log del bridge. `mgmit_bridge_get_stripe_invoice_info()` guarda el resultado en caché por petición para no consultar Stripe dos veces.
- `resend-membership-email.php` (root, solo admin, borrar tras usarlo): reenvío manual del email de bienvenida (con factura y confirmación de membresía) de pagos de Stripe ya registrados en `swpm_payments_tbl`. Lista el primer pago de cada suscripción de los últimos N días, deja elegir pagos con casillas y envía en modo **prueba** (a la dirección que se indique) o **real** (al email del pago, con casilla de confirmación). Los documentos llevan la fecha del pago original. Guarda en la opción `mgmit_resent_membership_emails` los reenvíos reales y los muestra en la lista. Para ello: nueva `mgmit_bridge_resend_membership_email()` y `mgmit_bridge_payment_datetime()`; `email-membership.php` admite `mgmit_pay_date` y `mgmit_send_to` en el IPN y devuelve si el envío se aceptó; `mgmit_bridge_build_invoice_vars()` usa esa fecha para el periodo de la factura.
- Trazas de diagnóstico en los emails de alta y renovación (`email-membership.php`, `email-renewal-confirmed.php`): al inicio se registra `send_membership_email: iniciado (payer_email, swpm_id, subscr_id)` (o `send_renewal_confirmed_email: iniciado …`) y cualquier error de PHP dentro del envío se captura y se registra como `ERROR <clase>: <mensaje> en <archivo>:<línea>`, en vez de pasar en silencio. La lógica de envío pasa a `…_run()`; los hooks siguen llamando a la función de siempre.
- Dirección desde HubSpot (propiedades `address`, `city`, `zip`, `country`, buscada con el mismo email del socio que el saludo) mediante `mgmit_bridge_get_hubspot_address()` en `stripe-invoice-info.php`. Factura: Stripe, luego HubSpot, luego WordPress (`billing_*` y ficha SWPM). Confirmación de membresía: perfil SWPM y, si no tiene dirección, HubSpot. Si HubSpot no tiene dirección, se registra `direccion: HubSpot sin dirección para …` en el log del bridge. `email-membership.php` y `email-renewal-confirmed.php` pasan `hubspot_address` a la factura.

### Cambiado
- `email-templates/mitgliedschaft-pdf.php`: la confirmación de membresía muestra la dirección del perfil del socio en SWPM (calle, código postal y ciudad, país: `address_street`, `address_zipcode`, `address_city`, `country`), no los datos de facturación de WordPress, y sustituye la línea "Ort:". Cada parte solo si tiene dato. El tratamiento va sin "Sehr geehrte(r)" ("Frau Mag. Dr. Nombre Apellido"). Nueva `mgmit_bridge_get_swpm_profile_address()` (en `stripe-invoice-info.php`), que deja en el log del bridge `confirmacion: el perfil SWPM del socio N no tiene dirección` cuando está vacío. `email-membership.php` y `email-renewal-confirmed.php` pasan `member_street`, `member_postcode`, `member_city` y `member_country`.
- Dirección de la factura (solo la factura, no la confirmación de membresía): prioridad 1, la dirección que el cliente introdujo en Stripe al pagar (datos de facturación del método de pago y, si no tienen calle, dirección del cliente de Stripe; nueva `mgmit_bridge_stripe_address_to_array()` y clave `address` en `mgmit_bridge_get_stripe_invoice_info()`); prioridad 2, si no hay o Stripe no responde, la de WordPress (`billing_*` y ficha SWPM). `vorschreibung-pdf.php` recibe `stripe_address` desde `mgmit_bridge_build_invoice_vars()`.
- `email-templates/vorschreibung-pdf.php`: en la factura el tratamiento va sin la fórmula de saludo ("Sehr geehrte Frau" → "Frau", "Sehr geehrter Herr" → "Herr"); la confirmación de membresía mantiene el valor de HubSpot tal cual. Si el país viene como texto (no como código AT/DE), se muestra en una línea propia bajo la localidad.
- Dirección de la factura: nueva `mgmit_bridge_get_member_address()` en `stripe-invoice-info.php`. Usa los datos de facturación del usuario de WordPress (`billing_*`) y, si no tienen calle, la dirección de la ficha del socio de SWPM (`address_street`, `address_zipcode`, `address_city`, `address_state`, `country`). `email-membership.php` y `email-renewal-confirmed.php` la usan.
- PDFs del bridge (factura y confirmación de membresía, alta y renovación): el tratamiento se muestra tal cual lo devuelve HubSpot en `courtesy_de` (p. ej. "Sehr geehrte Frau"), sin convertirlo a "Frau"/"Herr". El resolver devuelve el valor en la clave `courtesy_de`; `email-membership.php` y `email-renewal-confirmed.php` se lo pasan a las plantillas.

### Corregido
- `class-salutation-resolver.php`: en desarrollo el saludo caía al respaldo sin dejar rastro porque el plugin `hubspot-mapper` de ese entorno no tiene `MGMIT_HS_Contacts::get_properties` (existe solo desde la v2.5.0; el último commit lleva la v2.3.2). Ahora se comprueba `method_exists` y el log registra "MGMIT_HS_Contacts::get_properties no disponible". También se registra un `courtesy_de` con valor no reconocido, y el reconocimiento acepta "Herr"/"Frau" sueltos y cualquier combinación de mayúsculas o espacios. ZIP del plugin: `~/Desktop/webs/plugins/hubspot-mapper-v2.5.0.zip`.
- Saludo "Sehr geehrte(r)" y PDFs sin tratamiento ni título: el saludo se buscaba en HubSpot con `payer_email`, que es el email escrito en Stripe Checkout y puede ser distinto al de registro (el que conoce HubSpot). Nuevas `mgmit_bridge_get_swpm_member_from_ipn()` y `mgmit_bridge_build_salutation_for_ipn()` en `class-salutation-resolver.php`: localizan al socio en SWPM (por ID, suscripción o email), buscan en HubSpot con su email de registro (solo si no hay socio, con el del pago) y usan el nombre registrado del socio. `email-membership.php` y `email-renewal-confirmed.php` las usan (también para los datos de facturación). El email usado y la fuente (hubspot/fallback) quedan en `mgmit-bridge-log.txt`.
- Email de baja, aviso de renovación y facturas/notas de abono de WooCommerce usan el mismo criterio que el alta: el email del usuario de WordPress (el de SWPM, que está en HubSpot). Nuevas `mgmit_bridge_build_salutation_for_member()` y `mgmit_bridge_build_salutation_logged()`; `flow-deactivation.php`, `flow-renewal-reminder.php`, `invoice.php` y `credit-note.php` las usan (WooCommerce: email de la cuenta del cliente y, en pedidos de invitado, el de facturación). Cada resolución deja en el log del bridge el email usado y la fuente (hubspot/fallback); si HubSpot no devuelve `courtesy_de`, se registra también el código HTTP y el error.
- `stripe-invoice-info.php`: el nº de factura salía vacío porque `latest_invoice` puede ser ya la siguiente factura en borrador. Ahora se toma la factura más reciente de la suscripción que ya tenga número, sin exigir que esté pagada (en SEPA la factura queda abierta hasta que se liquida, pero ya está numerada).

### Cambiado
- `email-templates/body-renewal-reminder.php`: añadido el párrafo "Falls Sie die Mitgliedschaft nicht verlängern wollen, schreiben Sie dies bitte an info@megemit.org." (con enlace mailto) tras el aviso del cobro automático, para indicar cómo cancelar.
- `email-templates/mitgliedschaft-pdf.php`: el nombre del socio en la confirmación de membresía (adjunta en alta y renovación) incluye ahora `job_title_de` delante del nombre, igual que la factura y el saludo de los emails, y la etiqueta de tratamiento (Herr/Frau) ya no lleva ":" detrás. `email-membership.php` y `email-renewal-confirmed.php` pasan `job_title` a esa plantilla.
- `email-renewal-confirmed.php`: el adjunto de la factura en la renovación pasa a llamarse `rechnung_<txn_id>.pdf` (antes `Vorschreibung_<timestamp>.pdf`), igual que en el alta.
- `flow-renewal-reminder.php`: el aviso de renovación (30 y 15 días antes) calcula la fecha como último pago de Stripe registrado en `swpm_payments_tbl` + 1 año, en vez de `subscription_starts` + 1 año. Se avisa en ventana (≤30 días y ≤15 días, una vez por ciclo) para no perder avisos si el cron falla ese día. No se avisa a socios sin pagos de Stripe registrados (aún sin migrar), con suscripción cancelada (`subscr_status = canceled`), ni cuando la renovación es hoy o ya pasó. La decisión está aislada en `mgmit_renewal_reminder_decide()` (probada con 13 casos).
- `email-templates/vorschreibung-pdf.php`: la factura adjunta al alta y a la renovación se rehace según la muestra `docs/Entwurf-Rechnung.pdf`: dirección (`AT-6300 Wörgl`), fecha `Wörgl, am d.m.Y`, `Rechnungsnummer` / `Bestellnummer`, tabla Position/Menge/Artikelbezeichnung/Einzelpreis/Gesamtpreis con el periodo de la membresía, `Zwischensumme` y `Gesamt`, frase de pago y línea `@mikroimmuntherapie` en el pie. Eliminados asunto, saludo, párrafo y aviso legal.
- La calle de facturación no salía en la factura (la plantilla leía `$billing_address_1` y los emails pasaban `billing_address`). Corregido.

### Añadido
- `renewal-reminder-test.php` (root, solo admin, borrar tras las pruebas): escenarios de la lógica, listado de solo lectura de socios con su fecha de renovación y decisión de hoy, y envío de un aviso de prueba a una dirección concreta. No modifica datos de socios. La consulta del cron se extrajo a `mgmit_renewal_reminder_get_members()` para que el test use la misma.
- `stripe-invoice-info.php`: consulta la suscripción en Stripe y devuelve el nº de factura de Stripe (`Rechnungsnummer`) y el método de pago real (tarjeta, PayPal, Klarna, SEPA…). Periodo = fecha de compra hasta +1 año −1 día. `Bestellnummer` = ID de socio SWPM. Si Stripe falla, el PDF se genera igualmente sin esos datos.
- `email-templates/img/icon_social.png` (extraído de la muestra).

---

## 2026-10-11

### Cambiado
- `email-templates/vorschreibung-pdf.php`: todo el texto "Vorschreibung" sustituido por "Rechnung" (asunto, cuerpo, aviso legal). Añadidos `title` y `job_title_de` al nombre del destinatario (antes solo salía `salutation_full` + nombre/apellido, sin título ni profesión).
- `email-membership.php` / `email-renewal-confirmed.php`: pasan ahora `title` y `job_title` a la plantilla `vorschreibung-pdf`.

---

## 2026-10-10

### Cambiado
- `email-templates/mitgliedschaft-pdf.php`: título del documento en negrita con el azul del logo (`#195CA8`, muestreado de la cabecera), en vez de negro. El bold de Arial/Helvetica se veía fino en Dompdf y `text-shadow` no tiene efecto en ese motor (verificado); se cambió la fuente del título a DejaVu Sans, que sí renderiza un bold visiblemente más grueso.
- `email-templates/mitgliedschaft-pdf.php`: eliminada la filigrana (marca de agua) antes del título y la imagen incorrecta antes de "Hagleitner Doris" (reutilizaba por error la imagen de cabecera como si fuera su firma). Limpiado el CSS y la variable `$logo_watermark_path`, ya sin uso.
- `woocommerce/pdf/MeGeMit/header_invoice.jpg`: sustituida por una versión de mayor calidad (1641×214px, extraída de `Information Therapeuten-Beratung.pdf`, antes 594×68px) — afecta a todos los documentos que la usan (factura, nota de abono, confirmación de membresía, cuerpos de email).

---

## 2026-10-09

### Cambiado
- `email-membership.php` (email de Alta): el adjunto de la factura pasa a llamarse `rechnung_<txn_id>.pdf` (ID de transacción de Stripe) en vez de `Vorschreibung_<timestamp>.pdf`.

---

## 2026-10-08

### Cambiado
- Firma de los 4 emails de membresía: nueva imagen (`email-templates/img/doris_signature.png`) y pie de contacto (Postadresse, Adresse, redes sociales), sustituyendo al anterior (sin teléfono/fax/email/NIF).
- `class-salutation-resolver.php`: el saludo (`greeting`) ahora incluye `job_title_de` delante del apellido (detrás de `title`), igual que la cabecera de la factura — antes solo mostraba `title`.

---

## 2026-10-07

### Eliminado
- `flow-payment.php` / `flow-deactivation.php`: eliminado el envío manual de webhooks a HubSpot (`MGMIT_Webhook_Sender::send()`, eventos `order.created`/`customer.updated`) en alta y baja de membresía. Causaba datos incorrectos (IDs de Stripe cruzados, nombre/apellido mal separado por un bug de SWPM con caracteres UTF-8 como ü/ö/ä). La notificación a HubSpot pasa a gestionarla Stripe directamente mediante su propio webhook.
- `mgmit_bridge_payment_completed()` y `mgmit_bridge_send_deactivation_webhook()` eliminadas (ya no hacen falta); los hooks de SWPM llaman ahora directamente a las funciones de envío de email.

---

## 2026-10-06

### Cambiado
- `email-membership.php` / `email-renewal-confirmed.php`: `$salutation_label` ya no usa `'Herr/Frau'` como texto por defecto; si HubSpot no devuelve `courtesy_de`, queda vacío.
- `email-templates/mitgliedschaft-pdf.php`: el campo "Anrede" (Herr/Frau) solo se muestra si hay dato; si no, solo aparece el nombre del socio, sin etiqueta.

---

## 2026-10-05

### Añadido
- `class-salutation-resolver.php`: nuevo campo `courtesy_label` ('Herr'/'Frau'/'') en el resultado de `mgmit_bridge_build_salutation()`, para usarlo sin tener que derivarlo del saludo completo.
- Filtro de datos basura: si `title` o `job_title_de` de HubSpot contienen literalmente "Herr"/"Frau" (error de captura), se descartan antes de usarse.

### Cambiado
- `woocommerce/pdf/MeGeMit/invoice.php` y `credit-note.php`: la cabecera (nombre del cliente) ahora se construye con tratamiento + título + profesión resueltos vía HubSpot (`courtesy_de`, `title`, `job_title_de`), sustituyendo al antiguo meta `_anrede` del checkout. Aplica a todas las facturas y notas de abono de WooCommerce.
- `email-membership.php` y `email-renewal-confirmed.php`: simplificada la obtención de `salutation_full` usando el nuevo `courtesy_label` en vez de derivarlo por `strpos()` del saludo completo.

---

## 2026-10-02

### Añadido
- Lectura en vivo de HubSpot (`MGMIT_HS_Contacts::get_properties()`) para `courtesy_de`, `job_title_de` y `title`, con fallback seguro si falla la consulta (`inc/hubspot-swpm-stripe-bridge/class-salutation-resolver.php`, función `mgmit_bridge_build_salutation()`).
- `hs-connection-test.php` (root, solo admin): herramienta de diagnóstico de la conexión HubSpot y del saludo resultante.
- Email de confirmación de renovación (`email-renewal-confirmed.php`, hook `swpm_recurring_payment_received`), con factura y confirmación de membresía adjuntas.
- Aviso de renovación a 30 y 15 días antes del aniversario de cada socio (`flow-renewal-reminder.php`, cron diario), calculado sobre `subscription_starts + 1 año`.
- Email de confirmación de baja (`flow-deactivation.php` → `mgmit_bridge_send_cancellation_email()`), inexistente hasta ahora.
- Nuevas plantillas: `body-renewal-confirmed.php`, `body-renewal-reminder.php`, `body-cancellation.php`.

### Cambiado
- Contenido del email de alta (`email-templates/body.php`) actualizado al nuevo texto aprobado.
- `email-membership.php`: saludo resuelto vía HubSpot en vez de la lógica de género de SWPM (poco fiable: ~6.900 registros sin especificar). Eliminado el parámetro `$gender`, ya sin uso, y su lectura en `flow-payment.php`.
- `email-templates/vorschreibung-pdf.php`: eliminado el bloque de aviso de pago y datos bancarios (el cobro ya lo gestiona Stripe automáticamente).

### Eliminado
- Plantillas obsoletas del esquema de año natural: `renewal-confirmed.php`, `renewal-sepa.php`, `renewal-transfer.php`.

### Pendiente
- Procedimiento de baja antes de renovar (texto del aviso de 30/15 días) — a la espera del botón de baja.
- Dato `job_title_de = "Frau"` detectado en un contacto de prueba de HubSpot — posible error de entrada, a revisar en origen.

---

## 2026-09-30

### Cambiado
- Membresías Stripe: revertida la duración a "1 año desde la fecha de alta" (aniversario nativo de Stripe), en lugar del año natural (31/12) implementado previamente.
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/`: las versiones con lógica de año natural (`flow-payment.php`, `flow-deactivation.php`, `flow-renewal.php`, `flow-webhook-renewal.php`) se movieron a `legacy-ano-natural/` (sin cargar). En su lugar se restauraron las versiones previas de `flow-payment.php` y `flow-deactivation.php`, que dejan intacta la suscripción de aniversario creada por Stripe.
- `loader.php`: eliminados los `require_once` de `flow-renewal.php` y `flow-webhook-renewal.php`.
- `wp-content/themes/basel-child/functions.php`: eliminado el banner de renovación (`mgmit_renewal_banner_output`, hook `wp_footer`) — dependía de la meta `mgmit_membership_valid_until`/`mgmit_show_renewal_button` que ya no se genera. Los avisos de renovación pasan a gestionarse vía los recordatorios nativos de SWPM y Stripe.

---

## 2026-09-16

### hubspot-mapper 2.5.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Cambiado
- Eliminadas las referencias visibles a "MeGeMIT" en cabecera del plugin, menú admin y textos de la UI. Los identificadores internos (`MGMIT_*`, textdomain, option keys) se mantienen sin cambios para no romper la configuración ya guardada.

#### Añadido
- **Duplicar mapeo**: nuevo botón "Duplicar" en el listado que clona un mapeo existente (útil para formularios idénticos en distintos idiomas). El nombre del duplicado se genera automáticamente evitando colisiones con nombres ya existentes (sufijo `(copia)`, `(copia 2)`, etc.), y redirige directamente a la edición del nuevo mapeo.

---

## 2026-09-11

### Añadido
- `wp-content/mu-plugins/bacs-admin-only.php` — oculta el método de pago "Transferencia bancaria directa" (BACS) en el checkout del frontend mediante el filtro `woocommerce_available_payment_gateways`, manteniéndolo disponible para pedidos creados manualmente desde `wp-admin`. El gateway permanece "Activado" en `WooCommerce > Ajustes > Pagos`.

---

## 2026-09-03

### [mu-plugin hubspot-email-logger]
*Origen: docs/CHANGELOG.md*

#### Added — `wp-content/mu-plugins/hubspot-email-logger.php`

Registra en el timeline del contacto de HubSpot los correos que WooCommerce envía al cliente (confirmación de pedido, factura, notas, reembolsos), incluyendo la factura/nota de abono PDF adjunta. Estos correos salen directamente vía SMTP y HubSpot no los ve salvo que aterricen en un buzón conectado a Conversations.

- Hook en `woocommerce_email_sent`, filtrado a los IDs de email de cliente (`wc_hs_email_logger_customer_email_ids`, extensible por filtro).
- Envío a HubSpot diferido vía Action Scheduler (`wc_hs_email_logger_log_email`) para no retrasar el checkout.
- Resolución del Access Token en cascada (`wc_hs_email_logger_get_token`): constante `MGMIT_HS_ACCESS_TOKEN_SECRET` → opción `mgmit_mapper_credentials` (plugin `hubspot-mapper`) → opción `mgmit_hubspot_credentials` (bridge). Reutiliza `MGMIT_HS_Contacts::get_token()` si el plugin `hubspot-mapper` está activo.
- Adjuntos (PDF de factura/nota de abono) subidos a HubSpot Files API (`/files/v3/files`, carpeta `/wc-hs-email-logger`) de forma síncrona en el momento de la captura — antes de encolar, porque son archivos temporales que WooCommerce puede borrar antes de que corra la tarea diferida — y asociados al engagement vía `hs_attachment_ids`. Requiere el scope `files` en la Private App de HubSpot.
- Nombres de función con prefijo genérico `wc_hs_email_logger_` (sin referencias al sitio) para poder reutilizar el snippet en otros proyectos.

#### Fixed

- El payload completo (incluyendo el HTML renderizado del email) se pasaba directo como argumento de la acción de Action Scheduler; al superar los 8000 caracteres, Action Scheduler rechazaba la acción en silencio (excepción `ActionScheduler_Action::$args zu lang`) y no quedaba ningún rastro ni en logs ni en HubSpot. Ahora el payload se guarda en un transient y solo se pasa su clave a la acción.
- Bug de despliegue: el código usaba una constante inexistente (`MEGEMIT_HUBSPOT_TOKEN`) en las llamadas a la API en vez de `MGMIT_HS_ACCESS_TOKEN_SECRET`.

---

## 2026-08-31

### wc-moodle-sync 1.7.0
*Origen: wc-moodle-sync/CHANGELOG.md*

Desmatriculación automática al cancelar/reembolsar/fallar un pedido, y bloqueo
de reembolso si el curso ya se ha iniciado.

- `WCMS_Moodle_Api::remove_from_cohort()` — elimina al usuario del/de los cohort(s)
  vía `core_cohort_delete_cohort_members` (retira la matriculación sincronizada en Moodle).
- Nuevos hooks en `WC_Moodle_Sync`: `woocommerce_order_status_cancelled`,
  `_refunded`, `_failed` → `WCMS_Scheduler::enqueue_unenroll()`.
- `WCMS_Scheduler::enqueue_unenroll()` / `run_unenroll()` — desmatricula (saca del/de
  los cohort(s) del pedido) solo si el pedido llegó a matricularse
  (`_wcms_job_queued`) y aún no se ha desmatriculado (`_wcms_unenroll_done`,
  guardia de idempotencia).
- Nueva clase `WCMS_Refund_Guard` — bloquea la creación del reembolso (admin AJAX y
  REST API) si el comprador tiene progreso > 0% en cualquier curso Moodle en el que
  esté matriculado (vía `get_user_courses()`, campo `progress` nativo de
  `core_enrol_get_users_courses`). No distingue curso por línea de pedido: si el
  pedido es un pack y cualquiera de los cursos matriculados del alumno está iniciado,
  se bloquea el reembolso del pedido completo. Mensaje de aviso en alemán si
  `get_locale()` empieza por `de`, inglés en cualquier otro caso
  (helper `wcms_msg()` en `wc-moodle-sync.php`).

---

## 2026-08-27

### wc-moodle-sync 1.6.0
*Origen: wc-moodle-sync/CHANGELOG.md*

Sistema de bonificación con cupón de descuento por progreso de nota.

- Nuevo evento de webhook `grade_updated` (mismo endpoint `/wc-moodle-sync/v1/course-complete`).
- `WCMS_Moodle_Api::get_user_courses()` — cursos matriculados del alumno vía `core_enrol_get_users_courses`.
- `WCMS_Moodle_Api::get_course_grade_items()` — grade items por curso vía `gradereport_user_get_grade_items`.
- `WCMS_Completion_Handler::handle_grade_updated()` — calcula el % de nota combinado (suma de
  `graderaw`/`grademax` de todos los módulos de todos los cursos matriculados, excluyendo el
  total agregado del curso) y emite cupón de bonificación al cruzar el 40% y el 80%.
- Límite de 2 cupones activos (sin usar) por cliente, compartido entre cupones de finalización
  de curso y de bonificación. Dedupe por umbral vía user meta `_wcms_bonus_{40,80}_coupon`.
- `WCMS_Mailer::send_bonus_coupon()` — reutiliza la plantilla de email de finalización de curso
  con el subtítulo adaptado al % de progreso alcanzado.
- README.md actualizado (funciones Web Service requeridas, documentación del evento
  `grade_updated`, corrección de nota obsoleta sobre `roleid`).

**Pendiente (lado Moodle, fuera de este plugin):** observer de eventos que dispare el POST al
webhook con `event_type=grade_updated` cuando se actualiza una nota, igual que ya está pendiente
para `course_completed` y `exam_passed`.

---

## 2026-08-14

### hubspot-mapper 2.4.5
*Origen: hubspot-mapper/CHANGELOG.md*

#### Corregido
- **Fallo en cascada al registrar varios campos nuevos a la vez (fase de fallback uno-a-uno de `update_form_fields()`):** al añadir un campo con éxito, el código seguía usando la copia local (desactualizada) del formulario para el siguiente POST, provocando `412 CONCURRENT_UPDATE_BY_USER` en los campos siguientes de la misma tanda. Esto explicaba por qué, al guardar varios campos estáticos nuevos a la vez, solo el primero llegaba a registrarse. Ahora se usa la respuesta de cada POST exitoso como base para el siguiente, y si aun así llega un 412 (p.ej. por otro envío concurrente real), se refresca el formulario y se reintenta una vez antes de descartar el campo.

#### Documentado
- `hubspot_owner_id` (y cualquier propiedad que HubSpot marque `PROPERTY_DISABLED_IN_FORMS`) no puede enviarse vía Forms API bajo ningún formato: es una restricción de HubSpot, no un bug del plugin. Queda registrado en `hubspot-mapper.log` con el detalle devuelto por HubSpot.

### hubspot-mapper 2.4.4
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- **Log propio del plugin**, independiente de `WP_DEBUG_LOG`: `wp-content/plugins/hubspot-mapper/hubspot-mapper.log`. Necesario porque en el entorno de pruebas actual `WP_DEBUG_LOG` no está activo y no había forma de diagnosticar por qué algunos campos estáticos no llegaban a HubSpot.
- Logging explícito en `process()` de las props recibidas, los campos finalmente incluidos en el submit y el resultado, y en `ensure_form()` de qué campos se detectan como nuevos a sincronizar.

### hubspot-mapper 2.4.3
*Origen: hubspot-mapper/CHANGELOG.md*

#### Corregido
- **Propiedades de objetos no-contact (`company-`, `deal-`, `ticket-`) nunca se registraban de verdad en HubSpot:** al añadir un campo al formulario de HubSpot (`update_form_fields()`/`create_form()`), el `propertyObjectType` se enviaba siempre como `CONTACT` y la resolución de tipos/enum (`get_property_types()`, `resolve_enum_value()`) solo consultaba `crm/v3/properties/contacts`. Para una propiedad que solo existe en Company/Deal/Ticket (p.ej. `company-country_code`), HubSpot rechazaba el registro del campo (visible como `update_form_fields fallback HTTP 400` en el log) y el campo quedaba fuera de la definición del formulario, así que la submission lo enviaba pero HubSpot lo ignoraba.
- Ahora cada campo (mapeado o estático) se registra con el `propertyObjectType` correcto (`CONTACT`/`COMPANY`/`DEAL`/`TICKET`) según su prefijo, y los tipos/opciones de propiedad se resuelven contra el objeto correspondiente. Mapeos existentes se autoresincronizan en el primer envío tras la actualización.

### hubspot-mapper 2.4.2
*Origen: hubspot-mapper/CHANGELOG.md*

#### Corregido
- **Campos estáticos nuevos no llegaban a HubSpot en mapeos ya existentes:** `MGMIT_HS_Forms::ensure_form()` devolvía el `hs_form_guid` cacheado sin volver a llamar a `update_form_fields()`, así que un campo estático (p.ej. `company-country_code`) añadido a un mapeo que ya tenía formulario creado nunca se registraba en la definición del formulario en HubSpot; la submission lo incluía pero HubSpot lo descartaba por no reconocerlo. Ahora se persiste `hs_form_fields_synced` por mapeo y solo se llama a `update_form_fields()` cuando hay campos nuevos respecto a la última sincronización, evitando además una llamada extra a la API en cada envío normal.

### hubspot-mapper 2.4.1
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- **Soporte de prefijo de objeto (`objeto-propiedad`) en campos estáticos:** la UI de "Campos Estáticos" ahora documenta y permite el mismo formato ya soportado en campos mapeados (`contact-`, `company-`, `deal-`, `ticket-`). Ya funcionaba en backend para el modo overwrite; ahora queda expuesto y explicado en el formulario de mapeo.

#### Corregido
- **`append_property()` (checkbox "Concatenar") ignoraba el objeto:** solo soporta la API de Contacts, ya que requiere lookup por email. Ahora `class-mgmit-adapters.php` detecta si un campo estático con `append=true` apunta a un objeto distinto de `contact` y lo omite con log, en vez de concatenar silenciosamente contra el contacto equivocado. La UI avisa de esta limitación al guardar.

---

## 2026-07-30

### hubspot-mapper Sin publicar
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- **Adaptador para Profile Builder** (`wppb_register_success`): permite mapear registros creados con el plugin Profile Builder. Se registra con `priority => 5` intencionadamente baja, ya que el tema engancha `pb_force_redirect_after_registration` en prioridad 10 con `wp_redirect()+exit`, cortando cualquier callback posterior si no se ejecuta antes.
- `MGMIT_Mapper_Adapters::resolve_profile_builder()`: resuelve los datos desde `$_REQUEST` (vía `wp_unslash()`), con fallback a `post_data()`.

#### Corregido
- **`flatten()` usaba `, ` como separador para valores múltiples:** ahora usa `;`, alineado con `resolve_enum_value()` en `class-mgmit-hs-forms.php`, que espera ese separador en propiedades checkbox/select de HubSpot.

---

## 2026-07-13

### hubspot-mapper 2.4.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- **Soporte multi-objeto en el mapeo de propiedades HubSpot:** las propiedades ahora admiten el formato `{objeto}-{propiedad}` (ej: `contact-email`, `company-name`, `deal-dealname`). Objetos soportados: `contact` (0-1), `company` (0-2), `deal` (0-3), `ticket` (0-5).
- Helper público `MGMIT_HS_Forms::parse_prop_key($key)`: descompone la clave en `objectTypeId` y `name`; si no hay prefijo de objeto reconocido, aplica fallback a `contact` para retrocompatibilidad total con mapeos existentes.

#### Corregido
- **`submit()` enviaba todos los campos al objeto `contact` (`0-1`) de forma hardcodeada:** ahora cada campo lleva su `objectTypeId` correcto derivado del prefijo del nombre de propiedad.
- `process()`: la clasificación enum y la resolución de valores (`resolve_enum_value`) ahora usan el nombre de propiedad sin prefijo para consultar la API de HubSpot.
- `handle()` en adaptadores: la detección del campo email ahora reconoce tanto `email` como `contact-email`.
- `append_property` para campos estáticos con `append=true`: el nombre de propiedad se limpia de prefijo antes de enviarse a la CRM API.
- Validación `has_email` en `ajax_save_mapping`: acepta `contact-email` además de `email`.

#### UI
- Placeholder de la columna "Propiedad HubSpot" actualizado a `contact-firstname` para ilustrar el nuevo formato.
- Cabecera de la columna muestra el formato esperado: `objeto-propiedad`.

---

## 2026-07-02

### [basel-child swpm-woo-sync]
*Origen: docs/CHANGELOG.md*

#### Added — Tema `basel-child` (`functions.php`)

Permite que clientes WooCommerce (email existente en `wp_users` pero sin cuenta SWPM) se registren en Simple WP Membership vía el Form Builder de SWPM.

- **Validación de email duplicado en SWPM** (`mgmit_swpm_validate_email_not_in_swpm`) — filter en `swpm_validate_registration_form_submission`. Corrige el bug del form builder (`AND user_name != ''`) haciendo una query directa a `wpgr_swpm_members_tbl` sin esa condición. Bloquea el registro si el email ya existe en SWPM; permite el registro si solo existe en `wp_users`.
- **Sincronización del WP user existente** (`mgmit_swpm_sync_wp_user_on_registration`) — action en `swpm_front_end_registration_complete_fb` (form builder) y `swpm_front_end_registration_complete_user_data` (formulario estándar). Tras el registro SWPM exitoso actualiza en el WP user: `user_login` y `user_nicename` (via `$wpdb->update` directo + `clean_user_cache`), `user_pass`, `first_name`, `last_name` y `display_name`. No modifica administradores.

---

## 2026-07-01

### [basel-child pdf-invoice]
*Origen: docs/CHANGELOG.md*

#### Changed — Tema `basel-child` (`woocommerce/pdf/MeGeMit/invoice.php`)

- **Tratamiento del cliente** — se muestra el campo `_anrede` (Dr., Dra., etc.) delante del nombre del comprador en el bloque de dirección de factura. Se recupera via `$this->order->get_meta('_anrede')`.
- **Tipo de impuesto en totales** — se añade una fila "Impuesto X %" al final de la tabla de totales usando `$this->order->get_tax_totals()` y `WC_Tax::get_rate_percent_value( $tax->rate_id )`. Necesario porque con `woocommerce_tax_display_cart = incl` WooCommerce no genera filas de impuesto separadas en `get_order_item_totals()`.
- **Eliminación de nota de impuesto en el total** — la fila `order_total` ahora usa `wc_price( $this->order->get_total() )` en lugar de `get_formatted_order_total()`, evitando que aparezca el texto `(includes MwSt. XX,XX €)` entre paréntesis.
- **Instrucción de pago sin importe de impuesto** — la línea "Bitte überweisen…" usa también `wc_price()` directo, eliminando la misma nota parentética.

---

## 2026-06-25

### [basel-child webhook-filters]
*Origen: docs/CHANGELOG.md*

#### Changed — Tema `basel-child` (`inc/woocommerce/`)

- **URL de factura PDF en el payload del webhook de pedidos** — nuevo filtro `woocommerce_webhook_payload` (`webhook-filters.php`) que añade la clave `invoice_pdf_url` al payload enviado al webhook de pedidos (`vpsbridge.dimint.com/webhook/woocommerce/de`). Solo aplica a `resource = order`.
- **Endpoint propio de factura** (`invoice-endpoint.php`, nuevo) — sirve el PDF vía `admin-ajax.php?action=mgmit_invoice_pdf&order_id=X&token=Y`. El `token` es un HMAC-SHA256 firmado con la constante `INVOICE_SECRET` (nuevo `inc/woocommerce/config.php`, junto con `WEBHOOK_NEW_ORDER_TTL`), independiente de sesión/login. Se descartó usar el sistema nativo de `access_key` del plugin "PDF Invoices & Packing Slips" porque en modo `logged_in` (ajuste activo en el sitio) el enlace solo es válido para la misma sesión que lo generó — no sirve para un payload reenviado a terceros (HubSpot vía vpsbridge).

### [wc-moodle-sync 1.3.0]
*Origen: docs/CHANGELOG.md*

#### Added — Plugin `wc-moodle-sync`

- **`config.php`** — nuevo archivo de configuración local del plugin (fuera de `wp-config.php`): centraliza `WCMS_MOODLE_API_URL`, `WCMS_MOODLE_TOKEN`, `WCMS_MOODLE_LOGIN_URL`, `WCMS_COMPLETION_SECRET`, `WCMS_COUPON_SHOP_URL`, y las nuevas `WCMS_WELCOME_EMAIL_CC`, `WCMS_SEND_CERTIFICATE`, `WCMS_SEND_COUPON`.
- **CC en emails al alumno** — los tres emails (`send_welcome`, `send_certificate`, `send_course_completion`) añaden cabeceras `Cc:` por cada dirección válida en `WCMS_WELCOME_EMAIL_CC`.
- **Toggles de envío** — `WCMS_SEND_CERTIFICATE` y `WCMS_SEND_COUPON` permiten desactivar la generación/envío de certificado PDF y cupón descuento sin tocar código.

#### Changed — Plugin `wc-moodle-sync`

- **Email de bienvenida/matriculación traducido al alemán** — `build_html()` en `class-wcms-mailer.php` estaba en español; ahora coherente en idioma con los otros dos emails del plugin (certificado y cupón, que ya estaban en alemán).
- Documentado en `README.md` que el endpoint `course-complete` está operativo en WordPress pero pendiente de implementar el lado Moodle (event observer / plugin de webhooks) que lo invoque automáticamente.

---

## 2026-06-18

### [swpm-expiry-reminders 1.1.0]
*Origen: docs/CHANGELOG.md*

#### Changed — Plugin `swpm-expiry-reminders`

- **Cálculo de días normalizado a medianoche** — se comparan `$today_midnight` y `$expiry_midnight` (usando `strtotime( date('Y-m-d', ...) . ' 00:00:00' )`) en lugar de timestamps brutos, eliminando desfases de ±1 día especialmente en niveles de tipo Annual Fixed Date.
- **Scope por nivel de membresía** — nueva opción `swpm_expiry_reminder_scope` (`all` / `specific`). Cuando se selecciona `specific`, la opción `swpm_expiry_reminder_level_ids` almacena los IDs de los niveles a procesar. En el cron se aplica intersección entre los niveles con expiración configurada y los seleccionados.
- **Admin UI actualizada** — sección "Apply To" con radio buttons y lista de checkboxes de los niveles SWPM (obtenidos de `swpm_membership_tbl`), con toggle JS vanilla sin dependencia de jQuery.

---

## 2026-06-17

### [swpm-expiry-reminders 1.0.0]
*Origen: docs/CHANGELOG.md*

#### Added — Nuevo plugin `swpm-expiry-reminders`

Plugin standalone que extiende Simple WP Membership con recordatorios de caducidad de membresía enviados por email.

- Días de antelación configurables (por defecto: 45, 30, 15). Se pueden especificar múltiples valores separados por coma.
- Plantilla de email editable con `wp_editor` (Visual + Text). Plantilla por defecto en alemán.
- Variables dinámicas: `{first_name}`, `{last_name}`, `{email}`, `{membership_level}`, `{expiry_date}`, `{days_remaining}`, `{renewal_url}`.
- URL de página de renovación configurable desde el admin.
- Remitente (nombre + email) personalizable.
- Anti-duplicado por usuario: flag `swpm_expiry_reminder_{N}` en `wp_usermeta`; se limpia automáticamente al renovar vía hook `swpm_payment_ipn_processed`.
- Enganchado al cron diario nativo de SWPM (`swpm_daily_cron_event`); procesa miembros activos en lotes de 100.
- La fecha de expiración se obtiene de la configuración de recurrencia del nivel de membresía via `SwpmUtils::get_expiration_timestamp()` (soporta DAYS, WEEKS, MONTHS, YEARS, FIXED_DATE, ANNUAL_FIXED_DATE).
- Panel de configuración en **WP Membership → Expiry Reminders** (submenu bajo `simple_wp_membership`).
- Rutas: `wp-content/plugins/swpm-expiry-reminders/swpm-expiry-reminders.php`, `views/admin-settings.php`.

---

## 2026-06-11

### hubspot-mapper 2.3.3
*Origen: hubspot-mapper/CHANGELOG.md*

#### Corregido
- **Creación de formulario fallaba si algún campo no existía como propiedad en HubSpot:** `create_form()` ahora crea el formulario únicamente con el campo `email` (siempre existe en HubSpot) y a continuación llama a `update_form_fields()` para añadir el resto de campos de forma incremental. Los campos que no existan en HubSpot fallan solo en esa llamada sin bloquear la creación del formulario. Elimina la necesidad de mapear primero solo campos válidos y volver a guardar.

---

## 2026-06-10

### [wc-moodle-sync 1.2.0]
*Origen: docs/CHANGELOG.md*

#### Added — Plugin `wc-moodle-sync`

- **Webhook `course_completed`** — nuevo endpoint REST `POST /wp-json/wc-moodle-sync/v1/course-complete`. Cuando Moodle notifica la finalización de un curso, genera un cupón WooCommerce único (`MeGeMIT-XXXXXXXX`) al 100% de descuento, de un solo uso, restringido por email del usuario y por la categoría de producto `MDL-Coupon`. Envía email de felicitación en alemán con el código.
- **Webhook `exam_passed`** — el mismo endpoint acepta `event_type: exam_passed`: genera un certificado PDF dinámico con el nombre del alumno y la fecha en alemán superpuestos sobre la plantilla PNG del plugin; envía el PDF adjunto por email.
- **`class-wcms-completion-handler.php`** — clase singleton que registra el endpoint REST, autentica via `Authorization: Bearer`, despacha al flujo correcto según `event_type` y deduplica llamadas repetidas mediante `user_meta`.
- **`class-wcms-certificate.php`** — generador de certificados con GD. Carga `certificate-template.png`, superpone nombre (BrushScript.ttf, 46 pt, color `#2D2D4B`, centrado al 46 % del alto) y fecha en alemán (TimesItalic.ttf, 26 pt, color `#1D5FA5`, centrado al 72 % del alto), ensambla PDF 1.4 sin dependencias externas.
- **`assets/certificate-template.png`** — plantilla de certificado limpia (1684×1192 px, sin texto preexistente).
- **`assets/fonts/BrushScript.ttf`** y **`assets/fonts/TimesItalic.ttf`** — fuentes TTF para el certificado.
- **`class-wcms-mailer.php`** — añadidos `send_course_completion()` y `send_certificate()` con sus respectivas plantillas HTML en alemán.
- **Constantes nuevas** en `wc-moodle-sync.php`: `WCMS_COMPLETION_SECRET` y `WCMS_COUPON_SHOP_URL`.

#### Changed — Plugin `wc-moodle-sync`

- Descripción del cupón sigue el patrón `Akademie-Coupon für Nombre Apellido`.
- El código del cupón mantiene el formato `MeGeMIT-XXXXXXXX` (8 caracteres aleatorios en mayúsculas).
- Certificados almacenados en `wp-content/uploads/wcms-certificates/`.

---

## 2026-06-05

### hubspot-mapper 2.3.2
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- **Campos estáticos por mapeo (`static_fields`):** desde la vista de edición del mapeo se pueden definir pares (propiedad HubSpot → valor fijo) que se envían siempre con la submission, aunque esos campos no existan en el formulario de WordPress.
- UI: tabla "Campos Estáticos" con botones añadir/eliminar fila y columna "Concatenar"; integrada en la sección `mgmit-hs-collect-section` (se deshabilita junto al resto cuando `do_not_collect` está activo).
- Persistencia: clave `static_fields` (array de `{hs_prop, value, append}`) dentro de cada entrada de `mgmit_mapper_config`.
- **Modo Concatenar (`append=true`):** para campos de tipo Multiple Checkbox, en lugar de sobreescribir el valor existente hace GET → merge (deduplicado, separador `;`) → PATCH vía CRM Contacts API. Los campos con `append=false` siguen yendo por la submission del formulario.
- `MGMIT_HS_Contacts::append_property($email, $prop, $value)`: método estático que implementa el flujo GET+merge+PATCH; si el contacto no existe (404) crea uno nuevo con POST.
- `update_form_fields($guid, $field_names)` en `MGMIT_HS_Forms`: detecta campos estáticos ausentes en la definición del formulario HubSpot y los añade via Forms API v2 PUT antes de la primera submission.

#### Corregido
- **CF7 en producción:** JS de optimización/caché difería la carga del script; `mgmit_mapper_id` no se inyectaba antes de que CF7 serializara el formulario. Se añade un listener `submit` en fase de captura (`true`) en `hubspot_map.js` como garantía de último recurso — ejecuta `setupForm()` justo antes de cualquier otro handler.

---

## 2026-06-04

### hubspot-mapper 2.3.1
*Origen: hubspot-mapper/CHANGELOG.md*

#### Corregido
- **Forms API v2 en lugar de v3:** la creación de formularios cambia a `/forms/v2/forms` (estructura más simple, sin campos requeridos ocultos de v3).
- **`postSubmitAction.type`:** corregido de `"thank_you_text"` a `"thank_you"` (enum v3).
- **`fieldType`:** corregido de `"text"` a `"single_line_text"` / `"email"` (v3 no aceptaba `"text"`).
- **Log PHP 7.4:** el sitio corre PHP 7.4 vía ServBay; el log real está en `/Applications/ServBay/package/var/log/php/7.4/errors.log`, no en `wp-content/debug.log`.

#### Añadido
- **`find_form_by_name()`:** antes de crear un formulario nuevo, busca en HubSpot si ya existe uno con el mismo nombre (`hubspotFormName`). Cascada: guid en WP options → buscar por nombre en HubSpot → crear nuevo.

### hubspot-mapper 2.3.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Cambiado
- **Nuevo modelo de envío — Forms API v3 + Submissions API:** elimina la dependencia de
  LeadIn "collected forms" (JS, navegador, adblockers, configuración de cuenta HubSpot).
  El envío es ahora 100% server-side desde PHP.

#### Añadido
- **`MGMIT_HS_Forms`** (`includes/class-mgmit-hs-forms.php`):
  - `ensure_form()` — crea el formulario en HubSpot automáticamente (Forms API v3,
    `POST /marketing/v3/forms`) la primera vez que se procesa un mapeo; persiste el
    `formGuid` en la config del mapeo para no recrearlo.
  - `submit()` — envía los datos al endpoint de submissions v3
    (`POST https://api.hsforms.com/submissions/v3/integration/submit/{portalId}/{formGuid}`)
    sin autenticación. Incluye `hutk` (leído de `$_COOKIE['hubspotutk']` server-side)
    para atribución de sesión.
- `process()` orquesta ambos pasos y es el único punto de entrada desde los adaptadores.

#### Eliminado del flujo principal
- `MGMIT_Ghost_Relay::queue()` ya no se llama desde los adaptadores.
  La clase relay se mantiene en el plugin pero no participa en el flujo de envío.
- Se elimina `MGMIT_Ghost_Relay::init()` del bootstrap (hooks de wp_footer e inyección
  del script de tracking ya no se registran).

#### Requisitos
- Access Token con scope `forms` (además de `crm.objects.contacts.write`).

### hubspot-mapper 2.2.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Cambiado (BREAKING)
- **Nuevo modelo de envío — Ghost Form:** los datos ya no se envían a la CRM Contacts API.
  Tras confirmarse el éxito server-side del formulario, PHP crea un formulario sintético invisible
  («ghost form») que LeadIn captura y envía a HubSpot — **sin formGuid**. Los registros aparecen
  en **Marketing → Formularios** bajo el nombre configurado en cada mapeo (`hubspotFormName`).

#### Añadido
- **`MGMIT_Ghost_Relay`** (`includes/class-mgmit-ghost-relay.php`): gestiona el ciclo completo
  del relay — transient + cookie corta (120s), encolado del tracking script de HubSpot si no está
  presente en la página destino (`//js.hs-scripts.com/{portalId}.js`), emisión del inline script en
  `wp_footer`, y endpoint AJAX `mgmit_mapper_get_relay` para formularios AJAX (CF7).
- **Campo "Nombre en HubSpot"** (`hubspotFormName`) restaurado en el admin: valor obligatorio,
  se usa como `id`/`name` del ghost form → nombre en el dashboard de HubSpot.
- **Portal ID** en el panel de credenciales del admin: necesario para cargar el tracking script
  en páginas que no lo tengan. Cascada: opción propia → `mgmit_hubspot_credentials` → `mgmit_hubspot_config`.

#### Frontend (`hubspot_map.js`)
- Añadido motor del ghost form: `fireGhostForm()`, `fireWhenReady()` (espera init de `_hsq`),
  `window.__mgmitFireGhost` (punto de entrada desde el inline script PHP).
- Listener `wpcf7mailsent` para formularios CF7 AJAX: llama al endpoint `mgmit_mapper_get_relay`
  y dispara el ghost form en la misma página sin recargar.
- `getCookie()` helper para leer `hubspotutk` (hutk) y `mgmit_hs_relay`.

#### Notas
- La atribución por cookie (`hutk`) queda restablecida: el ghost form incluye `hs_context`
  con `hutk`, `pageUri` y `pageName` — exactamente como hacía el bridge en v1.x.
- `MGMIT_HS_Contacts` se mantiene en el plugin como clase de utilidad pero ya no se llama
  desde el flujo principal.

### hubspot-mapper 2.1.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Cambiado
- **Credenciales propias (desacople del bridge):** `MGMIT_HS_Contacts::get_token()` ahora resuelve el token en cascada — `MGMIT_HS_ACCESS_TOKEN_SECRET` (constante) → opción propia `mgmit_mapper_credentials` → opción del bridge `mgmit_hubspot_credentials` (respaldo). El plugin ya **no depende** de que el bridge esté instalado.

#### Añadido
- Panel **"Credenciales HubSpot"** en el admin (vista de lista): guarda el Access Token en la opción propia del plugin (AJAX `mgmit_mapper_save_credentials`) e indica de qué fuente se está tomando el token activo (o avisa si no hay ninguno).
- `MGMIT_HS_Contacts::get_token_source()` para diagnóstico del origen del token.

### hubspot-mapper 2.0.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Cambiado (BREAKING)
- **Nuevo modelo de envío:** los datos ya **no** se capturan dejando que HubSpot LeadIn escuche el `submit`. Ahora el envío lo realiza PHP contra el **CRM Contacts API** (`crm/v3/objects/contacts`, upsert por email) **solo cuando el formulario confirma su éxito server-side** (tras pasar la validación JS y PHP del propio plugin de formularios). Esto elimina los registros erróneos en HubSpot cuando el formulario tiene errores.
- **Sin formulario en HubSpot:** no se usa `formGuid` ni Forms API. Se reutiliza el token configurado en el plugin **MeGeMIT HubSpot Bridge** (`mgmit_hubspot_credentials`, con override por constante `MGMIT_HS_ACCESS_TOKEN_SECRET`).
- **Modelo de datos del mapeo:** se elimina `hubspotFormName`; se añaden `source` (plugin de origen) y `email_field` (campo email, clave del upsert).

#### Añadido
- **Capa de envío** [`MGMIT_HS_Contacts`](includes/class-mgmit-hs-contacts.php): upsert por email (PATCH `idProperty=email`, con POST de creación si 404).
- **Capa de adaptadores** [`MGMIT_Mapper_Adapters`](includes/class-mgmit-adapters.php) con hooks de éxito server-side para **SWPM**, **Contact Form 7**, **WooCommerce** y **Ultimate Member**. Extensible mediante el filtro `mgmit_mapper_adapters` (añadir plugins sin tocar el core).
- Admin UI: selector **"Origen del Formulario"** y campo **"Campo Email"** por mapeo; columna **"Origen"** en la lista.

#### Frontend (`hubspot_map.js`)
- Reescrito a **vanilla JS** (sin jQuery). Aplica `data-hs-do-not-collect="true"` en todos los formularios configurados (LeadIn off) e inyecta el hidden `mgmit_mapper_id`.
- `MutationObserver` con debounce para formularios cargados por AJAX/diferidos.

#### Notas
- La atribución por cookie de tracking (`hutk`) no se enlaza vía CRM API; es una limitación asumida al no usar un formulario de HubSpot.

---

## 2026-06-03

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Fixed
- **`email-membership.php` / `email-templates/body.php`** — imagen de cabecera del email de membresía ahora usa URL pública (`get_stylesheet_directory_uri`) en lugar de ruta de sistema, corrigiendo la visualización en clientes de correo.

#### Added
- **`inc/hubspot-swpm-stripe-bridge/`** — nuevo módulo que centraliza la integración entre HubSpot, SWPM, WooCommerce y Stripe: webhook sender, flujos de pago/desactivación, emails de membresía con plantillas.
- **`membership-woo-bridge.php`** — campo meta `_swpm_membership_level` en el admin de producto WooCommerce; guarda nivel previo al crear la orden para poder revertirlo.
- **`onboarding-enforcement.js`** — función `blockNavigation()`: bloquea todos los enlaces y elementos JS del header (carrito, buscador, sidebar) para usuarios con registro pendiente.

#### Changed
- **`membership-woo-bridge.php`** — `swpm_update_level_after_payment` ahora usa la API nativa de SWPM (`SwpmMemberUtils`, `SwpmTransactions`) disparando hooks y email de confirmación; fallback a actualización directa en BD si SWPM no está disponible.
- **`membership-woo-bridge.php`** — nueva función `swpm_revert_level_on_order_status_change`: revierte el nivel de membresía al anterior cuando el pago se cancela, falla o reembolsa.
- **`functions.php`** — URLs absolutas del header convertidas a rutas relativas; carga `hubspot-swpm-stripe-bridge/loader.php`.

#### Removed
- Plantilla PDF `MeGeMit2` (obsoleta): `invoice.php`, `style.css`, `html-document-wrapper.php`, `template-functions.php`, `facebook_icon.png`.

### hubspot-mapper 1.1.0
*Origen: hubspot-mapper/CHANGELOG.md*

#### Añadido
- Nueva opción por mapeo: **"Solo bloquear recogida de datos"** (`do_not_collect`).
  - Al activarla, HubSpot LeadIn no recogerá ni enviará los datos del formulario.
  - El atributo `data-hs-do-not-collect="true"` se inyecta en el `<form>` en el evento `DOMContentLoaded` nativo (antes de que LeadIn escanee la página), garantizando que el bloqueo llegue a tiempo.
  - Compatible con formularios cuyo selector apunta directamente al `<form>` o a un `div` contenedor (se localiza el `<form>` interno automáticamente).
- Insignia visual **"🚫 No recoger"** en la vista de lista del admin para mapeos con esta opción activa.

#### Cambiado
- Los campos "Nombre en HubSpot" y "Mapeo de Campos" se deshabilitan visualmente en el admin cuando `do_not_collect` está activo (no son necesarios).
- Versión bumpeada de `1.0.0` → `1.1.0`.

---

## 2026-05-26

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Feat — Nuevo plugin `hubspot-mapper` (frontend-only HubSpot field mapper)

Plugin independiente de mapeo 100% frontend de formularios SWPM y Ultimate Member → HubSpot, sin llamadas server-side a la API. Coexiste con `mgmit-hubspot-bridge` (v1.5.0, server-side) sin conflictos.

##### Características
- Mapea campos de formularios WP a propiedades HubSpot renombrando los campos en el submit (JS).
- Soporte para formularios SWPM (selector `#form-id`) y Ultimate Member (selector `.um-{id}`).
- Soporte para campos radio/checkbox: solo se renombran los inputs `:checked`.
- Inyecta `hs_context` con cookie hutk, pageName y pageUrl en cada submit.
- Admin UI con CRUD de mapeos: nombre, selector de formulario, nombre de formulario HubSpot, listado de pares campo WP → propiedad HS.
- Config almacenada en `wp_options['mgmit_mapper_config']`.

##### Archivos añadidos
- `wp-content/plugins/hubspot-mapper/hubspot-mapper.php` — main plugin file (clase `MGMIT_HubSpot_Mapper`)
- `wp-content/plugins/hubspot-mapper/includes/class-mgmit-mapper-admin-ui.php` — admin UI con AJAX
- `wp-content/plugins/hubspot-mapper/assets/js/hubspot_map.js` — lógica frontend de mapeo
- `wp-content/plugins/hubspot-mapper/assets/js/admin-mapper.js` — JS del panel admin

---

## 2026-05-21

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Feat - `basel-child`: Nuevo módulo `hubspot-swpm-stripe-bridge`

Integración directa SWPM + Stripe → webhook externo HubSpot Bridge, independiente del plugin WooCommerce.

##### Flujos implementados

**Flujo 1 — Pago de membresía completado:**
Hook `swpm_payment_ipn_processed` → `POST /webhook/woocommerce/de` con topic `order.created`.

**Flujo 2 — Desactivación de membresía:**
Hook `swpm_account_status_updated` → `POST /webhook/woocommerce/de` con topic `customer.updated`.
Se activa tanto por cancelación via Stripe como por desactivación manual desde el panel SWPM (transiciones a `inactive`, `expired` o `cancelled`).

##### Estructura del payload enviado

Ambos flujos envían estructura compatible con WooCommerce webhook handler:
- `id` + `external_id` — WP user ID del cliente
- `billing{}` — email, first_name, last_name (formato estándar que espera el handler)
- `meta_data[]` — membership_level_id, membership_level_name, source=swpm y campos específicos de cada flujo
- Flujo 1 añade: `status`, `total`, `currency`, `transaction_id`, `payment_method`, `number`

##### Firma HMAC-SHA256

Todas las peticiones incluyen `X-WC-Webhook-Signature: base64(HMAC-SHA256(body, secret))` compatible con la verificación del servidor webhook. El secret se configura desde el admin de WordPress.

##### Archivos añadidos
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/loader.php` — orquestador del módulo
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/class-webhook-sender.php` — envío HTTP con firma, logging y manejo de errores
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/flow-payment.php` — listener Flujo 1
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/flow-deactivation.php` — listener Flujo 2
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/admin-settings.php` — página **Ajustes → SWPM Bridge** para configurar el secret y consultar el log de errores

##### Archivos modificados
- `wp-content/themes/basel-child/functions.php` — añadido `require_once` del nuevo loader (línea 1063)

##### Configuración requerida
Ir a **Ajustes → SWPM Bridge** en el admin de WordPress e introducir el valor de `WOO_WEBHOOK_SECRET_DE` del servidor webhook.

##### Log de errores
Errores HTTP y de conexión se guardan en `wp-content/uploads/mgmit-bridge-log.txt`. El visor del log está integrado en la misma página de ajustes.

---

## 2026-05-13

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Fix - `basel-child`: Bloqueo de webhook `order.updated` en confirmación de pago Stripe

##### Problema resuelto
Al crear un pedido via checkout + Stripe, WooCommerce dispara `woocommerce_update_order` inmediatamente después de `woocommerce_new_order` (dentro de los mismos segundos) porque la confirmación de pago provoca una segunda llamada al método `update()` del data store. Esto generaba una entrada duplicada en HubSpot: una de creación (`order.created`) y una de modificación (`order.updated`) para el mismo pedido nuevo.

##### Causa raíz
Flujo interno de WooCommerce durante checkout + Stripe:
1. Pedido creado como `checkout-draft` → `create()` no dispara `woocommerce_new_order`
2. Stripe confirma → estado pasa a `processing` → `update()` detecta transición desde draft → dispara `woocommerce_new_order` → webhook `order.created` (ID 1) ✓
3. WooCommerce escribe `transaction_id` y `date_paid` → nueva llamada a `update()` → dispara `woocommerce_update_order` → webhook `order.updated` (ID 2) ✗ duplicado

##### Solución implementada
Filtro `woocommerce_webhook_should_deliver` que bloquea la entrega del webhook `order.updated` durante los primeros `MGMIT_WEBHOOK_NEW_ORDER_TTL` segundos (120s por defecto) tras la creación del pedido.

##### Archivos añadidos
- `wp-content/themes/basel-child/inc/woocommerce/loader.php` — orquestador del módulo WooCommerce (mismo patrón que `inc/hubspot-sync/loader.php`)
- `wp-content/themes/basel-child/inc/woocommerce/webhook-filters.php` — implementación del filtro

##### Archivos modificados
- `wp-content/themes/basel-child/functions.php` — añadido `require_once` del nuevo loader (línea 1062)

##### Configuración opcional
Para ajustar la ventana de bloqueo sin tocar el código, añadir en `wp-config.php`:
```php
define( 'MGMIT_WEBHOOK_NEW_ORDER_TTL', 120 ); // segundos
```

---

## 2026-05-11

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Seguridad - Plugin `mgmit-hubspot-bridge`: Token de HubSpot movido a `wp-config.php`

##### Problema resuelto
El token de la App Privada de HubSpot (`MGMIT_HS_ACCESS_TOKEN`) estaba hardcodeado en `mgmit-hubspot-bridge.php`, lo que exponía el secreto en el repositorio git.

##### Solución implementada
- El plugin ya **no contiene el token** en su código fuente.
- Las constantes se leen desde `wp-config.php`, que está excluido del repositorio vía `.gitignore`.

##### Configuración requerida en `wp-config.php`
Añadir las siguientes líneas **antes** de la línea `/* That's all, stop editing! */`:

```php
define('MGMIT_HS_ACCESS_TOKEN_SECRET', 'pat-eu1-XXXX...');  // Token de la App Privada HubSpot
define('MGMIT_HS_PORTAL_ID_SECRET',    '144893874');          // Portal ID de HubSpot
```

> **Importante:** Sin estas constantes en `wp-config.php`, el envío a HubSpot fallará silenciosamente (el token llegará vacío). Esta configuración debe añadirse manualmente en cada entorno (local, staging, producción).

##### Historial git
- Se reescribió el historial de la rama `develop` para eliminar el token de commits anteriores mediante `git filter-branch`.
- El force-push a `origin/develop` fue necesario como consecuencia de la reescritura.

---

## 2026-05-07

### [Unreleased]
*Origen: docs/CHANGELOG.md*

#### Añadido - `basel-child/functions.php`: Control de acceso al checkout por tipo de producto

- **Meta `_mgmit_outside_fachkreis`**: nuevo checkbox en la pantalla de edición de producto WooCommerce. Marca productos que pueden comprarse sin necesidad de registro en el Fachkreisbereich.
- **`mgmit_cart_all_outside_fachkreis()`**: helper que devuelve `true` solo si todos los productos del carrito tienen el meta activo.
- **`mgmit_block_checkout_for_guests()`**: hook en `template_redirect` (prioridad 5) que redirige al carrito si el usuario no está logueado y hay al menos un producto que requiere registro.
- **`mgmit_guest_checkout_notice()`**: reemplaza el botón "Ir a la caja" por un aviso explicativo en alemán cuando el usuario no está logueado y el carrito contiene productos del Fachkreisbereich.

#### Añadido - `basel-child/functions.php`: Guardar precio rebajado en line items de pedido

- **`mgmit_save_sale_price_on_order_item()`**: hook en `woocommerce_checkout_create_order_line_item` que persiste `_regular_price`, `_sale_price`, `_is_on_sale`, `_sale_price_dates_from` y `_sale_price_dates_to` en el meta del line item en el momento de la compra.

---

## 2026-05-06

### [1.5.0]
*Origen: docs/CHANGELOG.md*

#### Rediseño completo - Plugin `mgmit-hubspot-bridge`: Envío a HubSpot tras validación server-side

##### Problema resuelto
El plugin anterior enviaba datos a HubSpot capturando el `submit` del formulario en el navegador (antes de que SWPM validara los campos). Esto producía registros incompletos o inválidos en HubSpot cuando el usuario enviaba el formulario con errores.

##### Solución implementada

**Bloqueo de captura automática de HubSpot (`hubspot_map.js`)**
- El script inyecta el atributo `data-hs-do-not-collect="true"` en los formularios mapeados al cargarse la página. Esto usa la API oficial de HubSpot para impedir que el plugin `leadin` capture el formulario automáticamente.
- Adicionalmente, inyecta un campo oculto `mgmit_hs_form_id` con el selector CSS del formulario. Este campo viaja en el POST y permite al servidor identificar qué regla de mapeo aplicar.

**Envío a HubSpot Forms API v3 (server-side)**
- Eliminado el sistema de renombrado de campos JS y cualquier lógica de captura en el navegador.
- El envío a HubSpot ocurre ahora en PHP, **únicamente tras validación exitosa**, enganchado a los hooks de SWPM Form Builder:
  - `swpm_front_end_registration_complete_fb` → registro de nuevo miembro
  - `swpm_front_end_profile_edited_fb` → actualización de perfil
- Endpoint utilizado: `POST https://api.hsforms.com/submissions/v3/integration/secure/submit/{portalId}/{formGuid}` con autenticación mediante App Privada de HubSpot (token Bearer). El scope requerido en la App Privada es `forms` (`form-submissions-write`).

**Campo `formGuid` en la Admin UI**
- Renombrado el campo "Nombre del formulario HubSpot" por "Form GUID HubSpot" en el panel de administración. El GUID es el identificador UUID del formulario en HubSpot (formato: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`), visible en la URL del editor de formularios de HubSpot.
- Retrocompatibilidad mantenida: entradas existentes con `hubspotFormName` siguen funcionando.

##### Compatibilidad con otros plugins de formularios

> **Nota importante:** En su estado actual, el envío a HubSpot solo se activa con **SWPM Form Builder** (hooks `swpm_front_end_registration_complete_fb` y `swpm_front_end_profile_edited_fb`). El bloqueo de captura automática (JS) funciona con cualquier formulario HTML independientemente del plugin.
>
> Para soportar otros plugins de formularios basta con añadir su hook de éxito correspondiente en `init_hooks()` apuntando al mismo método `handle_swpm_submission()`:
>
> | Plugin | Hook de éxito |
> |---|---|
> | Contact Form 7 | `wpcf7_mail_sent` |
> | Gravity Forms | `gform_after_submission` |
> | WooCommerce | `woocommerce_checkout_order_processed` |
> | WordPress nativo | `user_register` |

##### Limpieza
- Eliminados: carpeta `tests/`, `node_modules/`, `jest.config.js`, `package.json`, `package-lock.json` y backup PHP.
- Eliminados todos los logs de debug temporales y transients de diagnóstico.
- Versión del plugin actualizada de `1.4.0` a `1.5.0`.

---

## 2026-04-27

### [1.4.1]
*Origen: docs/CHANGELOG.md*

#### Investigado - Plugin mgmit-hubspot-bridge: HS_CONFIG vacío en producción externa
- **Síntoma:** Al instalar el plugin en un WordPress externo (INT2020), `window.HS_CONFIG` llegaba como `[]` al navegador pese a tener la configuración correctamente guardada en BD.
- **Diagnóstico:**
  - La BD contenía el dato correcto (`mgmit_hubspot_config` serializado con una entrada de formulario).
  - Los logs de debug confirmaron que PHP leía y pasaba el array correctamente hasta `wp_localize_script`.
  - El problema era **triple capa de caché** (WordPress + servidor + navegador) que servía una versión antigua del HTML con `HS_CONFIG = []`.
- **Resolución:** Limpiar las 3 capas de caché. El código del plugin es correcto y no requirió modificaciones.
- **Añadido temporal (revertir):** Líneas `error_log` de debug en `enqueue_scripts()` → eliminar antes de deploy final.

#### Documentado - Comportamiento esperado del plugin en páginas sin formulario
- El script `hubspot_map.js` se encola en **todas las páginas** sin restricción. En páginas donde el formulario no existe, el JS imprime `[HS Mapper] Formulario no encontrado` en consola. Esto es **comportamiento normal e inofensivo**.

---

## 2026-04-24

### [1.4.0]
*Origen: docs/CHANGELOG.md*

#### Corregido - Onboarding HubSpot: Lógica de bloqueo rediseñada
- **`functions.php` → `mgmit_enforce_hs_form_completion()`:** Reescritura completa de la lógica de bloqueo.
  - **Antes:** Bloqueaba a cualquier usuario (logueado o no) con sesión/cookie activa, causando bloqueos incorrectos.
  - **Ahora:** Bloquea **solo** durante el flujo de registro nuevo, verificando triple condición:
    1. Usuario **no logueado** (SWPM no hace auto-login hasta `?hs_finish=1`)
    2. Sesión PHP con `mgmit_hs_user_id` válido (creada en el hook `user_register`)
    3. Metadato `mgmit_hs_details_pending = '1'` en BD para ese user_id
  - **Login posterior:** No bloquea, navegación normal de WordPress.
  - **Flujo completo:** Registro → bloqueo en `/registrierungsdetails/?enforced=1` → rellena HubSpot → `?hs_finish=1` → auto-login SWPM → sesión limpiada → libre.

#### Corregido - HubSpot Mapper: Nuevo enfoque de mapeo de campos (v7.0)
- **`hubspot_map.js`:** Rediseño completo del sistema de mapeo de campos.
  - **Antes (v6.0):** Creaba campos ocultos duplicados (`data-hs-bridge`) y sincronizaba valores en tiempo real. Los campos ocultos podían no recibir los valores correctamente.
  - **Ahora (v7.0):** En el momento del `submit`, renombra directamente los `name` de los campos originales (`swpm-526` → `firstname`, etc.) para que HubSpot los lea nativamente.
  - **Compatibilidad SWPM:** No interfiere con el procesamiento de SWPM porque el cambio de `name` ocurre en el evento `submit`, después de que SWPM ha validado el formulario internamente.
  - Logs detallados en consola para diagnóstico: `[HS Mapper] ✓ Campo renombrado: swpm-526 → firstname`.

#### Modificado - Métodos de pago WooCommerce para membresías
- **`membership-woo-bridge.php` → `swpm_filter_payment_gateways()`:** Ajuste de lógica de gateways.
  - Carrito con **solo membresía** → **todos** los métodos de pago disponibles (incluyendo SEPA).
  - Carrito con **otros productos** → SEPA (`stripe_sepa_debit`) **no disponible**.
  - Carrito vacío → SEPA no disponible.

---

## 2026-04-22

### [1.3.1]
*Origen: docs/CHANGELOG.md*

#### Añadido - Sincronización de Formulario HubSpot → WordPress
- **Módulo `/inc/hubspot-sync/`:** Sistema standalone de captura y sincronización de datos de formularios HubSpot embebidos.
  - **`form-capture.js`:** Listener de eventos `postMessage` para capturar `onFormSubmit` del iframe HubSpot. Mapea campos HubSpot (`firstname`, `lastname`, `phone`, `zip`, `city`, `job_title_de`, `country_of_the_contact`, `address`) a estructura WordPress interna.
  - **`handler.php`:** Clase `MGMIT_HubSpot_Sync` con REST endpoint POST `/wp-json/mgmit/v1/sync-hubspot-data`. Sincroniza datos a:
    - `wpgr_usermeta`: Con prefijo `billing_` (WooCommerce): `billing_address_1`, `billing_address_2`, `billing_postcode`, `billing_city`, `billing_phone`, `billing_country`, más `first_name`, `last_name`, `job_title_de`.
    - `wpgr_swpm_members_tbl`: Campos sin prefijo (SWPM) para usuarios registrados.
    - Validación de email y búsqueda de usuario por email antes de sincronizar.
  - **`loader.php`:** Enqueue condicional del script JS (solo en página ID 21568 `/registrierungsdetails/`) y carga del handler REST.
- **Funcionalidad de división de direcciones:** Campo `address` se divide automáticamente por primera coma en `address` (principal) + `address2` (secundaria).
- **Logging de operaciones:** Cada paso registra en `write_log()` para diagnóstico.
- **Integración en `functions.php`:** Require de `loader.php` en línea 1019 del tema hijo.

#### Notas Técnicas
- No utiliza llamadas server-side a la API de HubSpot. Captura y sincroniza datos 100% vía `postMessage` API.
- Compatibilidad PHP 7.4 (sin constructor promotion, union types, named arguments).
- Funcionalidad específica del tema hijo, no integrada en `mgmit-hubspot-bridge` (por regla de negocio).
- Endpoint requiere email válido en la solicitud; no sincroniza si el usuario no existe.

---

## 2026-04-21

### [1.3.0]
*Origen: docs/CHANGELOG.md*

#### Añadido - Fase 4: Visual Mapper UI
- **Vista Lista de Mapeos:** Tabla estilo WordPress nativa (`.wp-list-table`) que muestra todos los bridges configurados con columnas: Nombre, Selector CSS, Nombre HubSpot, Nº Campos y Acciones (Editar/Eliminar).
- **Editor Visual de Mapeo:** Formulario add/edit con campos tipados para Nombre descriptivo, Selector CSS del formulario, Nombre en HubSpot y tabla dinámica de campos (`[name WP]` → `[propiedad HubSpot]`) con botones `+ Añadir Campo` y `✕ Eliminar fila`.
- **AJAX `mgmit_save_mapping`:** Acción para crear o actualizar un mapeo individual con nonce y validación de permisos.
- **AJAX `mgmit_delete_mapping`:** Acción para eliminar un mapeo por UUID con nonce y confirmación en cliente.
- **`assets/js/admin-mapper.js`:** Módulo JS para la UI del panel: filas dinámicas, serialización de campos, llamadas AJAX y feedback visual inline.
- **IDs UUID en entradas:** Cada mapeo nuevo recibe un UUID v4 generado en PHP (compatible PHP 7.4). Las entradas legacy sin `id` siguen funcionando sin cambios.
- **Retrocompatibilidad total:** El handler `mgmit_save_hubspot_config` (Fase 3) se mantiene. El frontend `hubspot_map.js` no requiere ninguna modificación.

#### Corregido
- **Mapeos no visibles al abrir el panel:** La opción `mgmit_hubspot_config` nunca se inicializaba en la DB porque `activate_plugin()` sólo corría en la primera activación del plugin. Añadido método privado `get_config()` en `MGMIT_Admin_UI` que detecta si la opción es `false` o no es array y, en ese caso, llama a `activate_plugin()` para persistir los defaults antes de renderizar.

### [1.2.2]
*Origen: docs/CHANGELOG.md*

#### Corregido
- **Auto-login tras onboarding HubSpot:** La causa raíz era que `mgmit_clear_hs_pending_status()` usaba `SwpmMemberAuth` (clase inexistente) en lugar de `SwpmAuth`. Ahora se llama a `SwpmAuth::get_instance()->login_to_swpm_using_wp_user($user)`, que autentica al usuario simultáneamente en WordPress (`wp_set_auth_cookie`) y en SWPM (cookie propia), permitiendo el acceso a páginas protegidas por SWPM.
- **URL de redirección post-onboarding:** Corregido `home_url('/fachkreisbereich/')` → `home_url('/fachkreisbereich-mitglied/')` en el hook `init`.
- **Logging de debug:** Añadidos mensajes `write_log()` en el bloque `hs_finish`/`hs_test` para diagnóstico del flujo (visibles con `WP_DEBUG=true`).

---

## 2026-04-20

### [1.2.1]
*Origen: docs/CHANGELOG.md*

#### Añadido
- **Mapeo de Formulario de Perfil:** Configuración de puente para el formulario `#profile-form-level-13-16` hacia HubSpot (`MeGeMIT_DE_Profile_Update`).
- **Optimización de Action Scheduler:** Ajuste de intervalos de ejecución (120s) y lotes concurrentes (1) para mejorar el rendimiento en el entorno ServBay.
- **Desarrollo de Plugin "MeGeMIT HubSpot Bridge":** Creación de la arquitectura base (Fase 1 a 3) para centralizar la conexión a HubSpot fuera del tema hijo, respetando la estructura de reglas de negocio al mantener la lógica estricta de 'Onboarding' intacta dentro del tema padre/hijo.

#### Modificado
- **Estandarización de Scripts:** Renombre de funciones y handles de `swpm-hubspot-mapper` a `mgmit-hubspot-mapper` para consistencia con el prefijo del proyecto.
- **Limpieza de Base de Datos:** Eliminación de registros duplicados en la tabla de plugins activos (causados por redundancia en el proceso de migración/duplicación).
- **Documentación Técnica:** Refinado de `HUBSPOT_INTEGRATION.md` para reflejar la nueva estructura modular de scripts.

---

## 2026-04-17

### [1.2.0]
*Origen: docs/CHANGELOG.md*

#### Añadido
- **Módulo Independiente de Onboarding:** Creación de `inc/onboarding-enforcement.js` para separar totalmente la lógica de seguridad del mapeo de datos.
- **Seguridad de Grado Bancario (Sesiones PHP):** Migración de la persistencia de tokens de Cookies a Sesiones nativas de PHP (`$_SESSION`), garantizando cero colisiones de datos entre usuarios concurrentes.

#### Modificado
- **`basel-child/functions.php`:** 
    - Unificación de la lógica de captura en el hook universal `user_register`.
    - Refactorización de la lógica de carga de scripts para soportar carga modular.
- **`basel-child/inc/hubspot_map.js`:** Simplificación radical; ahora actúa como un componente puro de sincronización de campos (Agnóstico a la lógica de negocio).

#### Corregido
- **Fallo de Auto-login Local:** Resuelto el problema de "cookies invisibles" en PHP mediante el uso de almacenamiento persistente en el servidor (Sessions).
- **Redundancia de Bloqueo:** Corregido fallo donde el bloqueo se desactivaba prematuramente bajo ciertas condiciones de redirección.

---

## 2026-04-16

### [1.1.0]
*Origen: docs/CHANGELOG.md*

#### Añadido
- **Blindaje de Onboarding (Triple Seguro):** Implementación de una arquitectura de bloqueo redundante para forzar el registro de datos profesionales en HubSpot.
    - **Capa JS:** Intercepción en el navegador al enviar el formulario inicial.
    - **Capa Previa (PHP):** Detección temprana en el hook `init` para emitir cookies de seguimiento.
    - **Capa Persistente (DB):** Vinculación de metadatos de usuario (User Meta) para bloqueos en sesiones de login futuras.
- **UI de Aviso Premium:** Pop-up con efecto de desenfoque y diseño moderno para informar al usuario sobre la obligatoriedad del perfil profesional.
- **Documentación Técnica:** Actualización de `HUBSPOT_INTEGRATION.md` con el nuevo flujo de trabajo.

#### Modificado
- **`basel-child/functions.php`:** 
    - Reemplazo de hooks de SWPM por `swpm_front_end_registration_complete_user_data` para mayor fiabilidad.
    - Centralización de la lógica de limpieza de bloqueo mediante el parámetro `hs_finish=1`.
- **`basel-child/inc/hubspot_map.js`:** Integración de lógica de cookies y manejo de avisos por parámetos de URL.

#### Corregido
- **Error de Sintaxis PHP:** Eliminado cierre de llave huérfano que causaba un "Parse Error" en el tema hijo.
- **Detección de Sesión:** Corregido el problema de "limbo de sesión" tras el registro inicial mediante el uso de cookies persistentes de corta duración.

---

## Sin fecha (inicio del proyecto)

### [1.0.0]
*Origen: docs/CHANGELOG.md*

- Análisis inicial del entorno ServBay (macOS).
- Configuración de acceso a la base de datos MySQL (megemit_database).
- Creación del archivo `agent.md` para seguimiento técnico.

### hubspot-mapper 1.0.0
*Origen: hubspot-mapper/CHANGELOG.md*

- Mapeo frontend de campos SWPM → propiedades HubSpot mediante renombrado en el submit.
- Sin llamadas server-side — integración 100% frontend.
- Admin UI para gestionar múltiples mapeos (selector de formulario, nombre HubSpot, pares de campos).
