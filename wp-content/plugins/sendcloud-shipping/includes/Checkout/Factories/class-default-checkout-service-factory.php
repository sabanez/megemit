<?php

namespace Sendcloud\Shipping\Checkout\Factories;

use SendCloud\Checkout\Contracts\Facades\CheckoutService;
use SendCloud\Checkout\Contracts\Proxies\Proxy;
use SendCloud\Checkout\Contracts\Services\DeliveryMethodService;
use SendCloud\Checkout\Contracts\Services\DeliveryMethodSetupService;
use SendCloud\Checkout\Contracts\Services\DeliveryZoneService;
use SendCloud\Checkout\Contracts\Services\DeliveryZoneSetupService;
use SendCloud\Checkout\Contracts\Storage\CheckoutStorage;
use Sendcloud\Shipping\Checkout\Interfaces\Checkout_Service_Factory;
use Sendcloud\Shipping\Checkout\Services\Delivery_Method_Setup_Service;
use Sendcloud\Shipping\Checkout\Services\Delivery_Zone_Setup_Service;
use Sendcloud\Shipping\Checkout\Services\Null_Delivery_Method_Service;
use Sendcloud\Shipping\Checkout\Services\Null_Delivery_Zone_Service;
use Sendcloud\Shipping\Checkout\Services\Proxy_Service;
use Sendcloud\Shipping\Checkout\Storage\Checkout_Storage;
use Sendcloud\Shipping\Repositories\Delivery_Methods_Repository;
use Sendcloud\Shipping\Repositories\Delivery_Zones_Repository;

/**
 * Class Default_Checkout_Service_Factory
 *
 * @package Sendcloud\Shipping\Checkout\Factories
 */
class Default_Checkout_Service_Factory implements Checkout_Service_Factory {
	/**
	 * CheckoutStorage
	 *
	 * @var CheckoutStorage
	 */
	private $storage;
	/**
	 * DeliveryMethodService
	 *
	 * @var DeliveryMethodService
	 */
	private $delivery_method_service;
	/**
	 * DeliveryZoneService
	 *
	 * @var DeliveryZoneService
	 */
	private $delivery_zone_service;
	/**
	 * DeliveryMethodSetupService
	 *
	 * @var DeliveryMethodSetupService
	 */
	private $delivery_method_setup_service;
	/**
	 * DeliveryZoneSetupService
	 *
	 * @var DeliveryZoneSetupService
	 */
	private $delivery_zone_setup_service;
	/**
	 * Proxy
	 *
	 * @var Proxy
	 */
	private $proxy;

	/**
	 * Default_Checkout_Service_Factory constructor.
	 */
	public function __construct() {
		global $wpdb;
		$zones_repository                    = new Delivery_Zones_Repository( $wpdb );
		$methods_repository                  = new Delivery_Methods_Repository( $wpdb );
		$this->storage                       = new Checkout_Storage( $zones_repository, $methods_repository );
		$this->delivery_method_service       = new \SendCloud\Checkout\Services\DeliveryMethodService( $this->storage );
		$this->delivery_zone_service         = new \SendCloud\Checkout\Services\DeliveryZoneService( $this->storage );
		$this->delivery_method_setup_service = $this->get_delivery_method_setup_service( $this->storage, $zones_repository );
		$this->delivery_zone_setup_service   = $this->get_delivery_zone_setup_service( $this->storage );
		$this->proxy                         = new Proxy_Service();
	}

	/**
	 * Provides checkout service.
	 *
	 * @return CheckoutService
	 */
	public function make() {
		return new \SendCloud\Checkout\CheckoutService(
			$this->delivery_zone_service,
			$this->delivery_zone_setup_service,
			$this->delivery_method_service,
			$this->delivery_method_setup_service,
			$this->proxy
		);
	}

	/**
	 * Instantiates delivery zone setup service.
	 *
	 * @param CheckoutStorage $storage
	 *
	 * @return Delivery_Zone_Setup_Service | Null_Delivery_Zone_Service
	 */
	private function get_delivery_zone_setup_service( CheckoutStorage $storage ) {
		if ( empty( $GLOBALS['woocommerce'] ) ) {
			return new Null_Delivery_Zone_Service();
		}

		return new Delivery_Zone_Setup_Service( $storage );
	}

	/**
	 * Instantiates delivery methods setup service.
	 *
	 * @param CheckoutStorage $storage
	 * @param Delivery_Zones_Repository $repo
	 *
	 * @return Delivery_Method_Setup_Service | Null_Delivery_Method_Service
	 */
	private function get_delivery_method_setup_service( CheckoutStorage $storage, Delivery_Zones_Repository $repo ) {
		if ( empty( $GLOBALS['woocommerce'] ) ) {
			return new Null_Delivery_Method_Service();
		}

		return new Delivery_Method_Setup_Service( $storage, $repo );
	}
}
