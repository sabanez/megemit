<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'swpm_payment_ipn_processed', 'mgmit_bridge_payment_completed', 10, 1 );

function mgmit_bridge_payment_completed( $ipn_data ) {
    $email = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    if ( empty( $email ) ) {
        return;
    }

    $wp_user_id = email_exists( $email );
    if ( ! $wp_user_id ) {
        $wp_user_id = 0;
    }

    $first_name = isset( $ipn_data['first_name'] ) ? sanitize_text_field( $ipn_data['first_name'] ) : '';
    $last_name  = isset( $ipn_data['last_name'] )  ? sanitize_text_field( $ipn_data['last_name'] )  : '';
    $txn_id     = isset( $ipn_data['txn_id'] )     ? sanitize_text_field( $ipn_data['txn_id'] )     : '';
    $subscr_id  = isset( $ipn_data['subscr_id'] )  ? sanitize_text_field( $ipn_data['subscr_id'] )  : '';
    $amount     = isset( $ipn_data['mc_gross'] )   ? sanitize_text_field( $ipn_data['mc_gross'] )   : '0.00';
    $gateway    = isset( $ipn_data['gateway'] )    ? sanitize_text_field( $ipn_data['gateway'] )    : '';

    $level_id   = 0;
    $level_name = '';
    if ( isset( $ipn_data['member_id'] ) && $ipn_data['member_id'] ) {
        $level_id = SwpmMemberUtils::get_membership_level_id_of_a_member( (int) $ipn_data['member_id'] );
    }
    if ( $level_id ) {
        $level_row  = SwpmUtils::get_membership_level_row_by_id( $level_id );
        $level_name = ( $level_row && isset( $level_row->alias ) ) ? $level_row->alias : '';
    }

    // Buscar el producto WooCommerce cuyo meta _swpm_membership_level coincide con el nivel.
    // El handler del webhook busca el producto en HubSpot por woo_id (ID de producto WooCommerce).
    $woo_product_id = 0;
    $woo_product_sku = '';
    if ( $level_id ) {
        $woo_posts = get_posts( array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
            'meta_key'       => '_swpm_membership_level',
            'meta_value'     => $level_id,
            'fields'         => 'ids',
        ) );
        if ( ! empty( $woo_posts ) ) {
            $woo_product_id  = (int) $woo_posts[0];
            $woo_product_sku = get_post_meta( $woo_product_id, '_sku', true );
        }
    }

    // ID único de orden: hash numérico del txn_id para evitar duplicados y no usar el user ID.
    $order_id     = abs( crc32( $txn_id ? $txn_id : ( $subscr_id . $wp_user_id ) ) );
    $order_number = $txn_id ? $txn_id : $subscr_id;

    $now_iso = gmdate( 'Y-m-d\TH:i:s' );

    $payload = array(
        'id'             => $order_id,
        'number'         => $order_number,
        'status'         => 'completed',
        'currency'       => 'EUR',
        'total'          => $amount,
        'transaction_id' => $txn_id,
        'payment_method' => $gateway,
        'customer_id'    => $wp_user_id,
        'created_via'    => 'swpm',
        'date_created'   => $now_iso,
        'date_paid'      => $now_iso,
        'billing'        => array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
        ),
        'line_items'     => array(
            array(
                'name'       => $level_name ? $level_name : 'Membresia',
                'product_id' => $woo_product_id ? $woo_product_id : $level_id,
                'quantity'   => 1,
                'total'      => $amount,
                'subtotal'   => $amount,
                'price'      => floatval( $amount ),
                'sku'        => $woo_product_sku ? $woo_product_sku : '',
            ),
        ),
        'meta_data'      => array(
            array( 'key' => '_stripe_intent_id',     'value' => $txn_id ),
            array( 'key' => '_stripe_customer_id',   'value' => $subscr_id ),
            array( 'key' => 'membership_level_id',   'value' => $level_id ),
            array( 'key' => 'membership_level_name', 'value' => $level_name ),
            array( 'key' => 'source',                'value' => 'swpm' ),
        ),
    );

    MGMIT_Webhook_Sender::send( $payload, 'order.created' );

    // Leer género del miembro SWPM para el saludo del email
    $gender = '';
    if ( isset( $ipn_data['member_id'] ) && $ipn_data['member_id'] ) {
        global $wpdb;
        $swpm_table = $wpdb->prefix . 'swpm_members_table';
        $gender_raw = $wpdb->get_var( $wpdb->prepare(
            "SELECT gender FROM {$swpm_table} WHERE member_id = %d LIMIT 1",
            (int) $ipn_data['member_id']
        ) );
        if ( $gender_raw ) {
            $gender = sanitize_text_field( $gender_raw );
        }
    }

    mgmit_bridge_send_membership_email( $ipn_data, $gender );
}
