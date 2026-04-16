<?php
/**
 * CleverReach WooCommerce Integration.
 *
 * @package CleverReach
 */

namespace Sendcloud\Shipping\Controllers;

use Sendcloud\Shipping\Repositories\Api_Key_Repository;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sendcloud_Api_Enable_Controller
 *
 * @package Sendcloud\Shipping\Controllers
 */
class Sendcloud_Api_Enable_Controller {

	private $api_key_repository;

	public function __construct() {
		$this->api_key_repository = new Api_Key_Repository();
	}
	/**
	 * Enables WooCommerce API
	 */
	public function generate_redirect_url() {
		try {
			Logger::error('generating redirect url...');
			update_option( 'woocommerce_api_enabled', 'yes' );
			$redirect_url = $this->get_redirect_url();
		} catch ( \Exception $exception ) {
			$redirect_url = null;
		}

		Response::json( array('redirect_url' => $redirect_url) );
	}

	/**
	 * Get redirect url
	 *
	 * @return string|void
	 */
	private function get_redirect_url() {
		$permalinks_enabled = get_option('permalink_structure');
		if (!$permalinks_enabled) {
			return admin_url('admin.php?page=sendcloud-wc');
		}

		Logger::info('Connecting to Sendcloud.');
		$site_url = get_option('home');

		if (defined('SC_NGROK_URL')) {
			$ngrok_url = parse_url(SC_NGROK_URL, PHP_URL_HOST);
			$site_url = str_replace(parse_url($site_url, PHP_URL_HOST), $ngrok_url, $site_url);
		}

		$site_url = urlencode($site_url);
		$api_key = $this->api_key_repository->get_fresh_credentials();

		return sprintf('%s/shops/woocommerce/connect/?url_webshop=%s&api_key=%s&api_secret=%s', $this->get_panel_url(),
			$site_url, $api_key->get_consumer_key(), $api_key->get_consumer_secret());
	}

	/**
	 * Gets SendCloud panel url
	 *
	 * @return array|false|string
	 */
	private function get_panel_url() {
		$panel_url = getenv( 'SENDCLOUDSHIPPING_PANEL_URL' );
		if ( empty( $panel_url ) ) {
			$panel_url = 'https://panel.sendcloud.sc';
		}

		return $panel_url;
	}
}
