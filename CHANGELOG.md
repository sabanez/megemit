# Changelog

## 2026-09-30

### Cambiado
- Membresías Stripe: revertida la duración a "1 año desde la fecha de alta" (aniversario nativo de Stripe), en lugar del año natural (31/12) implementado previamente.
- `wp-content/themes/basel-child/inc/hubspot-swpm-stripe-bridge/`: las versiones con lógica de año natural (`flow-payment.php`, `flow-deactivation.php`, `flow-renewal.php`, `flow-webhook-renewal.php`) se movieron a `legacy-ano-natural/` (sin cargar). En su lugar se restauraron las versiones previas de `flow-payment.php` y `flow-deactivation.php`, que dejan intacta la suscripción de aniversario creada por Stripe.
- `loader.php`: eliminados los `require_once` de `flow-renewal.php` y `flow-webhook-renewal.php`.
- `wp-content/themes/basel-child/functions.php`: eliminado el banner de renovación (`mgmit_renewal_banner_output`, hook `wp_footer`) — dependía de la meta `mgmit_membership_valid_until`/`mgmit_show_renewal_button` que ya no se genera. Los avisos de renovación pasan a gestionarse vía los recordatorios nativos de SWPM y Stripe.

## 2026-09-11

### Añadido
- `wp-content/mu-plugins/bacs-admin-only.php` — oculta el método de pago "Transferencia bancaria directa" (BACS) en el checkout del frontend mediante el filtro `woocommerce_available_payment_gateways`, manteniéndolo disponible para pedidos creados manualmente desde `wp-admin`. El gateway permanece "Activado" en `WooCommerce > Ajustes > Pagos`.
