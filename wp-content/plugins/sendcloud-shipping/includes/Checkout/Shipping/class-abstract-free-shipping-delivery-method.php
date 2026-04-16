<?php

namespace Sendcloud\Shipping\Checkout\Shipping;

use Exception;
use Sendcloud\Shipping\Utility\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Free_Shipping_Delivery_Method extends Free_Shipping_Shipping_Method {

	/**
	 * Init user set variables.
	 */
	public function init() {
		parent::init();

		$this->sc_delivery_method_id = $this->get_option( 'sc_delivery_method_id' );
	}

	/**
	 * Is this method available?
	 *
	 * @param array $package
	 *
	 * @return bool
	 */
	public function is_available( $package ) {
		$is_available = parent::is_available( $package );

		if ( $is_available ) {
			try {
				$delivery_method = $this->get_delivery_method();
				if ( null === $delivery_method ) {
					return false;
				}

				$order = $this->create_order($package);
				$is_available = $delivery_method->isAvailable($order);
			} catch ( Exception $exception ) {
				Logger::error( 'Error while checking method availability. ' . $exception->getMessage() );

				return false;
			}
		}

		return $is_available;
	}
}
