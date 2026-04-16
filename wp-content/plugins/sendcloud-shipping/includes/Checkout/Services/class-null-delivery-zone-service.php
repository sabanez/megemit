<?php

namespace Sendcloud\Shipping\Checkout\Services;

use SendCloud\Checkout\Contracts\Services\DeliveryZoneSetupService;
use SendCloud\Checkout\Domain\Delivery\DeliveryZone;

/**
 * Class Null_Delivery_Zone_Service
 *
 * @package Sendcloud\Shipping\Checkout\Services
 */
class Null_Delivery_Zone_Service implements DeliveryZoneSetupService {
	/**
	 * Deletes specified zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function deleteSpecific( array $zones ) {
	}

	/**
	 * Deletes all created zones in system.
	 *
	 * @return void
	 */
	public function deleteAll() {
	}

	/**
	 * Updates delivery zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function update( array $zones ) {
	}

	/**
	 * Creates delivery zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function create( array $zones ) {
	}
}
