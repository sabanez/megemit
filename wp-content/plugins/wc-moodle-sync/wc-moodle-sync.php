<?php
/**
 * Plugin Name: WooCommerce & Moodle Sync
 * Description: Valida y crea usuarios en Moodle antes del pago. Matricula en cursos tras confirmar la compra. Asíncrono y compatible con packs multicurso.
 * Version:     1.7.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      MeGeMIT
 * Text Domain: wc-moodle-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve un mensaje en alemán si el sitio está en alemán (locale de_*),
 * o en inglés para cualquier otra configuración de idioma.
 *
 * @param string $de  Texto en alemán.
 * @param string $en  Texto en inglés.
 * @return string
 */
function wcms_msg( $de, $en ) {
	return ( 0 === strpos( get_locale(), 'de' ) ) ? $de : $en;
}

define( 'WCMS_VERSION', '1.7.0' );
define( 'WCMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Configuración propia del plugin (conexión Moodle, secretos, CC de emails,
 * activar/desactivar certificado y cupón). Ver wc-moodle-sync/config.php.
 */
require_once __DIR__ . '/config.php';

final class WC_Moodle_Sync {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_includes();
		$this->register_hooks();
	}

	private function load_includes() {
		require_once WCMS_PATH . 'includes/class-wcms-moodle-api.php';
		require_once WCMS_PATH . 'includes/class-wcms-mailer.php';
		require_once WCMS_PATH . 'includes/class-wcms-checkout-guard.php';
		require_once WCMS_PATH . 'includes/class-wcms-scheduler.php';
		require_once WCMS_PATH . 'includes/class-wcms-certificate.php';
		require_once WCMS_PATH . 'includes/class-wcms-completion-handler.php';
		require_once WCMS_PATH . 'includes/class-wcms-refund-guard.php';
	}

	private function register_hooks() {
		// Inicializar guard de checkout (valida/crea usuario Moodle antes del pago).
		WCMS_Checkout_Guard::get_instance();

		// Inicializar scheduler (matricula tras confirmar el pago).
		WCMS_Scheduler::get_instance();

		// Inicializar handler de finalización de curso (webhook Moodle → cupón + email).
		WCMS_Completion_Handler::get_instance();

		// Inicializar guard de reembolso (bloquea el reembolso si el curso ya se ha iniciado).
		WCMS_Refund_Guard::get_instance();

		// Disparar matriculación solo cuando el pago está confirmado.
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_enqueue_sync' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed',  array( $this, 'maybe_enqueue_sync' ), 10, 1 );

		// Disparar desmatriculación cuando el pedido se cancela, se reembolsa o falla el pago.
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_enqueue_unenroll' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded',  array( $this, 'maybe_enqueue_unenroll' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed',    array( $this, 'maybe_enqueue_unenroll' ), 10, 1 );
	}

	public function maybe_enqueue_sync( $order_id ) {
		WCMS_Scheduler::get_instance()->enqueue( $order_id );
	}

	public function maybe_enqueue_unenroll( $order_id ) {
		WCMS_Scheduler::get_instance()->enqueue_unenroll( $order_id );
	}
}

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	WC_Moodle_Sync::get_instance();
} );
