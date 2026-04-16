<?php
/**
 * Plugin Name: Sendcloud | Smart Shipping Service
 * Plugin URI: https://www.sendcloud.com
 * Description: Sendcloud plugin.
 * Version: 2.4.5
 * Woo: 18734000874665:d8305e8713533146bf1b6f330ce09e43
 * Author: Sendcloud B.V.
 * Author URI: https://www.sendcloud.com
 * Requires at least: 4.5.0
 * Tested up to: 6.6.1
 *
 * Text Domain: sendcloud-shipping
 * Domain Path: /i18n/languages/
 * WC requires at least: 2.6.0
 * WC tested up to: 9.1.4
 *
 * @package sendcloud-shipping
 */

use Sendcloud\Shipping\Sendcloud;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

if ( file_exists( __DIR__ . '/dev_env.php' ) ) {
	require_once __DIR__ . '/dev_env.php';
}

Sendcloud::init( __FILE__ );

//This is for backward compatibility with WooFunnels plugin
function sendcloudshipping_add_service_point_to_checkout() {
	//Don't do nothing, real method is used.
}
