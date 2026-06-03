# Changelog — HubSpot Mapper

## [1.1.0] — 2026-06-03

### Añadido
- Nueva opción por mapeo: **"Solo bloquear recogida de datos"** (`do_not_collect`).
  - Al activarla, HubSpot LeadIn no recogerá ni enviará los datos del formulario.
  - El atributo `data-hs-do-not-collect="true"` se inyecta en el `<form>` en el evento `DOMContentLoaded` nativo (antes de que LeadIn escanee la página), garantizando que el bloqueo llegue a tiempo.
  - Compatible con formularios cuyo selector apunta directamente al `<form>` o a un `div` contenedor (se localiza el `<form>` interno automáticamente).
- Insignia visual **"🚫 No recoger"** en la vista de lista del admin para mapeos con esta opción activa.

### Cambiado
- Los campos "Nombre en HubSpot" y "Mapeo de Campos" se deshabilitan visualmente en el admin cuando `do_not_collect` está activo (no son necesarios).
- Versión bumpeada de `1.0.0` → `1.1.0`.

---

## [1.0.0] — inicial

- Mapeo frontend de campos SWPM → propiedades HubSpot mediante renombrado en el submit.
- Sin llamadas server-side — integración 100% frontend.
- Admin UI para gestionar múltiples mapeos (selector de formulario, nombre HubSpot, pares de campos).
