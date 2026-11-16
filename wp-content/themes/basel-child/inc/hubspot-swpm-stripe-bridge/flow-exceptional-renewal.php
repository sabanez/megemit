<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aviso excepcional de normalización de pagos.
 *
 * Fuera del funcionamiento normal de SWPM. Los socios del nivel de pago cuya membresía se gestionó
 * manualmente hasta el 13/10/2026 (fecha en que entra en funcionamiento el flujo nuevo de Stripe)
 * reciben un único email que les informa de que, a partir de ahora, deben suscribirse ellos mismos
 * desde la web (URL configurable).
 *
 * Destinatarios: socios del nivel configurado (3) cuya fecha de alta en ese nivel
 * (subscription_starts) es ANTERIOR a la fecha de corte (13/10/2026). Los de la fecha de corte
 * en adelante ya pasan por el flujo normal de SWPM y quedan fuera. Como red de seguridad también
 * se excluye a quien tenga un pago registrado en swpm_payments_tbl o un subscr_id de Stripe (sub_).
 * No se exige account_state activo: a la fecha de envío estarán 'expired'.
 *
 * Fecha de envío: una única fecha para todos (por defecto 31/01/2027), sin excepciones y sin
 * depender de la fecha de alta de cada socio.
 *
 * Seguridad: interruptor general apagado por defecto; sin URL no se envía nada; un único envío
 * por socio (option mgmit_exc_renewal_sent, member_id => fecha); envío por lotes con cron cada
 * 15 minutos y bloqueo para que dos ejecuciones no se solapen.
 */

define( 'MGMIT_EXC_RENEWAL_OPTION', 'mgmit_exc_renewal_settings' );
define( 'MGMIT_EXC_RENEWAL_SENT_OPTION', 'mgmit_exc_renewal_sent' );
define( 'MGMIT_EXC_RENEWAL_LOCK', 'mgmit_exc_renewal_lock' );
define( 'MGMIT_EXC_RENEWAL_CRON', 'mgmit_exc_renewal_cron' );

/* ------------------------------------------------------------------ */
/* Configuración                                                       */
/* ------------------------------------------------------------------ */

function mgmit_exc_renewal_defaults() {
    return array(
        'enabled'     => 0,
        'url'         => '',
        'level_id'    => 3,
        'cutoff_date' => '2026-10-13',
        'send_date'   => '2027-01-31',
        'batch_size'  => 50,
    );
}

function mgmit_exc_renewal_get_config() {
    $saved = get_option( MGMIT_EXC_RENEWAL_OPTION, array() );
    return array_merge( mgmit_exc_renewal_defaults(), is_array( $saved ) ? $saved : array() );
}

function mgmit_exc_renewal_valid_date( $value ) {
    if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $m ) ) {
        return false;
    }
    return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

function mgmit_exc_renewal_sanitize( $in ) {
    $in  = is_array( $in ) ? $in : array();
    $out = mgmit_exc_renewal_defaults();

    $out['url'] = esc_url_raw( trim( isset( $in['url'] ) ? (string) $in['url'] : '' ) );

    foreach ( array( 'cutoff_date', 'send_date' ) as $key ) {
        if ( isset( $in[ $key ] ) && mgmit_exc_renewal_valid_date( $in[ $key ] ) ) {
            $out[ $key ] = $in[ $key ];
        }
    }

    $out['level_id']   = max( 1, absint( isset( $in['level_id'] ) ? $in['level_id'] : $out['level_id'] ) );
    $out['batch_size'] = min( 200, max( 1, absint( isset( $in['batch_size'] ) ? $in['batch_size'] : $out['batch_size'] ) ) );

    $out['enabled'] = ! empty( $in['enabled'] ) ? 1 : 0;
    if ( $out['enabled'] && '' === $out['url'] ) {
        $out['enabled'] = 0;
        if ( function_exists( 'add_settings_error' ) ) {
            add_settings_error( 'mgmit_exc_renewal', 'no_url', 'No se puede activar el envío sin la URL de suscripción. El interruptor se ha dejado apagado.', 'error' );
        }
    }

    return $out;
}

/* ------------------------------------------------------------------ */
/* Selección y fechas                                                  */
/* ------------------------------------------------------------------ */

/**
 * Fecha de envío, única para todos los socios (medianoche, zona del sitio). Función pura.
 *
 * @param array        $cfg Configuración.
 * @param DateTimeZone $tz  Zona horaria del sitio.
 * @return DateTime
 */
function mgmit_exc_renewal_send_date( array $cfg, DateTimeZone $tz ) {
    return new DateTime( $cfg['send_date'], $tz );
}

/**
 * Socios candidatos (antes de calcular fechas ni mirar a quién se envió ya).
 *
 * @return array Filas de swpm_members_tbl.
 */
function mgmit_exc_renewal_get_candidates( array $cfg ) {
    global $wpdb;

    $members  = $wpdb->prefix . 'swpm_members_tbl';
    $payments = $wpdb->prefix . 'swpm_payments_tbl';

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $sql = $wpdb->prepare(
        "SELECT m.member_id, m.user_name, m.email, m.first_name, m.last_name, m.subscription_starts, m.account_state
         FROM {$members} m
         WHERE m.membership_level = %d
           AND m.subscription_starts IS NOT NULL
           AND m.subscription_starts <> '0000-00-00'
           AND m.subscription_starts < %s
           AND ( m.subscr_id IS NULL OR m.subscr_id NOT LIKE %s )
           AND NOT EXISTS (
               SELECT 1 FROM {$payments} p
               WHERE ( p.member_id <> '' AND p.member_id = m.member_id )
                  OR ( p.subscr_id <> '' AND m.subscr_id <> '' AND m.subscr_id LIKE CONCAT( p.subscr_id, '%%' ) )
           )
         ORDER BY m.subscription_starts, m.member_id",
        (int) $cfg['level_id'],
        $cfg['cutoff_date'],
        $wpdb->esc_like( 'sub_' ) . '%'
    );

    $rows = $wpdb->get_results( $sql );
    return is_array( $rows ) ? $rows : array();
}

/**
 * Candidatos con su estado de envío.
 *
 * @return array Cada elemento: member, send, sent (string|''), valid_email (bool), due (bool).
 */
function mgmit_exc_renewal_build_rows( array $cfg ) {
    $tz    = wp_timezone();
    $today = new DateTime( 'today', $tz );
    $send  = mgmit_exc_renewal_send_date( $cfg, $tz );
    $sent  = get_option( MGMIT_EXC_RENEWAL_SENT_OPTION, array() );
    $sent  = is_array( $sent ) ? $sent : array();
    $out   = array();

    foreach ( mgmit_exc_renewal_get_candidates( $cfg ) as $member ) {
        $id    = (string) $member->member_id;
        $out[] = array(
            'member'      => $member,
            'send'        => $send,
            'sent'        => isset( $sent[ $id ] ) ? (string) $sent[ $id ] : '',
            'valid_email' => ! empty( $member->email ) && is_email( $member->email ),
            'due'         => $today >= $send,
        );
    }

    return $out;
}

/* ------------------------------------------------------------------ */
/* Cron y envío por lotes                                              */
/* ------------------------------------------------------------------ */

add_filter( 'cron_schedules', 'mgmit_exc_renewal_cron_schedules' );
function mgmit_exc_renewal_cron_schedules( $schedules ) {
    $schedules['mgmit_every_15_min'] = array(
        'interval' => 900,
        'display'  => 'Cada 15 minutos (aviso excepcional MeGeMIT)',
    );
    return $schedules;
}

add_action( 'init', 'mgmit_exc_renewal_schedule_cron' );
function mgmit_exc_renewal_schedule_cron() {
    if ( ! wp_next_scheduled( MGMIT_EXC_RENEWAL_CRON ) ) {
        wp_schedule_event( time() + 300, 'mgmit_every_15_min', MGMIT_EXC_RENEWAL_CRON );
    }
}

add_action( MGMIT_EXC_RENEWAL_CRON, 'mgmit_exc_renewal_run' );

/**
 * Envía un lote a los socios cuya fecha de envío ya llegó y a los que aún no se ha enviado.
 *
 * @param int $limit Máximo de emails en esta ejecución (0 = tamaño de lote configurado).
 * @return array array( 'status' => string, 'sent' => int, 'failed' => int ).
 */
function mgmit_exc_renewal_run( $limit = 0 ) {
    $cfg = mgmit_exc_renewal_get_config();

    if ( empty( $cfg['enabled'] ) || '' === $cfg['url'] ) {
        return array( 'status' => 'disabled', 'sent' => 0, 'failed' => 0 );
    }
    if ( get_transient( MGMIT_EXC_RENEWAL_LOCK ) ) {
        return array( 'status' => 'locked', 'sent' => 0, 'failed' => 0 );
    }
    set_transient( MGMIT_EXC_RENEWAL_LOCK, 1, 10 * MINUTE_IN_SECONDS );

    $limit  = $limit > 0 ? (int) $limit : (int) $cfg['batch_size'];
    $sent_n = 0;
    $failed = 0;

    foreach ( mgmit_exc_renewal_build_rows( $cfg ) as $row ) {
        if ( $sent_n + $failed >= $limit ) {
            break;
        }
        if ( '' !== $row['sent'] || ! $row['due'] || ! $row['valid_email'] ) {
            continue;
        }

        $member = $row['member'];
        if ( mgmit_exc_renewal_send_email( $member, $cfg ) ) {
            $sent = get_option( MGMIT_EXC_RENEWAL_SENT_OPTION, array() );
            $sent = is_array( $sent ) ? $sent : array();

            $sent[ (string) $member->member_id ] = gmdate( 'Y-m-d H:i:s' );
            update_option( MGMIT_EXC_RENEWAL_SENT_OPTION, $sent, false );
            $sent_n++;
            MGMIT_Webhook_Sender::debug_log( 'exceptional_renewal: enviado a member_id=' . $member->member_id );
        } else {
            $failed++;
            MGMIT_Webhook_Sender::debug_log( 'exceptional_renewal: wp_mail() devolvió false para member_id=' . $member->member_id );
        }
    }

    delete_transient( MGMIT_EXC_RENEWAL_LOCK );

    return array( 'status' => 'ok', 'sent' => $sent_n, 'failed' => $failed );
}

/**
 * @param object|null $member      Fila de swpm_members_tbl (null en el envío de prueba).
 * @param array       $cfg         Configuración.
 * @param string      $override_to Destinatario alternativo (envío de prueba).
 */
function mgmit_exc_renewal_send_email( $member, array $cfg, $override_to = '' ) {
    $greeting_line = 'Sehr geehrte Damen und Herren';
    if ( '' === $override_to ) {
        $salutation    = mgmit_bridge_build_salutation_for_member( $member );
        $greeting_line = $salutation['greeting'];
    }

    $body_html = mgmit_bridge_render_template(
        'body-exceptional-renewal',
        array(
            'greeting_line'  => $greeting_line,
            'header_img_url' => get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg',
            'subscribe_url'  => $cfg['url'],
        )
    );

    $admin_email = sanitize_email( get_option( 'admin_email' ) );
    $from_name   = sanitize_text_field( get_option( 'blogname' ) );
    $headers     = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $admin_email . '>',
    );

    $subject = 'Ihre MeGeMIT-Mitgliedschaft: Abschluss künftig über unsere Website';
    $to      = $member ? $member->email : '';
    if ( '' !== $override_to ) {
        $to      = $override_to;
        $subject = '[TEST] ' . $subject;
    }

    return wp_mail( $to, $subject, $body_html, $headers );
}

/* ------------------------------------------------------------------ */
/* Página de administración                                            */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', 'mgmit_exc_renewal_admin_menu' );
add_action( 'admin_init', 'mgmit_exc_renewal_admin_init' );

function mgmit_exc_renewal_admin_menu() {
    add_options_page(
        'Aviso excepcional de renovación',
        'Aviso excepcional',
        'manage_options',
        'mgmit-exc-renewal',
        'mgmit_exc_renewal_admin_page'
    );
}

function mgmit_exc_renewal_admin_init() {
    register_setting(
        'mgmit_exc_renewal_group',
        MGMIT_EXC_RENEWAL_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'mgmit_exc_renewal_sanitize',
            'default'           => mgmit_exc_renewal_defaults(),
        )
    );
}

function mgmit_exc_renewal_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $cfg = mgmit_exc_renewal_get_config();
    $msg = '';

    if ( isset( $_POST['mgmit_exc_action'] ) && check_admin_referer( 'mgmit_exc_renewal_action' ) ) {
        $action = sanitize_key( wp_unslash( $_POST['mgmit_exc_action'] ) );

        if ( 'test' === $action ) {
            $to = isset( $_POST['mgmit_exc_test_to'] ) ? sanitize_email( wp_unslash( $_POST['mgmit_exc_test_to'] ) ) : '';
            if ( ! is_email( $to ) ) {
                $msg = 'Indica un email válido para la prueba.';
            } else {
                $ok  = mgmit_exc_renewal_send_email( null, $cfg, $to );
                $msg = $ok ? 'Email de prueba enviado a ' . $to . '.' : 'wp_mail() devolvió false. Revisa el log del bridge.';
            }
        } elseif ( 'run' === $action ) {
            if ( empty( $_POST['mgmit_exc_confirm'] ) ) {
                $msg = 'Marca la casilla de confirmación para ejecutar un lote real.';
            } else {
                $res = mgmit_exc_renewal_run();
                if ( 'disabled' === $res['status'] ) {
                    $msg = 'El envío está desactivado (interruptor apagado o sin URL). No se ha enviado nada.';
                } elseif ( 'locked' === $res['status'] ) {
                    $msg = 'Hay otra ejecución en curso. Inténtalo en unos minutos.';
                } else {
                    $msg = 'Lote ejecutado: ' . $res['sent'] . ' enviados, ' . $res['failed'] . ' con error.';
                }
            }
        }
    }

    $rows        = mgmit_exc_renewal_build_rows( $cfg );
    $n_total     = count( $rows );
    $n_sent      = 0;
    $n_due       = 0;
    $n_bad_email = 0;
    $n_pending   = 0;
    foreach ( $rows as $row ) {
        if ( '' !== $row['sent'] ) {
            $n_sent++;
            continue;
        }
        if ( ! $row['valid_email'] ) {
            $n_bad_email++;
            continue;
        }
        $n_pending++;
        if ( $row['due'] ) {
            $n_due++;
        }
    }
    $opt = esc_attr( MGMIT_EXC_RENEWAL_OPTION );
    ?>
    <div class="wrap">
        <h1>Aviso excepcional de renovación</h1>
        <p>Email único a los socios cuya membresía se gestionó manualmente hasta el corte: les informa de que, a partir de ahora, deben suscribirse ellos mismos desde la web.</p>

        <?php settings_errors( 'mgmit_exc_renewal' ); ?>
        <?php if ( '' !== $msg ) : ?>
            <div class="notice notice-info"><p><?php echo esc_html( $msg ); ?></p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'mgmit_exc_renewal_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Envío activado</th>
                    <td><label><input type="checkbox" name="<?php echo $opt; ?>[enabled]" value="1" <?php checked( $cfg['enabled'], 1 ); ?> /> Permitir el envío (interruptor general; apagado = no se envía nada)</label></td>
                </tr>
                <tr>
                    <th scope="row">URL de suscripción</th>
                    <td><input type="url" name="<?php echo $opt; ?>[url]" value="<?php echo esc_attr( $cfg['url'] ); ?>" class="regular-text" placeholder="https://…" />
                        <p class="description">Aparece en el email como botón y como enlace. Obligatoria para poder activar el envío.</p></td>
                </tr>
                <tr>
                    <th scope="row">ID del nivel de pago</th>
                    <td><input type="number" name="<?php echo $opt; ?>[level_id]" value="<?php echo (int) $cfg['level_id']; ?>" class="small-text" min="1" /></td>
                </tr>
                <tr>
                    <th scope="row">Fecha de corte</th>
                    <td><input type="date" name="<?php echo $opt; ?>[cutoff_date]" value="<?php echo esc_attr( $cfg['cutoff_date'] ); ?>" />
                        <p class="description">Alta en el nivel <strong>anterior</strong> a esta fecha = recibe el aviso. Alta desde esta fecha (incluida) = ya va por el flujo normal de SWPM y queda fuera.</p></td>
                </tr>
                <tr>
                    <th scope="row">Cuándo se envía</th>
                    <td><input type="date" name="<?php echo $opt; ?>[send_date]" value="<?php echo esc_attr( $cfg['send_date'] ); ?>" />
                        <p class="description">Fecha única para todos los destinatarios, sin excepciones (no depende de su fecha de alta). A partir de ese día el cron envía los lotes.</p></td>
                </tr>
                <tr>
                    <th scope="row">Emails por lote</th>
                    <td><input type="number" name="<?php echo $opt; ?>[batch_size]" value="<?php echo (int) $cfg['batch_size']; ?>" class="small-text" min="1" max="200" />
                        <p class="description">Un cron envía un lote cada 15 minutos mientras queden socios con la fecha cumplida.</p></td>
                </tr>
            </table>
            <?php submit_button( 'Guardar' ); ?>
        </form>

        <hr>
        <h2>Estado</h2>
        <ul>
            <li>Destinatarios según el criterio: <strong><?php echo (int) $n_total; ?></strong></li>
            <li>Ya enviados: <strong><?php echo (int) $n_sent; ?></strong></li>
            <li>Con la fecha de envío cumplida y pendientes: <strong><?php echo (int) $n_due; ?></strong></li>
            <li>Sin email válido (no se pueden avisar): <strong><?php echo (int) $n_bad_email; ?></strong></li>
        </ul>
        <p>Fecha de envío configurada: <strong><?php echo esc_html( mgmit_exc_renewal_send_date( $cfg, wp_timezone() )->format( 'd.m.Y' ) ); ?></strong>
            (pendientes de enviar ese día: <strong><?php echo (int) $n_pending; ?></strong>)</p>

        <h2>Acciones</h2>
        <form method="post" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'mgmit_exc_renewal_action' ); ?>
            <input type="hidden" name="mgmit_exc_action" value="test" />
            <input type="email" name="mgmit_exc_test_to" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text" />
            <button type="submit" class="button">Enviar email de prueba</button>
            <span class="description">Va solo a esa dirección, con saludo genérico y la URL guardada.</span>
        </form>
        <form method="post" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'mgmit_exc_renewal_action' ); ?>
            <input type="hidden" name="mgmit_exc_action" value="run" />
            <label><input type="checkbox" name="mgmit_exc_confirm" value="1" /> Entiendo que esto envía emails reales ahora, solo a quien ya le toque.</label>
            <button type="submit" class="button button-secondary">Ejecutar un lote ahora</button>
        </form>

        <h2>Vista previa de destinatarios (primeros 100)</h2>
        <table class="widefat striped" style="max-width:900px;">
            <thead><tr><th>member_id</th><th>Alta</th><th>Envío</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ( array_slice( $rows, 0, 100 ) as $row ) : ?>
                <?php
                if ( '' !== $row['sent'] ) {
                    $state = 'Enviado ' . $row['sent'] . ' UTC';
                } elseif ( ! $row['valid_email'] ) {
                    $state = 'Sin email válido';
                } else {
                    $state = $row['due'] ? 'Toca enviar' : 'Pendiente';
                }
                ?>
                <tr>
                    <td><?php echo (int) $row['member']->member_id; ?></td>
                    <td><?php echo esc_html( $row['member']->subscription_starts ); ?></td>
                    <td><?php echo esc_html( $row['send']->format( 'd.m.Y' ) ); ?></td>
                    <td><?php echo esc_html( $state ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
