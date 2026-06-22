# WooCommerce & Moodle Sync

Versión: 1.2.0 — PHP 7.4+, WooCommerce 5+

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

### 2. WordPress (`wp-config.php`)

```php
define( 'WCMS_MOODLE_API_URL', 'https://tu-moodle.com/webservice/rest/server.php' );
define( 'WCMS_MOODLE_TOKEN',   'tu_token_aqui' );
define( 'WCMS_MOODLE_LOGIN_URL', 'https://onlineakademie.megemit.org/login/?lang=de' );

// Webhook de finalización (generar un token aleatorio seguro)
define( 'WCMS_COMPLETION_SECRET', 'un_token_aleatorio_seguro' );

// URL del Prämienshop para el botón del email de felicitación (opcional)
define( 'WCMS_COUPON_SHOP_URL', 'https://megemit.org/produktkategorie/mdlc/' );
```

### 3. Productos en WooCommerce

En cada producto tipo curso, añadir el campo personalizado:

| Campo | Valor |
|---|---|
| `moodle_course_ids` | `14` (curso único) o `14,25,31` (pack) |

Activar **Opciones de pantalla > Campos personalizados** si no son visibles.

### 4. Categoría de producto para cupones

Crear en **WooCommerce > Productos > Categorías** una categoría con nombre exacto `MDL-Coupon`. Los cupones generados quedarán restringidos a productos de esa categoría.

---

## Webhook de Moodle

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

1. Crear un pedido de prueba con un producto que tenga `moodle_course_ids`.
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
├── wc-moodle-sync.php                  Bootstrap, constantes, singleton principal
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
- Las credenciales y el token del webhook **no se almacenan en BD** — van en `wp-config.php`.
- Los certificados PDF se generan sin dependencias externas (sin Ghostscript ni ImageMagick).
- Compatible con PHP 7.4 y superior.
