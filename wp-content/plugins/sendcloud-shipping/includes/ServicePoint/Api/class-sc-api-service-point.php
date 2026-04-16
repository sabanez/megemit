<?php

namespace Sendcloud\Shipping\ServicePoint\Api;

use Sendcloud\Shipping\Models\Service_Point_Configuration;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use WC_API_Resource;
use WC_API_Server;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SendCloudShipping_API_ServicePoint extends WC_API_Resource {
	const CLASS_NAME = __CLASS__;

	protected $base = Sendcloud::BASE_API_URI;

	/**
	 * Service Point Configuration Repository
	 *
	 * @var Service_Point_Configuration_Repository
	 */
	private $service_point_config_repository;

	/**
	 * Register routes
	 *
	 * @param $routes
	 *
	 * @return mixed
	 */
	public function register_routes( $routes ) {
		# POST|DELETE /service_point
		$routes[ $this->base . '/service_point' ] = array(
			array( array( $this, 'enable_service_point' ), WC_API_SERVER::CREATABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'disable_service_point' ), WC_API_Server::DELETABLE ),
		);

		$routes[ $this->base . '/version' ] = array(
			array( array( $this, 'check_version' ), WC_API_SERVER::READABLE ),
		);

        Logger::info( 'SendCloudShipping_API_ServicePoint::register_routes(): ' . json_encode($routes) );

		return $routes;
	}

	/**
	 * Enables service point
	 *
	 * @param $data
	 *
	 * @return array|WP_Error
	 */
	public function enable_service_point( $data ) {
		Logger::info( 'The CREATABLE sendcloudshipping/service_point API endpoint invoked.' );
		if ( ! isset( $data['script'] ) ) {
			return new WP_Error( 'sendcloudshipping_api_missing_script_data',
				__( 'No data specified to enable the plugin', 'sendcloud-shipping' ), 400 );
		}
		$this->get_service_point_config_repository()->save( Service_Point_Configuration::from_array( $data ) );
        Logger::info( 'SendCloudShipping_API_ServicePoint::enable_service_point(): ' . json_encode(Service_Point_Configuration::from_array( $data )) );

		return array( 'message' => __( 'Plugin enabled', 'sendcloud-shipping' ) );
	}

	/**
	 * Disables service point
	 *
	 * @return array
	 */
	public function disable_service_point() {
		Logger::info( 'The DELETABLE sendcloudshipping/service_point API endpoint invoked.' );
		$this->get_service_point_config_repository()->delete();

		return array( 'message' => __( 'Plugin disabled', 'sendcloud-shipping' ) );
	}

	/**
	 * Returns Wordpress, Woocommerce and SendCloud version
	 *
	 * @return array
	 */
	public function check_version() {
		global $wp_version;
		$version = array(
			'wordpress'   => $wp_version,
			'woocommerce' => WC()->version,
			'sendcloud'   => Sendcloud::VERSION,
		);

		return $version;
	}

	/**
	 * Returns service point configuration repository
	 *
	 * @return Service_Point_Configuration_Repository
	 */
	private function get_service_point_config_repository() {
		if ( ! $this->service_point_config_repository ) {
			$this->service_point_config_repository = new Service_Point_Configuration_Repository();
		}

		return $this->service_point_config_repository;
	}
}
