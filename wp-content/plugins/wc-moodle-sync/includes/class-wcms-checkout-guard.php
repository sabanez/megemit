<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valida y crea el usuario en Moodle antes de procesar el pago.
 * Bloquea el checkout con wc_add_notice() si Moodle no está disponible.
 */
class WCMS_Checkout_Guard {

	private static $instance = null;

	/** @var WC_Logger_Interface */
	private $logger;

	private static $log_context = array( 'source' => 'wc-moodle-sync' );

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger = wc_get_logger();
		add_action( 'woocommerce_checkout_process', array( $this, 'validate' ) );
	}

	/**
	 * Comprueba si el carrito contiene cursos Moodle y garantiza que el usuario
	 * existe en Moodle antes de permitir el pago.
	 */
	public function validate() {
		if ( ! $this->cart_has_moodle_courses() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wc_add_notice(
				__( 'Debes iniciar sesión para comprar cursos.', 'wc-moodle-sync' ),
				'error'
			);
			return;
		}

		// Si ya tenemos el ID de Moodle guardado, no hace falta otra llamada.
		if ( get_user_meta( $user_id, '_wcms_moodle_user_id', true ) ) {
			return;
		}

		$wp_user  = get_userdata( $user_id );
		$username = strtolower( $wp_user->user_login );
		$api      = WCMS_Moodle_Api::get_instance();

		$moodle_user_id = $api->find_user( $username, $wp_user->user_email );

		if ( ! $moodle_user_id ) {
			$password       = wp_generate_password( 12, false, false ) . 'Aa1@';
			$moodle_user_id = $api->create_user( $wp_user, $username, $password );

			if ( ! $moodle_user_id ) {
				$this->logger->error( "Checkout bloqueado: no se pudo crear el usuario {$username} en Moodle. Revisa los logs de la API para más detalle.", self::$log_context );
				wc_add_notice(
					__( 'No ha sido posible preparar tu acceso a la plataforma de cursos. Por favor, inténtalo de nuevo o contacta con soporte.', 'wc-moodle-sync' ),
					'error'
				);
				return;
			}

			// Guardar contraseña temporal en user_meta para enviarla por email tras el pago.
			update_user_meta( $user_id, '_wcms_moodle_tmp_password', $password );
		}

		update_user_meta( $user_id, '_wcms_moodle_user_id', $moodle_user_id );
	}

	/**
	 * Comprueba si algún producto del carrito tiene moodle_course_ids definido.
	 *
	 * @return bool
	 */
	private function cart_has_moodle_courses() {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			$product_id = $item['product_id'];
			if ( get_post_meta( $product_id, 'moodle_course_ids', true ) ) {
				return true;
			}
		}

		return false;
	}
}
