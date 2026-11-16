<?php
/**
 * Filtros sobre los webhooks nativos de WooCommerce.
 *
 * Problema: al crear un pedido via checkout + Stripe, WooCommerce dispara
 * woocommerce_update_order justo después de woocommerce_new_order (dentro
 * de los mismos segundos) porque la confirmación de pago provoca una segunda
 * llamada a update() en el data store. Esto genera una entrada duplicada en
 * HubSpot (order.created + order.updated para el mismo pedido nuevo).
 *
 * Solución: bloquear la entrega del webhook order.updated durante los primeros
 * WEBHOOK_NEW_ORDER_TTL segundos tras la creación del pedido (ver config.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'woocommerce_webhook_should_deliver', 'mgmit_block_order_updated_on_new_order', 10, 3 );

function mgmit_block_order_updated_on_new_order( $should_deliver, $webhook, $arg ) {
    if ( 'order.updated' !== $webhook->get_topic() ) {
        return $should_deliver;
    }

    $order = wc_get_order( absint( $arg ) );
    if ( ! $order ) {
        return $should_deliver;
    }

    $date_created = $order->get_date_created();
    if ( ! $date_created ) {
        return $should_deliver;
    }

    $age = time() - $date_created->getTimestamp();
    if ( $age < WEBHOOK_NEW_ORDER_TTL ) {
        return false;
    }

    return $should_deliver;
}

/**
 * Añade la URL del PDF de factura (PDF Invoices & Packing Slips for WooCommerce)
 * al payload del webhook de pedidos, para que el receptor (vpsbridge) pueda
 * descargar/enlazar la factura sin credenciales de sesión.
 */
add_filter( 'woocommerce_webhook_payload', 'mgmit_add_invoice_url_to_webhook_payload', 10, 4 );

function mgmit_add_invoice_url_to_webhook_payload( $payload, $resource, $resource_id, $webhook_id ) {
    if ( 'order' !== $resource || ! function_exists( 'wcpdf_get_document' ) || ! function_exists( 'mgmit_get_invoice_pdf_url' ) ) {
        return $payload;
    }

    $order = wc_get_order( absint( $resource_id ) );
    if ( ! $order ) {
        return $payload;
    }

    $invoice_url = mgmit_get_invoice_pdf_url( $order->get_id() );
    if ( $invoice_url ) {
        $payload['invoice_pdf_url'] = $invoice_url;
    }

    // Fase 3 (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md): al cancelarse el pedido,
    // reemplazar el enlace por el de la nota de abono (Storno), generada
    // on-demand igual que la factura. Se reutiliza el mismo campo
    // "invoice_pdf_url" que HubSpot/vpsbridge ya procesa hoy -> no hace
    // falta coordinar ningún campo nuevo en destino.
    if ( 'cancelled' === $order->get_status() && function_exists( 'mgmit_get_storno_pdf_url' ) ) {
        $storno_url = mgmit_get_storno_pdf_url( $order->get_id() );
        if ( $storno_url ) {
            $payload['invoice_pdf_url'] = $storno_url;
        }
    }

    return $payload;
}
