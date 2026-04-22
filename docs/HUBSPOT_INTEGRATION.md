# WordPress to HubSpot Form Bridge (Custom Integration)

Este sistema permite conectar formularios nativos de WordPress (o de plugins como Simple WP Membership, Contact Form 7, etc.) con HubSpot de forma limpia, evitando la duplicidad de campos y asegurando que las propiedades se mapeen correctamente.

## Componentes del Sistema

1.  **`inc/hubspot_map.js`**: El motor lógico en JavaScript.
2.  **`functions.php`**: La base de datos de configuración y el cargador del script.

---

## Cómo Funciona (Lógica Técnica)

### 1. Shadow Fields (Campos Sombra)
Muchos plugins de WordPress generan nombres de campos complejos o dinámicos (ej: `swpm-472`). HubSpot no reconoce estos campos automáticamente. 
*   El script crea dinámicamente campos ocultos (`<input type="hidden">`) con nombres estándar de HubSpot (`firstname`, `email`, etc.).
*   Sincroniza en tiempo real el valor del campo original con el campo oculto.

### 2. Atributo `data-hs-ignore`
Para evitar que HubSpot recoja tanto el campo original como el campo sombra (lo que causaría datos duplicados o errores de mapeo), el script marca los campos originales con `data-hs-ignore="true"`. HubSpot ignorará estos campos por completo.

### 3. Mutación de IDs de Formulario
HubSpot utiliza el `id` del formulario para identificarlo. Si el plugin de formularios cambia el ID o usa uno genérico, el script inyecta el ID deseado antes del envío para asegurar que HubSpot clasifique el lead bajo el formulario correcto.

---

## Cómo Añadir un Nuevo Formulario

Para integrar un nuevo formulario, solo debes añadir un nuevo bloque al array `$config` dentro de la función `swpm_hubspot_mapper_script` en tu `functions.php`:

```php
array(
    'formId' => '#id-del-formulario',      // ID o clase CSS del formulario
    'hubspotFormName' => 'Nombre_Interno', // Cómo se verá en HubSpot
    'mapping' => array(
        'nombre_input_original' => 'propiedad_hubspot', // Ej: 'swpm-123' => 'email'
        'otro_input'            => 'firstname'
    )
)
```

---

## Ventajas para el Desarrollador

*   **Sin dependencias:** Solo requiere jQuery (estándar en WordPress).
*   **Agnóstico:** Funciona con cualquier plugin de formularios.
*   **SEO & Rendimiento:** El script es ligero y se carga de forma asíncrona al final de la página.
*   **Privacidad de Datos:** Al usar `data-hs-ignore`, tienes control total sobre qué datos se envían a HubSpot y cuáles se quedan solo en WordPress.

---

## Flujo de Onboarding Obligatorio (Registro en 2 Pasos)

Para mejorar la captación de datos, se ha implementado un sistema de "paso obligatorio" para los miembros del **Fachkreisbereich (Nivel 2)**.

### 1. Bloqueo de Navegación
Cuando un usuario completa el primer registro (SWPM), se le asigna el metadato `mgmit_hs_details_pending = 1`. 
*   **Consecuencia:** Mientras este valor sea `1`, cualquier intento de navegar fuera de `/registrierungsdetails/` resultará en una redirección forzosa de vuelta a dicha página.
*   **Aviso UX:** La redirección incluye el parámetro `?enforced=1`, que activa un Pop-up informativo en el frontend.

### 2. Desbloqueo (Finalización)
El usuario solo recupera la libertad de navegación cuando el servidor detecta el parámetro `hs_finish=1`.

**Configuración Requerida en HubSpot:**
El formulario de HubSpot en el paso 2 DEBE estar configurado para "Redirigir a otra página" tras el envío a la siguiente URL:
`https://dominio.com/registrierungsdetails/?hs_finish=1`

### 3. Pop-up de Aviso
El Pop-up está definido en `inc/onboarding-enforcement.js` y utiliza estilos en línea para evitar dependencias de CSS externas, asegurando que se vea correctamente incluso si hay problemas con las hojas de estilo del tema.

---

---

## Sincronización Automática de Formulario HubSpot Embebido

### Propósito
Capturar datos de un formulario HubSpot embebido en un iframe (página `/registrierungsdetails/`) y sincronizarlos automáticamente a las tablas de WordPress (`wpgr_usermeta`, `wpgr_swpm_members_tbl`) sin necesidad de HubSpot Workflows (feature de pago).

### Arquitectura del Módulo `inc/hubspot-sync/`

#### 1. **`form-capture.js`** — Captura de eventos
- Escucha eventos `window.message` emitidos por el iframe de HubSpot.
- Filtra por tipo `event.data.type === 'hsFormCallback'` y evento `onFormSubmit`.
- Extrae los campos del array `event.data.data` y los mapea a nombres internos WordPress.
- **Mapeo de campos HubSpot → Interno:**
  ```javascript
  'firstname'                 → 'first_name'
  'lastname'                  → 'last_name'
  'phone'                     → 'phone'
  'zip'                       → 'zip'
  'city'                      → 'city'
  'job_title_de'              → 'job_title'
  'country_of_the_contact'    → 'country'
  'address'                   → 'address' (+ 'address2' si contiene coma)
  ```
- **División de dirección:** Si el campo `address` contiene una coma, se divide en:
  - `address` = primera parte (ej: "Calle Principal 123")
  - `address2` = resto (ej: "Apt 4, Piso 2")
- Realiza un POST a `/wp-json/mgmit/v1/sync-hubspot-data` con estructura:
  ```json
  {
    "email": "usuario@example.com",
    "data": { campos mapeados }
  }
  ```

#### 2. **`handler.php`** — REST Endpoint
- Clase `MGMIT_HubSpot_Sync` con método `register_sync_endpoint()` que crea el route POST.
- **Validación:** Email requerido y válido. Búsqueda de usuario en `wpgr_users` por email.
- **Sincronización a `wpgr_usermeta`** (con prefijo `billing_` para WooCommerce):
  - `first_name`, `last_name` → `first_name`, `last_name`
  - `address` → `billing_address_1`
  - `address2` → `billing_address_2`
  - `zip` → `billing_postcode`
  - `city` → `billing_city`
  - `country` → `select2-billing_country-container` (selector específico)
  - `phone` → `billing_phone`
  - `job_title` → `job_title_de`
- **Sincronización a `wpgr_swpm_members_tbl`** (solo si existe registro SWPM para el usuario):
  - Campos sin prefijo `billing_`: `first_name`, `last_name`, `phone`, `address`, `address2`, `zip`, `city`, `country`
- **Respuesta exitosa:** `{"success":true,"message":"Datos sincronizados correctamente","user_id":123,"email":"..."}`
- **Respuesta fallida:** `{"success":false,"message":"...","user_id":123 o "email":...}` con código HTTP apropiado.
- Todas las operaciones quedan registradas en `write_log()`.

#### 3. **`loader.php`** — Gestión de carga
- Enqueue condicional de `form-capture.js` solo en página ID `21568` (`/registrierungsdetails/`).
- Incluye el handler PHP que registra el endpoint REST.
- Se carga automáticamente desde `functions.php` (línea 1019).

### Flujo Completo de Sincronización

```
1. Usuario rellena formulario HubSpot en /registrierungsdetails/?legacy=1
   ↓
2. Evento onFormSubmit dispara window.postMessage desde el iframe
   ↓
3. form-capture.js escucha el evento y extrae los campos
   ↓
4. Mapeo de campos HubSpot → nombres internos WordPress
   ↓
5. POST a /wp-json/mgmit/v1/sync-hubspot-data con email + datos
   ↓
6. handler.php valida el email y busca el usuario
   ↓
7. Si existe: actualiza wpgr_usermeta (con prefijo billing_) + wpgr_swpm_members_tbl
   ↓
8. Respuesta JSON al navegador (éxito o error)
   ↓
9. Console.log con status [HS Sync] ✅ o ❌
```

### Consideraciones Técnicas

- **Sin llamadas server-side a HubSpot:** El endpoint solo sincroniza datos internos; no toca la API de HubSpot.
- **Cross-origin compatible:** Usa `postMessage` API estándar (funciona con iframes de diferentes orígenes).
- **PHP 7.4 compatible:** Sin constructor promotion, union types, o sintaxis moderna.
- **No bloquea el envío a HubSpot:** El sync es asincrónico; HubSpot recibe el formulario normalmente.
- **Logging granular:** Cada paso registra con prefijo `[MGMIT_HS_SYNC]` para debugging.

### Debugging de Sincronización

1. **En consola del navegador (F12):**
   - Busca mensajes `[HS Sync]` que indican el flujo de captura.
   - Si ves `[HS Sync] ✅ Sincronización exitosa`, los datos se enviaron correctamente.
   - Si ves `❌ Error: ...`, revisa el JSON de respuesta del servidor.

2. **En el servidor (si WP_DEBUG=true):**
   - Revisa `wp-content/debug.log` para mensajes `[MGMIT_HS_SYNC]`.
   - Confirma que el usuario existe en `wpgr_users` (por email).
   - Verifica que `wpgr_swpm_members_tbl` tiene un registro para ese user_id.

3. **En la base de datos:**
   - `SELECT * FROM wpgr_usermeta WHERE user_id = X AND meta_key LIKE 'billing_%'` para verificar actualización.
   - `SELECT * FROM wpgr_swpm_members_tbl WHERE user_id = X` para verificar registro SWPM.

---

## Guía de Debugging General

Si el mapeo no parece funcionar:
1.  Abre la consola del navegador (F12).
2.  Busca mensajes con el prefijo `[HS Mapper]` (para mapeo de campos) o `[HS Sync]` (para sincronización).
3.  Si ves `Shadow field creado: [nombre]`, significa que el script ha encontrado el formulario y el campo con éxito.
4.  Si no ves nada, verifica que el `formId` en `functions.php` coincida exactamente con el ID que tiene la etiqueta `<form>` en el HTML.

---
*Desarrollado para MeGeMIT - Integración de Formularios de Membresía.*
