<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encola y ejecuta la matriculación en Moodle tras confirmar el pago.
 * La creación del usuario ya ocurrió en el checkout (WCMS_Checkout_Guard).
 */
class WCMS_Scheduler {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** @var WC_Logger_Interface */
	private $logger;

	private static $log_context = array( 'source' => 'wc-moodle-sync' );

	private function __construct() {
		$this->logger = wc_get_logger();
		add_action( 'wcms_process_order', array( $this, 'run' ), 10, 1 );
	}

	/**
	 * Encola la tarea de matriculación para el pedido, evitando duplicados.
	 *
	 * @param int $order_id
	 */
	public function enqueue( $order_id ) {
		if ( get_post_meta( $order_id, '_wcms_job_queued', true ) ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'wcms_process_order', array( $order_id ), 'wcms_group' );
		} else {
			$this->run( $order_id );
		}

		update_post_meta( $order_id, '_wcms_job_queued', 'yes' );
	}

	/**
	 * Worker ejecutado en segundo plano: matricula al usuario en los cursos del pedido.
	 *
	 * @param int $order_id
	 */
	public function run( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->logger->error( "Pedido #{$order_id} no encontrado.", self::$log_context );
			return;
		}

		$course_data = $this->collect_cohort_data( $order );
		if ( empty( $course_data['ids'] ) ) {
			return;
		}
		$cohort_ids   = $course_data['ids'];
		$course_names = $course_data['names'];

		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			$this->logger->warning( "Pedido #{$order_id} realizado por invitado. Ignorado.", self::$log_context );
			return;
		}

		$moodle_user_id = (int) get_user_meta( $user_id, '_wcms_moodle_user_id', true );
		if ( ! $moodle_user_id ) {
			// Pedido creado desde el admin: el checkout guard no corrió, intentamos crear/encontrar el usuario ahora.
			$moodle_user_id = $this->ensure_moodle_user( $user_id, $order_id );
			if ( ! $moodle_user_id ) {
				return;
			}
		}

		$api      = WCMS_Moodle_Api::get_instance();
		$enrolled = $api->add_to_cohort( $moodle_user_id, $cohort_ids );

		if ( ! $enrolled ) {
			return;
		}

		$wp_user  = get_userdata( $user_id );
		$username = strtolower( $wp_user->user_login );

		// Recuperar contraseña temporal si el usuario fue creado en este mismo checkout.
		$password = (string) get_user_meta( $user_id, '_wcms_moodle_tmp_password', true );
		$is_new   = ! empty( $password );

		WCMS_Mailer::get_instance()->send_welcome( $wp_user, $username, $password, $is_new, $course_names );

		// Limpiar contraseña temporal tras enviar el email.
		if ( $is_new ) {
			delete_user_meta( $user_id, '_wcms_moodle_tmp_password' );
		}
	}

	/**
	 * Garantiza que el usuario WP tiene un usuario en Moodle asociado.
	 * Usado cuando el pedido se crea desde el admin (el checkout guard no corrió).
	 *
	 * @param int $user_id   ID de usuario WP.
	 * @param int $order_id  ID del pedido (solo para logging).
	 * @return int  moodle_user_id, o 0 si no se pudo crear/encontrar.
	 */
	private function ensure_moodle_user( $user_id, $order_id ) {
		$wp_user  = get_userdata( $user_id );
		$username = strtolower( $wp_user->user_login );
		$api      = WCMS_Moodle_Api::get_instance();

		$moodle_user_id = $api->find_user( $username, $wp_user->user_email );

		if ( ! $moodle_user_id ) {
			$password       = wp_generate_password( 12, false, false ) . 'Aa1@';
			$moodle_user_id = $api->create_user( $wp_user, $username, $password );

			if ( ! $moodle_user_id ) {
				$this->logger->error( "Pedido #{$order_id}: no se pudo crear el usuario {$username} en Moodle desde el admin.", self::$log_context );
				return 0;
			}

			update_user_meta( $user_id, '_wcms_moodle_tmp_password', $password );
		}

		update_user_meta( $user_id, '_wcms_moodle_user_id', $moodle_user_id );
		$this->logger->info( "Pedido #{$order_id}: usuario Moodle #{$moodle_user_id} asociado al usuario WP #{$user_id} (creado desde admin).", self::$log_context );

		return $moodle_user_id;
	}

	/**
	 * Extrae IDs de cohort Moodle y nombres de curso de los items del pedido.
	 *
	 * @param WC_Order $order
	 * @return array { ids: int[], names: string[] }
	 */
	private function collect_cohort_data( $order ) {
		$ids   = array();
		$names = array();

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$raw        = get_post_meta( $product_id, 'moodle_cohort_ids', true );

			if ( empty( $raw ) ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ',', $raw ) );

			foreach ( $parts as $part ) {
				if ( is_numeric( $part ) ) {
					$ids[] = (int) $part;
				}
			}

			$names[] = $item->get_name();
		}

		return array(
			'ids'   => array_values( array_unique( $ids ) ),
			'names' => array_unique( $names ),
		);
	}
}
