<?php
/**
 * Email al cliente con la nota de abono (Storno) al cancelar o reembolsar
 * un pedido, con copia oculta (BCC) interna. Ver docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md.
 *
 * Cancelación ('cancelled'): WooCommerce no tiene email nativo al cliente
 * (solo 'cancelled_order', que es admin-only), así que se construye y envía
 * el email entero aquí, con la nota de abono adjunta.
 *
 * Reembolso ('refunded'): WooCommerce SÍ tiene email nativo al cliente
 * ('customer_refunded_order'). Ese email ya puede llevar la nota de abono
 * adjunta activando wp-admin → Facturas PDF → Documentos → Credit Note →
 * Attach to: → Pedido reembolsado (sin código). Lo que SÍ hace falta aquí es
 * añadirle el BCC interno, porque WooCommerce no lo soporta de forma nativa
 * en este sitio: el campo "BCC" por email individual solo existe si el
 * feature flag "email_improvements" de WooCommerce está activado, y aquí
 * está desactivado. Se añade vía el filtro woocommerce_email_headers,
 * scoped únicamente al email 'customer_refunded_order'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lista de direcciones en BCC para las comunicaciones de nota de abono.
 * Compartida entre el envío propio (cancelación) y el hook de headers
 * (reembolso), para no duplicar la lista en dos sitios.
 *
 * @return string[]
 */
function mgmit_credit_note_bcc_recipients() {
	return array_unique( array_filter( array(
		get_option( 'admin_email' ),
		'doris.hagleitner@megemit.org',
		// Copia de respaldo (2026-08-31): a Doris no le llegaban las copias
		// (dominio megemit.org, mismo servidor de envío) — pendiente de
		// investigar del lado del servidor de correo / buzón de Doris.
		'santi.bartolome.martinez@labolife.com',
	) ) );
}

add_filter( 'woocommerce_email_headers', 'mgmit_add_bcc_to_refunded_order_email', 10, 4 );

/**
 * Añade el BCC interno al email nativo de WooCommerce "Pedido reembolsado",
 * ya que ese email no soporta BCC por defecto en este sitio (ver docblock).
 *
 * @param string   $header
 * @param string   $email_id
 * @param mixed    $object
 * @param WC_Email $email
 * @return string
 */
function mgmit_add_bcc_to_refunded_order_email( $header, $email_id, $object, $email ) {
	if ( 'customer_refunded_order' !== $email_id ) {
		return $header;
	}

	foreach ( mgmit_credit_note_bcc_recipients() as $bcc_recipient ) {
		$header .= 'Bcc: ' . $bcc_recipient . "\r\n";
	}

	return $header;
}

add_action( 'woocommerce_order_status_cancelled', 'mgmit_send_credit_note_email_on_cancel' );

function mgmit_send_credit_note_email_on_cancel( $order_id ) {
	if ( ! function_exists( 'wcpdf_get_document' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$to = $order->get_billing_email();
	if ( empty( $to ) ) {
		return;
	}

	$credit_note = wcpdf_get_document( 'credit-note', $order, true );
	if ( ! $credit_note || ! $credit_note->exists() ) {
		return;
	}

	// Evita reenvíos si el estado se toca más de una vez (p. ej. guardado manual del pedido).
	if ( $order->get_meta( '_mgmit_credit_note_email_sent' ) ) {
		return;
	}

	$tmp_dir = function_exists( 'get_temp_dir' ) ? get_temp_dir() : sys_get_temp_dir() . '/';
	$pdf_path = $tmp_dir . sanitize_file_name( $credit_note->get_filename() );

	if ( false === file_put_contents( $pdf_path, $credit_note->get_pdf() ) ) {
		return;
	}

	$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$subject       = sprintf( 'Stornorechnung zu Ihrer Bestellung #%s', $order->get_order_number() );

	$body  = '<p>Guten Tag ' . esc_html( $customer_name ) . ',</p>';
	$body .= '<p>Ihre Bestellung #' . esc_html( $order->get_order_number() ) . ' wurde storniert. ';
	$body .= 'Die zugehörige Stornorechnung finden Sie im Anhang dieser E-Mail.</p>';
	$body .= '<p>Mit freundlichen Grüßen<br>MeGeMIT</p>';

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);
	foreach ( mgmit_credit_note_bcc_recipients() as $bcc_recipient ) {
		$headers[] = 'Bcc: ' . $bcc_recipient;
	}

	$sent = wp_mail( $to, $subject, $body, $headers, array( $pdf_path ) );

	if ( $sent ) {
		$order->update_meta_data( '_mgmit_credit_note_email_sent', current_time( 'mysql' ) );
		$order->save_meta_data();
		$order->add_order_note( 'Nota de abono enviada por email al cliente (y en BCC al admin).' );
	} else {
		$order->add_order_note( 'ERROR: no se pudo enviar el email con la nota de abono.' );
	}

	if ( file_exists( $pdf_path ) ) {
		wp_delete_file( $pdf_path );
	}
}
