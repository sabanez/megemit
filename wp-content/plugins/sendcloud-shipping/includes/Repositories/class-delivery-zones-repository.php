<?php

namespace Sendcloud\Shipping\Repositories;

use SendCloud\Checkout\Domain\Delivery\Country;
use SendCloud\Checkout\Domain\Delivery\DeliveryZone;

/**
 * Class Delivery_Zones_Repository
 *
 * @package Sendcloud\Shipping\Repositories
 */
class Delivery_Zones_Repository extends Abstract_Domain_Repository {
	/**
	 * Creates delivery zones.
	 *
	 * @param DeliveryZone[] $zones
	 */
	public function create( array $zones ) {
		if ( empty( $zones ) ) {
			return;
		}

		$zones = array_map( function ( DeliveryZone $data ) {
			return array( $data->getId(), $data->getSystemId(), $data->getRawConfig() );
		}, $zones );
		$query = 'INSERT INTO ' . $this->get_table_name() . ' (`external_id`, `system_id`, `data`) 
		          VALUES ' . $this->prepare_values( array( '%s', '%d', '%s' ), $zones );
		$this->db->query( $query );
	}

	/**
	 * Adds new wc shipping method instance to wc delivery zone.
	 *
	 * @param string $type
	 * @param int $zone_id
	 * @param int $order
	 *
	 * @return int
	 */
	public function add_wc_delivery_method_to_wc_delivery_zone( $type, $zone_id, $order ) {
		$this->db->insert(
			$this->db->prefix . 'woocommerce_shipping_zone_methods',
			array(
				'method_id'    => $type,
				'zone_id'      => $zone_id,
				'method_order' => $order,
			),
			array(
				'%s',
				'%d',
				'%d',
			)
		);

		return $this->db->insert_id;
	}

	/**
	 * Removes delivery method from zone.
	 *
	 * @param int $zone_id
	 * @param int $method_id
	 */
	public function remove_wc_delivery_method( $zone_id, $method_id ) {
		$this->db->delete(
			$this->db->prefix . 'woocommerce_shipping_zone_methods',
			array( 'zone_id' => $zone_id, 'instance_id' => $method_id )
		);
	}

	/**
	 * Get count of wc methods for a wc zone.
	 *
	 * @param int $zone_id Zone ID.
	 *
	 * @return int Method Count
	 */
	public function get_method_count( $zone_id ) {
		$query = "SELECT COUNT(*) FROM {$this->db->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d";

		return $this->db->get_var( $this->db->prepare( $query, $zone_id ) );
	}

	/**
	 * Checks if delivery zone has method.
	 *
	 * @param $zone_id
	 * @param $method_id
	 *
	 * @return bool
	 */
	public function has_method( $zone_id, $method_id ) {
		$query = "SELECT COUNT(*) FROM {$this->db->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND instance_id = %d";

		return $this->db->get_var( $this->db->prepare( $query, array( $zone_id, $method_id ) ) ) > 0;
	}

	/**
	 * Performs zone update
	 *
	 * @param DeliveryZone[] $zones
	 */
	public function update( array $zones ) {
		if ( empty( $zones ) ) {
			return;
		}

		foreach ( $zones as $zone ) {
			$query = 'UPDATE ' . $this->get_table_name() . ' SET `external_id`=%s, `system_id`=%d, data=%s WHERE (`external_id`=%s)';
			$query = $this->db->prepare( $query, array(
				$zone->getId(),
				$zone->getSystemId(),
				$zone->getRawConfig(),
				$zone->getId()
			) );
			$this->db->query( $query );
		}
	}

	/**
	 * Provides zones with system id.
	 *
	 * @param array $system_ids
	 *
	 * @return array
	 */
	public function find_by_system_ids( array $system_ids ) {
		if ( empty( $system_ids ) ) {
			return array();
		}

		$query = 'SELECT * 
				   FROM ' . $this->get_table_name() . ' 
				   WHERE ' . $this->get_in_clause( 'system_id', $system_ids, '%d' );

		$query  = $this->db->prepare( $query, $system_ids );
		$result = $this->db->get_results( $query, ARRAY_A );

		return array_map( array( $this, 'transform_to_entity' ), $result );
	}

	public function delete_obsolete_zone_configs() {
		$query = 'SELECT * 
				  FROM ' . $this->get_table_name() . " as dz
				  LEFT JOIN {$this->db->prefix}woocommerce_shipping_zones as wcz on dz.system_id=wcz.zone_id
				  WHERE zone_id IS NULL";

		$result = $this->db->get_results( $query, ARRAY_A );
		$result = array_map( static function ( $item ) {
			return $item['external_id'];
		}, $result );

		if ( ! empty( $result ) ) {
			$this->delete( $result );
		}
	}

	/**
	 * Provides table name.
	 *
	 * @return string
	 */
	protected function get_table_name() {
		return $this->db->prefix . 'sc_delivery_zones';
	}

	/**
	 * Transforms raw data from db to entity class.
	 *
	 * @param array $raw
	 *
	 * @return DeliveryZone
	 */
	protected function transform_to_entity( array $raw ) {
		$data    = json_decode( $raw['data'], true );
		$country = new Country( $data['location']['country']['iso_2'], $data['location']['country']['name'] );

		return new DeliveryZone( $raw['external_id'], (int) $raw['system_id'], $country, $raw['data'] );
	}
}
