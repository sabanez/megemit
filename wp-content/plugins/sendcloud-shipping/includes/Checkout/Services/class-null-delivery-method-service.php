<?php

namespace Sendcloud\Shipping\Checkout\Services;

use SendCloud\Checkout\Contracts\Services\DeliveryMethodSetupService;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;

/**
 * Class Null_Delivery_Method_Service
 * 
 * @package Sendcloud\Shipping\Checkout\Services
 */
class Null_Delivery_Method_Service implements DeliveryMethodSetupService {
	/**
	 * Deletes delivery methods specified in the provided batch.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function deleteSpecific( array $methods ) {
	}

	/**
	 * Deletes all delivery methods.
	 *
	 * @return void
	 */
	public function deleteAll() {
	}

	/**
	 * Updates delivery methods.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function update( array $methods ) {
	}

	/**
	 * Creates delivery methods.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function create( array $methods ) {
	}
}
