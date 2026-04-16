# Changelog - Proyecto Megemit

Todas las modificaciones técnicas realizadas en el entorno de WordPress y la integración con HubSpot.

## [1.1.0] - 2026-04-16

### Añadido
- **Sistema de Mapeo HubSpot (Bridge):** Creación de `wp-content/themes/basel-child/inc/hubspot_map.js`. Este script sincroniza campos personalizados de WordPress con propiedades estándar de HubSpot utilizando "Shadow Fields".
- **Documentación Técnica:** Creación de `wp-content/themes/basel-child/HUBSPOT_INTEGRATION.md` detallando el funcionamiento del puente con HubSpot.
- **Selectores de Formulario:** Soporte para múltiples selectores CSS en el registro (`#registro-profesional-13`, `#swpm-registration-form`, `.swpm-registration-form`).

### Modificado
- **`basel-child/functions.php`:** 
    - Implementación de la función `swpm_hubspot_mapper_script` para encolar el script de mapeo.
    - Configuración del mapeo de campos para los formularios de Registro Profesional (ID 13) y Actualización de Perfil (ID 16).
    - Ajuste de prioridad en `wp_enqueue_scripts` a `20` para asegurar la carga después de dependencias.
- **`basel/functions.php`:** Limpieza de comentarios de diagnóstico tras pruebas de carga.

### Corregido
- **Conflicto de Plugins:** Se detectó y corrigió una entrada duplicada del plugin `duplicator-pro` en la opción `active_plugins` de la base de datos, lo que causaba inestabilidad.
- **Error de JavaScript:** Corregida la iteración del bucle `forEach` en el mapeador de HubSpot que causaba un error de referencia de variable.
- **Saneamiento de Datos:** Implementación del atributo `data-hs-ignore="true"` en los campos originales para evitar que HubSpot recoja datos duplicados o "sucios".
- **Problema de Caché:** Identificación y resolución de un conflicto con `wp-fastest-cache` que impedía la carga de scripts actualizados.

---

## [1.0.0] - Inicio del Proyecto
- Análisis inicial del entorno ServBay (macOS).
- Configuración de acceso a la base de datos MySQL (megemit_database).
- Creación del archivo `agent.md` para seguimiento técnico.

---
*Fin del registro actual.*
