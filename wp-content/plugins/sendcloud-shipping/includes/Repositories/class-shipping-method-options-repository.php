<?php

namespace Sendcloud\Shipping\Repositories;

class Shipping_Method_Options_Repository {

	/**
	 * Get all Sendcloud shipping method configurations
	 *
	 * @return array
	 */
	public function get_all_methods_configurations() {
		global $wpdb;

		$configurations = [];
		$query          = "SELECT option_name, option_value
                  FROM {$wpdb->prefix}options
                  WHERE option_name LIKE 'sendcloudshipping%settings'";
		$result         = $wpdb->get_results( $query, ARRAY_A );
		foreach ( $result as $settings ) {
			$configurations[ $settings['option_name'] ] = unserialize( $settings['option_value'] );
		}

		return $configurations;
	}
}