<?php

namespace Sendcloud\Shipping\Integration\Api;

use Exception;
use SendCloud\Checkout\HTTP\Request;
use Sendcloud\Shipping\Checkout\Factories\Default_Checkout_Configurator_Factory;
use Sendcloud\Shipping\Checkout\Interfaces\Checkout_Configurator_Factory;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use WC_API_Resource;
use WC_API_Server;
use WP_Error;

class Integration_Api_Service extends WC_API_Resource {
	const CLASS_NAME = __CLASS__;

	protected $base = Sendcloud::BASE_API_URI;

	/**
	 * Service Point Configuration Repository
	 *
	 * @var Service_Point_Configuration_Repository
	 */
	private $service_point_config_repository;
	/**
	 * Checkout Configurator Factory
	 *
	 * @var Checkout_Configurator_Factory
	 */
	private $configurator_factory;

	/**
	 * Integration_Api_Service constructor.
	 *
	 * @param WC_API_Server $server
	 */
	public function __construct( WC_API_Server $server ) {
		parent::__construct( $server );

		$this->configurator_factory            = new Default_Checkout_Configurator_Factory();
		$this->service_point_config_repository = new Service_Point_Configuration_Repository();
	}

	/**
	 * Registers available routes.
	 *
	 * @param $routes
	 *
	 * @return \array[][]
	 */
	public function register_routes( $routes ) {
        Logger::info( 'Register available routes: ' . json_encode($routes) );
		$routes[ $this->base . '/integration' ] = array(
			array( array( $this, 'delete_integration' ), WC_API_SERVER::METHOD_DELETE ),
		);

		return $routes;
	}

	/**
	 * Handles integration deletion.
	 *
	 * @return array | WP_Error
	 */
	public function delete_integration() {
		Logger::info( 'The DELETABLE sendcloudshipping/integration API endpoint invoked.' );
		$configurator = $this->configurator_factory->make();
		try {
			$this->service_point_config_repository->delete();
			$configurator->deleteAll( new Request( array(), array() ) );
		} catch ( Exception $e ) {
			Logger::error( 'Failed to deleting integration: ' . $e->getMessage(), array( 'trace' => $e->getTraceAsString() ) );

			return new WP_Error(
				'sc-failed-to-delete',
				__( 'Failed to delete checkout configuration.', 'sendcloud-shipping' ),
				400
			);
		}

		return array( 'message' => __( 'Configuration deleted', 'sendcloud-shipping' ) );
	}
}
