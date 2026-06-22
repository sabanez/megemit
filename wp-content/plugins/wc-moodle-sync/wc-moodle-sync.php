<?php
/**
 * Plugin Name: WooCommerce & Moodle Sync
 * Description: Valida y crea usuarios en Moodle antes del pago. Matricula en cursos tras confirmar la compra. Asíncrono y compatible con packs multicurso.
 * Version:     1.2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      MeGeMIT
 * Text Domain: wc-moodle-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCMS_VERSION', '1.2.0' );
define( 'WCMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Constantes de conexión Moodle.
 * Definir en wp-config.php:
 *   define( 'WCMS_MOODLE_API_URL', 'https://tu-moodle.com/webservice/rest/server.php' );
 *   define( 'WCMS_MOODLE_TOKEN',   'tu_token_aqui' );
 */
if ( ! defined( 'WCMS_MOODLE_API_URL' ) ) {
	define( 'WCMS_MOODLE_API_URL', '' );
}
if ( ! defined( 'WCMS_MOODLE_TOKEN' ) ) {
	define( 'WCMS_MOODLE_TOKEN', '' );
}

/**
 * URL de login de Moodle (puede diferir del subdominio de la API).
 * Definir en wp-config.php:
 *   define( 'WCMS_MOODLE_LOGIN_URL', 'https://onlineakademie.megemit.org/login/?lang=de' );
 */
if ( ! defined( 'WCMS_MOODLE_LOGIN_URL' ) ) {
	define( 'WCMS_MOODLE_LOGIN_URL', '' );
}

/**
 * Token secreto para el webhook de finalización de curso.
 * Definir en wp-config.php:
 *   define( 'WCMS_COMPLETION_SECRET', 'un_token_aleatorio_seguro' );
 */
if ( ! defined( 'WCMS_COMPLETION_SECRET' ) ) {
	define( 'WCMS_COMPLETION_SECRET', '' );
}

/**
 * URL del Prämienshop para el botón en el email de felicitación.
 * Definir en wp-config.php o dejar el valor por defecto.
 *   define( 'WCMS_COUPON_SHOP_URL', 'https://megemit.org/produktkategorie/mdlc/' );
 */
if ( ! defined( 'WCMS_COUPON_SHOP_URL' ) ) {
	define( 'WCMS_COUPON_SHOP_URL', 'https://megemit.org/produktkategorie/mdlc/' );
}

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
	}

	private function register_hooks() {
		// Inicializar guard de checkout (valida/crea usuario Moodle antes del pago).
		WCMS_Checkout_Guard::get_instance();

		// Inicializar scheduler (matricula tras confirmar el pago).
		WCMS_Scheduler::get_instance();

		// Inicializar handler de finalización de curso (webhook Moodle → cupón + email).
		WCMS_Completion_Handler::get_instance();

		// Disparar matriculación solo cuando el pago está confirmado.
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_enqueue_sync' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed',  array( $this, 'maybe_enqueue_sync' ), 10, 1 );
	}

	public function maybe_enqueue_sync( $order_id ) {
		WCMS_Scheduler::get_instance()->enqueue( $order_id );
	}
}

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	WC_Moodle_Sync::get_instance();
} );
