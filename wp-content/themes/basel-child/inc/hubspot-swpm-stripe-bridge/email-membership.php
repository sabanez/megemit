<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Envía el email de bienvenida/confirmación de membresía con los 5 documentos adjuntos.
 *
 * @param array  $ipn_data  Datos del hook swpm_payment_ipn_processed.
 * @param string $gender    'male', 'female' o '' (no especificado).
 */
function mgmit_bridge_send_membership_email( $ipn_data, $gender ) {

    $email      = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    $first_name = isset( $ipn_data['first_name'] )  ? sanitize_text_field( $ipn_data['first_name'] ) : '';
    $last_name  = isset( $ipn_data['last_name'] )   ? sanitize_text_field( $ipn_data['last_name'] )  : '';
    $amount     = isset( $ipn_data['mc_gross'] )    ? sanitize_text_field( $ipn_data['mc_gross'] )   : '0.00';

    if ( empty( $email ) ) {
        MGMIT_Webhook_Sender::debug_log( 'send_membership_email: email vacío, omitiendo envío' );
        return;
    }

    // Datos de facturación del usuario WP
    $wp_user_id       = email_exists( $email );
    $billing_address  = '';
    $billing_city     = '';
    $billing_postcode = '';
    $billing_state    = '';
    $billing_country  = '';
    if ( $wp_user_id ) {
        $billing_address  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_address_1', true ) );
        $billing_city     = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_city',      true ) );
        $billing_postcode = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_postcode',  true ) );
        $billing_state    = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_state',     true ) );
        $billing_country  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_country',   true ) );
    }

    // Saludo según género
    if ( 'female' === $gender ) {
        $salutation_label = 'Sehr geehrte Frau';
        $salutation_full  = 'Frau';
        $greeting_line    = 'Sehr geehrte Frau ' . $last_name;
    } elseif ( 'male' === $gender ) {
        $salutation_label = 'Sehr geehrter Herr';
        $salutation_full  = 'Herr';
        $greeting_line    = 'Sehr geehrter Herr ' . $last_name;
    } else {
        $salutation_label = 'Herr/Frau';
        $salutation_full  = '';
        $greeting_line    = 'Sehr geehrte/r ' . $last_name;
    }

    $year           = gmdate( 'Y' );
    $date_formatted = gmdate( 'd.m.Y' );

    $header_img_path     = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
    $logo_watermark_path = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
    $header_img_url      = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';

    // Generar los 2 PDFs dinámicos
    $tmp_dir = wp_upload_dir();
    $tmp_dir = trailingslashit( $tmp_dir['basedir'] ) . 'mgmit-bridge-tmp';

    if ( ! file_exists( $tmp_dir ) ) {
        wp_mkdir_p( $tmp_dir );
    }

    $pdf_mitglied    = trailingslashit( $tmp_dir ) . 'Mitgliedschaftbestaetigung_' . time() . '.pdf';
    $pdf_vorschreib  = trailingslashit( $tmp_dir ) . 'Vorschreibung_' . time() . '.pdf';

    $ok_mitglied   = mgmit_bridge_generate_pdf( 'mitgliedschaft-pdf', $pdf_mitglied, compact(
        'first_name', 'last_name', 'billing_postcode', 'billing_city',
        'amount', 'year', 'date_formatted', 'salutation_label',
        'header_img_path', 'logo_watermark_path'
    ) );

    $ok_vorschreib = mgmit_bridge_generate_pdf( 'vorschreibung-pdf', $pdf_vorschreib, compact(
        'first_name', 'last_name', 'salutation_full', 'greeting_line',
        'billing_address', 'billing_city', 'billing_postcode', 'billing_state', 'billing_country',
        'amount', 'year', 'date_formatted', 'header_img_path'
    ) );

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
