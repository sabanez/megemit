<?php

namespace Sendcloud\Shipping\Checkout\Api;

use Exception;
use SendCloud\Checkout\API\Checkout\Checkout;
use SendCloud\Checkout\Exceptions\ValidationException;
use SendCloud\Checkout\HTTP\Request;
use Sendcloud\Shipping\Checkout\Factories\Default_Checkout_Configurator_Factory;
use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Response;
use WC_API_Resource;
use WC_API_Server;
use WP_Error;

/**
 * Class Checkout_Api_Service
 *
 * @package Sendcloud\Shipping\Checkout\Api
 */
class Checkout_Api_Service extends WC_API_Resource {
	const CLASS_NAME = __CLASS__;

	protected $base = Sendcloud::BASE_API_URI;
	protected $configurator_factory;

	/**
	 * Checkout_Api_Service constructor.
	 *
	 * @param WC_API_Server $server
	 */
	public function __construct( WC_API_Server $server ) {
		parent::__construct( $server );

		$this->configurator_factory = new Default_Checkout_Configurator_Factory();
	}

	/**
	 * Registers available routes.
	 *
	 * @param $routes
	 *
	 * @return \array[][]
	 */
	public function register_routes( $routes ) {
		$routes[ $this->base . '/checkout/configuration' ] = array(
			array( array( $this, 'update_configuration' ), WC_API_SERVER::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_configuration' ), WC_API_SERVER::METHOD_DELETE ),
		);

		return $routes;
	}

	/**
	 * Handles the checkout updated request.
	 *
	 * @param $data
	 *
	 * @return array|WP_Error
	 */
	public function update_configuration( $data ) {
		Logger::info( 'The EDITABLE sendcloudshipping/checkout/configuration API endpoint invoked. Data: ' . json_encode($data));
		$request      = new Request( $data, array() );
		$configurator = $this->configurator_factory->make();
		try {

			$configurator->update( $request );
			$checkout = Checkout::fromArray($data['checkout_configuration']);
			$config_repository = new SC_Config_Repository();
			$config_repository->save_integration_id($checkout->getIntegrationId());
			$config_repository->save_last_published_time($checkout->getUpdatedAt());

		} catch ( ValidationException $e ) {
			Response::json( $this->format_error_data( $e->getValidationErrors() ), 422 );
		} catch ( Exception $e ) {
			Logger::error( 'Failed to update checkout configuration: ' . $e->getMessage(),
				array( 'trace' => $e->getTraceAsString() )
			);

			return new WP_Error(
				'sc-invalid-payload',
				__( 'Invalid checkout payload.', 'sendcloud-shipping' ),
				400
			);
		}

		return array( 'message' => __( 'Configuration updated', 'sendcloud-shipping' ) );
	}

	/**
	 * Handles the checkout deleted request.
	 *
	 * @return array|WP_Error
	 */
	public function delete_configuration() {
		Logger::info( 'The DELETABLE sendcloudshipping/checkout/configuration API endpoint invoked.' );
		$configurator = $this->configurator_factory->make();
		try {
			$configurator->deleteAll( new Request( array(), array() ) );
		} catch ( Exception $e ) {
			Logger::error( 'Failed to delete checkout configuration: ' . $e->getMessage(),
				array( 'trace' => $e->getTraceAsString() ) );

			return new WP_Error(
				'sc-failed-to-delete',
				__( 'Failed to delete checkout configuration.', 'sendcloud-shipping' ),
				400
			);
		}

		return array( 'message' => __( 'Configuration deleted', 'sendcloud-shipping' ) );
	}

	/**
	 * Formats error response.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function format_error_data( array $data ) {
		$details = array_map( function ( $item ) {
			return array(
				'path'    => $item['path'],
				'context' => $item['context'],
				'code'    => $item['code'],
				'message' => $item['message'],
			);
		}, $data );

		return array( 'errors' => $details );
	}
}
