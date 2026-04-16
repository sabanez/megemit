<?php

namespace Sendcloud\Shipping\Checkout\Shipping\SameDay;

use DateTime;
use Exception;
use Sendcloud\Shipping\Checkout\Shipping\Base_Checkout_Handler;
use Sendcloud\Shipping\Models\Delivery_Method_Meta_Data;
use Sendcloud\Shipping\Repositories\Checkout_Payload_Meta_Repository;
use Sendcloud\Shipping\Repositories\Delivery_Methods_Repository;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;

class Checkout_Handler extends Base_Checkout_Handler {
	/**
	 * Delivery Methods Repository
	 *
	 * @var Delivery_Methods_Repository
	 */
	private $delivery_method_repository;

	/**
	 * Checkout_Handler constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->delivery_method_repository = new Delivery_Methods_Repository( $wpdb );
	}

	/**
	 * Hooks all checkout functions
	 */
	public function init() {
		add_action( 'woocommerce_checkout_process',
			new Logging_Callable( array( $this, 'validate_same_day_shipping_selection' ) ) );
		add_action( 'woocommerce_checkout_update_order_meta',
			new Logging_Callable( array( $this, 'save_same_day_delivery_meta' ) ) );
		add_action( 'woocommerce_store_api_checkout_order_processed',
			new Logging_Callable( array( $this, 'wc_blocks_validate_same_day_shipping_selection' ) ) );
	}

	/**
	 * Process place order event when woocommerce blocks are used in checkout
	 *
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function wc_blocks_validate_same_day_shipping_selection( \WC_Order $order ) {
		if ( empty( $order->get_items( 'shipping' ) ) ) {
			return;
		}
		$chosen_shipping_methods = wc()->session->get( 'chosen_shipping_methods', '' );
		$shipping_method_parts   = explode( ':', reset( $chosen_shipping_methods ) );

		$this->validate( $shipping_method_parts );
		$this->save( $shipping_method_parts, $order->get_id() );
	}

	/**
	 * Validates submitted same day delivery data (if any) before order is created
	 */
	public function validate_same_day_shipping_selection() {
		$shipping_method_parts = $this->extract_shipping_method();
		$this->validate( $shipping_method_parts );
	}

	/**
	 * Validate
	 *
	 * @param $shipping_method_parts
	 *
	 * @return void
	 */
	private function validate( $shipping_method_parts ) {
		$selected_shipping_method = array_key_exists( 0,
			$shipping_method_parts ) ? (string) $shipping_method_parts[0] : '';
		$instance_id              = array_key_exists( 1,
			$shipping_method_parts ) ? (string) $shipping_method_parts[1] : '';

		if ( Same_Day_Shipping_Method::ID !== $selected_shipping_method ) {
			Logger::info( 'SameDay/Checkout_Handler::validate_same_day_shipping_selection(): same day shipping method is not selected.' );

			return;
		}

		Logger::info( 'SameDay/Checkout_Handler::validate_same_day_shipping_selection(): same day shipping method is selected.' );
		$delivery_method = $this->delivery_method_repository->find_by_system_id( $instance_id );

		try {
			$is_available = null !== $delivery_method && $delivery_method->isAvailable( $this->create_order() );
		} catch ( Exception $exception ) {
			Logger::error( 'Error while checking method availability. ' . $exception->getMessage() );
			$is_available = false;
		}

		if ( ! $is_available ) {
			wc_add_notice( __( 'Shipping method not available.', 'sendcloud-shipping' ), 'error' );
		}
	}

	/**
	 * Save order meta
	 *
	 * @param $order_id
	 */
	public function save_same_day_delivery_meta( $order_id ) {
		Logger::info( 'SameDay/Checkout_Handler::save_same_day_delivery_meta(): ' . 'order id:' . $order_id );

		$shipping_method_parts = $this->extract_shipping_method();
		$this->save( $shipping_method_parts, $order_id );
	}

	/**
	 * Save same day delivery meta data
	 *
	 * @param $shipping_method_parts
	 * @param $order_id
	 *
	 * @return void
	 */
	private function save( $shipping_method_parts, $order_id ) {
		if ( ! array_key_exists( 0, $shipping_method_parts ) || Same_Day_Shipping_Method::ID !== $shipping_method_parts[0] ) {
			return;
		}
		$instance_id          = array_key_exists( 1,
			$shipping_method_parts ) ? (string) $shipping_method_parts[1] : '';
		$delivery_method_data = $this->delivery_method_repository->find_by_system_id( $instance_id );

		if ( null !== $delivery_method_data ) {
			$checkout_payload_meta_repo   = new Checkout_Payload_Meta_Repository();
			$data                         = json_decode( $delivery_method_data->getRawConfig(), true );
			$data['delivery_method_data'] = $this->create_delivery_method_data_object()->to_array();
			$checkout_payload_meta_repo->save_raw( $order_id, $data );
		}
	}

	/**
	 * Creates delivery method data object
	 *
	 * @return \Sendcloud\Shipping\Models\Delivery_Method_Meta_Data
	 */
	private function create_delivery_method_data_object() {
		$current              = new DateTime();
		$delivery_method_data = new Delivery_Method_Meta_Data();
		$delivery_method_data->set_delivery_date( $current );
		$delivery_method_data->set_formatted_delivery_date( __( date_format( $current, 'F' ), 'sendcloud-shipping' ) . date_format( $current, ' j, Y' ) );
		$delivery_method_data->set_parcel_handover_date( $current );
		$delivery_method_data->set_formatted_parcel_handover_date( __( date_format( $current, 'F' ), 'sendcloud-shipping' ) . date_format( $current, ' j, Y' ) );

		return $delivery_method_data;
	}
}
