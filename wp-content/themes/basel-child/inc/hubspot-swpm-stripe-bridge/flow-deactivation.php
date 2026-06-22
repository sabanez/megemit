<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PASO 1 — updated_post_meta
 *
 * SWPM escribe subscr_status='canceled' en la transacción justo después de que
 * Stripe confirma la cancelación (class.swpm-utils-subscription.php:648).
 * En ese momento degradamos el nivel del miembro al nivel gratuito.
 * update_membership_level_and_role dispara swpm_membership_level_changed,
 * que es donde enviamos el webhook (ver PASO 2).
 */
add_action( 'updated_post_meta', 'mgmit_bridge_watch_subscr_status', 10, 4 );

function mgmit_bridge_watch_subscr_status( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( 'subscr_status' !== $meta_key ) {
        return;
    }
    if ( ! in_array( $meta_value, array( 'canceled', 'cancelled' ), true ) ) {
        return;
    }
    if ( 'swpm_transactions' !== get_post_type( $post_id ) ) {
        return;
    }

    MGMIT_Webhook_Sender::debug_log( 'watch_subscr_status: subscr_status=' . $meta_value . ' post_id=' . $post_id );

    // Deduplicación: evitar doble procesado si admin-cancel y Stripe webhook coinciden
    $transient_key = 'mgmit_deact_txn_' . $post_id;
    if ( get_transient( $transient_key ) ) {
        MGMIT_Webhook_Sender::debug_log( 'watch_subscr_status: duplicado ignorado post_id=' . $post_id );
        return;
    }
    set_transient( $transient_key, 1, 300 );

    // Resolver member_id desde la transacción
    $member_id = (int) get_post_meta( $post_id, 'member_id', true );
    if ( ! $member_id ) {
        $subscr_id = get_post_meta( $post_id, 'subscr_id', true );
        if ( $subscr_id ) {
            $member_record = SwpmMemberUtils::get_user_by_subsriber_id( $subscr_id );
            if ( $member_record ) {
                $member_id = (int) $member_record->member_id;
            }
        }
    }

    if ( ! $member_id ) {
        MGMIT_Webhook_Sender::debug_log( 'watch_subscr_status: no se pudo resolver member_id para post_id=' . $post_id );
        return;
    }

    // Degradar al nivel gratuito. Esto dispara swpm_membership_level_changed (PASO 2)
    $free_level_id = (int) get_option( 'mgmit_bridge_free_level_id', 2 );
    MGMIT_Webhook_Sender::debug_log( 'watch_subscr_status: degradando member_id=' . $member_id . ' al nivel ' . $free_level_id );
    SwpmMemberUtils::update_membership_level_and_role( $member_id, $free_level_id );
}

/**
 * PASO 2 — swpm_membership_level_changed
 *
 * SWPM dispara este hook desde dentro de update_membership_level_and_role(),
 * cuando el nivel ya está guardado en DB. Aquí leemos el estado actualizado
 * y enviamos el webhook con datos correctos.
 */
add_action( 'swpm_membership_level_changed', 'mgmit_bridge_on_level_changed', 10, 1 );

function mgmit_bridge_on_level_changed( $data ) {
    $member_id  = isset( $data['member_id'] )  ? (int) $data['member_id']  : 0;
    $from_level = isset( $data['from_level'] ) ? (int) $data['from_level'] : 0;
    $to_level   = isset( $data['to_level'] )   ? (int) $data['to_level']   : 0;

    if ( ! $member_id ) {
        return;
    }

    $free_level_id = (int) get_option( 'mgmit_bridge_free_level_id', 2 );

    // Solo actuar si el cambio es hacia el nivel gratuito desde un nivel superior
    if ( $to_level !== $free_level_id || $from_level === $free_level_id ) {
        return;
    }

    MGMIT_Webhook_Sender::debug_log( 'on_level_changed: member_id=' . $member_id . ' from=' . $from_level . ' to=' . $to_level . ' — enviando webhook' );
    mgmit_bridge_send_deactivation_webhook( $member_id, $to_level );
}

/**
 * Construye el payload con el estado ya actualizado y lo envía al webhook externo.
 *
 * @param int $member_id   ID del miembro SWPM.
 * @param int $new_level_id Nivel al que se ha degradado (ya en DB).
 */
function mgmit_bridge_send_deactivation_webhook( $member_id, $new_level_id ) {
    $member = SwpmMemberUtils::get_user_by_id( $member_id );

    if ( ! $member ) {
        MGMIT_Webhook_Sender::debug_log( 'send_deactivation_webhook: miembro no encontrado id=' . $member_id );
        return;
    }

    $email      = sanitize_email( $member->email );
    $first_name = sanitize_text_field( $member->first_name );
    $last_name  = sanitize_text_field( $member->last_name );

    $wp_user_id = email_exists( $email );
    if ( ! $wp_user_id ) {
        $wp_user_id = 0;
    }

    // Nivel ya actualizado en DB — leemos desde la fuente para asegurar consistencia
    $level_id  = $new_level_id;
    $level_row = SwpmUtils::get_membership_level_row_by_id( $level_id );
    $level_name = ( $level_row && isset( $level_row->alias ) ) ? $level_row->alias : '';

    $subscr_id = sanitize_text_field( (string) SwpmMemberUtils::get_member_field_by_id( $member_id, 'subscr_id' ) );
    $now_iso   = gmdate( 'Y-m-d\TH:i:s' );

    $billing_phone    = '';
    $billing_company  = '';
    $billing_address  = '';
    $billing_city     = '';
    $billing_state    = '';
    $billing_postcode = '';
    $billing_country  = '';
    if ( $wp_user_id ) {
        $billing_phone    = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_phone',     true ) );
        $billing_company  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_company',   true ) );
        $billing_address  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_address_1', true ) );
        $billing_city     = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_city',      true ) );
        $billing_state    = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_state',     true ) );
        $billing_postcode = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_postcode',  true ) );
        $billing_country  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_country',   true ) );
    }

    // Limpiar caché de usuario para que roles reflejen el nivel actualizado
    clean_user_cache( $wp_user_id );
    $wp_user = $wp_user_id ? get_userdata( $wp_user_id ) : null;

    $payload = array(
        'id'                 => $wp_user_id,
        'email'              => $email,
        'first_name'         => $first_name,
        'last_name'          => $last_name,
        'username'           => $wp_user ? $wp_user->user_login : ( isset( $member->user_name ) ? $member->user_name : '' ),
        'role'               => $wp_user ? implode( ',', $wp_user->roles ) : '',
        'date_created'       => $now_iso,
        'date_modified'      => $now_iso,
        'is_paying_customer' => false,
        'billing'            => array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $billing_phone,
            'company'    => $billing_company,
            'address_1'  => $billing_address,
            'city'       => $billing_city,
            'state'      => $billing_state,
            'postcode'   => $billing_postcode,
            'country'    => $billing_country,
        ),
        'shipping'           => array(
            'first_name' => '',
            'last_name'  => '',
            'company'    => '',
            'address_1'  => '',
            'city'       => '',
            'state'      => '',
            'postcode'   => '',
            'country'    => '',
            'phone'      => '',
        ),
        'meta_data'          => array(
            array( 'key' => 'membership_level_id',   'value' => $level_id ),
            array( 'key' => 'membership_level_name', 'value' => $level_name ),
            array( 'key' => 'subscription_id',       'value' => $subscr_id ),
            array( 'key' => 'to_status',             'value' => 'inactive' ),
            array( 'key' => 'source',                'value' => 'swpm' ),
        ),
    );

    MGMIT_Webhook_Sender::debug_log( 'send_deactivation_webhook: enviando payload level_id=' . $level_id . ' level_name=' . $level_name );
    MGMIT_Webhook_Sender::send( $payload, 'customer.updated' );
}
