<?php

namespace Sendcloud\Shipping\Checkout\Interfaces;

use SendCloud\Checkout\Contracts\Facades\CheckoutService;

/**
 * Interface Checkout_Service_Factory
 *
 * @package Sendcloud\Shipping\Checkout\Interfaces
 */
interface Checkout_Service_Factory {
	/**
	 * Provides checkout service.
	 *
	 * @return CheckoutService
	 */
	public function make();
}
