# Changelog

## [Unreleased] — 2026-06-03

### Fixed
- **`email-membership.php` / `email-templates/body.php`** — imagen de cabecera del
  email de membresía ahora usa URL pública (`get_stylesheet_directory_uri`) en lugar
  de ruta de sistema, corrigiendo la visualización en clientes de correo.

### Added
- **`inc/hubspot-swpm-stripe-bridge/`** — nuevo módulo que centraliza la integración
  entre HubSpot, SWPM, WooCommerce y Stripe: webhook sender, flujos de pago/desactivación,
  emails de membresía con plantillas.
- **`membership-woo-bridge.php`** — campo meta `_swpm_membership_level` en el admin de
  producto WooCommerce; guarda nivel previo al crear la orden para poder revertirlo.
- **`onboarding-enforcement.js`** — función `blockNavigation()`: bloquea todos los enlaces
  y elementos JS del header (carrito, buscador, sidebar) para usuarios con registro pendiente.

### Changed
- **`membership-woo-bridge.php`** — `swpm_update_level_after_payment` ahora usa la API
  nativa de SWPM (`SwpmMemberUtils`, `SwpmTransactions`) disparando hooks y email de
  confirmación; fallback a actualización directa en BD si SWPM no está disponible.
- **`membership-woo-bridge.php`** — nueva función `swpm_revert_level_on_order_status_change`:
  revierte el nivel de membresía al anterior cuando el pago se cancela, falla o reembolsa.
- **`functions.php`** — URLs absolutas del header convertidas a rutas relativas;
  carga `hubspot-swpm-stripe-bridge/loader.php`.

### Removed
- Plantilla PDF `MeGeMit2` (obsoleta): `invoice.php`, `style.css`, `html-document-wrapper.php`,
  `template-functions.php`, `facebook_icon.png`.
