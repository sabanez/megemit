<?php

namespace Sendcloud\Shipping\Checkout\Shipping;

use Sendcloud\Shipping\Repositories\Checkout_Payload_Meta_Repository;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Order_View_Extender {

	const DELIVERY_METHODS = [ 'nominated_day_delivery', 'same_day_delivery', 'service_point_delivery' ];
	const BASE_WIDGETS_VIEW_FOLDER = '/widgets';
	/**
	 * Checkout_Payload_Meta_Repository
	 *
	 * @var Checkout_Payload_Meta_Repository
	 */
	private $checkout_payload_meta_repository;

	/**
	 * Hooks all checkout functions
	 */
	public function init() {
		add_action( 'woocommerce_thankyou', new Logging_Callable( array( $this, 'add_delivery_date_info' ) ), 11 );
		add_action( 'woocommerce_view_order', new Logging_Callable( array( $this, 'add_delivery_date_info' ) ), 11 );
		add_action( 'woocommerce_email_after_order_table',
			new Logging_Callable( array( $this, 'add_delivery_date_info_in_mail' ) ), 15 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address',
			new Logging_Callable( array( $this, 'add_delivery_date_info_in_admin_order' ) ), 11 );
	}

	/**
	 * Adds delivery date information in the order thank you, order view and email template pages
	 *
	 * @param $order_id
	 */
	public function add_delivery_date_info( $order_id ) {
        Logger::info('Order_View_Extender::add_delivery_date_info(): ' . 'order id:' . $order_id );

		$this->render( $order_id, '/checkout/thank-you-widget.php' );
	}

	/**
	 * Adds delivery date information in the order emails
	 *
	 * @param $order
	 */
	public function add_delivery_date_info_in_mail( $order ) {
        $order_id = $this->get_order_id( $order );
        Logger::info('Order_View_Extender::add_delivery_date_info_in_mail(): ' . 'order id:' . $order_id );

		$this->render( $order_id, '/mail/widget.php' );
	}

	/**
	 * Adds delivery date information in the order details page
	 *
	 * @param $order
	 */
	public function add_delivery_date_info_in_admin_order( $order ) {
        $order_id = $this->get_order_id( $order );
        Logger::info('Order_View_Extender::add_delivery_date_info_in_admin_order(): ' . 'order id:' . $order_id );

		$this->render( $order_id, '/checkout/extend-admin-order-details.php' );
	}

	/**
	 * Gets order for a given order
	 *
	 * @param $order
	 *
	 * @return mixed
	 */
	private function get_order_id( $order ) {
		if ( version_compare( WC()->version, '3.0', '>=' ) ) {
			return $order->get_id();
		}

		return $order->id;

	}

	/**
	 * Renders the widget template
	 *
	 * @param $order_id
	 * @param $template
	 *
	 * @return void
	 */
	private function render( $order_id, $template ) {
		$delivery_method_type = $this->get_checkout_payload_meta_repository()->get_delivery_method_type( $order_id );
		$delivery_method = $this->get_checkout_payload_meta_repository()->get_delivery_method_data( $order_id );

		if ( ! in_array( $delivery_method_type, self::DELIVERY_METHODS, true ) ) {
			return;
		}
		if ( ! $delivery_method ) {
			return;
		}
		if ( 'service_point_delivery' === $delivery_method_type ) {
			$template = '/service-point-delivery' . $template;
		}

		echo wp_kses(
			View::file( self::BASE_WIDGETS_VIEW_FOLDER . $template )->render( array( 'delivery_method_data' => $delivery_method ) ),
			View::get_allowed_tags()
		);
	}

	/**
	 * Returns checkout payload meta repository
	 *
	 * @return Checkout_Payload_Meta_Repository
	 */
	private function get_checkout_payload_meta_repository() {
		if ( ! $this->checkout_payload_meta_repository ) {
			$this->checkout_payload_meta_repository = new Checkout_Payload_Meta_Repository();
		}

		return $this->checkout_payload_meta_repository;
	}
}
