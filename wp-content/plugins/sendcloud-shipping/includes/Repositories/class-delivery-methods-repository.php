<?php

namespace Sendcloud\Shipping\Repositories;

use SendCloud\Checkout\Domain\Delivery\Carrier;
use SendCloud\Checkout\Domain\Delivery\DeliveryDay;
use SendCloud\Checkout\Domain\Delivery\DeliveryMethod;
use SendCloud\Checkout\Domain\Delivery\FreeShipping;
use SendCloud\Checkout\Domain\Delivery\HandoverDay;
use SendCloud\Checkout\Domain\Delivery\Holiday;
use SendCloud\Checkout\Domain\Delivery\ServicePointData;
use SendCloud\Checkout\Domain\Delivery\ShippingProduct;
use SendCloud\Checkout\Domain\Delivery\ShippingRate;
use SendCloud\Checkout\Domain\Delivery\ShippingRateData;

/**
 * Class Delivery_Methods_Repository
 *
 * @package Sendcloud\Shipping\Repositories
 */
class Delivery_Methods_Repository extends Abstract_Domain_Repository {
	/**
	 * Creates delivery methods.
	 *
	 * @param array $methods
	 */
	public function create( array $methods ) {
		if ( empty( $methods ) ) {
			return;
		}

		$methods = array_map( function ( DeliveryMethod $method ) {
			return array(
				$method->getId(),
				$method->getSystemId(),
				$method->getDeliveryZoneId(),
				$method->getRawConfig()
			);
		}, $methods );

		$query = 'INSERT INTO ' . $this->get_table_name() . ' (`external_id`, `system_id`, `delivery_zone_id`, `data`) 
		          VALUES ' . $this->prepare_values( array( '%s', '%d', '%s', '%s' ), $methods );
		$this->db->query( $query );
	}

	/**
	 * Updates delivery methods.
	 *
	 * @param array $methods
	 */
	public function update( array $methods ) {
		if ( empty( $methods ) ) {
			return;
		}

		/**
		 * Delivery method
		 *
		 * @var DeliveryMethod $method
		 */
		foreach ( $methods as $method ) {
			$query = 'UPDATE ' . $this->get_table_name() . ' SET `external_id`=%s, `system_id`=%d, `delivery_zone_id`=%s, `data`=%s WHERE (`external_id`=%s)';
			$query = $this->db->prepare( $query, array(
				$method->getId(),
				$method->getSystemId(),
				$method->getDeliveryZoneId(),
				$method->getRawConfig(),
				$method->getId()
			) );
			$this->db->query( $query );
		}
	}

	public function delete_obsolete_method_configs() {
		$query = 'SELECT * 
				  FROM ' . $this->get_table_name() . " as dm
				  LEFT JOIN {$this->db->prefix}woocommerce_shipping_zone_methods as wcm on dm.system_id=wcm.instance_id
				  WHERE instance_id IS NULL";

		$result = $this->db->get_results( $query, ARRAY_A );
		$result = array_map( static function ( $item ) {
			return $item['external_id'];
		}, $result );

		if ( ! empty( $result ) ) {
			$this->delete( $result );
		}
	}

	/**
	 * Finds delivery methods in delivery zones.
	 *
	 * @param array $zone_ids
	 *
	 * @return array
	 */
	public function find_in_zones( array $zone_ids ) {
		if ( empty( $zone_ids ) ) {
			return array();
		}

		$query = 'SELECT * 
				   FROM ' . $this->get_table_name() . ' 
				   WHERE ' . $this->get_in_clause( 'delivery_zone_id', $zone_ids );

		$query  = $this->db->prepare( $query, $zone_ids );
		$result = $this->db->get_results( $query, ARRAY_A );

		return array_map( array( $this, 'transform_to_entity' ), $result );
	}

	/**
	 * Finds delivery method with specified system id.
	 *
	 * @param int $system_id
	 *
	 * @return DeliveryMethod
	 */
	public function find_by_system_id( $system_id ) {
		$query = 'SELECT * 
				   FROM ' . $this->get_table_name() . ' 
				   WHERE system_id=%d';

		$query  = $this->db->prepare( $query, $system_id );
		$result = $this->db->get_results( $query, ARRAY_A );

		return ! empty( $result[0] ) ? $this->transform_to_entity( $result[0] ) : null;
	}

	/**
	 * Deletes delivery method in zones.
	 *
	 * @param array $zone_ids
	 */
	public function delete_in_zones( array $zone_ids ) {
		if ( empty( $zone_ids ) ) {
			return;
		}

		$query = 'DELETE 
				  FROM ' . $this->get_table_name() . ' 
				  WHERE ' . $this->get_in_clause( 'delivery_zone_id', $zone_ids );

		$query = $this->db->prepare( $query, $zone_ids );
		$this->db->query( $query );
	}

	/**
	 * Deletes shipping methods with specified stem ids.
	 *
	 * @param array $system_ids
	 */
	public function delete_by_system_ids( array $system_ids ) {
		if ( empty( $system_ids ) ) {
			return;
		}

		$query = 'DELETE 
				  FROM ' . $this->get_table_name() . ' 
				  WHERE ' . $this->get_in_clause( 'system_id', $system_ids, '%d' );

		$query = $this->db->prepare( $query, $system_ids );
		$this->db->query( $query );
	}

	/**
	 * Transforms raw data to delivery method.
	 *
	 * @param array $raw
	 *
	 * @return DeliveryMethod
	 */
	protected function transform_to_entity( array $raw ) {
		$data = json_decode( $raw['data'], true );

		$carriers           = [];
		$service_point_data = null;
		$carrier            = null;
		$shipping_product   = null;
		$processing_days    = [];
		$holidays           = [];
		if ( 'service_point_delivery' === $data['delivery_method_type'] ) {
			foreach ( $data['carriers'] as $carrier ) {
				$carriers[] = $this->make_carrier( $carrier );
			}
			$service_point_data = $this->make_service_point_data( $data['service_point_data'] );
		} else {
			$carrier          = $this->make_carrier( $data['carrier'] );
			$shipping_product = $this->make_shipping_product( $data['shipping_product'] );
			$processing_days  = $this->get_processing_days( $data );
			$holidays         = $this->get_holidays( $data );
		}

		return new DeliveryMethod(
			$raw['external_id'],
			(int) $raw['system_id'],
			$raw['delivery_zone_id'],
			$data['delivery_method_type'],
			$data['external_title'],
			$data['internal_title'],
			array_key_exists( 'description', $data ) ? $data['description'] : '',
			$carrier,
			$data['sender_address_id'],
			$data['show_carrier_information_in_checkout'],
			$shipping_product,
			$this->make_shipping_rate_data( $data ),
			$processing_days,
			array_key_exists( 'time_zone_name', $data ) ? $data['time_zone_name'] : '',
			$holidays,
			$carriers,
			$service_point_data,
			$raw['data']
		);
	}

	/**
	 * Get holidays
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function get_holidays( array $data ) {
		$holidays      = array();
		$saved_holiday = array_key_exists( 'holidays', $data ) ? $data['holidays'] : array();
		foreach ( $saved_holiday as $holiday ) {
			$holidays[] = new Holiday(
				$holiday['frequency'],
				$holiday['from_date'],
				$holiday['recurring'],
				$holiday['title'],
				$holiday['to_date']
			);
		}

		return $holidays;
	}

	/**
	 * Get processing days
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function get_processing_days( array $data ) {
		$processingDays = array();

		// Compatibility for already existing payloads(original ones) in the database.
		$saved_processing_days = array_key_exists( 'nominated_day_processing_days', $data ) ? $data['nominated_day_processing_days'] : array();

		// Compatibility for the new payloads.
		if ( 'standard_delivery' === $data['delivery_method_type'] ) {
			$saved_processing_days = array_key_exists( 'order_placement_days', $data ) ? $data['order_placement_days'] : array();
		} else {
			$saved_processing_days = array_key_exists( 'parcel_handover_days', $data ) ? $data['parcel_handover_days'] : $saved_processing_days;
		}

		foreach ( $saved_processing_days as $day => $value ) {
			$processingDays[ $day ] = null !== $value ? new HandoverDay(
				$value['enabled'],
				$value['cut_off_time_hours'],
				$value['cut_off_time_minutes']
			) : null;
		}

		return $processingDays;
	}

	/**
	 * Creates carrier from array.
	 *
	 * @param array $carrier_data
	 *
	 * @return Carrier
	 */
	private function make_carrier( array $carrier_data ) {
		return new Carrier( $carrier_data['name'], $carrier_data['code'], $carrier_data['logo_url'] );
	}

	/**
	 * Instantiates shipping product from raw data.
	 *
	 * @param array $product_data
	 *
	 * @return ShippingProduct
	 */
	private function make_shipping_product( array $product_data ) {
		$delivery_days       = array();
		$saved_delivery_days = ! empty( $product_data['carrier_delivery_days'] ) ? $product_data['carrier_delivery_days'] : array();
		foreach ( $saved_delivery_days as $day => $data ) {
			$delivery_days[ $day ] = null !== $data ? new DeliveryDay(
				array_key_exists( 'enabled', $data ) ? $data['enabled'] : null,
				$data['start_time_hours'],
				$data['start_time_minutes'],
				$data['end_time_hours'],
				$data['end_time_minutes']
			) : null;
		}

		return new ShippingProduct(
			$product_data['code'],
			$product_data['name'],
			$product_data['lead_time_hours'],
			array_key_exists( 'lead_time_hours_override', $product_data ) ? $product_data['lead_time_hours_override'] : 0,
			$product_data['selected_functionalities'],
			$delivery_days
		);
	}

	private function make_shipping_rate_data( $data ) {
		if ( empty( $data['shipping_rate_data'] ) ) {
			$currency = get_option( 'woocommerce_currency' );

			return new ShippingRateData( false, $currency, new FreeShipping( false, 0 ), array() );
		}

		$shipping_rate_data = $data['shipping_rate_data'];
		$shipping_rates     = array();
		foreach ( $shipping_rate_data['shipping_rates'] as $shipping_rate_source ) {
			$shipping_rates[] = new ShippingRate(
				$shipping_rate_source['rate'],
				$shipping_rate_source['enabled'],
				array_key_exists( 'is_default', $shipping_rate_source ) ? $shipping_rate_source['is_default'] : true,
				array_key_exists( 'min_weight', $shipping_rate_source ) ? $shipping_rate_source['min_weight'] : null,
				array_key_exists( 'max_weight', $shipping_rate_source ) ? $shipping_rate_source['max_weight'] : null
			);
		}

		$free_shipping = new FreeShipping(
			$shipping_rate_data['free_shipping']['enabled'],
			$shipping_rate_data['free_shipping']['from_amount']
		);

		return new ShippingRateData(
			$shipping_rate_data['enabled'],
			$shipping_rate_data['currency'],
			$free_shipping,
			$shipping_rates
		);
	}

	/**
	 * Create service point data object
	 *
	 * @param $data
	 *
	 * @return ServicePointData
	 */
	private function make_service_point_data( $data ) {
		return new ServicePointData( $data['api_key'], $data['country_iso_2'] );
	}

	/**
	 * Provides delivery methods table.
	 *
	 * @return string
	 */
	protected function get_table_name() {
		return $this->db->prefix . 'sc_delivery_methods';
	}
}
