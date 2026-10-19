# Changelog

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

## 2026-10-11

### Cambiado
- `email-templates/vorschreibung-pdf.php`: todo el texto "Vorschreibung" sustituido por "Rechnung" (asunto, cuerpo, aviso legal). Añadidos `title` y `job_title_de` al nombre del destinatario (antes solo salía `salutation_full` + nombre/apellido, sin título ni profesión).
- `email-membership.php` / `email-renewal-confirmed.php`: pasan ahora `title` y `job_title` a la plantilla `vorschreibung-pdf`.

## 2026-10-10

### Cambiado
- `email-templates/mitgliedschaft-pdf.php`: título del documento en negrita con el azul del logo (`#195CA8`, muestreado de la cabecera), en vez de negro. El bold de Arial/Helvetica se veía fino en Dompdf y `text-shadow` no tiene efecto en ese motor (verificado); se cambió la fuente del título a DejaVu Sans, que sí renderiza un bold visiblemente más grueso.
- `email-templates/mitgliedschaft-pdf.php`: eliminada la filigrana (marca de agua) antes del título y la imagen incorrecta antes de "Hagleitner Doris" (reutilizaba por error la imagen de cabecera como si fuera su firma). Limpiado el CSS y la variable `$logo_watermark_path`, ya sin uso.
- `woocommerce/pdf/MeGeMit/header_invoice.jpg`: sustituida por una versión de mayor calidad (1641×214px, extraída de `Information Therapeuten-Beratung.pdf`, antes 594×68px) — afecta a todos los documentos que la usan (factura, nota de abono, confirmación de membresía, cuerpos de email).

## 2026-10-09

### Cambiado
- `email-membership.php` (email de Alta): el adjunto de la factura pasa a llamarse `rechnung_<txn_id>.pdf` (ID de transacción de Stripe) en vez de `Vorschreibung_<timestamp>.pdf`.

## 2026-10-08

### Cambiado
- Firma de los 4 emails de membresía: nueva imagen (`email-templates/img/doris_signature.png`) y pie de contacto (Postadresse, Adresse, redes sociales), sustituyendo al anterior (sin teléfono/fax/email/NIF).
- `class-salutation-resolver.php`: el saludo (`greeting`) ahora incluye `job_title_de` delante del apellido (detrás de `title`), igual que la cabecera de la factura — antes solo mostraba `title`.

## 2026-10-07

### Eliminado
- `flow-payment.php` / `flow-deactivation.php`: eliminado el envío manual de webhooks a HubSpot (`MGMIT_Webhook_Sender::send()`, eventos `order.created`/`customer.updated`) en alta y baja de membresía. Causaba datos incorrectos (IDs de Stripe cruzados, nombre/apellido mal separado por un bug de SWPM con caracteres UTF-8 como ü/ö/ä). La notificación a HubSpot pasa a gestionarla Stripe directamente mediante su propio webhook.
- `mgmit_bridge_payment_completed()` y `mgmit_bridge_send_deactivation_webhook()` eliminadas (ya no hacen falta); los hooks de SWPM llaman ahora directamente a las funciones de envío de email.

## 2026-10-06

### Cambiado
- `email-membership.php` / `email-renewal-confirmed.php`: `$salutation_label` ya no usa `'Herr/Frau'` como texto por defecto; si HubSpot no devuelve `courtesy_de`, queda vacío.
- `email-templates/mitgliedschaft-pdf.php`: el campo "Anrede" (Herr/Frau) solo se muestra si hay dato; si no, solo aparece el nombre del socio, sin etiqueta.

## 2026-10-05

### Añadido
- `class-salutation-resolver.php`: nuevo campo `courtesy_label` ('Herr'/'Frau'/'') en el resultado de `mgmit_bridge_build_salutation()`, para usarlo sin tener que derivarlo del saludo completo.
- Filtro de datos basura: si `title` o `job_title_de` de HubSpot contienen literalmente "Herr"/"Frau" (error de captura), se descartan antes de usarse.

### Cambiado
- `woocommerce/pdf/MeGeMit/invoice.php` y `credit-note.php`: la cabecera (nombre del cliente) ahora se construye con tratamiento + título + profesión resueltos vía HubSpot (`courtesy_de`, `title`, `job_title_de`), sustituyendo al antiguo meta `_anrede` del checkout. Aplica a todas las facturas y notas de abono de WooCommerce.
- `email-membership.php` y `email-renewal-confirmed.php`: simplificada la obtención de `salutation_full` usando el nuevo `courtesy_label` en vez de derivarlo por `strpos()` del saludo completo.

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

## 2026-09-30

### Cambiado
- Membresías Stripe: revertida la duración a "1 año desde la fecha de alta" (aniversario nativo de Stripe), en lugar del año natural (31/12) implementado previamente.
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/`: las versiones con lógica de año natural (`flow-payment.php`, `flow-deactivation.php`, `flow-renewal.php`, `flow-webhook-renewal.php`) se movieron a `legacy-ano-natural/` (sin cargar). En su lugar se restauraron las versiones previas de `flow-payment.php` y `flow-deactivation.php`, que dejan intacta la suscripción de aniversario creada por Stripe.
- `loader.php`: eliminados los `require_once` de `flow-renewal.php` y `flow-webhook-renewal.php`.
- `wp-content/themes/basel-child/functions.php`: eliminado el banner de renovación (`mgmit_renewal_banner_output`, hook `wp_footer`) — dependía de la meta `mgmit_membership_valid_until`/`mgmit_show_renewal_button` que ya no se genera. Los avisos de renovación pasan a gestionarse vía los recordatorios nativos de SWPM y Stripe.

## 2026-09-11

### Añadido
- `wp-content/mu-plugins/bacs-admin-only.php` — oculta el método de pago "Transferencia bancaria directa" (BACS) en el checkout del frontend mediante el filtro `woocommerce_available_payment_gateways`, manteniéndolo disponible para pedidos creados manualmente desde `wp-admin`. El gateway permanece "Activado" en `WooCommerce > Ajustes > Pagos`.
