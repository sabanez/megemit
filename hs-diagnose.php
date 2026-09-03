<?php
/**
 * MeGeMIT — Diagnóstico logger HubSpot (correos WooCommerce)
 * Comprueba por qué el mu-plugin hubspot-email-logger.php no está
 * registrando los correos de cliente en el timeline de HubSpot.
 *
 * Acceso: solo administradores WP autenticados.
 * Uso: /hs-diagnose.php (desde el root del sitio)
 *
 * BORRAR ESTE ARCHIVO CUANDO TERMINE EL DIAGNÓSTICO.
 */

require_once dirname( __FILE__ ) . '/wp-load.php';

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Acceso denegado. Debes estar autenticado como administrador.' );
}

$test_email  = '';
$test_result = null;
$opcache_msg = '';

if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'mgmit_hs_diag' ) ) {
		wp_die( 'Nonce inválido. Recarga la página e inténtalo de nuevo.' );
	}

	if ( isset( $_POST['mgmit_diag_test_lookup'] ) ) {
		$test_email = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
		if ( is_email( $test_email ) && function_exists( 'wc_hs_email_logger_get_contact_id' ) ) {
			$contact_id  = wc_hs_email_logger_get_contact_id( $test_email );
			$test_result = $contact_id
				? "OK — contactId encontrado: {$contact_id}"
				: 'Sin resultado (revisa el log de abajo o el email no existe en HubSpot).';
		} elseif ( ! function_exists( 'wc_hs_email_logger_get_contact_id' ) ) {
			$test_result = 'ERROR: la función wc_hs_email_logger_get_contact_id() no existe. El mu-plugin no está cargado.';
		} else {
			$test_result = 'Email no válido.';
		}
	}

	if ( isset( $_POST['mgmit_diag_opcache_reset'] ) ) {
		if ( function_exists( 'opcache_reset' ) ) {
			$opcache_msg = opcache_reset() ? 'OPcache reseteado correctamente.' : 'opcache_reset() devolvió false.';
		} else {
			$opcache_msg = 'opcache_reset() no está disponible en este servidor.';
		}
	}
}

// --- Recogida de datos de diagnóstico -------------------------------------

$fn_capture_exists  = function_exists( 'wc_hs_email_logger_capture_customer_email' );
$fn_logger_exists   = function_exists( 'wc_hs_email_logger_log_email_to_timeline' );
$fn_lookup_exists   = function_exists( 'wc_hs_email_logger_get_contact_id' );
$fn_token_exists    = function_exists( 'wc_hs_email_logger_get_token' );
$token_value        = $fn_token_exists ? wc_hs_email_logger_get_token() : '';
$token_defined      = ! empty( $token_value );
$file_path          = WPMU_PLUGIN_DIR . '/hubspot-email-logger.php';
$file_exists        = file_exists( $file_path );
$file_mtime         = $file_exists ? date( 'Y-m-d H:i:s', filemtime( $file_path ) ) : '—';
$wc_version         = defined( 'WC_VERSION' ) ? WC_VERSION : '(WooCommerce no activo o no detectado)';
$as_available       = function_exists( 'as_get_scheduled_actions' );

$opcache_status = function_exists( 'opcache_get_status' ) ? opcache_get_status( false ) : false;

$recent_actions = array();
if ( $as_available ) {
	$action_ids = as_get_scheduled_actions(
		array(
			'hook'     => 'wc_hs_email_logger_log_email',
			'per_page' => 20,
			'orderby'  => 'date',
			'order'    => 'DESC',
		),
		'ids'
	);

	foreach ( $action_ids as $action_id ) {
		$action = ActionScheduler::store()->fetch_action( $action_id );
		$status = ActionScheduler::store()->get_status( $action_id );
		$logs   = ActionScheduler::logger()->get_logs( $action_id );

		$last_log = '';
		if ( ! empty( $logs ) ) {
			$last = end( $logs );
			$last_log = $last->get_message();
		}

		$recent_actions[] = array(
			'id'       => $action_id,
			'status'   => $status,
			'scheduled'=> $action->get_schedule()->get_date() ? $action->get_schedule()->get_date()->format( 'Y-m-d H:i:s' ) : '—',
			'last_log' => $last_log,
		);
	}
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Diagnóstico HubSpot Email Logger</title>
<style>
	body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; line-height: 1.5; }
	h2 { border-bottom: 2px solid #ddd; padding-bottom: 6px; margin-top: 40px; }
	.ok { color: #0a7c2f; font-weight: bold; }
	.err { color: #c0392b; font-weight: bold; }
	.warn { color: #b8860b; font-weight: bold; }
	table { border-collapse: collapse; width: 100%; margin-top: 10px; }
	th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; font-size: 13px; }
	th { background: #f5f5f5; }
	code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
	input[type=email] { padding: 6px; width: 300px; }
	button { padding: 8px 14px; cursor: pointer; }
	.box { background: #fafafa; border: 1px solid #ddd; padding: 12px 16px; border-radius: 6px; margin-top: 10px; }
</style>
</head>
<body>

<h1>Diagnóstico — HubSpot Email Logger</h1>
<p><em>Borra este archivo del servidor cuando termines.</em></p>

<h2>1. Carga del mu-plugin</h2>
<ul>
	<li>Archivo <code><?php echo esc_html( $file_path ); ?></code>:
		<?php echo $file_exists ? '<span class="ok">existe</span> (modificado: ' . esc_html( $file_mtime ) . ')' : '<span class="err">NO existe</span>'; ?>
	</li>
	<li>Función <code>wc_hs_email_logger_capture_customer_email()</code>:
		<?php echo $fn_capture_exists ? '<span class="ok">cargada</span>' : '<span class="err">NO cargada</span>'; ?>
	</li>
	<li>Función <code>wc_hs_email_logger_log_email_to_timeline()</code>:
		<?php echo $fn_logger_exists ? '<span class="ok">cargada</span>' : '<span class="err">NO cargada</span>'; ?>
	</li>
	<li>Función <code>wc_hs_email_logger_get_contact_id()</code>:
		<?php echo $fn_lookup_exists ? '<span class="ok">cargada</span>' : '<span class="err">NO cargada</span>'; ?>
	</li>
	<li>Token de HubSpot resuelto vía <code>wc_hs_email_logger_get_token()</code> (constante, opción del mapper u opción del bridge):
		<?php echo $token_defined ? '<span class="ok">encontrado</span>' : '<span class="err">NO encontrado por ninguna vía</span>'; ?>
	</li>
	<li>WooCommerce activo: <code><?php echo esc_html( $wc_version ); ?></code></li>
</ul>

<h2>2. OPcache</h2>
<?php if ( $opcache_status ) : ?>
	<ul>
		<li>Activo: <span class="ok">sí</span></li>
		<li>Hits: <?php echo (int) $opcache_status['opcache_statistics']['hits']; ?>, Misses: <?php echo (int) $opcache_status['opcache_statistics']['misses']; ?></li>
		<li>Validación de timestamps (<code>opcache.validate_timestamps</code>): <?php echo $opcache_status['directives']['opcache.validate_timestamps'] ? 'activada (los cambios de archivo se detectan solos)' : '<span class="warn">DESACTIVADA — los archivos subidos no se reflejan hasta resetear OPcache manualmente</span>'; ?></li>
	</ul>
	<form method="post">
		<?php wp_nonce_field( 'mgmit_hs_diag' ); ?>
		<button type="submit" name="mgmit_diag_opcache_reset" value="1">Resetear OPcache</button>
	</form>
<?php else : ?>
	<p>OPcache no activo o no disponible en este servidor.</p>
<?php endif; ?>
<?php if ( $opcache_msg ) : ?>
	<div class="box"><?php echo esc_html( $opcache_msg ); ?></div>
<?php endif; ?>

<h2>3. Test real de conexión a HubSpot</h2>
<form method="post">
	<?php wp_nonce_field( 'mgmit_hs_diag' ); ?>
	<label>Email de contacto existente en HubSpot para probar el lookup:</label><br>
	<input type="email" name="test_email" value="<?php echo esc_attr( $test_email ); ?>" required>
	<button type="submit" name="mgmit_diag_test_lookup" value="1">Probar lookup</button>
</form>
<?php if ( null !== $test_result ) : ?>
	<div class="box"><?php echo esc_html( $test_result ); ?></div>
<?php endif; ?>

<h2>4. Últimas 20 acciones programadas (<code>wc_hs_email_logger_log_email</code>)</h2>
<?php if ( ! $as_available ) : ?>
	<p class="err">Action Scheduler no disponible.</p>
<?php elseif ( empty( $recent_actions ) ) : ?>
	<p class="warn">No hay ninguna acción registrada con este hook. El logger nunca ha llegado a encolar nada — el problema está antes de Action Scheduler.</p>
<?php else : ?>
	<table>
		<tr><th>ID</th><th>Estado</th><th>Programada</th><th>Último log</th></tr>
		<?php foreach ( $recent_actions as $a ) : ?>
			<tr>
				<td><?php echo (int) $a['id']; ?></td>
				<td><?php echo esc_html( $a['status'] ); ?></td>
				<td><?php echo esc_html( $a['scheduled'] ); ?></td>
				<td><?php echo esc_html( $a['last_log'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

</body>
</html>
