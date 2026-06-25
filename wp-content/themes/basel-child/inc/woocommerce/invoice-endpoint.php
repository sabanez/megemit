<?php
/**
 * Endpoint propio para servir el PDF de factura sin depender de sesión
 * ni del ajuste "Document link access type" del plugin de facturas.
 *
 * La URL se firma con HMAC (MGMIT_INVOICE_SECRET + order_id), por lo que
 * es válida indefinidamente para quien la reciba (HubSpot, vpsbridge, etc.)
 * sin necesitar estar logueado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Genera la URL firmada del PDF de factura para un pedido.
 *
 * @param int $order_id
 * @return string
 */
function mgmit_get_invoice_pdf_url( $order_id ) {
    if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
        return '';
    }

    $order_id = absint( $order_id );
    $token    = hash_hmac( 'sha256', (string) $order_id, INVOICE_SECRET );

    return add_query_arg(
        array(
            'action'   => 'mgmit_invoice_pdf',
            'order_id' => $order_id,
            'token'    => $token,
        ),
        admin_url( 'admin-ajax.php' )
    );
}

add_action( 'wp_ajax_mgmit_invoice_pdf', 'mgmit_serve_invoice_pdf' );
add_action( 'wp_ajax_nopriv_mgmit_invoice_pdf', 'mgmit_serve_invoice_pdf' );

function mgmit_serve_invoice_pdf() {
    if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
        wp_die( 'No configurado.', '', array( 'response' => 500 ) );
    }

    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

    if ( ! $order_id || ! $token ) {
        wp_die( 'Faltan parámetros.', '', array( 'response' => 400 ) );
    }

    $expected_token = hash_hmac( 'sha256', (string) $order_id, INVOICE_SECRET );

    if ( ! hash_equals( $expected_token, $token ) ) {
        wp_die( 'Token no válido.', '', array( 'response' => 403 ) );
    }

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        wp_die( 'Pedido no encontrado.', '', array( 'response' => 404 ) );
    }

    if ( ! function_exists( 'wcpdf_get_document' ) ) {
        wp_die( 'Plugin de facturas no disponible.', '', array( 'response' => 500 ) );
    }

    $invoice = wcpdf_get_document( 'invoice', $order, true );

    if ( ! $invoice ) {
        wp_die( 'No se pudo generar la factura.', '', array( 'response' => 500 ) );
    }

    $pdf_data = $invoice->get_pdf();

    nocache_headers();
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $invoice->get_filename() ) . '"' );
    header( 'Content-Length: ' . strlen( $pdf_data ) );

    echo $pdf_data;
    exit;
}
