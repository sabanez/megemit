<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bloquea el reembolso de un pedido si el usuario tiene progreso de
 * finalización > 0% en cualquier curso en el que esté matriculado en Moodle.
 *
 * No se distingue qué curso concreto del pedido está iniciado: si el pedido
 * contiene varios cursos (pack) y el alumno ya empezó cualquiera de los cursos
 * en los que está matriculado, se bloquea el reembolso del pedido completo
 * (asunción confirmada: sin reembolsos parciales por línea).
 */
class WCMS_Refund_Guard {

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

		// Reembolso manual desde el admin (pantalla de edición de pedido).
		add_action( 'wp_ajax_woocommerce_refund_line_items', array( $this, 'guard_admin_ajax_refund' ), 1 );

		// Reembolso vía API REST de WooCommerce.
		add_filter( 'woocommerce_rest_pre_insert_shop_order_refund', array( $this, 'guard_rest_refund' ), 10, 2 );
	}

	/**
	 * Intercepta el AJAX de reembolso del admin antes de que WooCommerce lo procese.
	 * Si el pedido tiene un curso iniciado, corta la petición con un error JSON.
	 */
	public function guard_admin_ajax_refund() {
		if ( empty( $_POST['order_id'] ) ) {
			return;
		}

		$order_id = absint( $_POST['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$blocking_reason = $this->find_started_course( $order );
		if ( null === $blocking_reason ) {
			return;
		}

		wp_send_json_error( wcms_msg(
			"Rückerstattung nicht möglich: Der Kurs \"{$blocking_reason}\" wurde bereits begonnen.",
			"Refund not possible: the course \"{$blocking_reason}\" has already been started."
		) );
	}

	/**
	 * Intercepta la creación de reembolsos vía REST API.
	 *
	 * @param WC_Order_Refund $refund
	 * @param WP_REST_Request $request
	 * @return WC_Order_Refund|WP_Error
	 */
	public function guard_rest_refund( $refund, $request ) {
		$order_id = $refund->get_parent_id();
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			return $refund;
		}

		$blocking_reason = $this->find_started_course( $order );
		if ( null === $blocking_reason ) {
			return $refund;
		}

		return new WP_Error(
			'wcms_course_started',
			wcms_msg(
				"Rückerstattung nicht möglich: Der Kurs \"{$blocking_reason}\" wurde bereits begonnen.",
				"Refund not possible: the course \"{$blocking_reason}\" has already been started."
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Comprueba si el comprador tiene progreso > 0% en algún curso en el que
	 * esté matriculado en Moodle (sin distinguir cuál de los cursos del pedido).
	 *
	 * @param WC_Order $order
	 * @return string|null  Nombre del curso iniciado, o null si ninguno lo está.
	 */
	private function find_started_course( $order ) {
		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return null;
		}

		$moodle_user_id = (int) get_user_meta( $user_id, '_wcms_moodle_user_id', true );
		if ( ! $moodle_user_id ) {
			return null;
		}

		$api     = WCMS_Moodle_Api::get_instance();
		$courses = $api->get_user_courses( $moodle_user_id );

		foreach ( $courses as $course ) {
			$progress = isset( $course['progress'] ) ? (float) $course['progress'] : null;

			if ( null !== $progress && $progress > 0 ) {
				$name = isset( $course['fullname'] ) ? $course['fullname'] : ( 'Moodle #' . $course['id'] );
				$this->logger->info( "Refund guard: pedido #{$order->get_id()} bloqueado, curso Moodle '{$name}' con {$progress}% de progreso para user Moodle #{$moodle_user_id}.", self::$log_context );
				return $name;
			}
		}

		return null;
	}
}
