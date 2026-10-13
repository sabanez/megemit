<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aviso de renovación a 30 y 15 días antes de la renovación de cada socio.
 *
 * La fecha de renovación es: fecha del último pago registrado en SWPM
 * (swpm_payments_tbl, gateway stripe-sca-subs) + 1 año. SWPM guarda un pago
 * nuevo en cada cobro (alta y renovaciones por el webhook invoice.payment_succeeded),
 * así que el pago más reciente marca siempre el ciclo vigente. Los socios sin
 * ningún pago de Stripe registrado (aún no migrados al nuevo proceso) no reciben aviso.
 *
 * Ventana: se avisa cuando faltan 30 días o menos (aviso de 30) y cuando faltan
 * 15 o menos (aviso de 15), una sola vez por ciclo. Así no se pierde el aviso si
 * el cron no corre el día exacto.
 *
 * No se avisa a los socios con suscripción cancelada (subscr_status = canceled)
 * ni cuando la fecha de renovación ya pasó o es hoy.
 *
 * Deduplicación: user meta con la fecha de renovación para la que ya se envió
 * cada aviso. Tras una renovación la fecha cambia y los avisos vuelven a ser posibles.
 */

add_action( 'wp', 'mgmit_renewal_reminder_schedule_cron' );

function mgmit_renewal_reminder_schedule_cron() {
    if ( ! wp_next_scheduled( 'mgmit_renewal_reminder_daily_check' ) ) {
        wp_schedule_event( time(), 'daily', 'mgmit_renewal_reminder_daily_check' );
    }
}

add_action( 'mgmit_renewal_reminder_daily_check', 'mgmit_renewal_reminder_run_daily_check' );

function mgmit_renewal_reminder_run_daily_check() {
    $timezone = wp_timezone();
    $today    = new DateTime( 'today', $timezone );

    $members = mgmit_renewal_reminder_get_members();

    if ( empty( $members ) ) {
        return;
    }

    foreach ( $members as $member ) {
        mgmit_renewal_reminder_process_member( $member, $today, $timezone );
    }
}

/**
 * Socios activos con su último pago de Stripe registrado en SWPM
 * (por member_id o por subscr_id). Sin pago de Stripe no aparece.
 *
 * @return array Filas con member_id, user_name, email, first_name, last_name, subscr_id, last_payment.
 */
function mgmit_renewal_reminder_get_members() {
    global $wpdb;

    return $wpdb->get_results(
        "SELECT m.member_id, m.user_name, m.email, m.first_name, m.last_name, m.subscr_id,
                MAX(p.txn_date) AS last_payment
         FROM {$wpdb->prefix}swpm_members_tbl m
         INNER JOIN {$wpdb->prefix}swpm_payments_tbl p
             ON p.gateway = 'stripe-sca-subs'
            AND p.txn_date <> '0000-00-00'
            AND (
                 ( p.member_id <> '' AND p.member_id = m.member_id )
                 OR ( p.subscr_id <> '' AND m.subscr_id LIKE CONCAT( p.subscr_id, '%' ) )
            )
         WHERE m.account_state = 'active'
         GROUP BY m.member_id"
    );
}

/**
 * Decide si toca avisar. Función pura (sin base de datos) para poder probarla.
 *
 * @param DateTime $today          Hoy (medianoche, zona del sitio).
 * @param string   $last_payment   Fecha del último pago (Y-m-d).
 * @param string   $sent_30_for    Fecha de renovación para la que ya se envió el aviso de 30.
 * @param string   $sent_15_for    Idem para el aviso de 15.
 * @return array|null array( 'notice' => 30|15, 'days_left' => int, 'renewal_key' => 'Y-m-d' ) o null.
 */
function mgmit_renewal_reminder_decide( DateTime $today, $last_payment, $sent_30_for, $sent_15_for ) {
    try {
        $renewal = new DateTime( $last_payment, $today->getTimezone() );
    } catch ( \Exception $e ) {
        return null;
    }
    $renewal->modify( '+1 year' );
    $renewal_key = $renewal->format( 'Y-m-d' );

    $days_left = (int) $today->diff( $renewal )->format( '%r%a' );

    // Renovación hoy o ya pasada (p. ej. cobro fallido): no avisar.
    if ( $days_left < 1 || $days_left > 30 ) {
        return null;
    }

    if ( $days_left <= 15 ) {
        if ( $sent_15_for === $renewal_key ) {
            return null;
        }
        $notice = 15;
    } else {
        if ( $sent_30_for === $renewal_key ) {
            return null;
        }
        $notice = 30;
    }

    return array(
        'notice'      => $notice,
        'days_left'   => $days_left,
        'renewal_key' => $renewal_key,
    );
}

/**
 * ¿La suscripción de Stripe de este socio está cancelada en SWPM?
 *
 * @param string $subscr_id Valor de subscr_id del socio ("sub_xxx" o "sub_xxx|123").
 */
function mgmit_renewal_reminder_is_cancelled( $subscr_id ) {
    $sub_id = trim( explode( '|', (string) $subscr_id )[0] );
    if ( '' === $sub_id || ! class_exists( 'SWPM_Utils_Subscriptions' ) ) {
        return false;
    }

    $cpt_id = SWPM_Utils_Subscriptions::get_subscription_agreement_cpt_id_by_subs_id( $sub_id );
    if ( ! $cpt_id ) {
        return false;
    }

    return in_array( get_post_meta( $cpt_id, 'subscr_status', true ), array( 'canceled', 'cancelled' ), true );
}

function mgmit_renewal_reminder_process_member( $member, DateTime $today, DateTimeZone $timezone ) {
    if ( empty( $member->email ) || ! is_email( $member->email ) ) {
        return;
    }

    $wp_user_id = email_exists( $member->email );
    if ( ! $wp_user_id ) {
        $wp_user    = get_user_by( 'login', $member->user_name );
        $wp_user_id = $wp_user ? $wp_user->ID : 0;
    }
    if ( ! $wp_user_id ) {
        return;
    }

    $decision = mgmit_renewal_reminder_decide(
        $today,
        $member->last_payment,
        (string) get_user_meta( $wp_user_id, 'mgmit_renewal_reminder_30_sent_for', true ),
        (string) get_user_meta( $wp_user_id, 'mgmit_renewal_reminder_15_sent_for', true )
    );
    if ( ! $decision ) {
        return;
    }

    if ( mgmit_renewal_reminder_is_cancelled( $member->subscr_id ) ) {
        return;
    }

    $meta_key = 30 === $decision['notice'] ? 'mgmit_renewal_reminder_30_sent_for' : 'mgmit_renewal_reminder_15_sent_for';
    $sent     = mgmit_renewal_reminder_send_email( $member->email, $member->first_name, $member->last_name, $member );

    if ( $sent ) {
        update_user_meta( $wp_user_id, $meta_key, $decision['renewal_key'] );
        MGMIT_Webhook_Sender::debug_log(
            'renewal_reminder: aviso de ' . $decision['notice'] . ' enviado a ' . $member->email .
            ' (quedan ' . $decision['days_left'] . ' días, renovación ' . $decision['renewal_key'] . ')'
        );
    } else {
        MGMIT_Webhook_Sender::debug_log( 'renewal_reminder: wp_mail() devolvió false para ' . $member->email );
    }
}

function mgmit_renewal_reminder_send_email( $email, $first_name, $last_name, $member = null ) {
    $salutation    = $member
        ? mgmit_bridge_build_salutation_for_member( $member )
        : mgmit_bridge_build_salutation( $email, $first_name, $last_name );
    $greeting_line = $salutation['greeting'];

    $header_img_url = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';

    $body_html = mgmit_bridge_render_template( 'body-renewal-reminder', compact( 'greeting_line', 'header_img_url' ) );

    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );

    $subject = 'Ankündigung: Ihre MeGeMIT-Mitgliedschaft verlängert sich bald automatisch';

    return wp_mail( $email, $subject, $body_html, $headers );
}
