<?php

namespace Sendcloud\Shipping\Checkout\Shipping\NominatedDay;

use Sendcloud\Shipping\Checkout\Shipping\Base_Checkout_Handler;
use Sendcloud\Shipping\Repositories\Checkout_Payload_Meta_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;
use WC_Shipping_Rate;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Checkout_Handler extends Base_Checkout_Handler {

	/**
	 * Hooks all checkout functions
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', new Logging_Callable( array( $this, 'register_scripts' ) ) );
		add_filter( 'clean_url', new Logging_Callable( array( $this, 'bypass_widget_src_cleanup' ) ), 10, 2 );
		add_filter( 'script_loader_tag', array( $this, 'defer_widget_scrip_tag' ), 10, 2 );

		add_action( 'woocommerce_checkout_after_order_review',
			new Logging_Callable( array( $this, 'initialize_nominated_day_widget' ) ) );
		add_action( 'woocommerce_after_shipping_rate', new Logging_Callable( array(
			$this,
			'render_nominated_day_widget'
		) ), 10,
			2 );
		add_action( 'woocommerce_checkout_process', new Logging_Callable( array(
			$this,
			'validate_nominated_day_selection'
		) ) );
		add_action( 'woocommerce_checkout_update_order_meta', new Logging_Callable( array(
			$this,
			'save_nominated_day_meta'
		) ) );

		// compatibility with WooFunnel plugin
		add_action( 'wfacp_checkout_after_order_review', new Logging_Callable( array(
			$this,
			'initialize_nominated_day_widget'
		) ) );

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			new Logging_Callable( array( $this, 'add_script_to_checkout_block' ) ), 111
		);

		add_action( 'woocommerce_store_api_checkout_order_processed', new Logging_Callable( array(
			$this,
			'wc_blocks_validate_nominated_day_selection'
		) ) );
	}

	/**
	 * Process place order event when woocommerce blocks are used in checkout
	 *
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function wc_blocks_validate_nominated_day_selection( \WC_Order $order ) {
		$data                    = WC()->session->get( 'sc_data' );
		$chosen_shipping_methods = wc()->session->get( 'chosen_shipping_methods', '' );
		$shipping_method_id      = explode( ':', reset( $chosen_shipping_methods ) )[0];

		if ( Nominated_Day_Shipping_Method::ID !== $shipping_method_id || empty($order->get_items('shipping'))) {
			return;
		}

		if ( ! $data ) {
			wc_add_notice( __( 'Please choose a delivery date.', 'sendcloud-shipping' ), 'error' );
		} else {
			$checkout_payload_meta_repo = new Checkout_Payload_Meta_Repository();
			$checkout_payload_meta_repo->save_raw( $order->get_id(), $data );
			WC()->session->set( 'sc_data', null );
		}
	}



	/**
	 * Initializes nominated day widget for usage in checkout page
	 */
	public function initialize_nominated_day_widget() {
		wp_enqueue_script( static::WIDGET_JS_HANDLE );
		wp_enqueue_script( static::WIDGET_JS_CONTROLLER_HANDLE );
		echo wp_kses( View::file( '/widgets/checkout/nominated-day-widget-locale-messages.php' )->render( array(
			'locale_messages' => self::get_nominated_day_locale_messages(),
		) ), View::get_allowed_tags() );
	}

	/**
	 * Initializes nominated day shipping method widget
	 *
	 * @param WC_Shipping_Rate $method
	 */
	public function render_nominated_day_widget( $method, $index ) {
		if ( Nominated_Day_Shipping_Method::ID !== $method->method_id ) {
			Logger::info( 'NominatedDay/Checkout_Handler::render_nominated_day_widget(): nominated day shipping method is not selected.' );

			return;
		}

		Logger::info( 'NominatedDay/Checkout_Handler::render_nominated_day_widget(): nominated day shipping method is selected.' );
		$this->render_widget( $method, $index );
	}

	/**
	 * Validates submitted nominated day data (if any) before order is created
	 */
	public function validate_nominated_day_selection() {
		$selected_shipping_method = $this->extract_shipping_method_id();

		if ( Nominated_Day_Shipping_Method::ID !== $selected_shipping_method ) {
			Logger::info( 'NominatedDay/Checkout_Handler::validate_nominated_day_selection(): nominated day shipping method is not selected.' );

			return;
		}

		Logger::info( 'NominatedDay/Checkout_Handler::validate_nominated_day_selection(): nominated day shipping method is selected.' );
		$nominated_day_data = $this->extract_widget_submit_data( Nominated_Day_Shipping_Method::ID );
		if ( empty( $nominated_day_data['delivery_method_data'] ) ) {
			wc_add_notice( __( 'Please choose a delivery date.', 'sendcloud-shipping' ), 'error' );
		}
	}

	public function save_nominated_day_meta( $order_id ) {
		Logger::info( 'NominatedDay/Checkout_Handler::save_nominated_day_meta(): ' . 'order id:' . $order_id );

		$nominated_day_data = $this->extract_widget_submit_data( Nominated_Day_Shipping_Method::ID );
		if ( ! empty( $nominated_day_data ) ) {
			$checkout_payload_meta_repo = new Checkout_Payload_Meta_Repository();
			$checkout_payload_meta_repo->save_raw( $order_id, $nominated_day_data );
		}
	}

	/**
	 * @return array
	 */
	public static function get_nominated_day_locale_messages() {
		return array(
			'Delivered by {carrier}'                                                                                                           => __( 'Delivered by {carrier}', 'sendcloud-shipping' ),
			'Clear search'                                                                                                                     => __( 'Clear search', 'sendcloud-shipping' ),
			'Close'                                                                                                                            => __( 'Close', 'sendcloud-shipping' ),
			'Closed tomorrow'                                                                                                                  => __( 'Closed tomorrow', 'sendcloud-shipping' ),
			'Closed'                                                                                                                           => __( 'Closed', 'sendcloud-shipping' ),
			'Could not find any service points for this location.'                                                                             => __( 'Could not find any service points for this location.', 'sendcloud-shipping' ),
			'Day'                                                                                                                              => __( 'Day', 'sendcloud-shipping' ),
			'Friday'                                                                                                                           => __( 'Friday', 'sendcloud-shipping' ),
			'Heads Up! The opening times of this service point have changed. If the opening times are still OK to you, click on select again.' => __( 'Heads Up! The opening times of this service point have changed. If the opening times are still OK to you, click on select again.', 'sendcloud-shipping' ),
			'Monday'                                                                                                                           => __( 'Monday', 'sendcloud-shipping' ),
			'One of the items in your cart is too large to be shipped to a service point.'                                                     => __( 'One of the items in your cart is too large to be shipped to a service point.', 'sendcloud-shipping' ),
			'Open tomorrow'                                                                                                                    => __( 'Open tomorrow', 'sendcloud-shipping' ),
			'Opening times'                                                                                                                    => __( 'Opening times', 'sendcloud-shipping' ),
			'PO box number is required.'                                                                                                       => __( 'PO box number is required.', 'sendcloud-shipping' ),
			'PO box number'                                                                                                                    => __( 'PO box number', 'sendcloud-shipping' ),
			'Post office box'                                                                                                                  => __( 'Post office box', 'sendcloud-shipping' ),
			'Saturday'                                                                                                                         => __( 'Saturday', 'sendcloud-shipping' ),
			'Search for service point locations'                                                                                               => __( 'Search for service point locations', 'sendcloud-shipping' ),
			'Search'                                                                                                                           => __( 'Search', 'sendcloud-shipping' ),
			'Select'                                                                                                                           => __( 'Select', 'sendcloud-shipping' ),
			'Selected'                                                                                                                         => __( 'Selected', 'sendcloud-shipping' ),
			'Select location'                                                                                                                  => __( 'Select location', 'sendcloud-shipping' ),
			'Sorry, this service point is no longer available.'                                                                                => __( 'Sorry, this service point is no longer available.', 'sendcloud-shipping' ),
			'Sorry, we were unable to record your selected service point. Please try again.'                                                   => __( 'Sorry, we were unable to record your selected service point. Please try again.', 'sendcloud-shipping' ),
			'Sunday'                                                                                                                           => __( 'Sunday', 'sendcloud-shipping' ),
			'Temporarily unavailable'                                                                                                          => __( 'Temporarily unavailable', 'sendcloud-shipping' ),
			'Thursday'                                                                                                                         => __( 'Thursday', 'sendcloud-shipping' ),
			'Toggle list'                                                                                                                      => __( 'Toggle list', 'sendcloud-shipping' ),
			'Toggle map'                                                                                                                       => __( 'Toggle map', 'sendcloud-shipping' ),
			'Tuesday'                                                                                                                          => __( 'Tuesday', 'sendcloud-shipping' ),
			'We couldn’t display this delivery option. Choose another option to continue.'                                                     => __( 'We couldn’t display this delivery option. Choose another option to continue.', 'sendcloud-shipping' ),
			'View opening times'                                                                                                               => __( 'View opening times', 'sendcloud-shipping' ),
			'Wednesday'                                                                                                                        => __( 'Wednesday', 'sendcloud-shipping' ),
		);
	}
}
