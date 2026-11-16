<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Flujo de notificaciones de renovación anual de membresías.
 *
 * - Cron diario que comprueba si hay que enviar aviso a 45, 30, 15 o 1 día/s antes del 31/12.
 * - Solo envía si el usuario no ha renovado ya (mgmit_membership_valid_until apunta al año actual).
 * - Registra cada aviso enviado para evitar duplicados.
 * - SEPA: informa de renovación automática. Transfer: informa de caducidad + botón de renovar.
 */

// ── Registro del evento cron ──────────────────────────────────────────────────

add_action( 'wp', 'mgmit_renewal_schedule_cron' );

function mgmit_renewal_schedule_cron() {
    if ( ! wp_next_scheduled( 'mgmit_renewal_daily_check' ) ) {
        wp_schedule_event( time(), 'daily', 'mgmit_renewal_daily_check' );
    }
}

add_action( 'mgmit_renewal_daily_check', 'mgmit_renewal_run_daily_check' );

// ── Lógica principal del cron ─────────────────────────────────────────────────

function mgmit_renewal_run_daily_check() {
    $timezone   = new DateTimeZone( 'Europe/Madrid' );
    $now        = new DateTime( 'now', $timezone );
    $year       = (int) $now->format( 'Y' );
    $dec_31     = new DateTime( $year . '-12-31 23:59:59', $timezone );
    $diff       = $now->diff( $dec_31 );
    $days_left  = (int) $diff->days;

    // Solo actuar en ventanas de aviso
    $notice_days = array( 45, 30, 15, 1 );
    if ( ! in_array( $days_left, $notice_days, true ) ) {
        return;
    }

    MGMIT_Webhook_Sender::debug_log( 'mgmit_renewal_daily_check: días restantes=' . $days_left );

    // Obtener todos los usuarios con mgmit_membership_valid_until del año actual
    $users = get_users( array(
        'meta_key'     => 'mgmit_membership_valid_until',
        'meta_value'   => $year . '-12-31',
        'meta_compare' => '=',
        'fields'       => array( 'ID', 'user_email', 'display_name' ),
    ) );

    foreach ( $users as $user ) {
        mgmit_renewal_process_user( $user, $days_left, $year );
    }
}

// ── Procesado por usuario ─────────────────────────────────────────────────────

function mgmit_renewal_process_user( $user, $days_left, $year ) {
    $wp_user_id     = $user->ID;
    $payment_method = get_user_meta( $wp_user_id, 'mgmit_payment_method', true );

    // Solo avisar a SEPA y Transfer; 'card' no aplica a este flujo
    if ( ! in_array( $payment_method, array( 'sepa', 'transfer' ), true ) ) {
        return;
    }

    // Comprobar si este aviso ya fue enviado
    $notices_sent = get_user_meta( $wp_user_id, 'mgmit_renewal_notices_sent', true );
    if ( ! is_array( $notices_sent ) ) {
        $notices_sent = array();
    }
    if ( in_array( $days_left, $notices_sent, true ) ) {
        return;
    }

    // Obtener datos del miembro SWPM para el saludo
    $gender     = mgmit_renewal_get_member_gender( $wp_user_id );
    $swpm_names = mgmit_renewal_get_member_names( $wp_user_id );
    $first_name = $swpm_names['first_name'];
    $last_name  = $swpm_names['last_name'];

    if ( 'female' === $gender ) {
        $greeting_line = 'Sehr geehrte Frau ' . $last_name;
    } elseif ( 'male' === $gender ) {
        $greeting_line = 'Sehr geehrter Herr ' . $last_name;
    } else {
        $greeting_line = 'Sehr geehrte/r ' . ( $last_name ? $last_name : $first_name );
    }

    $header_img_url = get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
    $valid_until    = '31.12.' . $year;

    $sent = false;

    if ( 'sepa' === $payment_method ) {
        $deactivate_url  = home_url( '/mitgliedschaft-deaktivieren/' );
        $next_year       = $year + 1;
        $renewal_charge_date = '1. Januar ' . $next_year;
        $body_html       = mgmit_bridge_render_template( 'renewal-sepa', compact(
            'greeting_line', 'header_img_url', 'valid_until', 'days_left', 'deactivate_url', 'renewal_charge_date'
        ) );
        $subject         = 'Ihre MeGeMIT-Mitgliedschaft wird am 1. Januar ' . $next_year . ' automatisch verlängert';
        $sent            = mgmit_renewal_send_email( $user->user_email, $subject, $body_html );
    } elseif ( 'transfer' === $payment_method ) {
        $renewal_url = mgmit_renewal_get_renewal_button_url();
        $body_html   = mgmit_bridge_render_template( 'renewal-transfer', compact(
            'greeting_line', 'header_img_url', 'valid_until', 'days_left', 'renewal_url'
        ) );
        $subject     = 'Ihre MeGeMIT-Mitgliedschaft läuft am 31.12.' . $year . ' ab';
        $sent        = mgmit_renewal_send_email( $user->user_email, $subject, $body_html );

        // Activar botón en área privada a partir del día 45
        if ( $days_left <= 45 ) {
            update_user_meta( $wp_user_id, 'mgmit_show_renewal_button', '1' );
        }
    }

    if ( $sent ) {
        $notices_sent[] = $days_left;
        update_user_meta( $wp_user_id, 'mgmit_renewal_notices_sent', $notices_sent );
        MGMIT_Webhook_Sender::debug_log(
            'mgmit_renewal: aviso día ' . $days_left . ' enviado a user=' . $wp_user_id .
            ' method=' . $payment_method
        );
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function mgmit_renewal_send_email( $to, $subject, $body_html ) {
    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );
    return wp_mail( $to, $subject, $body_html, $headers );
}

function mgmit_renewal_get_member_gender( $wp_user_id ) {
    global $wpdb;
    $email  = get_userdata( $wp_user_id )->user_email;
    $result = $wpdb->get_var( $wpdb->prepare(
        "SELECT gender FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s LIMIT 1",
        $email
    ) );
    return $result ? sanitize_text_field( $result ) : '';
}

function mgmit_renewal_get_member_names( $wp_user_id ) {
    global $wpdb;
    $email  = get_userdata( $wp_user_id )->user_email;
    $result = $wpdb->get_row( $wpdb->prepare(
        "SELECT first_name, last_name FROM {$wpdb->prefix}swpm_members_tbl WHERE email = %s LIMIT 1",
        $email
    ) );
    return array(
        'first_name' => $result ? sanitize_text_field( $result->first_name ) : '',
        'last_name'  => $result ? sanitize_text_field( $result->last_name )  : '',
    );
}

function mgmit_renewal_get_renewal_button_url() {
    // URL de la página donde está el botón de pago SWPM para renovación
    // Ajustar al slug real de la página de renovación
    return home_url( '/mitgliedschaft-erneuern/' );
}

/**
 * Fuerza la ejecución del cron desde WP-Admin para testing.
 * Uso: añadir ?mgmit_force_renewal_check=1 en el navegador (solo admins).
 */
add_action( 'init', 'mgmit_renewal_force_check_debug' );

function mgmit_renewal_force_check_debug() {
    if ( ! isset( $_GET['mgmit_force_renewal_check'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    mgmit_renewal_run_daily_check();
    wp_die( 'mgmit_renewal_daily_check ejecutado. Revisa el log de SWPM.' );
}
