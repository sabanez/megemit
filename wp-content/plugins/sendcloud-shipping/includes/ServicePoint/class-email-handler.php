<?php

namespace Sendcloud\Shipping\ServicePoint;

use Sendcloud\Shipping\Repositories\Service_Point_Meta_Repository;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Email_Handler {
	const CLASS_NAME = __CLASS__;

	/**
	 * Service Point Meta Repository
	 *
	 * @var Service_Point_Meta_Repository
	 */
	private $service_point_meta_repository;

	/**
	 * Hooks email functions
	 */
	public function init() {
		add_action( 'woocommerce_email_after_order_table', new Logging_Callable( array(
			$this,
			'add_service_point_data_in_email',
		) ), 15, 2 );
	}

	/**
	 * Adds service point information in email
	 *
	 * @param $order
	 * @param $sent_to_admin
	 */
	public function add_service_point_data_in_email( $order, $sent_to_admin ) {
		if ( version_compare( WC()->version, '3.0', '>=' ) ) {
			$order_id = $order->get_id();
		} else {
			$order_id = $order->id;
		}
        Logger::info( 'Email_Handler::add_service_point_data_in_email(): ' .  'order id: ' . $order_id);
		$service_point = $this->get_service_point_meta_repository()->get( $order_id );
		if ( $service_point ) {
            Logger::info( 'Email_Handler::add_service_point_data_in_email(): ' .  'service point: ' . json_encode($service_point->to_array()) );
			echo wp_kses( View::file( '/widgets/mail/extend-order-created-mail.php' )->render(
				array(
					'address'     => $service_point->get_extra(),
					'post_number' => $service_point->get_post_number()
				) ), View::get_allowed_tags() );

		}
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

}
