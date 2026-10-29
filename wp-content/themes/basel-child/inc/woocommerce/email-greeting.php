<?php
/**
 * Saludo formal alemán para los emails de cliente de WooCommerce.
 *
 * Reutiliza el resolver del bridge HubSpot/SWPM/Stripe (courtesy_de y job_title_de
 * de HubSpot; nombre, apellido y email del pedido). Nunca bloquea el envío: si el
 * resolver no está disponible, degrada a "Sehr geehrte(r) Vorname Nachname".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @param WC_Order $order Pedido del email.
 * @return string Saludo sin signo de puntuación final, p. ej. "Sehr geehrter Herr Dr. Müller".
 */
function mgmit_wc_email_greeting( $order ) {
    $email      = sanitize_email( $order->get_billing_email() );
    $first_name = trim( $order->get_billing_first_name() );
    $last_name  = trim( $order->get_billing_last_name() );

    if ( function_exists( 'mgmit_bridge_build_salutation' ) && '' !== $email ) {
        $salutation = mgmit_bridge_build_salutation( $email, $first_name, $last_name );
        if ( ! empty( $salutation['greeting'] ) ) {
            return $salutation['greeting'];
        }
    }

    return 'Sehr geehrte(r) ' . trim( $first_name . ' ' . $last_name );
}
