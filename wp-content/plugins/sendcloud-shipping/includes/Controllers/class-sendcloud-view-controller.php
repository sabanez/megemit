<?php
/**
 * CleverReach WooCommerce Integration.
 *
 * @package CleverReach
 */

namespace Sendcloud\Shipping\Controllers;

use Sendcloud\Shipping\Repositories\Api_Key_Repository;
use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Sendcloud;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sendcloud_View_Controller
 *
 * @package Sendcloud\Shipping\Controllers
 */
class Sendcloud_View_Controller {

	public function __construct() {
		wp_enqueue_style( 'sendcloud-css', Sendcloud::get_plugin_url( 'resources/css/sendcloud.css' ), array(),
			Sendcloud::VERSION );
	}

	/**
	 * Renders appropriate view
	 */
	public function render() {
		wp_enqueue_script( 'sendcloud-js-page', Sendcloud::get_plugin_url( 'resources/js/sendcloud.page.js' ), array( 'jquery' ),
			Sendcloud::VERSION, true );
		wp_enqueue_script( 'sendcloud-js-config', Sendcloud::get_plugin_url( 'resources/js/sendcloud.config.js' ), array( 'jquery' ),
			Sendcloud::VERSION, true );

		echo wp_kses( View::file( '/wc-settings/sendcloud-page.php' )->render( array(
			'panel_url'          => $this->get_panel_url(),
			'permalinks_enabled' => get_option( 'permalink_structure' ),
			'weight_unit'        => get_option( 'woocommerce_weight_unit' ),
			'config'             => $this->get_sendcloud_config(),
			'currency'           => get_woocommerce_currency_symbol( get_option( 'woocommerce_currency' ) ),
			'types'              => $this->get_types_translated(),
		) ), View::get_allowed_tags() );
	}


	/**
	 * Gets SendCloud panel url
	 *
	 * @return array|false|string
	 */
	private function get_panel_url() {
		$panel_url = getenv( 'SENDCLOUDSHIPPING_PANEL_URL' );
		if ( empty( $panel_url ) ) {
			$panel_url = 'https://app.sendcloud.com';
		}

		return $panel_url;
	}

	private function get_sendcloud_config() {
		$repository = new SC_Config_Repository();

		return array(
			'integration_id'   => $repository->get_integration_id()
		);
	}

	private function get_types_translated() {
		return array(
			'standard_delivery'      => __( 'Standard', 'sendcloud-shipping' ),
			'nominated_day_delivery' => __( 'Nominated day', 'sendcloud-shipping' ),
			'service_point_delivery' => __( 'Service point', 'sendcloud-shipping' ),
			'same_day_delivery'      => __( 'Same day', 'sendcloud-shipping' ),
		);
	}
}
