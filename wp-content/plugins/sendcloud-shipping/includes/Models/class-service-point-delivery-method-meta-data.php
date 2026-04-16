<?php

namespace Sendcloud\Shipping\Models;

class Service_Point_Delivery_Method_Meta_Data {

	/**
	 * Formatted address
	 *
	 * @var string
	 */
	private $formatted_address;

	/**
	 * Service point ID
	 *
	 * @var int
	 */
	private $service_point_id;

	/**
	 * Post number
	 *
	 * @var string
	 */
	private $post_number;

	/**
	 * Get formatted address
	 *
	 * @return string
	 */
	public function get_formatted_address() {
		return $this->formatted_address;
	}

	/**
	 * Set formatted address
	 *
	 * @param string $formatted_address
	 */
	public function set_formatted_address( $formatted_address ) {
		$this->formatted_address = $formatted_address;
	}

	/**
	 * Get service point ID
	 *
	 * @return int
	 */
	public function get_service_point_id() {
		return $this->service_point_id;
	}

	/**
	 * Set service point ID
	 *
	 * @param int $service_point_id
	 */
	public function set_service_point_id( $service_point_id ) {
		$this->service_point_id = $service_point_id;
	}

	/**
	 * Get post number
	 *
	 * @return string
	 */
	public function get_post_number() {
		return $this->post_number;
	}

	/**
	 * Set post number
	 *
	 * @param string $post_number
	 */
	public function set_post_number( $post_number ) {
		$this->post_number = $post_number;
	}

	/**
	 * Return object as array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'formatted_address' => $this->formatted_address,
			'service_point_id'  => $this->service_point_id,
			'post_number'       => $this->post_number
		);
	}

	/**
	 * Creates object from array
	 *
	 * @param $data
	 *
	 * @return Service_Point_Delivery_Method_Meta_Data
	 */
	public static function from_array( $data ) {
		$instance = new self();

		$instance->set_formatted_address( array_key_exists( 'formatted_address', $data ) ? $data['formatted_address'] : '' );
		$instance->set_service_point_id( array_key_exists( 'service_point_id', $data ) ? $data['service_point_id'] : 0 );
		$instance->set_post_number( array_key_exists( 'post_number', $data ) ? $data['post_number'] : '' );

		return $instance;
	}
}
