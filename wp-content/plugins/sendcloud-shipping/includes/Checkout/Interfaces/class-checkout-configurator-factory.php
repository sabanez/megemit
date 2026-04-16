<?php

namespace Sendcloud\Shipping\Checkout\Interfaces;

use SendCloud\Checkout\Configurator;

/**
 * Interface Checkout_Configurator_Factory
 *
 * @package Sendcloud\Shipping\Checkout\Interfaces
 */
interface Checkout_Configurator_Factory {
	/**
	 * Provides configurator instance.
	 *
	 * @return Configurator
	 */
	public function make();
}
