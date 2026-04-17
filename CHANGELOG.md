# Changelog - Proyecto Megemit

Todas las modificaciones técnicas realizadas en el entorno de WordPress y la integración con HubSpot.

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
