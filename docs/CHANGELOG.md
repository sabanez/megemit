# Changelog - Proyecto Megemit

Todas las modificaciones técnicas realizadas en el entorno de WordPress y la integración con HubSpot.

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
