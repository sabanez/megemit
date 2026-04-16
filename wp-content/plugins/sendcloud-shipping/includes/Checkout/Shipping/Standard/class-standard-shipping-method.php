<?php

namespace Sendcloud\Shipping\Checkout\Shipping\Standard;

use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Delivery_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Standard_Shipping_Method extends Free_Shipping_Delivery_Method {
	const CLASS_NAME = __CLASS__;

	const ID = 'sc_standard';

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
		$this->method_title       = __( 'Standard Delivery (deprecated)', 'sendcloud-shipping' );
		$this->method_description = __( 'Set up next day, standard, international depending on carrier. Configure the method in the Sendcloud Checkout panel.',
			'sendcloud-shipping' );

		parent::init();
	}
}
