<?php
/**
 * Envío de email al cliente con la nota de abono (Storno) cuando un pedido
 * se cancela. WooCommerce no tiene un email nativo al cliente para pedidos
 * cancelados (solo 'cancelled_order', que es admin-only) — ver
 * docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md, Fase 4.
 *
 * El caso de reembolso ('refunded') NO necesita este archivo: WooCommerce sí
 * tiene un email nativo al cliente ('customer_refunded_order'), y ya puede
 * adjuntarse la nota de abono desde wp-admin → Facturas PDF → Documentos →
 * Credit Note → Attach to: → Pedido reembolsado, sin código adicional.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

	// BCC: admin_email de WordPress + Doris (equivalente a "Storno-Rechnung an" de Solve).
	$bcc_recipients = array_unique( array_filter( array(
		get_option( 'admin_email' ),
		'doris.hagleitner@megemit.org',
	) ) );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);
	foreach ( $bcc_recipients as $bcc_recipient ) {
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
