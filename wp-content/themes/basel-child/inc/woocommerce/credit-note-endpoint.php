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
 * @param int $item_id  Opcional: línea del pedido (pedidos facturados por línea), solo sus notas de abono.
 * @return string
 */
function mgmit_get_storno_pdf_url( $order_id, $item_id = 0 ) {
	if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
		return '';
	}

	$order_id = absint( $order_id );
	$item_id  = absint( $item_id );
	$args     = array(
		'action'   => 'mgmit_storno_pdf',
		'order_id' => $order_id,
		// Prefijo 'storno-' para no compartir token con el de la factura del mismo pedido.
		'token'    => hash_hmac( 'sha256', mgmit_storno_token_payload( $order_id, $item_id ), INVOICE_SECRET ),
	);

	if ( $item_id ) {
		$args['item_id'] = $item_id;
	}

	return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
}

/**
 * Texto firmado: 'storno-pedido' (URLs ya emitidas siguen válidas) o 'storno-pedido:línea'.
 */
function mgmit_storno_token_payload( $order_id, $item_id = 0 ) {
	return $item_id ? 'storno-' . absint( $order_id ) . ':' . absint( $item_id ) : 'storno-' . absint( $order_id );
}

add_action( 'wp_ajax_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );
add_action( 'wp_ajax_nopriv_mgmit_storno_pdf', 'mgmit_serve_storno_pdf' );

function mgmit_serve_storno_pdf() {
	if ( ! defined( 'INVOICE_SECRET' ) || empty( INVOICE_SECRET ) ) {
		wp_die( 'No configurado.', '', array( 'response' => 500 ) );
	}

	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	$item_id  = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
	$token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

	if ( ! $order_id || ! $token ) {
		wp_die( 'Faltan parámetros.', '', array( 'response' => 400 ) );
	}

	$expected_token = hash_hmac( 'sha256', mgmit_storno_token_payload( $order_id, $item_id ), INVOICE_SECRET );

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

	$filename = $credit_note->get_filename();

	// Notas de abono de una sola línea (pedidos facturados por línea).
	if ( $item_id && function_exists( 'mgmit_credit_note_only_item' ) ) {
		mgmit_credit_note_only_item( $item_id );
	}

	$pdf_data = $credit_note->get_pdf();

	if ( $item_id ) {
		$numbers = array();
		foreach ( function_exists( 'mgmit_get_split_credit_notes' ) ? mgmit_get_split_credit_notes( $order ) : array() as $entry ) {
			if ( (int) $entry['item_id'] === $item_id ) {
				$numbers[] = $entry['number'];
			}
		}
		if ( empty( $numbers ) ) {
			wp_die( 'Nota de abono no encontrada.', '', array( 'response' => 404 ) );
		}
		$filename = 'credit-note-' . implode( '-', $numbers ) . '.pdf';
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $filename ) . '"' );
	header( 'Content-Length: ' . strlen( $pdf_data ) );

	echo $pdf_data;
	exit;
}
