<?php

namespace Sendcloud\Shipping\ServicePoint\Shipping;

use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Shipping_Method;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SendCloudShipping_Service_Point_Shipping_Method extends Free_Shipping_Shipping_Method {
	const CLASS_NAME = __CLASS__;

	const ID = 'service_point_shipping_method';

	/**
	 * Service Point Configuration Repository
	 *
	 * @var Service_Point_Configuration_Repository
	 */
	private $service_point_config_repository;

	/**
	 * Init user set variables.
	 */
	public function init() {
		$this->id                 = self::ID;
		$this->method_title       = __( 'Service Point Delivery', 'sendcloud-shipping' );
		$this->method_description = wp_kses( __( 'Deliver to a service point in the customer’s area.',
			'sendcloud-shipping' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) );

		parent::init();

		$this->carrier_select = $this->get_option( 'carrier_select' );
	}

	/**
	 * Checks if this method is enabled or not
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$script = $this->get_service_point_config_repository()->get()->get_script();
		if ( empty( $script ) ) {
			return false;
		}

		return parent::is_enabled();
	}

	/**
	 * Add extra fields
	 *
	 * @param $form_fields
	 */
	protected function add_extra_fields( &$form_fields ) {
		parent::add_extra_fields( $form_fields );
		$form_fields['carrier_select'] = array(
			'title'       => __( 'Carrier Selection', 'sendcloud-shipping' ),
			'type'        => 'text',
			'default'     => '',
			'desc_tip'    => true,
			'description' => __( 'A comma separated list of your Sendcloud enabled carrier codes (e.g. ups, dpd, dhl), an empty value here will display all your Sendcloud enabled carriers',
				'sendcloud-shipping' ),
		);
	}

	/**
	 * Calculates shipping costs
	 *
	 * @param array $package
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->check_free_shipping() ) {
			$this->add_rate( array(
				'id'      => $this->get_rate_id(),
				'label'   => $this->title,
				'cost'    => 0,
				'taxes'   => false,
				'package' => $package,
			) );
		} else {
			parent::calculate_shipping( $package );
		}
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
}
