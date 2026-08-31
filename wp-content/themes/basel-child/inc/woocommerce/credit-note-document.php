<?php
/**
 * Fase 1 — Registro del tipo de documento "credit-note" (Storno) en
 * PDF Invoices & Packing Slips for WooCommerce (WPO WCPDF).
 *
 * Ver plan: docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md
 *
 * Solo registra el tipo de documento y su numeración propia (independiente
 * de la de "Factura"), reutilizando el motor de settings/numeración/PDF que
 * ya trae el plugin. No genera nada automáticamente todavía — eso es la
 * Fase 3, en webhook-filters.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wpo_wcpdf_document_classes', 'mgmit_register_credit_note_document' );

/**
 * Registra WPO\IPS\Documents\CreditNote junto a Invoice/PackingSlip.
 *
 * @param array $document_classes
 * @return array
 */
function mgmit_register_credit_note_document( $document_classes ) {
	// Si el plugin de facturas no está activo, no hacemos nada.
	if ( ! class_exists( '\WPO\IPS\Documents\OrderDocumentMethods' ) ) {
		return $document_classes;
	}

	if ( ! class_exists( '\WPO\IPS\Documents\CreditNote' ) ) {
		require_once __DIR__ . '/class-credit-note.php';
	}

	$document_classes['\WPO\IPS\Documents\CreditNote'] = new \WPO\IPS\Documents\CreditNote();

	return $document_classes;
}
