<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Envía el email de confirmación de renovación de membresía (factura +
 * confirmación de membresía adjuntas) cuando SWPM procesa un pago recurrente.
 *
 * Se engancha en swpm_recurring_payment_received, disparado por SWPM justo
 * cuando confirma un cobro de renovación (antes de guardar la transacción).
 */
add_action( 'swpm_recurring_payment_received', 'mgmit_bridge_send_renewal_confirmed_email', 10, 1 );

function mgmit_bridge_send_renewal_confirmed_email( $ipn_data ) {
    MGMIT_Webhook_Sender::debug_log(
        'send_renewal_confirmed_email: iniciado (payer_email=' . ( isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '' ) .
        ', subscr_id=' . ( isset( $ipn_data['subscr_id'] ) ? $ipn_data['subscr_id'] : '' ) . ')'
    );

    // Un error de PHP aquí no debe pasar en silencio ni interrumpir el procesado de SWPM.
    try {
        mgmit_bridge_send_renewal_confirmed_email_run( $ipn_data );
    } catch ( \Throwable $e ) {
        MGMIT_Webhook_Sender::debug_log(
            'send_renewal_confirmed_email: ERROR ' . get_class( $e ) . ': ' . $e->getMessage() . ' en ' . basename( $e->getFile() ) . ':' . $e->getLine()
        );
    }
}

function mgmit_bridge_send_renewal_confirmed_email_run( $ipn_data ) {

    $email      = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    $first_name = isset( $ipn_data['first_name'] )  ? sanitize_text_field( $ipn_data['first_name'] ) : '';
    $last_name  = isset( $ipn_data['last_name'] )   ? sanitize_text_field( $ipn_data['last_name'] )  : '';
    $amount     = isset( $ipn_data['mc_gross'] )    ? sanitize_text_field( $ipn_data['mc_gross'] )   : '0.00';
    $txn_id     = ! empty( $ipn_data['txn_id'] )    ? sanitize_file_name( $ipn_data['txn_id'] )      : 'sinref';

    if ( empty( $email ) ) {
        MGMIT_Webhook_Sender::debug_log( 'send_renewal_confirmed_email: email vacío, omitiendo envío' );
        return;
    }

    // Datos de facturación del usuario WP (primero por el email de registro del socio,
    // que puede diferir del email del cobro en Stripe).
    $swpm_member      = mgmit_bridge_get_swpm_member_from_ipn( $ipn_data );
    $wp_user_id       = ( $swpm_member && ! empty( $swpm_member->email ) ) ? email_exists( $swpm_member->email ) : false;
    if ( ! $wp_user_id ) {
        $wp_user_id = email_exists( $email );
    }
    $member_address   = mgmit_bridge_get_member_address( $swpm_member, $wp_user_id );
    $billing_address  = $member_address['address'];
    $billing_city     = $member_address['city'];
    $billing_postcode = $member_address['postcode'];
    $billing_state    = $member_address['state'];
    $billing_country  = $member_address['country'];

    // Confirmación de membresía: dirección del perfil del socio en SWPM (la que coincide con HubSpot).
    $profile_address  = mgmit_bridge_get_swpm_profile_address( $swpm_member );
    $member_street    = $profile_address['street'];
    $member_postcode  = $profile_address['postcode'];
    $member_city      = $profile_address['city'];
    $member_country   = $profile_address['country'];

    // Saludo formal vía HubSpot (courtesy_de + job_title_de), con fallback seguro si falla la consulta.
    $salutation      = mgmit_bridge_build_salutation_for_ipn( $ipn_data, $first_name, $last_name );
    $first_name      = $salutation['first_name'];
    $last_name       = $salutation['last_name'];
    $greeting_line   = $salutation['greeting'];
    $salutation_full = $salutation['courtesy_de']; // valor de HubSpot tal cual, para los PDFs
    $job_title       = $salutation['job_title_de'];

    // Dirección de HubSpot (con el mismo email del socio que el saludo): respaldo de la
    // confirmación (si el perfil SWPM no tiene dirección) y de la factura (tras Stripe).
    $hubspot_address = mgmit_bridge_get_hubspot_address( $salutation['lookup_email'] );
    if ( '' === $member_street && '' === $member_postcode && '' === $member_city ) {
        $member_street   = $hubspot_address['address'];
        $member_postcode = $hubspot_address['postcode'];
        $member_city     = $hubspot_address['city'];
        $member_country  = $hubspot_address['country'];
    }

    $year           = gmdate( 'Y' );
    $date_formatted = gmdate( 'd.m.Y' );

    $header_img_path = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
    $header_img_url  = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';

    // Generar los 2 PDFs dinámicos (misma plantilla que el email de Alta)
    $tmp_dir = wp_upload_dir();
    $tmp_dir = trailingslashit( $tmp_dir['basedir'] ) . 'mgmit-bridge-tmp';

    if ( ! file_exists( $tmp_dir ) ) {
        wp_mkdir_p( $tmp_dir );
    }

    $pdf_mitglied   = trailingslashit( $tmp_dir ) . 'Mitgliedschaftbestaetigung_' . time() . '.pdf';
    $pdf_vorschreib = trailingslashit( $tmp_dir ) . 'rechnung_' . $txn_id . '.pdf';

    $ok_mitglied   = mgmit_bridge_generate_pdf( 'mitgliedschaft-pdf', $pdf_mitglied, compact(
        'first_name', 'last_name', 'member_street', 'member_postcode', 'member_city', 'member_country',
        'amount', 'year', 'date_formatted', 'header_img_path', 'job_title'
    ) + array( 'salutation_label' => $salutation_full ) );

    $ok_vorschreib = mgmit_bridge_generate_pdf( 'vorschreibung-pdf', $pdf_vorschreib, compact(
        'first_name', 'last_name', 'job_title', 'hubspot_address',
        'billing_address', 'billing_city', 'billing_postcode', 'billing_state', 'billing_country',
        'amount', 'date_formatted', 'header_img_path'
    ) + array( 'salutation_full' => $salutation_full ) + mgmit_bridge_build_invoice_vars( $ipn_data ) );

    $attachments = array();
    if ( $ok_mitglied ) {
        $attachments[] = $pdf_mitglied;
    }
    if ( $ok_vorschreib ) {
        $attachments[] = $pdf_vorschreib;
    }

    $body_html = mgmit_bridge_render_template( 'body-renewal-confirmed', compact( 'greeting_line', 'header_img_url' ) );

    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );

    $subject = 'Bestätigung der Fortsetzung Ihrer Mitgliedschaft bei der MeGeMIT';

    $sent = wp_mail( $email, $subject, $body_html, $headers, $attachments );

    if ( $sent ) {
        MGMIT_Webhook_Sender::debug_log( 'send_renewal_confirmed_email: enviado OK a ' . $email );
    } else {
        MGMIT_Webhook_Sender::debug_log( 'send_renewal_confirmed_email: wp_mail() devolvió false para ' . $email );
    }

    if ( $ok_mitglied && file_exists( $pdf_mitglied ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        unlink( $pdf_mitglied );
    }
    if ( $ok_vorschreib && file_exists( $pdf_vorschreib ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        unlink( $pdf_vorschreib );
    }
}
