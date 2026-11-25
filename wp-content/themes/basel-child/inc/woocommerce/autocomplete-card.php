<?php
/**
 * Autocompletar pedidos pagados con tarjeta (Stripe).
 *
 * Cuando Stripe confirma el cobro con tarjeta, WooCommerce llama a
 * payment_complete() y el pedido pasa a "Procesando". Aquí se cambia ese estado
 * destino a "Completado" para que el flujo posterior (matrícula en Moodle,
 * email de pedido completado) no espere al administrador.
 *
 * Pedidos gratuitos (total = 0, incluye cupones del 100 %): no hay pago que
 * validar, así que se completan siempre al momento, sin interruptor y sin
 * excepciones (también membresías SWPM). Reglas de abajo = solo tarjeta:
 *
 *  - Solo si MGMIT_AUTOCOMPLETE_CARD es true (config.php; apagado por defecto).
 *  - Solo método de pago 'stripe' (tarjeta). Quedan fuera Klarna, SEPA, PayPal, BACS.
 *  - Quedan fuera los pedidos con alguna membresía SWPM (siguen con revisión manual).
 *
 * Para tarjeta, el filtro solo se ejecuta con el cobro confirmado, así que nunca
 * completa un pedido con pago pendiente.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'woocommerce_payment_complete_order_status', 'mgmit_autocomplete_card_status', 20, 3 );

/**
 * @param string          $status   Estado destino calculado por WooCommerce.
 * @param int             $order_id ID del pedido.
 * @param WC_Order|false  $order    Pedido.
 * @return string
 */
function mgmit_autocomplete_card_status( $status, $order_id, $order = false ) {
    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }
    if ( ! $order ) {
        return $status;
    }

    // Pedido gratuito: nada que cobrar, se valida al momento.
    if ( 'processing' === $status && 0.0 === (float) $order->get_total() ) {
        $order->add_order_note( 'Autocompletado: pedido gratuito, sin pago que validar.' );
        return 'completed';
    }

    if ( ! defined( 'MGMIT_AUTOCOMPLETE_CARD' ) || ! MGMIT_AUTOCOMPLETE_CARD ) {
        return $status;
    }

    // Solo tarjeta de Stripe.
    if ( 'stripe' !== $order->get_payment_method() ) {
        return $status;
    }

    // Solo si WooCommerce iba a dejarlo en Procesando (no pisar otros estados).
    if ( 'processing' !== $status ) {
        return $status;
    }

    // Excluir pedidos con membresía SWPM.
    if ( function_exists( 'swpm_get_level_from_attribute' ) ) {
        foreach ( $order->get_items() as $item ) {
            if ( swpm_get_level_from_attribute( $item->get_product_id() ) ) {
                return $status;
            }
        }
    }

    $order->add_order_note( 'Autocompletado: pago con tarjeta Stripe confirmado.' );

    return 'completed';
}
