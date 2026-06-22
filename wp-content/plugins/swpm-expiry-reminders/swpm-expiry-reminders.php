<?php
/**
 * Plugin Name: SWPM Expiry Reminders
 * Description: Sends membership expiry reminder emails at configurable days before expiration.
 * Version:     1.2.0
 * Requires PHP: 7.4
 * Author:      MeGeMIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SWPM_ER_PATH', plugin_dir_path( __FILE__ ) );
define( 'SWPM_ER_VERSION', '1.2.0' );

require_once SWPM_ER_PATH . 'views/email-template.php';

// ── Admin UI ──────────────────────────────────────────────────────────────────

add_action( 'admin_menu', 'swpm_er_register_submenu', 20 );

function swpm_er_register_submenu() {
    add_submenu_page(
        'simple_wp_membership',
        __( 'Expiry Reminders', 'swpm-er' ),
        __( 'Expiry Reminders', 'swpm-er' ),
        'manage_options',
        'swpm_expiry_reminders',
        'swpm_er_render_settings_page'
    );
}

function swpm_er_render_settings_page() {
    include SWPM_ER_PATH . 'views/admin-settings.php';
}

// ── Save settings ─────────────────────────────────────────────────────────────

add_action( 'admin_post_swpm_er_save_settings', 'swpm_er_save_settings' );

function swpm_er_save_settings() {
    check_admin_referer( 'swpm_er_settings_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'No tienes permisos suficientes.', 'swpm-er' ) );
    }

    update_option( 'swpm_expiry_reminders_enabled', isset( $_POST['swpm_expiry_reminders_enabled'] ) ? 1 : 0 );

    $days_raw = isset( $_POST['swpm_expiry_reminder_days'] ) ? sanitize_text_field( $_POST['swpm_expiry_reminder_days'] ) : '45,30,15';
    $days_arr = array_filter( array_map( 'intval', explode( ',', $days_raw ) ) );
    update_option( 'swpm_expiry_reminder_days', implode( ',', $days_arr ) );

    update_option( 'swpm_expiry_renewal_url',   esc_url_raw( isset( $_POST['swpm_expiry_renewal_url'] ) ? $_POST['swpm_expiry_renewal_url'] : '' ) );
    update_option( 'swpm_expiry_email_subject', sanitize_text_field( isset( $_POST['swpm_expiry_email_subject'] ) ? $_POST['swpm_expiry_email_subject'] : '' ) );
    update_option( 'swpm_expiry_email_body',    wp_kses_post( isset( $_POST['swpm_expiry_email_body'] ) ? $_POST['swpm_expiry_email_body'] : '' ) );
    update_option( 'swpm_expiry_from_email',    sanitize_email( isset( $_POST['swpm_expiry_from_email'] ) ? $_POST['swpm_expiry_from_email'] : '' ) );
    update_option( 'swpm_expiry_from_name',     sanitize_text_field( isset( $_POST['swpm_expiry_from_name'] ) ? $_POST['swpm_expiry_from_name'] : '' ) );

    // Scope: all / specific
    $scope = ( isset( $_POST['swpm_expiry_reminder_scope'] ) && $_POST['swpm_expiry_reminder_scope'] === 'specific' ) ? 'specific' : 'all';
    update_option( 'swpm_expiry_reminder_scope', $scope );

    $level_ids = array();
    if ( $scope === 'specific' && isset( $_POST['swpm_expiry_reminder_level_ids'] ) && is_array( $_POST['swpm_expiry_reminder_level_ids'] ) ) {
        $level_ids = array_map( 'intval', $_POST['swpm_expiry_reminder_level_ids'] );
        $level_ids = array_filter( $level_ids );
    }
    update_option( 'swpm_expiry_reminder_level_ids', $level_ids );

    wp_redirect( add_query_arg( array( 'page' => 'swpm_expiry_reminders', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
    exit;
}

// ── Previsualización del email ────────────────────────────────────────────────

add_action( 'admin_post_swpm_er_preview_email', 'swpm_er_preview_email' );

function swpm_er_preview_email() {
    check_admin_referer( 'swpm_er_settings_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'No tienes permisos suficientes.', 'swpm-er' ) );
    }

    $subject_tpl = isset( $_POST['swpm_expiry_email_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['swpm_expiry_email_subject'] ) ) : swpm_er_default_subject();
    $body_tpl    = isset( $_POST['swpm_expiry_email_body'] )    ? wp_kses_post( wp_unslash( $_POST['swpm_expiry_email_body'] ) )       : swpm_er_default_body();

    $placeholders = array(
        '{first_name}'       => 'Max',
        '{last_name}'        => 'Mustermann',
        '{email}'            => 'max.mustermann@example.com',
        '{membership_level}' => 'Premium',
        '{expiry_date}'      => date_i18n( get_option( 'date_format' ), strtotime( '+15 days' ) ),
        '{renewal_url}'      => get_option( 'swpm_expiry_renewal_url', home_url( '/' ) ),
        '{days_remaining}'   => 15,
    );

    $subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject_tpl );
    $body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body_tpl );

    echo '<p style="font-family:Arial,sans-serif;background:#f4f4f4;padding:10px 20px;margin:0;"><strong>' . __( 'Betreff:', 'swpm-er' ) . '</strong> ' . esc_html( $subject ) . '</p>';
    echo swpm_er_render_email_html( $body );
    exit;
}

// ── Cron: enganche al evento daily de SWPM ────────────────────────────────────

add_action( 'swpm_daily_cron_event', 'swpm_er_check_reminders' );

function swpm_er_check_reminders() {
    if ( ! get_option( 'swpm_expiry_reminders_enabled', 0 ) ) {
        return;
    }

    if ( ! class_exists( 'SwpmUtils' ) || ! class_exists( 'SwpmMembershipLevel' ) ) {
        return;
    }

    $days_option   = get_option( 'swpm_expiry_reminder_days', '45,30,15' );
    $reminder_days = array_filter( array_map( 'intval', explode( ',', $days_option ) ) );

    if ( empty( $reminder_days ) ) {
        return;
    }

    global $wpdb;

    // Determinar qué niveles procesar según scope
    $scope             = get_option( 'swpm_expiry_reminder_scope', 'all' );
    $scoped_level_ids  = get_option( 'swpm_expiry_reminder_level_ids', array() );

    if ( $scope === 'specific' && empty( $scoped_level_ids ) ) {
        return;
    }

    // Niveles con expiración configurada (subscription_period definido)
    $levels_with_expiry = $wpdb->get_col(
        "SELECT id FROM {$wpdb->prefix}swpm_membership_tbl
         WHERE subscription_period != '' AND subscription_period != '0'"
    );

    if ( empty( $levels_with_expiry ) ) {
        return;
    }

    // Intersección con los niveles seleccionados si scope = specific
    if ( $scope === 'specific' ) {
        $levels_with_expiry = array_values( array_intersect( $levels_with_expiry, $scoped_level_ids ) );
        if ( empty( $levels_with_expiry ) ) {
            return;
        }
    }

    $level_ids_sql = implode( ',', array_map( 'intval', $levels_with_expiry ) );

    // Medianoche de hoy en la timezone del sitio para comparación precisa
    $today_midnight = strtotime( date( 'Y-m-d', current_time( 'timestamp' ) ) . ' 00:00:00' );

    $counter = 0;
    do {
        $members = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT member_id, membership_level, subscription_starts, account_state, email, first_name, last_name
                 FROM {$wpdb->prefix}swpm_members_tbl
                 WHERE account_state = 'active'
                 AND membership_level IN ({$level_ids_sql})
                 LIMIT %d, 100",
                $counter
            )
        );

        if ( empty( $members ) ) {
            break;
        }

        foreach ( $members as $member ) {
            $expiry_timestamp = SwpmUtils::get_expiration_timestamp( $member );

            if ( ! $expiry_timestamp || $expiry_timestamp === PHP_INT_MAX ) {
                continue;
            }

            // Normalizar expiración a medianoche para comparación por días exactos
            $expiry_midnight  = strtotime( date( 'Y-m-d', $expiry_timestamp ) . ' 00:00:00' );
            $days_remaining   = (int) round( ( $expiry_midnight - $today_midnight ) / DAY_IN_SECONDS );

            foreach ( $reminder_days as $target_days ) {
                if ( $days_remaining !== $target_days ) {
                    continue;
                }

                $flag_key   = 'swpm_expiry_reminder_' . $target_days;
                $wp_user    = get_user_by( 'email', $member->email );
                $wp_user_id = $wp_user ? $wp_user->ID : 0;

                if ( $wp_user_id && get_user_meta( $wp_user_id, $flag_key, true ) ) {
                    continue;
                }

                swpm_er_send_reminder_email( $member, $expiry_timestamp, $days_remaining );

                if ( $wp_user_id ) {
                    update_user_meta( $wp_user_id, $flag_key, current_time( 'mysql' ) );
                }
            }
        }

        $counter += 100;
    } while ( count( $members ) === 100 );
}

// ── Envío del email ───────────────────────────────────────────────────────────

function swpm_er_send_reminder_email( $member, $expiry_timestamp, $days_remaining ) {
    $subject_tpl = get_option( 'swpm_expiry_email_subject', swpm_er_default_subject() );
    $body_tpl    = get_option( 'swpm_expiry_email_body',    swpm_er_default_body() );
    $from_email  = get_option( 'swpm_expiry_from_email',    get_option( 'admin_email' ) );
    $from_name   = get_option( 'swpm_expiry_from_name',     get_bloginfo( 'name' ) );
    $renewal_url = get_option( 'swpm_expiry_renewal_url',   '' );

    $level_name = '';
    if ( class_exists( 'SwpmPermission' ) ) {
        $permission = SwpmPermission::get_instance( $member->membership_level );
        $level_name = $permission ? $permission->get( 'alias' ) : '';
    }

    $expiry_date = date_i18n( get_option( 'date_format' ), $expiry_timestamp );

    $placeholders = array(
        '{first_name}'       => $member->first_name,
        '{last_name}'        => $member->last_name,
        '{email}'            => $member->email,
        '{membership_level}' => $level_name,
        '{expiry_date}'      => $expiry_date,
        '{renewal_url}'      => $renewal_url,
        '{days_remaining}'   => $days_remaining,
    );

    $subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject_tpl );
    $body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body_tpl );
    $body    = swpm_er_render_email_html( $body );

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    );

    wp_mail( $member->email, $subject, $body, $headers );
}

// ── Limpiar flags al renovar ──────────────────────────────────────────────────

add_action( 'swpm_payment_ipn_processed', 'swpm_er_clear_reminder_flags' );

function swpm_er_clear_reminder_flags( $ipn_data ) {
    $email = isset( $ipn_data['payer_email'] ) ? $ipn_data['payer_email'] : '';
    if ( empty( $email ) ) {
        return;
    }

    $wp_user = get_user_by( 'email', $email );
    if ( ! $wp_user ) {
        return;
    }

    $days_option   = get_option( 'swpm_expiry_reminder_days', '45,30,15' );
    $reminder_days = array_filter( array_map( 'intval', explode( ',', $days_option ) ) );

    foreach ( $reminder_days as $days ) {
        delete_user_meta( $wp_user->ID, 'swpm_expiry_reminder_' . $days );
    }
}

// ── Plantillas por defecto ────────────────────────────────────────────────────

function swpm_er_default_subject() {
    return 'Ihre Mitgliedschaft läuft am {expiry_date} ab';
}

function swpm_er_default_body() {
    return '<p>Sehr geehrte(r) {first_name} {last_name},</p>

<p>Ihre Mitgliedschaft <strong>{membership_level}</strong> läuft am <strong>{expiry_date}</strong> ab (noch {days_remaining} Tage).</p>

<p>Um Ihre Mitgliedschaft zu verlängern, klicken Sie bitte auf den folgenden Link:</p>

<p><a href="{renewal_url}" style="background:#0073aa;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;">Mitgliedschaft verlängern</a></p>

<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>

<p>Mit freundlichen Grüßen</p>';
}
