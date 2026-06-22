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
 * MGMIT_WEBHOOK_NEW_ORDER_TTL segundos tras la creación del pedido.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'MGMIT_WEBHOOK_NEW_ORDER_TTL' ) ) {
    define( 'MGMIT_WEBHOOK_NEW_ORDER_TTL', 120 );
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
    if ( $age < MGMIT_WEBHOOK_NEW_ORDER_TTL ) {
        return false;
    }

    return $should_deliver;
}
