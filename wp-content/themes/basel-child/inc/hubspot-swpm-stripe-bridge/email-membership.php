<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Envía el email de bienvenida/confirmación de membresía con los 5 documentos adjuntos.
 *
 * @param array $ipn_data Datos del hook swpm_payment_ipn_processed.
 */
function mgmit_bridge_send_membership_email( $ipn_data ) {

    $email      = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    $first_name = isset( $ipn_data['first_name'] )  ? sanitize_text_field( $ipn_data['first_name'] ) : '';
    $last_name  = isset( $ipn_data['last_name'] )   ? sanitize_text_field( $ipn_data['last_name'] )  : '';
    $amount     = isset( $ipn_data['mc_gross'] )    ? sanitize_text_field( $ipn_data['mc_gross'] )   : '0.00';
    $txn_id     = isset( $ipn_data['txn_id'] )      ? sanitize_file_name( $ipn_data['txn_id'] )      : 'sinref';

    if ( empty( $email ) ) {
        MGMIT_Webhook_Sender::debug_log( 'send_membership_email: email vacío, omitiendo envío' );
        return;
    }

    // Datos de facturación del usuario WP (primero por el email de registro del socio,
    // que puede diferir del email escrito en Stripe Checkout).
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
    $salutation       = mgmit_bridge_build_salutation_for_ipn( $ipn_data, $first_name, $last_name );
    $first_name       = $salutation['first_name'];
    $last_name        = $salutation['last_name'];
    $greeting_line    = $salutation['greeting'];
    $salutation_full  = $salutation['courtesy_de']; // valor de HubSpot tal cual, para los PDFs
    $salutation_label = $salutation_full;
    $job_title        = $salutation['job_title_de'];

    // Dirección de HubSpot (con el mismo email del socio que el saludo): respaldo de la
    // confirmación (si el perfil SWPM no tiene dirección) y de la factura (tras Stripe).
    $hubspot_address  = mgmit_bridge_get_hubspot_address( $salutation['lookup_email'] );
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

    // Generar los 2 PDFs dinámicos
    $tmp_dir = wp_upload_dir();
    $tmp_dir = trailingslashit( $tmp_dir['basedir'] ) . 'mgmit-bridge-tmp';

    if ( ! file_exists( $tmp_dir ) ) {
        wp_mkdir_p( $tmp_dir );
    }

    $pdf_mitglied    = trailingslashit( $tmp_dir ) . 'Mitgliedschaftbestaetigung_' . time() . '.pdf';
    $pdf_vorschreib  = trailingslashit( $tmp_dir ) . 'rechnung_' . $txn_id . '.pdf';

    $ok_mitglied   = mgmit_bridge_generate_pdf( 'mitgliedschaft-pdf', $pdf_mitglied, compact(
        'first_name', 'last_name', 'member_street', 'member_postcode', 'member_city', 'member_country',
        'amount', 'year', 'date_formatted', 'salutation_label', 'job_title',
        'header_img_path'
    ) );

    $invoice_vars = mgmit_bridge_build_invoice_vars( $ipn_data );

    $ok_vorschreib = mgmit_bridge_generate_pdf( 'vorschreibung-pdf', $pdf_vorschreib, compact(
        'first_name', 'last_name', 'salutation_full', 'job_title', 'hubspot_address',
        'billing_address', 'billing_city', 'billing_postcode', 'billing_state', 'billing_country',
        'amount', 'date_formatted', 'header_img_path'
    ) + $invoice_vars );

    // Construir lista de adjuntos
    $static_dir  = __DIR__ . '/email-attachments/static/';
    $attachments = array(
        $static_dir . 'Datenschutz-Mitgliederinformation.pdf',
        $static_dir . 'Information Therapeuten-Beratung.pdf',
        $static_dir . 'Bestellschein_Informationsmaterial_2025_07_23.pdf',
    );
    if ( $ok_mitglied ) {
        $attachments[] = $pdf_mitglied;
    }
    if ( $ok_vorschreib ) {
        $attachments[] = $pdf_vorschreib;
    }

    // Cuerpo HTML del email
    $body_html = mgmit_bridge_render_template( 'body', compact(
        'greeting_line', 'header_img_url'
    ) );

    // Cabeceras del email
    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );

    $subject = 'Herzlich Willkommen als Mitglied bei der MeGeMIT';

    $sent = wp_mail( $email, $subject, $body_html, $headers, $attachments );

    if ( $sent ) {
        MGMIT_Webhook_Sender::debug_log( 'send_membership_email: enviado OK a ' . $email );
    } else {
        MGMIT_Webhook_Sender::debug_log( 'send_membership_email: wp_mail() devolvió false para ' . $email );
    }

    // Borrar PDFs temporales
    if ( $ok_mitglied && file_exists( $pdf_mitglied ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        unlink( $pdf_mitglied );
    }
    if ( $ok_vorschreib && file_exists( $pdf_vorschreib ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        unlink( $pdf_vorschreib );
    }
}

/**
 * Genera un PDF a partir de una plantilla PHP usando Dompdf.
 *
 * @param string $template_name  Nombre sin extensión en email-templates/.
 * @param string $output_path    Ruta absoluta donde guardar el PDF.
 * @param array  $vars           Variables a inyectar en la plantilla.
 * @return bool  true si el archivo fue escrito correctamente.
 */
function mgmit_bridge_generate_pdf( $template_name, $output_path, $vars ) {
    $dompdf_autoload = WP_PLUGIN_DIR
        . '/woocommerce-pdf-invoices-packing-slips/vendor/strauss/autoload.php';

    if ( ! file_exists( $dompdf_autoload ) ) {
        MGMIT_Webhook_Sender::debug_log( 'generate_pdf: no se encontró el autoloader de Dompdf en ' . $dompdf_autoload );
        return false;
    }

    // Cargar autoloader solo si Dompdf no está ya disponible
    if ( ! class_exists( 'WPO\\IPS\\Vendor\\Dompdf\\Dompdf' ) ) {
        require_once $dompdf_autoload;
    }

    if ( ! class_exists( 'WPO\\IPS\\Vendor\\Dompdf\\Dompdf' ) ) {
        MGMIT_Webhook_Sender::debug_log( 'generate_pdf: clase Dompdf no disponible tras cargar autoloader' );
        return false;
    }

    $html = mgmit_bridge_render_template( $template_name, $vars );

    if ( empty( $html ) ) {
        MGMIT_Webhook_Sender::debug_log( 'generate_pdf: HTML vacío para plantilla ' . $template_name );
        return false;
    }

    $options_class = 'WPO\\IPS\\Vendor\\Dompdf\\Options';
    $options       = new $options_class();
    $options->set( 'isRemoteEnabled', true );
    $options->set( 'isHtml5ParserEnabled', true );
    $options->set( 'defaultFont', 'Arial' );
    $options->set( 'chroot', array( WP_CONTENT_DIR, get_stylesheet_directory() ) );

    $dompdf_class = 'WPO\\IPS\\Vendor\\Dompdf\\Dompdf';
    $dompdf       = new $dompdf_class( $options );
    $dompdf->loadHtml( $html );
    $dompdf->setPaper( 'A4', 'portrait' );
    $dompdf->render();

    $pdf_output = $dompdf->output();

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    $written = file_put_contents( $output_path, $pdf_output );

    if ( false === $written ) {
        MGMIT_Webhook_Sender::debug_log( 'generate_pdf: error escribiendo ' . $output_path );
        return false;
    }

    return true;
}

/**
 * Renderiza una plantilla PHP de email-templates/ y devuelve el HTML como string.
 *
 * @param string $template_name  Nombre sin extensión.
 * @param array  $vars           Variables disponibles en la plantilla.
 * @return string
 */
function mgmit_bridge_render_template( $template_name, $vars ) {
    $template_path = __DIR__ . '/email-templates/' . $template_name . '.php';

    if ( ! file_exists( $template_path ) ) {
        MGMIT_Webhook_Sender::debug_log( 'render_template: plantilla no encontrada ' . $template_path );
        return '';
    }

    // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
    extract( $vars );

    ob_start();
    include $template_path;
    return ob_get_clean();
}
