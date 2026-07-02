# Changelog - Proyecto Megemit

Todas las modificaciones técnicas realizadas en el entorno de WordPress y la integración con HubSpot.

## [basel-child swpm-woo-sync] — 2026-07-02

### Added — Tema `basel-child` (`functions.php`)

Permite que clientes WooCommerce (email existente en `wp_users` pero sin cuenta SWPM) se registren en Simple WP Membership vía el Form Builder de SWPM.

- **Validación de email duplicado en SWPM** (`mgmit_swpm_validate_email_not_in_swpm`) — filter en `swpm_validate_registration_form_submission`. Corrige el bug del form builder (`AND user_name != ''`) haciendo una query directa a `wpgr_swpm_members_tbl` sin esa condición. Bloquea el registro si el email ya existe en SWPM; permite el registro si solo existe en `wp_users`.
- **Sincronización del WP user existente** (`mgmit_swpm_sync_wp_user_on_registration`) — action en `swpm_front_end_registration_complete_fb` (form builder) y `swpm_front_end_registration_complete_user_data` (formulario estándar). Tras el registro SWPM exitoso actualiza en el WP user: `user_login` y `user_nicename` (via `$wpdb->update` directo + `clean_user_cache`), `user_pass`, `first_name`, `last_name` y `display_name`. No modifica administradores.

---

## [basel-child pdf-invoice] — 2026-07-01

### Changed — Tema `basel-child` (`woocommerce/pdf/MeGeMit/invoice.php`)

- **Tratamiento del cliente** — se muestra el campo `_anrede` (Dr., Dra., etc.) delante del nombre del comprador en el bloque de dirección de factura. Se recupera via `$this->order->get_meta('_anrede')`.
- **Tipo de impuesto en totales** — se añade una fila "Impuesto X %" al final de la tabla de totales usando `$this->order->get_tax_totals()` y `WC_Tax::get_rate_percent_value( $tax->rate_id )`. Necesario porque con `woocommerce_tax_display_cart = incl` WooCommerce no genera filas de impuesto separadas en `get_order_item_totals()`.
- **Eliminación de nota de impuesto en el total** — la fila `order_total` ahora usa `wc_price( $this->order->get_total() )` en lugar de `get_formatted_order_total()`, evitando que aparezca el texto `(includes MwSt. XX,XX €)` entre paréntesis.
- **Instrucción de pago sin importe de impuesto** — la línea "Bitte überweisen…" usa también `wc_price()` directo, eliminando la misma nota parentética.

---

## [basel-child webhook-filters] — 2026-06-25

### Changed — Tema `basel-child` (`inc/woocommerce/`)

- **URL de factura PDF en el payload del webhook de pedidos** — nuevo filtro `woocommerce_webhook_payload` (`webhook-filters.php`) que añade la clave `invoice_pdf_url` al payload enviado al webhook de pedidos (`vpsbridge.dimint.com/webhook/woocommerce/de`). Solo aplica a `resource = order`.
- **Endpoint propio de factura** (`invoice-endpoint.php`, nuevo) — sirve el PDF vía `admin-ajax.php?action=mgmit_invoice_pdf&order_id=X&token=Y`. El `token` es un HMAC-SHA256 firmado con la constante `INVOICE_SECRET` (nuevo `inc/woocommerce/config.php`, junto con `WEBHOOK_NEW_ORDER_TTL`), independiente de sesión/login. Se descartó usar el sistema nativo de `access_key` del plugin "PDF Invoices & Packing Slips" porque en modo `logged_in` (ajuste activo en el sitio) el enlace solo es válido para la misma sesión que lo generó — no sirve para un payload reenviado a terceros (HubSpot vía vpsbridge).

---

## [swpm-expiry-reminders 1.1.0] — 2026-06-18

### Changed — Plugin `swpm-expiry-reminders`

- **Cálculo de días normalizado a medianoche** — se comparan `$today_midnight` y `$expiry_midnight` (usando `strtotime( date('Y-m-d', ...) . ' 00:00:00' )`) en lugar de timestamps brutos, eliminando desfases de ±1 día especialmente en niveles de tipo Annual Fixed Date.
- **Scope por nivel de membresía** — nueva opción `swpm_expiry_reminder_scope` (`all` / `specific`). Cuando se selecciona `specific`, la opción `swpm_expiry_reminder_level_ids` almacena los IDs de los niveles a procesar. En el cron se aplica intersección entre los niveles con expiración configurada y los seleccionados.
- **Admin UI actualizada** — sección "Apply To" con radio buttons y lista de checkboxes de los niveles SWPM (obtenidos de `swpm_membership_tbl`), con toggle JS vanilla sin dependencia de jQuery.

---

## [swpm-expiry-reminders 1.0.0] — 2026-06-17

### Added — Nuevo plugin `swpm-expiry-reminders`

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

## [wc-moodle-sync 1.3.0] — 2026-06-25

### Added — Plugin `wc-moodle-sync`

- **`config.php`** — nuevo archivo de configuración local del plugin (fuera de `wp-config.php`): centraliza `WCMS_MOODLE_API_URL`, `WCMS_MOODLE_TOKEN`, `WCMS_MOODLE_LOGIN_URL`, `WCMS_COMPLETION_SECRET`, `WCMS_COUPON_SHOP_URL`, y las nuevas `WCMS_WELCOME_EMAIL_CC`, `WCMS_SEND_CERTIFICATE`, `WCMS_SEND_COUPON`.
- **CC en emails al alumno** — los tres emails (`send_welcome`, `send_certificate`, `send_course_completion`) añaden cabeceras `Cc:` por cada dirección válida en `WCMS_WELCOME_EMAIL_CC`.
- **Toggles de envío** — `WCMS_SEND_CERTIFICATE` y `WCMS_SEND_COUPON` permiten desactivar la generación/envío de certificado PDF y cupón descuento sin tocar código.

### Changed — Plugin `wc-moodle-sync`

- **Email de bienvenida/matriculación traducido al alemán** — `build_html()` en `class-wcms-mailer.php` estaba en español; ahora coherente en idioma con los otros dos emails del plugin (certificado y cupón, que ya estaban en alemán).
- Documentado en `README.md` que el endpoint `course-complete` está operativo en WordPress pero pendiente de implementar el lado Moodle (event observer / plugin de webhooks) que lo invoque automáticamente.

---

## [wc-moodle-sync 1.2.0] — 2026-06-10

### Added — Plugin `wc-moodle-sync`

- **Webhook `course_completed`** — nuevo endpoint REST `POST /wp-json/wc-moodle-sync/v1/course-complete`. Cuando Moodle notifica la finalización de un curso, genera un cupón WooCommerce único (`MeGeMIT-XXXXXXXX`) al 100% de descuento, de un solo uso, restringido por email del usuario y por la categoría de producto `MDL-Coupon`. Envía email de felicitación en alemán con el código.
- **Webhook `exam_passed`** — el mismo endpoint acepta `event_type: exam_passed`: genera un certificado PDF dinámico con el nombre del alumno y la fecha en alemán superpuestos sobre la plantilla PNG del plugin; envía el PDF adjunto por email.
- **`class-wcms-completion-handler.php`** — clase singleton que registra el endpoint REST, autentica via `Authorization: Bearer`, despacha al flujo correcto según `event_type` y deduplica llamadas repetidas mediante `user_meta`.
- **`class-wcms-certificate.php`** — generador de certificados con GD. Carga `certificate-template.png`, superpone nombre (BrushScript.ttf, 46 pt, color `#2D2D4B`, centrado al 46 % del alto) y fecha en alemán (TimesItalic.ttf, 26 pt, color `#1D5FA5`, centrado al 72 % del alto), ensambla PDF 1.4 sin dependencias externas.
- **`assets/certificate-template.png`** — plantilla de certificado limpia (1684×1192 px, sin texto preexistente).
- **`assets/fonts/BrushScript.ttf`** y **`assets/fonts/TimesItalic.ttf`** — fuentes TTF para el certificado.
- **`class-wcms-mailer.php`** — añadidos `send_course_completion()` y `send_certificate()` con sus respectivas plantillas HTML en alemán.
- **Constantes nuevas** en `wc-moodle-sync.php`: `WCMS_COMPLETION_SECRET` y `WCMS_COUPON_SHOP_URL`.

### Changed — Plugin `wc-moodle-sync`

- Descripción del cupón sigue el patrón `Akademie-Coupon für Nombre Apellido`.
- El código del cupón mantiene el formato `MeGeMIT-XXXXXXXX` (8 caracteres aleatorios en mayúsculas).
- Certificados almacenados en `wp-content/uploads/wcms-certificates/`.

---

## [Unreleased] — 2026-06-03

### Fixed
- **`email-membership.php` / `email-templates/body.php`** — imagen de cabecera del email de membresía ahora usa URL pública (`get_stylesheet_directory_uri`) en lugar de ruta de sistema, corrigiendo la visualización en clientes de correo.

### Added
- **`inc/hubspot-swpm-stripe-bridge/`** — nuevo módulo que centraliza la integración entre HubSpot, SWPM, WooCommerce y Stripe: webhook sender, flujos de pago/desactivación, emails de membresía con plantillas.
- **`membership-woo-bridge.php`** — campo meta `_swpm_membership_level` en el admin de producto WooCommerce; guarda nivel previo al crear la orden para poder revertirlo.
- **`onboarding-enforcement.js`** — función `blockNavigation()`: bloquea todos los enlaces y elementos JS del header (carrito, buscador, sidebar) para usuarios con registro pendiente.

### Changed
- **`membership-woo-bridge.php`** — `swpm_update_level_after_payment` ahora usa la API nativa de SWPM (`SwpmMemberUtils`, `SwpmTransactions`) disparando hooks y email de confirmación; fallback a actualización directa en BD si SWPM no está disponible.
- **`membership-woo-bridge.php`** — nueva función `swpm_revert_level_on_order_status_change`: revierte el nivel de membresía al anterior cuando el pago se cancela, falla o reembolsa.
- **`functions.php`** — URLs absolutas del header convertidas a rutas relativas; carga `hubspot-swpm-stripe-bridge/loader.php`.

### Removed
- Plantilla PDF `MeGeMit2` (obsoleta): `invoice.php`, `style.css`, `html-document-wrapper.php`, `template-functions.php`, `facebook_icon.png`.

---

## [Unreleased] - 2026-05-26

### Feat — Nuevo plugin `hubspot-mapper` (frontend-only HubSpot field mapper)

Plugin independiente de mapeo 100% frontend de formularios SWPM y Ultimate Member → HubSpot, sin llamadas server-side a la API. Coexiste con `mgmit-hubspot-bridge` (v1.5.0, server-side) sin conflictos.

#### Características
- Mapea campos de formularios WP a propiedades HubSpot renombrando los campos en el submit (JS).
- Soporte para formularios SWPM (selector `#form-id`) y Ultimate Member (selector `.um-{id}`).
- Soporte para campos radio/checkbox: solo se renombran los inputs `:checked`.
- Inyecta `hs_context` con cookie hutk, pageName y pageUrl en cada submit.
- Admin UI con CRUD de mapeos: nombre, selector de formulario, nombre de formulario HubSpot, listado de pares campo WP → propiedad HS.
- Config almacenada en `wp_options['mgmit_mapper_config']`.

#### Archivos añadidos
- `wp-content/plugins/hubspot-mapper/hubspot-mapper.php` — main plugin file (clase `MGMIT_HubSpot_Mapper`)
- `wp-content/plugins/hubspot-mapper/includes/class-mgmit-mapper-admin-ui.php` — admin UI con AJAX
- `wp-content/plugins/hubspot-mapper/assets/js/hubspot_map.js` — lógica frontend de mapeo
- `wp-content/plugins/hubspot-mapper/assets/js/admin-mapper.js` — JS del panel admin

---

## [Unreleased] - 2026-05-21

### Feat - `basel-child`: Nuevo módulo `hubspot-swpm-stripe-bridge`

Integración directa SWPM + Stripe → webhook externo HubSpot Bridge, independiente del plugin WooCommerce.

#### Flujos implementados

**Flujo 1 — Pago de membresía completado:**
Hook `swpm_payment_ipn_processed` → `POST /webhook/woocommerce/de` con topic `order.created`.

**Flujo 2 — Desactivación de membresía:**
Hook `swpm_account_status_updated` → `POST /webhook/woocommerce/de` con topic `customer.updated`.
Se activa tanto por cancelación via Stripe como por desactivación manual desde el panel SWPM (transiciones a `inactive`, `expired` o `cancelled`).

#### Estructura del payload enviado

Ambos flujos envían estructura compatible con WooCommerce webhook handler:
- `id` + `external_id` — WP user ID del cliente
- `billing{}` — email, first_name, last_name (formato estándar que espera el handler)
- `meta_data[]` — membership_level_id, membership_level_name, source=swpm y campos específicos de cada flujo
- Flujo 1 añade: `status`, `total`, `currency`, `transaction_id`, `payment_method`, `number`

#### Firma HMAC-SHA256

Todas las peticiones incluyen `X-WC-Webhook-Signature: base64(HMAC-SHA256(body, secret))` compatible con la verificación del servidor webhook. El secret se configura desde el admin de WordPress.

#### Archivos añadidos
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/loader.php` — orquestador del módulo
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/class-webhook-sender.php` — envío HTTP con firma, logging y manejo de errores
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/flow-payment.php` — listener Flujo 1
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/flow-deactivation.php` — listener Flujo 2
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/admin-settings.php` — página **Ajustes → SWPM Bridge** para configurar el secret y consultar el log de errores

#### Archivos modificados
- `wp-content/themes/basel-child/functions.php` — añadido `require_once` del nuevo loader (línea 1063)

#### Configuración requerida
Ir a **Ajustes → SWPM Bridge** en el admin de WordPress e introducir el valor de `WOO_WEBHOOK_SECRET_DE` del servidor webhook.

#### Log de errores
Errores HTTP y de conexión se guardan en `wp-content/uploads/mgmit-bridge-log.txt`. El visor del log está integrado en la misma página de ajustes.

---

## [Unreleased] - 2026-05-13

### Fix - `basel-child`: Bloqueo de webhook `order.updated` en confirmación de pago Stripe

#### Problema resuelto
Al crear un pedido via checkout + Stripe, WooCommerce dispara `woocommerce_update_order` inmediatamente después de `woocommerce_new_order` (dentro de los mismos segundos) porque la confirmación de pago provoca una segunda llamada al método `update()` del data store. Esto generaba una entrada duplicada en HubSpot: una de creación (`order.created`) y una de modificación (`order.updated`) para el mismo pedido nuevo.

#### Causa raíz
Flujo interno de WooCommerce durante checkout + Stripe:
1. Pedido creado como `checkout-draft` → `create()` no dispara `woocommerce_new_order`
2. Stripe confirma → estado pasa a `processing` → `update()` detecta transición desde draft → dispara `woocommerce_new_order` → webhook `order.created` (ID 1) ✓
3. WooCommerce escribe `transaction_id` y `date_paid` → nueva llamada a `update()` → dispara `woocommerce_update_order` → webhook `order.updated` (ID 2) ✗ duplicado

#### Solución implementada
Filtro `woocommerce_webhook_should_deliver` que bloquea la entrega del webhook `order.updated` durante los primeros `MGMIT_WEBHOOK_NEW_ORDER_TTL` segundos (120s por defecto) tras la creación del pedido.

#### Archivos añadidos
- `wp-content/themes/basel-child/inc/woocommerce/loader.php` — orquestador del módulo WooCommerce (mismo patrón que `inc/hubspot-sync/loader.php`)
- `wp-content/themes/basel-child/inc/woocommerce/webhook-filters.php` — implementación del filtro

#### Archivos modificados
- `wp-content/themes/basel-child/functions.php` — añadido `require_once` del nuevo loader (línea 1062)

#### Configuración opcional
Para ajustar la ventana de bloqueo sin tocar el código, añadir en `wp-config.php`:
```php
define( 'MGMIT_WEBHOOK_NEW_ORDER_TTL', 120 ); // segundos
```

---

## [Unreleased] - 2026-05-11

### Seguridad - Plugin `mgmit-hubspot-bridge`: Token de HubSpot movido a `wp-config.php`

#### Problema resuelto
El token de la App Privada de HubSpot (`MGMIT_HS_ACCESS_TOKEN`) estaba hardcodeado en `mgmit-hubspot-bridge.php`, lo que exponía el secreto en el repositorio git.

#### Solución implementada
- El plugin ya **no contiene el token** en su código fuente.
- Las constantes se leen desde `wp-config.php`, que está excluido del repositorio vía `.gitignore`.

#### Configuración requerida en `wp-config.php`
Añadir las siguientes líneas **antes** de la línea `/* That's all, stop editing! */`:

```php
define('MGMIT_HS_ACCESS_TOKEN_SECRET', 'pat-eu1-XXXX...');  // Token de la App Privada HubSpot
define('MGMIT_HS_PORTAL_ID_SECRET',    '144893874');          // Portal ID de HubSpot
```

> **Importante:** Sin estas constantes en `wp-config.php`, el envío a HubSpot fallará silenciosamente (el token llegará vacío). Esta configuración debe añadirse manualmente en cada entorno (local, staging, producción).

#### Historial git
- Se reescribió el historial de la rama `develop` para eliminar el token de commits anteriores mediante `git filter-branch`.
- El force-push a `origin/develop` fue necesario como consecuencia de la reescritura.

---

## [Unreleased] - 2026-05-07

### Añadido - `basel-child/functions.php`: Control de acceso al checkout por tipo de producto

- **Meta `_mgmit_outside_fachkreis`**: nuevo checkbox en la pantalla de edición de producto WooCommerce. Marca productos que pueden comprarse sin necesidad de registro en el Fachkreisbereich.
- **`mgmit_cart_all_outside_fachkreis()`**: helper que devuelve `true` solo si todos los productos del carrito tienen el meta activo.
- **`mgmit_block_checkout_for_guests()`**: hook en `template_redirect` (prioridad 5) que redirige al carrito si el usuario no está logueado y hay al menos un producto que requiere registro.
- **`mgmit_guest_checkout_notice()`**: reemplaza el botón "Ir a la caja" por un aviso explicativo en alemán cuando el usuario no está logueado y el carrito contiene productos del Fachkreisbereich.

### Añadido - `basel-child/functions.php`: Guardar precio rebajado en line items de pedido

- **`mgmit_save_sale_price_on_order_item()`**: hook en `woocommerce_checkout_create_order_line_item` que persiste `_regular_price`, `_sale_price`, `_is_on_sale`, `_sale_price_dates_from` y `_sale_price_dates_to` en el meta del line item en el momento de la compra.

---

## [1.5.0] - 2026-05-06

### Rediseño completo - Plugin `mgmit-hubspot-bridge`: Envío a HubSpot tras validación server-side

#### Problema resuelto
El plugin anterior enviaba datos a HubSpot capturando el `submit` del formulario en el navegador (antes de que SWPM validara los campos). Esto producía registros incompletos o inválidos en HubSpot cuando el usuario enviaba el formulario con errores.

#### Solución implementada

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

#### Compatibilidad con otros plugins de formularios

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

#### Limpieza
- Eliminados: carpeta `tests/`, `node_modules/`, `jest.config.js`, `package.json`, `package-lock.json` y backup PHP.
- Eliminados todos los logs de debug temporales y transients de diagnóstico.
- Versión del plugin actualizada de `1.4.0` a `1.5.0`.

---

## [1.4.1] - 2026-04-27

### Investigado - Plugin mgmit-hubspot-bridge: HS_CONFIG vacío en producción externa
- **Síntoma:** Al instalar el plugin en un WordPress externo (INT2020), `window.HS_CONFIG` llegaba como `[]` al navegador pese a tener la configuración correctamente guardada en BD.
- **Diagnóstico:**
  - La BD contenía el dato correcto (`mgmit_hubspot_config` serializado con una entrada de formulario).
  - Los logs de debug confirmaron que PHP leía y pasaba el array correctamente hasta `wp_localize_script`.
  - El problema era **triple capa de caché** (WordPress + servidor + navegador) que servía una versión antigua del HTML con `HS_CONFIG = []`.
- **Resolución:** Limpiar las 3 capas de caché. El código del plugin es correcto y no requirió modificaciones.
- **Añadido temporal (revertir):** Líneas `error_log` de debug en `enqueue_scripts()` → eliminar antes de deploy final.

### Documentado - Comportamiento esperado del plugin en páginas sin formulario
- El script `hubspot_map.js` se encola en **todas las páginas** sin restricción. En páginas donde el formulario no existe, el JS imprime `[HS Mapper] Formulario no encontrado` en consola. Esto es **comportamiento normal e inofensivo**.

---

## [1.4.0] - 2026-04-24

### Corregido - Onboarding HubSpot: Lógica de bloqueo rediseñada
- **`functions.php` → `mgmit_enforce_hs_form_completion()`:** Reescritura completa de la lógica de bloqueo.
  - **Antes:** Bloqueaba a cualquier usuario (logueado o no) con sesión/cookie activa, causando bloqueos incorrectos.
  - **Ahora:** Bloquea **solo** durante el flujo de registro nuevo, verificando triple condición:
    1. Usuario **no logueado** (SWPM no hace auto-login hasta `?hs_finish=1`)
    2. Sesión PHP con `mgmit_hs_user_id` válido (creada en el hook `user_register`)
    3. Metadato `mgmit_hs_details_pending = '1'` en BD para ese user_id
  - **Login posterior:** No bloquea, navegación normal de WordPress.
  - **Flujo completo:** Registro → bloqueo en `/registrierungsdetails/?enforced=1` → rellena HubSpot → `?hs_finish=1` → auto-login SWPM → sesión limpiada → libre.

### Corregido - HubSpot Mapper: Nuevo enfoque de mapeo de campos (v7.0)
- **`hubspot_map.js`:** Rediseño completo del sistema de mapeo de campos.
  - **Antes (v6.0):** Creaba campos ocultos duplicados (`data-hs-bridge`) y sincronizaba valores en tiempo real. Los campos ocultos podían no recibir los valores correctamente.
  - **Ahora (v7.0):** En el momento del `submit`, renombra directamente los `name` de los campos originales (`swpm-526` → `firstname`, etc.) para que HubSpot los lea nativamente.
  - **Compatibilidad SWPM:** No interfiere con el procesamiento de SWPM porque el cambio de `name` ocurre en el evento `submit`, después de que SWPM ha validado el formulario internamente.
  - Logs detallados en consola para diagnóstico: `[HS Mapper] ✓ Campo renombrado: swpm-526 → firstname`.

### Modificado - Métodos de pago WooCommerce para membresías
- **`membership-woo-bridge.php` → `swpm_filter_payment_gateways()`:** Ajuste de lógica de gateways.
  - Carrito con **solo membresía** → **todos** los métodos de pago disponibles (incluyendo SEPA).
  - Carrito con **otros productos** → SEPA (`stripe_sepa_debit`) **no disponible**.
  - Carrito vacío → SEPA no disponible.

---

## [1.3.1] - 2026-04-22

### Añadido - Sincronización de Formulario HubSpot → WordPress
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

### Notas Técnicas
- No utiliza llamadas server-side a la API de HubSpot. Captura y sincroniza datos 100% vía `postMessage` API.
- Compatibilidad PHP 7.4 (sin constructor promotion, union types, named arguments).
- Funcionalidad específica del tema hijo, no integrada en `mgmit-hubspot-bridge` (por regla de negocio).
- Endpoint requiere email válido en la solicitud; no sincroniza si el usuario no existe.

---

## [1.3.0] - 2026-04-21

### Añadido - Fase 4: Visual Mapper UI
- **Vista Lista de Mapeos:** Tabla estilo WordPress nativa (`.wp-list-table`) que muestra todos los bridges configurados con columnas: Nombre, Selector CSS, Nombre HubSpot, Nº Campos y Acciones (Editar/Eliminar).
- **Editor Visual de Mapeo:** Formulario add/edit con campos tipados para Nombre descriptivo, Selector CSS del formulario, Nombre en HubSpot y tabla dinámica de campos (`[name WP]` → `[propiedad HubSpot]`) con botones `+ Añadir Campo` y `✕ Eliminar fila`.
- **AJAX `mgmit_save_mapping`:** Acción para crear o actualizar un mapeo individual con nonce y validación de permisos.
- **AJAX `mgmit_delete_mapping`:** Acción para eliminar un mapeo por UUID con nonce y confirmación en cliente.
- **`assets/js/admin-mapper.js`:** Módulo JS para la UI del panel: filas dinámicas, serialización de campos, llamadas AJAX y feedback visual inline.
- **IDs UUID en entradas:** Cada mapeo nuevo recibe un UUID v4 generado en PHP (compatible PHP 7.4). Las entradas legacy sin `id` siguen funcionando sin cambios.
- **Retrocompatibilidad total:** El handler `mgmit_save_hubspot_config` (Fase 3) se mantiene. El frontend `hubspot_map.js` no requiere ninguna modificación.

### Corregido
- **Mapeos no visibles al abrir el panel:** La opción `mgmit_hubspot_config` nunca se inicializaba en la DB porque `activate_plugin()` sólo corría en la primera activación del plugin. Añadido método privado `get_config()` en `MGMIT_Admin_UI` que detecta si la opción es `false` o no es array y, en ese caso, llama a `activate_plugin()` para persistir los defaults antes de renderizar.

---

## [1.2.2] - 2026-04-21

### Corregido
- **Auto-login tras onboarding HubSpot:** La causa raíz era que `mgmit_clear_hs_pending_status()` usaba `SwpmMemberAuth` (clase inexistente) en lugar de `SwpmAuth`. Ahora se llama a `SwpmAuth::get_instance()->login_to_swpm_using_wp_user($user)`, que autentica al usuario simultáneamente en WordPress (`wp_set_auth_cookie`) y en SWPM (cookie propia), permitiendo el acceso a páginas protegidas por SWPM.
- **URL de redirección post-onboarding:** Corregido `home_url('/fachkreisbereich/')` → `home_url('/fachkreisbereich-mitglied/')` en el hook `init`.
- **Logging de debug:** Añadidos mensajes `write_log()` en el bloque `hs_finish`/`hs_test` para diagnóstico del flujo (visibles con `WP_DEBUG=true`).

---

## [1.2.1] - 2026-04-20

### Añadido
- **Mapeo de Formulario de Perfil:** Configuración de puente para el formulario `#profile-form-level-13-16` hacia HubSpot (`MeGeMIT_DE_Profile_Update`).
- **Optimización de Action Scheduler:** Ajuste de intervalos de ejecución (120s) y lotes concurrentes (1) para mejorar el rendimiento en el entorno ServBay.
- **Desarrollo de Plugin "MeGeMIT HubSpot Bridge":** Creación de la arquitectura base (Fase 1 a 3) para centralizar la conexión a HubSpot fuera del tema hijo, respetando la estructura de reglas de negocio al mantener la lógica estricta de 'Onboarding' intacta dentro del tema padre/hijo.

### Modificado
- **Estandarización de Scripts:** Renombre de funciones y handles de `swpm-hubspot-mapper` a `mgmit-hubspot-mapper` para consistencia con el prefijo del proyecto.
- **Limpieza de Base de Datos:** Eliminación de registros duplicados en la tabla de plugins activos (causados por redundancia en el proceso de migración/duplicación).
- **Documentación Técnica:** Refinado de `HUBSPOT_INTEGRATION.md` para reflejar la nueva estructura modular de scripts.

---

## [1.2.0] - 2026-04-17

### Añadido
- **Módulo Independiente de Onboarding:** Creación de `inc/onboarding-enforcement.js` para separar totalmente la lógica de seguridad del mapeo de datos.
- **Seguridad de Grado Bancario (Sesiones PHP):** Migración de la persistencia de tokens de Cookies a Sesiones nativas de PHP (`$_SESSION`), garantizando cero colisiones de datos entre usuarios concurrentes.

### Modificado
- **`basel-child/functions.php`:** 
    - Unificación de la lógica de captura en el hook universal `user_register`.
    - Refactorización de la lógica de carga de scripts para soportar carga modular.
- **`basel-child/inc/hubspot_map.js`:** Simplificación radical; ahora actúa como un componente puro de sincronización de campos (Agnóstico a la lógica de negocio).

### Corregido
- **Fallo de Auto-login Local:** Resuelto el problema de "cookies invisibles" en PHP mediante el uso de almacenamiento persistente en el servidor (Sessions).
- **Redundancia de Bloqueo:** Corregido fallo donde el bloqueo se desactivaba prematuramente bajo ciertas condiciones de redirección.

---

## [1.1.0] - 2026-04-16

### Añadido
- **Blindaje de Onboarding (Triple Seguro):** Implementación de una arquitectura de bloqueo redundante para forzar el registro de datos profesionales en HubSpot.
    - **Capa JS:** Intercepción en el navegador al enviar el formulario inicial.
    - **Capa Previa (PHP):** Detección temprana en el hook `init` para emitir cookies de seguimiento.
    - **Capa Persistente (DB):** Vinculación de metadatos de usuario (User Meta) para bloqueos en sesiones de login futuras.
- **UI de Aviso Premium:** Pop-up con efecto de desenfoque y diseño moderno para informar al usuario sobre la obligatoriedad del perfil profesional.
- **Documentación Técnica:** Actualización de `HUBSPOT_INTEGRATION.md` con el nuevo flujo de trabajo.

### Modificado
- **`basel-child/functions.php`:** 
    - Reemplazo de hooks de SWPM por `swpm_front_end_registration_complete_user_data` para mayor fiabilidad.
    - Centralización de la lógica de limpieza de bloqueo mediante el parámetro `hs_finish=1`.
- **`basel-child/inc/hubspot_map.js`:** Integración de lógica de cookies y manejo de avisos por parámetos de URL.

### Corregido
- **Error de Sintaxis PHP:** Eliminado cierre de llave huérfano que causaba un "Parse Error" en el tema hijo.
- **Detección de Sesión:** Corregido el problema de "limbo de sesión" tras el registro inicial mediante el uso de cookies persistentes de corta duración.

---

## [1.0.0] - Inicio del Proyecto
- Análisis inicial del entorno ServBay (macOS).
- Configuración de acceso a la base de datos MySQL (megemit_database).
- Creación del archivo `agent.md` para seguimiento técnico.

---
*Fin del registro actual.*
