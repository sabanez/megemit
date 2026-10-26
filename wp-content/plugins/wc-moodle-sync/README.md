# WooCommerce & Moodle Sync

Versión: 1.8.0 — PHP 7.4+, WooCommerce 5+

Matricula automáticamente a compradores de WooCommerce en cursos Moodle de forma asíncrona (vía cohorts). Genera cupones de descuento al finalizar un curso, cupones de bonificación al alcanzar el 40%/80% de progreso combinado (en pausa), certificados PDF al aprobar un examen, y la titulación (certificado único) al superar el 80% en los dos exámenes.

## Configuración

### 1. Moodle

1. Activar **Servicios Web > REST** en Administración del sitio.
2. Crear un **Servicio externo** y añadir las funciones:
   - `core_user_get_users_by_field`
   - `core_user_create_users`
   - `core_cohort_add_cohort_members`
   - `core_enrol_get_users_courses`
   - `gradereport_user_get_grade_items`
3. Generar un **Token** para un usuario administrador.
4. Asegurarse de que los cursos tienen habilitado el método de matriculación **Sincronización de cohortes**.

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

// Activar/desactivar certificado PDF (exam_passed), cupón (course_completed) y titulación
define( 'WCMS_SEND_CERTIFICATE', true );
define( 'WCMS_SEND_COUPON', true );
define( 'WCMS_SEND_DEGREE', true );
```

La titulación se configura con `WCMS_DEGREES` (plantilla, centrado del texto y exámenes requeridos, ver más abajo).

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

> ℹ️ **Lado Moodle:** el plugin `local_wcms_bridge` (ZIP aparte, ver sección más abajo) detecta `grade_updated` y `course_completed` y hace el POST a este endpoint. **`exam_passed` sigue sin tener disparador automático en Moodle** (no existe un evento nativo único para «examen aprobado»); hoy solo puede dispararse con una llamada manual (`curl`).

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

### Evento: nota actualizada → cupón de bonificación por progreso

```json
{
  "event_type": "grade_updated",
  "moodle_user_id": 123,
  "moodle_course_id": 456
}
```

Cada curso está formado por varios módulos y cada módulo tiene un grade (nota). Al recibir este
evento, el plugin recalcula el **% de nota combinado** del alumno: suma de las notas obtenidas
entre suma de las notas máximas, de todos los módulos (`itemtype=mod`, se excluye el total
agregado del curso) de **todos los cursos en los que está matriculado** en Moodle
(`core_enrol_get_users_courses` + `gradereport_user_get_grade_items`).

- Al alcanzar el **40%** combinado se emite un cupón de bonificación (mismo esquema que el de
  finalización de curso: `MeGeMIT-XXXXXXXX`, 100%, un solo uso, restringido por email y categoría
  `MDL-Coupon`).
- Al alcanzar el **80%** combinado se emite un segundo cupón de bonificación.
- **Límite de 2 cupones activos (sin usar) por cliente**, contando conjuntamente los cupones de
  finalización de curso y de bonificación. Si el cliente ya tiene 2 cupones sin usar, el nuevo
  umbral alcanzado no genera cupón (se pierde, no se encola).
- Deduplicación por umbral: cada umbral (40%/80%) solo genera cupón una vez por alumno
  (meta `_wcms_bonus_40_coupon` / `_wcms_bonus_80_coupon`).
- Envía email de felicitación (mismo diseño que el de finalización de curso, con el texto
  adaptado al % de progreso alcanzado).

> ℹ️ El plugin de Moodle `local_wcms_bridge` envía este evento al actualizarse una nota de módulo
> (`\core\event\user_graded`) o al enviarse un quiz (`attempt_submitted`). Las **bonificaciones están
> en pausa** hasta definir las reglas de asignación (los umbrales 40%/80% actuales son provisionales).

### Titulación (certificado único) — evento `grade_updated`

No es un evento nuevo: se evalúa dentro de `grade_updated`, antes y de forma independiente de las
bonificaciones, y solo si `WCMS_SEND_DEGREE` está activo y el curso del evento es uno de los exámenes.

El alumno recibe **un solo certificado** cuando alcanza el **80 %** de la nota máxima en **los dos**
exámenes (`WCMS_DEGREES`):

| Cohort | Examen | Curso Moodle | cmid | Máximo |
|---|---|---|---|---|
| 3 · Grundkurse | GRUNDKURS-EXAMEN | 10 | 246 | 46 |
| 2 · Aufbaukurse | AUFBAUKURS-EXAMEN | 9 | 252 | 42 |

- El % se calcula con el `grademax` real devuelto por Moodle; `max` en la config solo genera un aviso en el log si cambia.
- Plantilla: `assets/certificate-template-degree.png`; nombre y fecha se superponen centrados según `center_x`.
- Se envía con `WCMS_Mailer::send_certificate()` (texto en alemán, PDF adjunto) y se guarda en `wp-content/uploads/wcms-certificates/degree-*.pdf`.
- Deduplicación por usuario (meta `_wcms_degree_certificate`). No hay envío retroactivo: el certificado se emite solo cuando llega un evento `grade_updated`. Los alumnos que ya cumplían los requisitos antes de activar la función (39 en la verificación del 2026-10-21) **recibirían el certificado si Moodle emite un evento nuevo de nota de los cursos 9 o 10 para ellos**, porque no tienen aún la meta de deduplicación.
- Respuesta REST: clave `degree` con `disabled | not_applicable | already_issued | no_data | pending | error | ok`.

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

## Plugin de Moodle `local_wcms_bridge`

ZIP independiente (`local_wcms_bridge-v1.0.1.zip`, Moodle 4.4). Se instala en Administración del sitio > Plugins > Instalar plugins.

- Escucha `\core\event\user_graded` (solo ítems de módulo), `\mod_quiz\event\attempt_submitted` y `\core\event\course_completed`, y encola un POST a este endpoint (tarea ad hoc, con reintentos; descarta si WordPress responde 404).
- Ajustes en Administración > Plugins locales: activar, **solo registro** (por defecto, no envía; escribe en el cron), URL del endpoint, secreto (= `WCMS_COMPLETION_SECRET`) y cursos permitidos (`9,10`).
- No incluye `exam_passed`.

Orden recomendado de puesta en marcha: instalar, poner cursos `9,10`, activar en modo «solo registro», comprobar que llegan eventos en el cron, desmarcar «solo registro» y por último activar `WCMS_SEND_DEGREE` en WordPress.

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
│   ├── class-wcms-completion-handler.php  Webhook receptor: cupón, certificado y titulación
│   └── class-wcms-certificate.php      Generador de certificado PDF con GD
├── assets/
│   ├── certificate-template.png        Plantilla PNG limpia (1684×1192 px)
│   ├── certificate-template-degree.png Plantilla de la titulación (1684×1192 px)
│   └── fonts/
│       ├── BrushScript.ttf             Fuente cursiva para el nombre
│       └── TimesItalic.ttf             Fuente itálica para la fecha
└── README.md
```

## Notas

- La matriculación a curso/módulo la resuelve el propio Moodle vía "Sincronización de cohortes"; el plugin solo añade al usuario al cohort (`core_cohort_add_cohort_members`).
- Las credenciales y el token del webhook **no se almacenan en BD** — van en `config.php`, dentro de la carpeta del plugin.
- Los certificados PDF se generan sin dependencias externas (sin Ghostscript ni ImageMagick).
- Compatible con PHP 7.4 y superior.
