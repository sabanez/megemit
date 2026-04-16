<?php

namespace Sendcloud\Shipping\Repositories;

use wpdb;

/**
 * Class Abstract_Domain_Repository
 *
 * @package Sendcloud\Shipping\Repositories
 */
abstract class Abstract_Domain_Repository {
	/**
	 * WordPress database
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Abstract_Domain_Repository constructor.
	 *
	 * @param wpdb $db
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Provides all domain entities.
	 *
	 * @return array
	 */
	public function find_all() {
		$query  = 'SELECT * FROM ' . $this->get_table_name();
		$result = $this->db->get_results( $query, ARRAY_A );

		return array_map( array( $this, 'transform_to_entity' ), $result );
	}

	/**
	 * Finds domain object with external id.
	 *
	 * @param array $external_ids
	 *
	 * @return array
	 */
	public function find( array $external_ids ) {
		if ( empty( $external_ids ) ) {
			return array();
		}

		$query = 'SELECT * 
				   FROM ' . $this->get_table_name() . ' 
				   WHERE ' . $this->get_in_clause( 'external_id', $external_ids );

		$query  = $this->db->prepare( $query, $external_ids );
		$result = $this->db->get_results( $query, ARRAY_A );

		return array_map( array( $this, 'transform_to_entity' ), $result );
	}

	/**
	 * Deletes all domain entities.
	 */
	public function delete_all() {
		$query = 'TRUNCATE ' . $this->get_table_name();
		$this->db->query( $query );
	}

	/**
	 * Deletes domain entities.
	 *
	 * @param array $external_ids
	 */
	public function delete( array $external_ids ) {
		if ( empty( $external_ids ) ) {
			return;
		}

		$query = 'DELETE  FROM ' . $this->get_table_name() . ' 
		          WHERE ' . $this->get_in_clause( 'external_id', $external_ids );

		$query = $this->db->prepare( $query, $external_ids );
		$this->db->query( $query );
	}

	/**
	 * Creates domain entities.
	 *
	 * @param array $objects
	 */
	abstract public function create( array $objects );

	/**
	 * Performs object update.
	 *
	 * @param array $objects
	 */
	abstract public function update( array $objects );

	/**
	 * Transforms raw data to domain object.
	 *
	 * @param array $raw
	 *
	 * @return object
	 */
	abstract protected function transform_to_entity( array $raw );

	/**
	 * Provides table name.
	 *
	 * @return string
	 */
	abstract protected function get_table_name();

	/**
	 * Generates field in clause selector.
	 *
	 * @param $field
	 * @param array $values
	 * @param string $format
	 *
	 * @return string
	 */
	protected function get_in_clause( $field, array $values, $format = '%s' ) {
		return "`$field` IN(" . implode( ', ', array_fill( 0, count( $values ), $format ) ) . ')';
	}

	/**
	 * Prepares values for insert.
	 *
	 * @param array $format
	 * @param array $raw_data
	 *
	 * @return string
	 */
	protected function prepare_values( array $format, array $raw_data ) {
		$base_query = '(' . implode( ',', $format ) . ')';
		$db         = $this->db;
		$queries    = array_map( function ( $row ) use ( $base_query, $db ) {
			return $db->prepare( $base_query, $row );
		}, $raw_data );

		return implode( ',', $queries );
	}
}
