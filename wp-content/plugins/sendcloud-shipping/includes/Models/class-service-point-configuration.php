<?php

namespace Sendcloud\Shipping\Models;

class Service_Point_Configuration {
	/**
	 * Script
	 *
	 * @var string
	 */
	private $script;

	/**
	 * Carriers
	 *
	 * @var string
	 */
	private $carriers;

	/**
	 * Get script
	 *
	 * @return string
	 */
	public function get_script() {
		return $this->script;
	}

	/**
	 * Set script
	 *
	 * @param string $script
	 */
	public function set_script( $script ) {
		$this->script = $script;
	}

	/**
	 * Get carriers
	 *
	 * @return string
	 */
	public function get_carriers() {
		return $this->carriers;
	}

	/**
	 * Set carriers
	 *
	 * @param string $carriers
	 */
	public function set_carriers( $carriers ) {
		$this->carriers = $carriers;
	}

	/**
	 * Created Service_Point_Configuration instance based on provided array with data
	 *
	 * @param $data
	 *
	 * @return Service_Point_Configuration
	 */
	public static function from_array( $data ) {
		$service_point_configurations           = new self();
		$service_point_configurations->script   = array_key_exists( 'script', $data ) ? $data['script'] : '';
		$service_point_configurations->carriers = array_key_exists( 'carriers', $data ) ? $data['carriers'] : '';

		return $service_point_configurations;
	}
}
