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
 * que es donde enviamos el email de confirmación de baja (ver PASO 2).
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
 * cuando el nivel ya está guardado en DB. Aquí enviamos el email de
 * confirmación de baja al socio. La notificación a HubSpot la gestiona
 * Stripe directamente mediante su propio webhook.
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

    MGMIT_Webhook_Sender::debug_log( 'on_level_changed: member_id=' . $member_id . ' from=' . $from_level . ' to=' . $to_level . ' — enviando email de baja' );
    mgmit_bridge_send_cancellation_email( $member_id );
}

/**
 * Envía el email de confirmación de baja al socio.
 *
 * @param int $member_id ID del miembro SWPM ya degradado al nivel gratuito.
 */
function mgmit_bridge_send_cancellation_email( $member_id ) {
    $member = SwpmMemberUtils::get_user_by_id( $member_id );

    if ( ! $member || empty( $member->email ) ) {
        MGMIT_Webhook_Sender::debug_log( 'send_cancellation_email: miembro no encontrado o sin email, id=' . $member_id );
        return;
    }

    $email      = sanitize_email( $member->email );
    $first_name = sanitize_text_field( $member->first_name );
    $last_name  = sanitize_text_field( $member->last_name );

    if ( ! is_email( $email ) ) {
        MGMIT_Webhook_Sender::debug_log( 'send_cancellation_email: email inválido para member_id=' . $member_id );
        return;
    }

    $salutation      = mgmit_bridge_build_salutation_for_member( $member );
    $greeting_line   = $salutation['greeting'];
    $header_img_url  = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';

    $body_html = mgmit_bridge_render_template( 'body-cancellation', compact( 'greeting_line', 'header_img_url' ) );

    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );

    $subject = 'Bestätigung Ihrer Kündigung bei der MeGeMIT';

    $sent = wp_mail( $email, $subject, $body_html, $headers );

    if ( $sent ) {
        MGMIT_Webhook_Sender::debug_log( 'send_cancellation_email: enviado OK a ' . $email );
    } else {
        MGMIT_Webhook_Sender::debug_log( 'send_cancellation_email: wp_mail() devolvió false para ' . $email );
    }
}
