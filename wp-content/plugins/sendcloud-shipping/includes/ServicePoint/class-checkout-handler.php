<?php

namespace Sendcloud\Shipping\ServicePoint;

use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Checkout_Handler as Checkout_Shipping_Handler;
use Sendcloud\Shipping\Models\Service_Point_Configuration;
use Sendcloud\Shipping\Models\Service_Point_Meta;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;
use Sendcloud\Shipping\Repositories\Service_Point_Meta_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\ServicePoint\Shipping\SendCloudShipping_Service_Point_Shipping_Method;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Checkout_Handler {
	const CLASS_NAME = __CLASS__;

	const SERVICE_POINT_ID = 'sendcloudshipping_service_point_selected';
	const SERVICE_POINT_EXTRA_FIELD_NAME = 'sendcloudshipping_service_point_extra';
	const SERVICE_POINT_POST_NUMBER_FIELD_NAME = 'sendcloudshipping_post_number';


	/**
	 * Service Point Configuration Repository
	 *
	 * @var Service_Point_Configuration_Repository
	 */
	private $service_point_config_repository;

	/**
	 * Service Point Meta Repository
	 *
	 * @var Service_Point_Meta_Repository
	 */
	private $service_point_meta_repository;

	/**
	 * Hooks all checkout functions
	 */
	public function init() {
		add_action( 'woocommerce_checkout_after_order_review', new Logging_Callable( array(
			$this,
			'add_service_point_to_checkout',
		) ) );
		add_action( 'wfacp_checkout_after_order_review', new Logging_Callable( array(
			$this,
			'add_service_point_to_checkout',
		) ) );
		add_action( 'woocommerce_after_shipping_rate', new Logging_Callable( array(
			$this,
			'add_extra_shipping_method'
		) ) );
		add_action( 'woocommerce_checkout_process',
			new Logging_Callable( array( $this, 'add_notice_if_service_point_not_chosen' ) ) );
		add_action( 'woocommerce_checkout_update_order_meta', new Logging_Callable( array(
			$this,
			'update_order_meta'
		) ) );
		add_action( 'woocommerce_thankyou', new Logging_Callable( array( $this, 'add_service_point_info' ) ), 11 );
		add_action( 'woocommerce_view_order', new Logging_Callable( array( $this, 'add_service_point_info' ) ), 11 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address',
			new Logging_Callable( array( $this, 'add_service_point_data_in_admin_order' ) ), 11 );

		add_action( 'wp_enqueue_scripts', new Logging_Callable( array( $this, 'add_service_point_to_checkout' ) ), 11 );
		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			new Logging_Callable( array( $this, 'add_service_point_to_checkout_block' ) ), 111
		);
	}

	/***
	 * Adds service point script to checkout when woocommerce blocks are used
	 *
	 * @return void
	 */
	public function add_service_point_to_checkout_block() {
		$shipping_methods = WC()->session->previous_shipping_methods;
		if ( empty( $shipping_methods ) ) {
			return;
		}
		foreach ($shipping_methods[0] as $method){
			$id          = explode( ':', $method );
			$name = ! empty( $id[0] ) ? $id[0] : null;
			$instance_id = ! empty( $id[1] ) ? $id[1] : null;
			if ( SendCloudShipping_Service_Point_Shipping_Method::ID === $name ) {
				$carriers = $this->get_service_point_config_repository()->get( $instance_id )->get_carriers();
				echo wp_kses( View::file( '/widgets/checkout/extra-shipping-method.php' )->render( array(
					'field_id'       => $method . ':carrier_select',
					'carrier_select' =>  isset( $carriers ) ? $this->removeAllSpaces( $carriers ) : '',
				) ), View::get_allowed_tags() );
			}
		}
	}

	/**
	 * Adds service point to checkout
	 */
	public function add_service_point_to_checkout() {
        if (!is_checkout() && !is_cart()) {
            return;
        }

		$script = $this->get_service_point_config_repository()->get()->get_script();

		if ( empty( $script ) ) {
			return;
		}

		$parts = explode( '_', get_locale() );

		wp_enqueue_script( 'sendcloud-service-point-js', $script, array(), Sendcloud::VERSION, true );

        echo wp_kses( View::file( '/widgets/checkout/add-service-point-data.php' )->render( array(
            'language'             => $parts[0],
            // Double encode dimensions because we want to pass the string as one GET param
            'cart_dimensions'      => base64_encode( json_encode( $this->cart_max_dimensions() ) ),
            'cart_dimensions_unit' => json_encode( get_option( 'woocommerce_dimension_unit' ) ),
            'select_spp_label'     => __( 'Select Service Point', 'sendcloud-shipping' ),
            'shipping_data'        => Checkout_Shipping_Handler::create_shipment_data(),
            'locale_messages'      => \Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Checkout_Handler::get_nominated_day_locale_messages(),
        ) ), View::get_allowed_tags() );
	}

	/**
	 * Adds service point shipping method
	 *
	 * @param $method
	 */
	public function add_extra_shipping_method( $method ) {
        Logger::info( 'Checkout_Handler::add_extra_shipping_method(): ' .  'method id: ' . $method->method_id );

		if ( SendCloudShipping_Service_Point_Shipping_Method::ID === $method->method_id ) {
			$instance_id = $method->instance_id;
			if ( ! $instance_id ) {
				$id          = explode( ':', $method->id );
				$instance_id = ! empty( $id[1] ) ? $id[1] : null;
			}
			$carriers = $this->get_service_point_config_repository()->get( $instance_id )->get_carriers();
            Logger::info( 'Checkout_Handler::add_extra_shipping_method(): ' .  'carriers: ' . $carriers );

			echo wp_kses( View::file( '/widgets/checkout/extra-shipping-method.php' )->render( array(
				'field_id'       => $method->id . ':carrier_select',
				'carrier_select' => isset( $carriers ) ? $this->removeAllSpaces($carriers) : ''
			) ), View::get_allowed_tags() );
		}
	}

	/**
	 * Checks whether or not user chose service point before creating an order
	 */
	public function add_notice_if_service_point_not_chosen() {
        Logger::info( 'Checkout_Handler::add_notice_if_service_point_not_chosen() invoked ' );

		$servicePointSM = $this->get_selected_shipping_method_id_key();
		$nonce          = $this->get_nonce();

		$servicePointSelected = isset( $_POST[ self::SERVICE_POINT_ID ] )
								&& ( ( $nonce && wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce-process_checkout' ) )
									 || WC()->session->get( 'reload_checkout', false ) )
								&& ! empty( $_POST[ self::SERVICE_POINT_ID ] );
		if ( SendCloudShipping_Service_Point_Shipping_Method::ID === $servicePointSM && ! $servicePointSelected ) {
			wc_add_notice( __( 'Please choose a service point.', 'sendcloud-shipping' ), 'error' );
		}
	}

	/**
	 * Updates post meta field if service point is selected
	 *
	 * @param $order_id
	 */
	public function update_order_meta( $order_id ) {
        Logger::info( 'Checkout_Handler::update_order_meta(): ' .  'order id: ' . $order_id );

		$nonce                = $this->get_nonce();
		$servicePointSelected = isset( $_POST[ self::SERVICE_POINT_ID ], $_POST[ self::SERVICE_POINT_EXTRA_FIELD_NAME ], $_POST[ self::SERVICE_POINT_POST_NUMBER_FIELD_NAME ] )
								&& ( ( $nonce && wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce-process_checkout' ) )
									 || WC()->session->get( 'reload_checkout', false ) );
        Logger::info( 'Checkout_Handler::update_order_meta(): ' .  'service point selected: ' . $servicePointSelected );

		if ( $servicePointSelected ) {
			$service_point = new Service_Point_Meta();
			$service_point->set_id( sanitize_text_field( $_POST[ self::SERVICE_POINT_ID ] ) );
			$service_point->set_extra( sanitize_text_field( $_POST[ self::SERVICE_POINT_EXTRA_FIELD_NAME ] ) );
			$service_point->set_post_number( sanitize_text_field( $_POST[ self::SERVICE_POINT_POST_NUMBER_FIELD_NAME ] ) );

			$this->get_service_point_meta_repository()->save( $order_id, $service_point );
		}
	}

	/**
	 * Adds service point information in the order details page
	 *
	 * @param $order
	 */
	public function add_service_point_data_in_admin_order( $order ) {
		if ( version_compare( WC()->version, '3.0', '>=' ) ) {
			$order_id = $order->get_id();
		} else {
			$order_id = $order->id;
		}

        Logger::info( 'Checkout_Handler::add_service_point_data_in_admin_order(): ' .  'order id: ' . $order_id );

		$service_point = $this->get_service_point_meta_repository()->get( $order_id );
		if ( $service_point ) {
			echo wp_kses( View::file( '/widgets/checkout/extend-order-details.php' )->render( array(
				'address'     => $service_point->get_extra(),
				'post_number' => $service_point->get_post_number()
			) ), View::get_allowed_tags() );
		}
	}

	/**
	 * Adds service point information in the order thank you page
	 *
	 * @param $order_id
	 */
	public function add_service_point_info( $order_id ) {
        Logger::info( 'Checkout_Handler::add_service_point_info(): ' .  'order id: ' . $order_id );
		$service_point = $this->get_service_point_meta_repository()->get( $order_id );
		if ( $service_point ) {
            Logger::info( 'Checkout_Handler::add_service_point_info(): ' .  'service point: ' . json_encode($service_point->to_array()) );
			echo wp_kses( View::file( '/widgets/checkout/service-point-legacy-thank-you-widget.php' )->render( array(
				'address'     => $service_point->get_extra(),
				'post_number' => $service_point->get_post_number()
			) ), View::get_allowed_tags() );
		}
	}

	/**
	 * Gets product dimensions
	 *
	 * @return array
	 */
	private function cart_max_dimensions() {
		$dimensions = array();
		$cart       = WC()->cart;

		foreach ( $cart->get_cart() as $cart_item_key => $values ) {
			$product = $values['data'];
			if ( $product->has_dimensions() ) {
				$dimensions[] = array(
					$product->get_length(),
					$product->get_width(),
					$product->get_height()
				);
			}
		}

		return $dimensions;
	}

	/**
	 * Get selected shipping method id key
	 *
	 * @return mixed|string
	 */
	private function get_selected_shipping_method_id_key() {
		$nonce          = $this->get_nonce();

		if ( isset( $_POST['shipping_method'][0] )
			 && $nonce
			 && wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce-process_checkout' ) ) {
			$parts          = explode( ':', sanitize_text_field( $_POST['shipping_method'][0] ) );
			return $parts[0];
		}

		/*
		 * In case where the customer account is created on checkout, nonce value is already verified by the base
		 * checkout action at the start, then the customer account is created, after that nonce value is no longer valid
		 * for the newly started session.
		 * The reload_checkout flag is set on session by the WooCommerce system so it is safe to use as an indicator
		 * of that checkout case, and since the nonce was already verified, it is safe to read request parameters.
		 */
		$is_checkout_reload = WC()->session->get( 'reload_checkout', false );
		if ( isset( $_POST['shipping_method'][0] ) && $is_checkout_reload ) {
			$parts          = explode( ':', sanitize_text_field( $_POST['shipping_method'][0] ) );
			return $parts[0];
		}

		return '';
	}

	/**
	 * Remove all spaces from a string
	 *
	 * @param string $str
	 *
	 * @return array|string|string[]
	 */
	private function removeAllSpaces(string $str) {
		// Replace all spaces with an empty string
		return str_replace(' ', '', $str);
	}

	/**
	 * Returns service point configuration repository
	 *
	 * @return Service_Point_Configuration_Repository
	 */
	private function get_service_point_config_repository() {
		if ( ! $this->service_point_config_repository ) {
			$this->service_point_config_repository = new Service_Point_Configuration_Repository();
		}

		return $this->service_point_config_repository;
	}

	/**
	 * Returns service point meta repository
	 *
	 * @return Service_Point_Meta_Repository
	 */
	private function get_service_point_meta_repository() {
		if ( ! $this->service_point_meta_repository ) {
			$this->service_point_meta_repository = new Service_Point_Meta_Repository();
		}

		return $this->service_point_meta_repository;
	}

	/**
	 * Fetches nonce from request. If nonce is not set in request, it will return null.
	 *
	 * @return mixed
	 */
	private function get_nonce() {
		if ( isset( $_REQUEST['woocommerce-process-checkout-nonce'] ) ) {
			return wp_kses_post( $_REQUEST['woocommerce-process-checkout-nonce'] );
		}

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			return wp_kses_post( $_REQUEST['_wpnonce'] );
		}

		return null;
	}
}
