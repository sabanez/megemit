# Changelog — wc-moodle-sync

## 1.7.0 — 2026-08-31

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

## 1.6.0 — 2026-08-27

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
