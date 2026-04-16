<?php

namespace Sendcloud\Shipping\Checkout\Shipping\ServicePoint;

use SendCloud\Checkout\Exceptions\Unit\UnitNotSupportedException;
use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Delivery_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Service_Point_Shipping_Method extends Free_Shipping_Delivery_Method {
	const CLASS_NAME = __CLASS__;

	const ID = 'sc_service_point';

	/**
	 * Delivery method ID
	 *
	 * @var int
	 */
	public $sc_delivery_method_id;

	/**
	 * Init user set variables.
	 */
	public function init() {
		$this->id                 = self::ID;
		$this->method_title       = __( 'Service Point Delivery (deprecated)', 'sendcloud-shipping' );
		$this->method_description = __( 'Deliver to a service point in the customer’s area.',
			'sendcloud-shipping' );

		parent::init();
	}

	/**
	 * Calculate shipping cost if free shipping is not enabled
	 *
	 * @param $package
	 *
	 * @return int|mixed
	 * @throws UnitNotSupportedException
	 */
	public function calculate_shipping_cost( $package ) {
		$shipping_rates = $this->get_option('sc_shipping_rates');
		if ( isset( $shipping_rates['items'] ) ) {
			return parent::calculate_shipping_cost( $package );
		}

		$default_rate = $shipping_rates['default_rate'];
		if ( $default_rate ) {
			return $default_rate;
		}

		return 0;
	}
}
