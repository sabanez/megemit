<?php
/**
 * Fase 2 (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md) — endpoint propio para
 * servir el PDF de la nota de abono, calco literal de invoice-endpoint.php.
 *
 * Misma firma HMAC (INVOICE_SECRET) y mismo mecanismo sin sesión/login,
 * para que HubSpot/vpsbridge pueda descargar el PDF con el enlace recibido
 * en el payload del webhook (Fase 3).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera la URL firmada del PDF de la nota de abono para un pedido.
 *
 * @param int $order_id
 * @return string
 */
function mgmit_get_storno_pdf_url( $order_id ) {
	if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
		return '';
	}

	$order_id = absint( $order_id );
	// Prefijo 'storno-' para no compartir token con el de la factura del mismo pedido.
	$token = hash_hmac( 'sha256', 'storno-' . $order_id, INVOICE_SECRET );

	return add_query_arg(
		array(
			'action'   => 'mgmit_storno_pdf',
			'order_id' => $order_id,
			'token'    => $token,
		),
		admin_url( 'admin-ajax.php' )
	);
}

add_action( 'wp_ajax_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );
add_action( 'wp_ajax_nopriv_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );

function mgmit_serve_storno_pdf() {
	if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
		wp_die( 'No configurado.', '', array( 'response' => 500 ) );
	}

	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	$token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

	if ( ! $order_id || ! $token ) {
		wp_die( 'Faltan parámetros.', '', array( 'response' => 400 ) );
	}

	$expected_token = hash_hmac( 'sha256', 'storno-' . $order_id, INVOICE_SECRET );

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

	$credit_note = wcpdf_get_document( 'credit-note', $order, true );

	if ( ! $credit_note ) {
		wp_die( 'No se pudo generar la nota de abono.', '', array( 'response' => 500 ) );
	}

	$pdf_data = $credit_note->get_pdf();

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $credit_note->get_filename() ) . '"' );
	header( 'Content-Length: ' . strlen( $pdf_data ) );

	echo $pdf_data;
	exit;
}
