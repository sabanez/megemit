<?php

namespace Sendcloud\Shipping\Checkout\Services;

use Exception;
use SendCloud\Checkout\Contracts\Services\DeliveryZoneSetupService;
use SendCloud\Checkout\Contracts\Storage\CheckoutStorage;
use SendCloud\Checkout\Domain\Delivery\DeliveryZone;
use Sendcloud\Shipping\Utility\Logger;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Class Delivery_Zone_Setup_Service
 *
 * @package Sendcloud\Shipping\Checkout\Services
 */
class Delivery_Zone_Setup_Service implements DeliveryZoneSetupService {
	/**
	 * Checkout Storage
	 *
	 * @var CheckoutStorage
	 */
	private $storage;

	/**
	 * Delivery_Zone_Setup_Service constructor.
	 *
	 * @param CheckoutStorage $storage
	 */
	public function __construct( CheckoutStorage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Deletes specified zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function deleteSpecific( array $zones ) {
		foreach ( $zones as $zone ) {
			$this->do_delete( $zone );
		}
	}

	/**
	 * Deletes all created zones in system.
	 *
	 * @return void
	 */
	public function deleteAll() {
		$zones = $this->storage->findAllZoneConfigs();
		foreach ( $zones as $zone ) {
			$this->do_delete( $zone );
		}
	}

	/**
	 * Updates delivery zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function update( array $zones ) {
		foreach ( $zones as $zone ) {
			$this->do_update( $zone );
		}
	}

	/**
	 * Creates delivery zones.
	 *
	 * @param DeliveryZone[] $zones
	 *
	 * @return void
	 */
	public function create( array $zones ) {
		foreach ( $zones as $zone ) {
			$this->do_create( $zone );
		}
	}

	/**
	 * Deletes woocommerce delivery zone.
	 *
	 * @param DeliveryZone $zone
	 */
	private function do_delete( DeliveryZone $zone ) {
		try {
			WC_Shipping_Zones::delete_zone( $zone->getSystemId() );
		} catch ( Exception $e ) {
			Logger::error( 'Failed to delete woocommerce shipping zone', array( 'trace' => $e->getTraceAsString() ) );
		}
	}

	/**
	 * Creates new Woocommerce zone.
	 *
	 * @param DeliveryZone $zone
	 */
	private function do_create( DeliveryZone $zone ) {
		$wc_zone = new WC_Shipping_Zone();
		$this->persist( $zone, $wc_zone );
	}

	/**
	 * Performs update of a Woocommerce zone.
	 *
	 * @param DeliveryZone $zone
	 */
	private function do_update( DeliveryZone $zone ) {
		try {
			$wc_zone = new WC_Shipping_Zone( $zone->getSystemId() );
		} catch ( Exception $e ) {
			// User has deleted shipping zone in the mean time.
			$wc_zone = new WC_Shipping_Zone();
		}
		$this->persist( $zone, $wc_zone );
	}

	/**
	 * Sets delivery zone data
	 *
	 * @param DeliveryZone $zone
	 * @param WC_Shipping_Zone $wc_zone
	 */
	private function set_wc_zone_data( DeliveryZone $zone, WC_Shipping_Zone $wc_zone ) {
		$wc_zone->set_zone_name( $zone->getCountry()->getName() );
		$wc_zone->clear_locations();
		$wc_zone->add_location( strtoupper( $zone->getCountry()->getIsoCode() ), 'country' );
	}

	/**
	 * Persists Woocommerce shipping zone.
	 *
	 * @param DeliveryZone $zone
	 * @param WC_Shipping_Zone $wc_zone
	 */
	private function persist( DeliveryZone $zone, WC_Shipping_Zone $wc_zone ) {
		$this->set_wc_zone_data( $zone, $wc_zone );
		$wc_zone->save();
		$zone->setSystemId( $wc_zone->get_id() );
	}
}
