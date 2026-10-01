<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Procesado de renovaciones automáticas SEPA confirmadas por Stripe.
 *
 * Se engancha a 'swpm_recurring_payment_received' (swpm_handle_subsc_ipn.php:514),
 * que se dispara cuando el webhook invoice.payment_succeeded + subscription_cycle
 * ha sido procesado correctamente por SWPM.
 *
 * Acciones:
 * 1. Actualiza mgmit_membership_valid_until al 31/12 del año siguiente.
 * 2. Limpia mgmit_renewal_notices_sent y mgmit_show_renewal_button.
 * 3. Envía email de confirmación de renovación al miembro.
 */
add_action( 'swpm_recurring_payment_received', 'mgmit_bridge_handle_sepa_renewal', 10, 1 );

function mgmit_bridge_handle_sepa_renewal( $ipn_data ) {
    $email      = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    $subscr_id  = isset( $ipn_data['subscr_id'] )   ? sanitize_text_field( $ipn_data['subscr_id'] ) : '';
    $amount     = isset( $ipn_data['mc_gross'] )     ? sanitize_text_field( $ipn_data['mc_gross'] )  : '0.00';
    $gateway    = isset( $ipn_data['gateway'] )      ? $ipn_data['gateway'] : '';

    if ( empty( $email ) ) {
        return;
    }

    // Solo procesamos renovaciones Stripe (SEPA); ignorar PayPal u otros
    if ( 'stripe-sca-subs' !== $gateway ) {
        return;
    }

    $wp_user_id = email_exists( $email );
    if ( ! $wp_user_id ) {
        MGMIT_Webhook_Sender::debug_log( 'handle_sepa_renewal: no se encontró usuario WP para ' . $email );
        return;
    }

    // En renovación (Stripe cobra el 1/01) el vencimiento es el 31/12 del año en curso.
    // La regla de octubre no aplica a renovaciones — el billing_cycle_anchor es enero.
    $timezone         = new DateTimeZone( 'Europe/Madrid' );
    $now              = new DateTime( 'now', $timezone );
    $year             = (int) $now->format( 'Y' );
    $valid_until_db   = $year . '-12-31';
    $valid_until_fmt  = '31.12.' . $year;

    // Actualizar user meta
    update_user_meta( $wp_user_id, 'mgmit_membership_valid_until', $valid_until_db );
    delete_user_meta( $wp_user_id, 'mgmit_renewal_notices_sent' );
    delete_user_meta( $wp_user_id, 'mgmit_show_renewal_button' );

    MGMIT_Webhook_Sender::debug_log(
        'handle_sepa_renewal: renovación confirmada user=' . $wp_user_id .
        ' valid_until=' . $valid_until_db
    );

    // Obtener datos para el email
    global $wpdb;
    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT first_name, last_name, gender FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s LIMIT 1",
        $email
    ) );

    $first_name = $member ? sanitize_text_field( $member->first_name ) : '';
    $last_name  = $member ? sanitize_text_field( $member->last_name )  : '';
    $gender     = $member ? sanitize_text_field( $member->gender )     : '';

    if ( 'female' === $gender ) {
        $greeting_line = 'Sehr geehrte Frau ' . $last_name;
    } elseif ( 'male' === $gender ) {
        $greeting_line = 'Sehr geehrter Herr ' . $last_name;
    } else {
        $greeting_line = 'Sehr geehrte/r ' . ( $last_name ? $last_name : $first_name );
    }

    $header_img_url = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';

    $body_html = mgmit_bridge_render_template( 'renewal-confirmed', compact(
        'greeting_line', 'header_img_url', 'valid_until_fmt', 'amount'
    ) );

    // Renombrar variable para la plantilla (usa $valid_until)
    $body_html = mgmit_bridge_render_template( 'renewal-confirmed', array(
        'greeting_line'  => $greeting_line,
        'header_img_url' => $header_img_url,
        'valid_until'    => $valid_until_fmt,
        'amount'         => $amount,
    ) );

    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );
    $subject = 'Ihre MeGeMIT-Mitgliedschaft wurde erfolgreich verlängert';

    $sent = wp_mail( $email, $subject, $body_html, $headers );

    MGMIT_Webhook_Sender::debug_log(
        'handle_sepa_renewal: email confirmación ' . ( $sent ? 'enviado' : 'fallido' ) . ' a ' . $email
    );
}
