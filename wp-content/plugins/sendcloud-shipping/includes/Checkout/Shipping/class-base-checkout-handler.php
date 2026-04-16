<?php

namespace Sendcloud\Shipping\Checkout\Shipping;

use SendCloud\Checkout\Domain\Delivery\Availability\Order;
use SendCloud\Checkout\Domain\Delivery\Availability\OrderItem;
use SendCloud\Checkout\Domain\Delivery\Availability\Weight;
use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Checkout_Handler as NominatedDayCheckoutHandler;
use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Checkout_Handler as ServicePointCheckoutHandler;
use Sendcloud\Shipping\Repositories\Delivery_Methods_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;

class Base_Checkout_Handler {
	const CHECKOUT_PLUGIN_UI_JS = 'https://cdn.jsdelivr.net/npm/@sendcloud/checkout-plugin-ui@^2.0.0/dist/checkout-plugin-ui-loader.js';
	const WIDGET_JS_CONTROLLER_HANDLE = 'sendcloud-checkout-widget-controller';
	const WIDGET_JS_HANDLE = 'sendcloud-checkout-widget';
    const SHIPPING_METHODS_JS_HANDLE = 'sendcloud-shipping-methods';

	/**
	 * Hooks all checkout functions
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'woocommerce_after_shipping_rate', new Logging_Callable( array(
			$this,
			'render_description'
		) ), 10,
			2 );
	}

	/**
	 * Add JS for checkout blocks
	 *
	 * @return void
	 */
	public function add_script_to_checkout_block() {
		wp_enqueue_script( self::SHIPPING_METHODS_JS_HANDLE,
			Sendcloud::get_plugin_url( 'resources/js/sendcloud.checkout-shipping-methods-controller.js' ),
			array( 'jquery' ), Sendcloud::VERSION, true );
		echo wp_kses( View::file( '/widgets/checkout/sendcloud-block-checkout.php' )->render(
			array(
				'shipping_data'   => ServicePointCheckoutHandler::create_shipment_data(),
				'locale_messages' => NominatedDayCheckoutHandler::get_nominated_day_locale_messages()
			)
		), View::get_allowed_tags() );
	}

	/**
	 * Register all required JS and CSS scripts for the nominated day and service point widget rendering on the checkout page
	 */
	public function register_scripts() {
        wp_enqueue_script( static::WIDGET_JS_HANDLE, static::CHECKOUT_PLUGIN_UI_JS, array(), Sendcloud::VERSION, true );
        wp_enqueue_script( static::WIDGET_JS_CONTROLLER_HANDLE,
			Sendcloud::get_plugin_url( 'resources/js/sendcloud.checkout-widget-controller.js' ),
			array( 'jquery', static::WIDGET_JS_HANDLE ), Sendcloud::VERSION, true );
		wp_enqueue_style( 'sendcloud-checkout-css', Sendcloud::get_plugin_url( 'resources/css/sendcloud-checkout.css' ), array(),
			Sendcloud::VERSION );
	}

	/**
	 * Render delivery method description
	 *
	 * @param $method
	 * @param $index
	 *
	 * @return void
	 */
	public function render_description( $method, $index ) {
		$delivery_method_config = $this->get_delivery_method_config( $this->get_method_instance_id( $method ) );
		if ( empty( $delivery_method_config ) || 0 !== $index || empty($delivery_method_config['description'])) {
			return;
		}

		echo wp_kses( View::file( '/widgets/checkout/checkout-description.php' )->render( array(
			'delivery_method_config' => $delivery_method_config
		) ), View::get_allowed_tags() );
	}

	/**
	 * Render widget
	 *
	 * @param $method
	 * @param $index
	 *
	 * @return void
	 */
	public function render_widget( $method, $index ) {
		$delivery_method_config = $this->get_delivery_method_config( $this->get_method_instance_id( $method ) );
		if ( empty( $delivery_method_config ) || 0 !== $index ) {
			return;
		}

		echo wp_kses( View::file( '/widgets/checkout/checkout-widget.php' )->render( array(
			'index'                  => $index,
			'shipping_method_id'     => $method->id,
			'delivery_method_config' => $delivery_method_config,
			'locale'                 => $this->get_locale(),
		) ), View::get_allowed_tags() );
	}

	/**
	 * Add defer attribute during rendering of the widget script tag
	 *
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public function defer_widget_scrip_tag( $tag, $handle ) {
		if ( static::WIDGET_JS_HANDLE === $handle ) {
			return (string) str_replace( array( ' src', 'text/javascript' ), array( ' defer src', 'module' ), $tag );
		}

		return $tag;
	}

	/**
	 * Clean url
	 *
	 * @param $cleaned_url
	 * @param $original_url
	 *
	 * @return mixed|string
	 */
	public function bypass_widget_src_cleanup( $cleaned_url, $original_url ) {
		if ( self::CHECKOUT_PLUGIN_UI_JS === $original_url ) {
			return $original_url;
		}

		return $cleaned_url;
	}

	/**
	 * Create order
	 *
	 * @return Order
	 */
	protected function create_order() {
		$items       = array();
		$weight_unit = get_option( 'woocommerce_weight_unit' );
		$cart_items  = WC()->cart->get_cart();
		foreach ( $cart_items as $cart_item ) {
			$data    = $cart_item['data'];
			$weight  = (float) $data->get_data()['weight'];
			$items[] = new OrderItem( $cart_item['id'], new Weight( $weight, $weight_unit ), $cart_item['quantity'] );
		}

		return new Order( null, $items );
	}

	/**
	 * Extracts delivery data from posted data
	 *
	 * @param $shipping_method_id
	 *
	 * @return array Submitted widget data or empty array
	 */
	protected function extract_widget_submit_data( $shipping_method_id ) {
		$selected_shipping_method = $this->extract_shipping_method_id();
        Logger::info(
            'Base/Checkout_Handler::extract_widget_submit_data(): ' .
            'selected shipping method:' . $selected_shipping_method .
            ', shipping method id: ' . $shipping_method_id
        );

		if ( $selected_shipping_method !== $shipping_method_id ) {
			return array();
		}

		return $this->get_widget_submit_data();
	}

	/**
	 * Provides configuration for delivery method.
	 *
	 * @param int
	 * $method_id
	 *
	 * @return array
	 */
	protected function get_delivery_method_config( $method_id ) {
        Logger::info('Base_Checkout_Handler::get_delivery_method_config(): ' . 'method id:' . $method_id );

		global $wpdb;
		$repo   = new Delivery_Methods_Repository( $wpdb );
		$method = $repo->find_by_system_id( $method_id );
		if ( null === $method ) {
			return array();
		}

		return json_decode( $method->getRawConfig(), true );
	}

	/**
	 * Get locale
	 *
	 * @return string
	 */
	protected function get_locale() {
		if ( strpos( get_locale(), '_' ) ) {
			$localeParts = explode( '_', get_locale() );

			return implode( '-', array( strtolower( $localeParts[0] ), strtoupper( $localeParts[1] ) ) );
		}

		return get_locale();
	}

	protected function get_method_instance_id( $shipping_method ) {
        Logger::info('Base_Checkout_Handler::get_method_instance_id(): ' . 'shipping method id:' . $shipping_method->instance_id );

		if ( ! empty( $shipping_method->instance_id ) ) {
			return $shipping_method->instance_id;
		}

		$id = $shipping_method->id;
		$id = explode( ':', $id );

		return ! empty( $id[1] ) ? $id[1] : null;
	}

	/**
	 * Extracts selected shipping method id form posted data
	 *
	 * @return string
	 */
	protected function extract_shipping_method_id() {
		$selected_shipping_method = '';
		$shipping_method_parts    = $this->extract_shipping_method();
		if ( ! empty( $shipping_method_parts ) ) {
			$selected_shipping_method = (string) $shipping_method_parts[0];
		}

		return $selected_shipping_method;
	}

	/**
	 * Extract shipping method
	 *
	 * @return array|false|string[]
	 */
	protected function extract_shipping_method() {
		$nonce = $this->get_nonce();
		if ( isset( $_POST['shipping_method'][0] )
			 && $nonce
			 && wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce-process_checkout' ) ) {
			return explode( ':', sanitize_text_field( $_POST['shipping_method'][0] ) );
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
			return explode( ':', sanitize_text_field( $_POST['shipping_method'][0] ) );
		}

		return [];
	}

	/**
	 * Get widget submit data
	 *
	 * @return array|mixed
	 */
	private function get_widget_submit_data() {
		$nonce                           = $this->get_nonce();
		$selected_shipping_method_id_key = '';
		$shipping_method_selected        = isset( $_POST ['shipping_method'][0] )
										   && ( ( $nonce && wp_verify_nonce( sanitize_text_field( $nonce ), 'woocommerce-process_checkout' ) )
												|| WC()->session->get( 'reload_checkout', false ) );

		if ( $shipping_method_selected ) {
			$selected_shipping_method_id_key = esc_attr( sanitize_title( $_POST['shipping_method'][0] ) );
		}

		if ( empty( $_POST['sendcloudshipping_widget_submit_data'][ $selected_shipping_method_id_key ] ) ) {
			return array();
		}

		return json_decode( html_entity_decode( stripslashes( sanitize_text_field( $_POST['sendcloudshipping_widget_submit_data'][ $selected_shipping_method_id_key ] ) ) ),
			true );
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
