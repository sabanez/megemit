<?php

namespace Sendcloud\Shipping\Checkout\Services;

use InvalidArgumentException;
use SendCloud\Checkout\Contracts\Services\DeliveryMethodSetupService;
use SendCloud\Checkout\Contracts\Storage\CheckoutStorage;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Domain\Delivery\ShippingRateData;
use Sendcloud\Shipping\Checkout\Factories\Shipping_Method_Factory;
use Sendcloud\Shipping\Checkout\Shipping\Free_Shipping_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Nominated_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\SameDay\Same_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Service_Point_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\Standard\Standard_Shipping_Method;
use Sendcloud\Shipping\Repositories\Delivery_Zones_Repository;

/**
 * Class Delivery_Method_Setup_Service
 *
 * @package Sendcloud\Shipping\Checkout\Services
 */
class Delivery_Method_Setup_Service implements DeliveryMethodSetupService {
	private static $delivery_method_types = array(
		'standard_delivery'      => Standard_Shipping_Method::CLASS_NAME,
		'nominated_day_delivery' => Nominated_Day_Shipping_Method::CLASS_NAME,
		'same_day_delivery'      => Same_Day_Shipping_Method::CLASS_NAME,
		'service_point_delivery' => Service_Point_Shipping_Method::CLASS_NAME
	);
	/**
	 * Checkout Storage
	 *
	 * @var CheckoutStorage
	 */
	private $storage;
	/**
	 * Delivery Zones Repository
	 *
	 * @var Delivery_Zones_Repository
	 */
	private $repository;

	/**
	 * Delivery_Method_Setup_Service constructor.
	 *
	 * @param CheckoutStorage $storage
	 * @param Delivery_Zones_Repository $repository
	 */
	public function __construct( CheckoutStorage $storage, Delivery_Zones_Repository $repository ) {
		$this->storage    = $storage;
		$this->repository = $repository;
	}

	/**
	 * Deletes delivery methods specified in the provided batch.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function deleteSpecific( array $methods ) {
		foreach ( $methods as $method ) {
			$this->do_delete( $method );
		}
	}

	/**
	 * Deletes all delivery methods.
	 *
	 * @return void
	 */
	public function deleteAll() {
		$methods = $this->storage->findAllMethodConfigs();
		foreach ( $methods as $method ) {
			$this->do_delete( $method );
		}
	}

	/**
	 * Updates delivery methods.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function update( array $methods ) {
		foreach ( $methods as $method ) {
			$this->do_update( $method );
		}
	}

	/**
	 * Creates delivery methods.
	 *
	 * @param DeliveryMethod[] $methods
	 *
	 * @return void
	 */
	public function create( array $methods ) {
		foreach ( $methods as $method ) {
			$this->do_create( $method );
		}
	}

	/**
	 * Deletes wc shipping method.
	 *
	 * @param DeliveryMethod $method
	 */
	private function do_delete( DeliveryMethod $method ) {
		$zone = $this->storage->findZoneConfigs( array( $method->getDeliveryZoneId() ) );
		if ( empty( $zone[0] ) ) {
			// Nothing to delete. User already deleted zone.

			return;
		}

		$zone = $zone[0];

		$this->repository->remove_wc_delivery_method( $zone->getSystemId(), $method->getSystemId() );
	}

	/**
	 * Updates shipping method.
	 *
	 * @param DeliveryMethod $method
	 */
	private function do_update( DeliveryMethod $method ) {
		$zones = $this->storage->findZoneConfigs( array( $method->getDeliveryZoneId() ) );
		if ( empty( $zones[0] ) ) {
			throw new InvalidArgumentException( 'Delivery zone not found.' );
		}

		if ( ! array_key_exists( $method->getType(), self::$delivery_method_types ) ) {
			throw new InvalidArgumentException( 'Unknown delivery method.' );
		}

		$zone_config = $zones[0];
		if ( ! $this->repository->has_method( $zone_config->getSystemId(), $method->getSystemId() ) ) {
			$this->do_create( $method );

			return;
		}

		$instance = Shipping_Method_Factory::create( $method );
		$instance->init_instance_settings();
		$this->set_data( $method, $instance );
	}

	/**
	 * Creates shipping method by adding it to a shipping zone.
	 *
	 * @param DeliveryMethod $method
	 */
	private function do_create( DeliveryMethod $method ) {
		$zones = $this->storage->findZoneConfigs( array( $method->getDeliveryZoneId() ) );
		if ( empty( $zones[0] ) ) {
			throw new InvalidArgumentException( 'Delivery zone not found.' );
		}

		if ( ! array_key_exists( $method->getType(), self::$delivery_method_types ) ) {
			throw new InvalidArgumentException( 'Unknown delivery method.' );
		}

		$zone_config = $zones[0];
		$count       = $this->repository->get_method_count( $zone_config->getSystemId() );
		$instance_id = $this->repository->add_wc_delivery_method_to_wc_delivery_zone(
			self::$delivery_method_types[ $method->getType() ]::ID,
			$zone_config->getSystemId(),
			$count + 1
		);
		$method->setSystemId( $instance_id );
		$instance = Shipping_Method_Factory::create( $method );
		$this->set_data( $method, $instance );
	}

	/**
	 * Sets shipping method data.
	 *
	 * @param DeliveryMethod $method
	 * @param Nominated_Day_Shipping_Method $instance
	 */
	private function set_data( DeliveryMethod $method, Free_Shipping_Shipping_Method $instance ) {
		$instance->instance_settings['title']                 = $method->getExternalTitle();
		$instance->instance_settings['sc_delivery_method_id'] = $method->getId();
		if ( $method->getShippingRateData()->isEnabled() ) {
			$this->set_shipping_rates_config( $method->getShippingRateData(), $instance );
		}

		update_option(
			$instance->get_instance_option_key(),
			/**
			 * Apply filters for delivery method update
			 *
			 * @since 2.0.0
			 */
			apply_filters(
				'woocommerce_shipping_' . $instance->id . '_instance_settings_values',
				$instance->instance_settings,
				$instance
			)
		);
	}

	/**
	 * Set shipping rates configuration
	 *
	 * @param ShippingRateData $shipping_rate_data
	 * @param Free_Shipping_Shipping_Method $instance
	 */
	private function set_shipping_rates_config( ShippingRateData $shipping_rate_data, Free_Shipping_Shipping_Method $instance ) {
		$free_shipping                                           = $shipping_rate_data->getFreeShipping();
		$instance->instance_settings['free_shipping_enabled']    = $free_shipping->isEnabled() ? 'yes' : 'no';
		$instance->instance_settings['free_shipping_min_amount'] = $free_shipping->getFromAmount();
		$instance->instance_settings['disable_cost']             = $shipping_rate_data->isEnabled();
		$rates                                                   = array();
		foreach ( $shipping_rate_data->getShippingRates() as $shipping_rate ) {
			if ( $shipping_rate->getMinWeight() !== null && $shipping_rate->getMaxWeight() !== null ) {
				$rates['items'][] = array(
					'min_weight' => $shipping_rate->getMinWeight(),
					'max_weight' => $shipping_rate->getMaxWeight(),
					'rate'       => $shipping_rate->getRate(),
					'is_enabled' => $shipping_rate->isEnabled(),
				);
			}

			if ( ( $shipping_rate->getMinWeight() === null && $shipping_rate->getMaxWeight() === null )
				 || $shipping_rate->isDefault() ) {
				$rates['default_rate'] = $shipping_rate->getRate();
			}
		}

		$instance->instance_settings['sc_shipping_rates'] = $rates;
	}
}
