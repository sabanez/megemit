<?php

namespace Sendcloud\Shipping\Checkout\Shipping\NominatedDay;

use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Delivery_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nominated_Day_Shipping_Method extends Free_Shipping_Delivery_Method {
	const CLASS_NAME = __CLASS__;

	const ID = 'sc_nominated_day';

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
		$this->method_title       = __( 'Nominated Day Delivery (deprecated)', 'sendcloud-shipping' );
		$this->method_description = __( 'Let your buyer choose the delivery date. Configure the method in the Sendcloud Checkout panel.',
			'sendcloud-shipping' );

		parent::init();
	}
}
