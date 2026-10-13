<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// La notificación a HubSpot la gestiona Stripe directamente mediante su propio
// webhook; aquí solo nos ocupamos del email de bienvenida/confirmación.
add_action( 'swpm_payment_ipn_processed', 'mgmit_bridge_send_membership_email', 10, 1 );
