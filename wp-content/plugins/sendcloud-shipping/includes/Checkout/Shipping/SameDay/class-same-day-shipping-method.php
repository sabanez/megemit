<?php

namespace  Sendcloud\Shipping\Checkout\Shipping\SameDay;

use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Delivery_Method;

class Same_Day_Shipping_Method extends Free_Shipping_Delivery_Method {
	const CLASS_NAME = __CLASS__;

	const ID = 'sc_same_day';

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
		$this->method_title       = __( 'Same Day Delivery (deprecated)', 'sendcloud-shipping' );
		$this->method_description = __( 'Offer same day delivery before a chosen cut-off time. Configure the method in the Sendcloud Checkout panel.',
			'sendcloud-shipping' );

		parent::init();
	}
}
