# MeGeMIT WordPress — CLAUDE.md

## Stack

| Componente | Versión | Notas |
|---|---|---|
| WordPress | 6.9.4 | |
| PHP | 8.3 local / **7.4 producción** | Compatibilidad 7.4 obligatoria |
| MySQL | 8.4.8 | ServBay, macOS |
| Tema activo | Basel Child | Padre: Basel |
| DB | `megemit_database` | Prefix: `wpgr_`, user: `root`, host: `localhost` |

## Reglas de trabajo

1. **Aprobación previa** antes de cualquier modificación de código.
2. **Fases incrementales** — validar cada fase antes de continuar; registrar en `CHANGELOG.md`.
3. **PHP 7.4 estricto** — prohibido: constructor promotion, union types, named arguments, match expressions, nullsafe operator.

## Arquitectura

### Plugin `mgmit-hubspot-bridge` (v1.3.0, estable)
- Ruta: `wp-content/plugins/mgmit-hubspot-bridge/`
- Mapea campos de formularios SWPM → propiedades HubSpot vía campos ocultos JS.
- Config en `wp_options['mgmit_hubspot_config']`. Filtro extensible: `mgmit_hs_bridge_config`.
- Frontend: `assets/js/hubspot_map.js` (consume `HS_CONFIG` vía `wp_localize_script`).
- Admin UI: `includes/class-mgmit-admin-ui.php`.
- **Sin llamadas server-side a la API de HubSpot** — integración 100% frontend.

### Tema hijo `basel-child` (lógica de onboarding)
- Onboarding separado del plugin intencionalmente (regla de negocio de presentación).
- Archivos clave: `functions.php`, `inc/onboarding-enforcement.js`.
- Metadatos de control:
  - `mgmit_hs_details_pending = '1'` → bloqueo total (nuevo registro pendiente)
  - `mgmit_hs_details_pending = '0'` → completado (no se vuelve a pedir)
  - `mgmit_hs_legacy_pending = '1'` → pendiente suave (usuario preexistente, sin bloqueo)
- Página de destino: ID `21568` (`/registrierungsdetails/`). Desbloqueo vía `?hs_finish=1`.
- Login redirect para legacy pending → misma página con `?legacy=1`.

### Herramienta de migración
- `hs-migration.php` en el root — página standalone que carga `wp-load.php`.
- Solo accesible para administradores autenticados.
- Sube CSV de HubSpot, cruza por email con usuarios WP, actualiza metadatos bulk.
- Campos verificados: `Courtesy DE`, `First Name`, `Last Name`, `Main speciality`, `Origin`.
