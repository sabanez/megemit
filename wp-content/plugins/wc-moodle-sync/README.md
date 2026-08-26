# WooCommerce & Moodle Sync

Versión: 1.3.0 — PHP 7.4+, WooCommerce 5+

Matricula automáticamente a compradores de WooCommerce en cursos Moodle de forma asíncrona. Genera cupones de descuento al finalizar un curso y certificados PDF al aprobar un examen.

## Configuración

### 1. Moodle

1. Activar **Servicios Web > REST** en Administración del sitio.
2. Crear un **Servicio externo** y añadir las funciones:
   - `core_user_get_users_by_field`
   - `core_user_create_users`
   - `enrol_manual_enrol_users`
3. Generar un **Token** para un usuario administrador.
4. Asegurarse de que los cursos tienen habilitado **Matriculación Manual**.

### 2. WordPress (`wc-moodle-sync/config.php`)

Toda la configuración del plugin vive en `config.php`, en la raíz del propio plugin (no en `wp-config.php`):

```php
define( 'WCMS_MOODLE_API_URL', 'https://tu-moodle.com/webservice/rest/server.php' );
define( 'WCMS_MOODLE_TOKEN',   'tu_token_aqui' );
define( 'WCMS_MOODLE_LOGIN_URL', 'https://onlineakademie.megemit.org/login/?lang=de' );

// Webhook de finalización (generar un token aleatorio seguro)
define( 'WCMS_COMPLETION_SECRET', 'un_token_aleatorio_seguro' );

// URL del Prämienshop para el botón del email de felicitación (opcional)
define( 'WCMS_COUPON_SHOP_URL', 'https://megemit.org/produktkategorie/mdlc/' );

// Direcciones en copia (CC) para los emails al alumno (vacío = sin copia)
define( 'WCMS_WELCOME_EMAIL_CC', 'persona1@megemit.org,persona2@megemit.org' );

// Activar/desactivar certificado PDF (exam_passed) y cupón (course_completed)
define( 'WCMS_SEND_CERTIFICATE', true );
define( 'WCMS_SEND_COUPON', true );
```

### 3. Productos en WooCommerce

En cada producto tipo curso, añadir el campo personalizado:

| Campo | Valor |
|---|---|
| `moodle_cohort_ids` | `3` (cohort único) o `3,7` (pack, varios cohorts) |

El valor es el **ID del cohort en Moodle**, no de un curso. La matriculación ya no se hace
directamente a un curso: el usuario se añade al cohort indicado (`core_cohort_add_cohort_members`),
y es el propio Moodle el que matricula automáticamente a todos los cursos/módulos que tengan
configurado ese cohort como método de matriculación ("Sincronización de cohortes"). Esto evita
tener que enumerar cada módulo del curso desde WordPress.

Requisito en Moodle: cada curso/módulo que deba desbloquearse para este producto necesita el
método de matriculación **Sincronización de cohortes** activado y apuntando al cohort
correspondiente (Administración del curso > Usuarios > Métodos de matriculación).

Activar **Opciones de pantalla > Campos personalizados** si no son visibles.

### 4. Categoría de producto para cupones

Crear en **WooCommerce > Productos > Categorías** una categoría con nombre exacto `MDL-Coupon`. Los cupones generados quedarán restringidos a productos de esa categoría.

---

## Webhook de Moodle

> ⚠️ **PENDIENTE DE PROGRAMAR (lado Moodle):** todo lo descrito en esta sección está implementado y operativo en WordPress — el endpoint REST, la generación de cupón/certificado y el envío de los emails funcionan ya si se les hace una llamada válida (probado manualmente con `curl`, ver más abajo). **Lo que falta es la pieza en Moodle** que detecte los eventos `course_completed` y `exam_passed` (aprobar el quiz) y haga el POST automático hacia este endpoint con el `WCMS_COMPLETION_SECRET` correspondiente. Sin esa pieza, nadie llama al endpoint y no se envía ningún cupón ni certificado de forma automática. Posibles vías a evaluar cuando se aborde: plugin de Moodle de tipo "Webhooks/Event Observer" de terceros, o un observer de eventos personalizado (`course_completed`, `mod_quiz\event\attempt_submitted`) que dispare el `curl`/HTTP POST.

El plugin expone un endpoint REST que Moodle debe llamar en dos eventos:

### Endpoint

```
POST /wp-json/wc-moodle-sync/v1/course-complete
Authorization: Bearer <WCMS_COMPLETION_SECRET>
Content-Type: application/json
```

### Evento: curso completado → cupón descuento

```json
{
  "event_type": "course_completed",
  "moodle_user_id": 123,
  "moodle_course_id": 456
}
```

- Genera un cupón WooCommerce único (`MeGeMIT-XXXXXXXX`) al 100%, de un solo uso.
- Restricción por email del usuario y por categoría `MDL-Coupon`.
- Descripción del cupón: `Akademie-Coupon für Nombre Apellido`.
- Envía email de felicitación con el código al alumno.
- Deduplicación: si el cupón ya fue emitido para ese usuario/curso, devuelve `already_issued` sin crear duplicado.

### Evento: examen aprobado → certificado PDF

```json
{
  "event_type": "exam_passed",
  "moodle_user_id": 123,
  "moodle_course_id": 456
}
```

- Genera un certificado PDF con el nombre del alumno y la fecha en alemán, superpuestos sobre la plantilla PNG del plugin.
- Envía email en alemán con el PDF adjunto.
- El certificado se guarda en `wp-content/uploads/wcms-certificates/`.
- Deduplicación: si el certificado ya fue emitido para ese usuario/curso, devuelve `already_issued`.

---

## Cómo verificar

1. Crear un pedido de prueba con un producto que tenga `moodle_cohort_ids`.
2. Ir a **WooCommerce > Estado > Acciones Programadas**.
3. Filtrar por hook `wcms_process_order`.
4. Si falla, revisar **WooCommerce > Estado > Registros** — los errores llevan el prefijo `[wc-moodle-sync]`.

Para probar el webhook manualmente:

```bash
curl -X POST https://tu-sitio.com/wp-json/wc-moodle-sync/v1/course-complete \
  -H "Authorization: Bearer TU_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"event_type":"course_completed","moodle_user_id":123,"moodle_course_id":456}'
```

---

## Estructura

```
wc-moodle-sync/
├── wc-moodle-sync.php                  Bootstrap, carga config.php, singleton principal
├── config.php                          Configuración local: Moodle, secretos, CC emails, toggles
├── includes/
│   ├── class-wcms-scheduler.php        Encola y ejecuta la tarea asíncrona de matriculación
│   ├── class-wcms-moodle-api.php       Wrapper HTTP para la API REST de Moodle
│   ├── class-wcms-mailer.php           Emails: bienvenida, cupón y certificado
│   ├── class-wcms-checkout-guard.php   Valida/crea usuario Moodle antes del pago
│   ├── class-wcms-completion-handler.php  Webhook receptor: cupón y certificado
│   └── class-wcms-certificate.php      Generador de certificado PDF con GD
├── assets/
│   ├── certificate-template.png        Plantilla PNG limpia (1684×1192 px)
│   └── fonts/
│       ├── BrushScript.ttf             Fuente cursiva para el nombre
│       └── TimesItalic.ttf             Fuente itálica para la fecha
└── README.md
```

## Notas

- El rol asignado en Moodle es `5` (Estudiante). Modificar `roleid` en `class-wcms-moodle-api.php` si difiere.
- Las credenciales y el token del webhook **no se almacenan en BD** — van en `config.php`, dentro de la carpeta del plugin.
- Los certificados PDF se generan sin dependencias externas (sin Ghostscript ni ImageMagick).
- Compatible con PHP 7.4 y superior.
