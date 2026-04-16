<?php

namespace Sendcloud\Shipping\Repositories;

class Plugin_Options_Repository {
	/**
	 * Provides current schema version.
	 *
	 * @NOTICE default version is 1.0.0 if version has not been previously set.
	 *
	 * @return string
	 */
	public function get_schema_version() {
		return get_option( 'SC_SCHEMA_VERSION', '1.0.0' );
	}

	/**
	 * Sets schema version.
	 *
	 * @param string $version
	 */
	public function set_schema_version( $version ) {
		update_option( 'SC_SCHEMA_VERSION', $version );
	}
}
