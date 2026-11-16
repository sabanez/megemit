<?php
/**
 * MeGeMIT — Reenvío manual del email de bienvenida de una membresía
 *
 * Para clientes que pagaron pero no recibieron el email de alta con factura y
 * confirmación de membresía. Lista los pagos de Stripe (primer pago de una
 * suscripción) registrados en SWPM y reenvía el email de los que elijas, con los
 * documentos fechados el día del pago original.
 *
 * Dos modos: "prueba" (el email llega a la dirección que indiques, p. ej. la tuya) y
 * "real" (llega al email del pago del cliente, tras confirmarlo expresamente).
 *
 * Acceso: solo administradores WP autenticados.
 * Uso: /resend-membership-email.php (desde el root del sitio). Borrar al terminar.
 */

require_once dirname( __FILE__ ) . '/wp-load.php';

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Acceso denegado. Debes estar autenticado como administrador.' );
}

if ( ! function_exists( 'mgmit_bridge_resend_membership_email' ) ) {
    wp_die( 'La función de reenvío no está cargada (¿tema basel-child activo y bridge actualizado?).' );
}

global $wpdb;

$days    = isset( $_REQUEST['days'] ) ? max( 1, min( 365, (int) $_REQUEST['days'] ) ) : 45;
$results = array();
$option  = 'mgmit_resent_membership_emails';
$resent  = get_option( $option, array() );
if ( ! is_array( $resent ) ) {
    $resent = array();
}

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['mgmit_resend_submit'] ) ) {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'mgmit_resend_membership' ) ) {
        wp_die( 'Nonce inválido. Recarga la página e inténtalo de nuevo.' );
    }

    $mode     = ( isset( $_POST['mode'] ) && 'real' === $_POST['mode'] ) ? 'real' : 'test';
    $test_to  = isset( $_POST['test_to'] ) ? sanitize_email( wp_unslash( $_POST['test_to'] ) ) : '';
    $confirm  = ! empty( $_POST['confirm_real'] );
    $ids      = isset( $_POST['payment_ids'] ) && is_array( $_POST['payment_ids'] ) ? array_map( 'absint', $_POST['payment_ids'] ) : array();

    if ( ! $ids ) {
        $results[] = array( false, 'No has seleccionado ningún pago.' );
    } elseif ( 'test' === $mode && ! is_email( $test_to ) ) {
        $results[] = array( false, 'Para el modo prueba indica un email de destino válido.' );
    } elseif ( 'real' === $mode && ! $confirm ) {
        $results[] = array( false, 'Para enviar a los clientes marca la casilla de confirmación.' );
    } else {
        foreach ( $ids as $pid ) {
            $payment = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, email, first_name, last_name, member_id, txn_date, txn_id, subscr_id, payment_amount, gateway
                     FROM {$wpdb->prefix}swpm_payments_tbl
                     WHERE id = %d AND gateway = 'stripe-sca-subs'",
                    $pid
                )
            );
            if ( ! $payment ) {
                $results[] = array( false, 'Pago ' . $pid . ': no encontrado o no es un pago de Stripe.' );
                continue;
            }

            $ok = mgmit_bridge_resend_membership_email( $payment, 'test' === $mode ? $test_to : '' );

            if ( $ok && 'real' === $mode ) {
                $resent[ $pid ] = array(
                    'when' => current_time( 'mysql' ),
                    'to'   => $payment->email,
                );
                update_option( $option, $resent, false );
            }
            $results[] = array(
                (bool) $ok,
                'Pago ' . $pid . ' (' . $payment->email . ', pagado el ' . $payment->txn_date . '): ' .
                ( $ok
                    ? ( 'test' === $mode ? 'enviado de PRUEBA a ' . $test_to : 'enviado al cliente.' )
                    : 'NO se pudo enviar. Revisa mgmit-bridge-log.txt.' ),
            );
        }
    }
}

$payments = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT p.id, p.email, p.first_name, p.last_name, p.member_id, p.txn_date, p.txn_id, p.subscr_id, p.payment_amount,
                m.email AS member_email, m.first_name AS member_first, m.last_name AS member_last
         FROM {$wpdb->prefix}swpm_payments_tbl p
         LEFT JOIN {$wpdb->prefix}swpm_members_tbl m ON m.member_id = p.member_id
         WHERE p.gateway = 'stripe-sca-subs'
           AND p.status = 'subscription created'
           AND p.txn_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
         ORDER BY p.id DESC
         LIMIT 150",
        $days
    )
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reenviar email de bienvenida — MeGeMIT</title>
<style>
body { font-family: -apple-system, Arial, sans-serif; max-width: 1100px; margin: 30px auto; padding: 0 16px; color: #1a2433; }
h1 { font-size: 1.3rem; }
table { border-collapse: collapse; width: 100%; font-size: .85rem; }
th, td { border: 1px solid #d5dbe4; padding: 5px 8px; text-align: left; vertical-align: top; }
th { background: #eef1f5; }
.box { padding: 10px 14px; border-radius: 4px; margin: 8px 0; }
.ok { background: #e3f6ea; } .err { background: #fde8e8; }
fieldset { border: 1px solid #d5dbe4; margin: 18px 0; padding: 12px 16px; }
label { display: block; margin: 6px 0; }
.muted { color: #666; font-size: .8rem; }
</style>
</head>
<body>
<h1>Reenviar el email de bienvenida de una membresía</h1>
<p>Pagos de Stripe (primer pago de una suscripción) de los últimos
<form method="get" style="display:inline"><input type="number" name="days" value="<?php echo esc_attr( $days ); ?>" min="1" max="365" style="width:60px"> días
<button type="submit">Actualizar</button></form>. El email se envía con la factura y la confirmación de membresía, fechadas el día del pago.</p>

<?php foreach ( $results as $r ) : ?>
<div class="box <?php echo $r[0] ? 'ok' : 'err'; ?>"><?php echo esc_html( $r[1] ); ?></div>
<?php endforeach; ?>

<?php if ( ! $payments ) : ?>
<p>No hay pagos de Stripe en ese periodo.</p>
<?php else : ?>
<form method="post">
<?php wp_nonce_field( 'mgmit_resend_membership' ); ?>
<input type="hidden" name="days" value="<?php echo esc_attr( $days ); ?>">
<table>
<tr><th></th><th>Pago</th><th>Fecha del pago</th><th>Socio</th><th>Email del pago (destino)</th><th>Email del socio en SWPM</th><th>Importe</th><th>Reenvío anterior</th></tr>
<?php foreach ( $payments as $p ) : ?>
<tr>
    <td><input type="checkbox" name="payment_ids[]" value="<?php echo esc_attr( $p->id ); ?>" aria-label="Seleccionar pago <?php echo esc_attr( $p->id ); ?>"></td>
    <td><?php echo esc_html( $p->id ); ?></td>
    <td><?php echo esc_html( $p->txn_date ); ?></td>
    <td><?php echo esc_html( $p->member_id . ' · ' . trim( $p->member_first . ' ' . $p->member_last ) ); ?></td>
    <td><?php echo esc_html( $p->email ); ?></td>
    <td><?php echo esc_html( $p->member_email ); ?></td>
    <td><?php echo esc_html( $p->payment_amount ); ?></td>
    <td><?php echo isset( $resent[ $p->id ] ) ? esc_html( 'Ya reenviado el ' . $resent[ $p->id ]['when'] . ' a ' . $resent[ $p->id ]['to'] ) : '—'; ?></td>
</tr>
<?php endforeach; ?>
</table>

<fieldset>
    <legend>Cómo enviar</legend>
    <label><input type="radio" name="mode" value="test" checked> <strong>Prueba</strong>: enviar a esta dirección (no llega al cliente):
        <input type="email" name="test_to" size="34" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"></label>
    <label><input type="radio" name="mode" value="real"> <strong>Real</strong>: enviar al email del pago de cada cliente seleccionado</label>
    <label><input type="checkbox" name="confirm_real" value="1"> Confirmo que el modo "Real" envía el email a los clientes seleccionados</label>
    <p class="muted">Recomendado: primero una prueba con un pago, revisar el email y los PDFs adjuntos, y después el envío real.</p>
</fieldset>
<button type="submit" name="mgmit_resend_submit" value="1">Reenviar seleccionados</button>
</form>
<?php endif; ?>
</body>
</html>
