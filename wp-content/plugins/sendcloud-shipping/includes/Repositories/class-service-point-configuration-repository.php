<?php

namespace Sendcloud\Shipping\Repositories;

use Sendcloud\Shipping\Models\Service_Point_Configuration;

class Service_Point_Configuration_Repository {
	const SERVICE_POINT_SCRIPT_FIELD_NAME = 'sendcloudshipping_service_point_script';
	const SERVICE_POINT_CARRIERS_FIELD_NAME = 'sendcloudshipping_service_point_carriers';

	/**
	 * Retrieves service point configuration
	 *
	 * @param null $instance_id
	 *
	 * @return Service_Point_Configuration
	 */
	public function get( $instance_id = null ) {
		$configuration = new Service_Point_Configuration();
		$configuration->set_script( get_option( self::SERVICE_POINT_SCRIPT_FIELD_NAME ) );
		if ( $instance_id ) {
			$service_point_data = get_option( 'sendcloudshipping_service_point_shipping_method_' . $instance_id . '_settings' );
			if ( $service_point_data ) {
				$configuration->set_carriers( $service_point_data['carrier_select'] );
			}
		} else {
			$configuration->set_carriers( get_option( self::SERVICE_POINT_CARRIERS_FIELD_NAME ) );
		}

		return $configuration;
	}

	/**
	 * Updates service point configuration
	 *
	 * @param Service_Point_Configuration $configuration
	 */
	public function save( Service_Point_Configuration $configuration ) {
		update_option( self::SERVICE_POINT_CARRIERS_FIELD_NAME, $configuration->get_carriers() );
		update_option( self::SERVICE_POINT_SCRIPT_FIELD_NAME, $configuration->get_script() );
	}

	/**
	 * Delete service point configuration
	 */
	public function delete() {
		delete_option( self::SERVICE_POINT_SCRIPT_FIELD_NAME );
		delete_option( self::SERVICE_POINT_CARRIERS_FIELD_NAME );
	}
}
