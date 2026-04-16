<?php

namespace Sendcloud\Shipping\Models;

class Service_Point_Meta {

	/**
	 * ID
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Extra
	 *
	 * @var string
	 */
	private $extra;

	/**
	 * Post number
	 *
	 * @var string
	 */
	private $post_number;

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set ID
	 *
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get extra
	 *
	 * @return string
	 */
	public function get_extra() {
		return $this->extra;
	}

	/**
	 * Set extra
	 *
	 * @param string $extra
	 */
	public function set_extra( $extra ) {
		$this->extra = $extra;
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
			'id'    => $this->id,
			'extra' => $this->extra,
			'post_number' => $this->post_number
		);
	}

	/**
	 * Creates object from array
	 *
	 * @param $data
	 *
	 * @return Service_Point_Meta
	 */
	public static function from_array( $data ) {
		$service_point = new self();

		$service_point->id    = array_key_exists( 'id', $data ) ? $data['id'] : '';
		$service_point->extra = array_key_exists( 'extra', $data ) ? $data['extra'] : '';
		$service_point->post_number = array_key_exists( 'post_number', $data ) ? $data['post_number'] : '';

		return $service_point;
	}
}
