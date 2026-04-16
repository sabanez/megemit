<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

use Sendcloud\Shipping\Checkout\Factories\Default_Checkout_Service_Factory;
use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;
use Sendcloud\Shipping\Utility\Logger;

// ***********************************************************************************
// STEP 1. ***************************************************************************
// Drop configuration.                                                               *
// ***********************************************************************************
function load_woocommerce() {
	if ( ! empty( $GLOBALS['woocommerce'] ) ) {
		return;
	}

	$standard_paths = array(
		WP_PLUGIN_DIR . '/woocommerce/woocommerce.php',
		WPMU_PLUGIN_DIR . '/woocommerce/woocommerce.php',
		ABSPATH . PLUGINDIR . '/woocommerce/woocommerce.php',
		ABSPATH . MUPLUGINDIR . '/woocommerce/woocommerce.php',
	);

	foreach ( $standard_paths as $standard_path ) {
		if ( file_exists( $standard_path ) ) {
			require_once $standard_path;

			break;
		}
	}
}

function delete_configuration() {
	try {
		load_woocommerce();

		$factory = new Default_Checkout_Service_Factory();
		$service = $factory->make();
		$service->uninstall();
		$repository = new Service_Point_Configuration_Repository();
		$repository->delete();
		$config_repository = new SC_Config_Repository();
		$config_repository->delete_integration_id();
		$config_repository->delete_last_published_time();
	} catch ( Exception $e ) {
		Logger::error( 'Uninstall failed.', array( 'trace' => $e->getTraceAsString() ) );
	}
}

// ***********************************************************************************
// STEP 2. ***************************************************************************
// Drop database.                                                                    *
// ***********************************************************************************

function drop_database( wpdb $wpdb ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sc_delivery_methods" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sc_delivery_zones" );

	delete_option( 'SC_SCHEMA_VERSION' );
}

// ***********************************************************************************
// STEP 3. ***************************************************************************
// Execute.                                                                          *
// ***********************************************************************************

global $wpdb;
if ( is_multisite() ) {
	$sites = get_sites();
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		delete_configuration();
		drop_database( $wpdb );
		restore_current_blog();
	}
} else {
	delete_configuration();
	drop_database( $wpdb );
}
