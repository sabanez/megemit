<?php

namespace Sendcloud\Shipping\Utility;

use SendCloud\Shipping\Database\Exceptions\Migration_Exception;
use SendCloud\Shipping\Database\Migrator;
use Sendcloud\Shipping\Repositories\Plugin_Options_Repository;
use Sendcloud\Shipping\Sendcloud;
use WP_Site;
use wpdb;

/**
 * Class Database
 *
 * @package Sendcloud\Shipping\Database
 */
class Database {

	/**
	 * Plugin Options Repository
	 *
	 * @var Plugin_Options_Repository
	 */
	private $repository;

	/**
	 * WordPress database
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Database constructor.
	 *
	 * @param Plugin_Options_Repository $repository
	 */
	public function __construct( Plugin_Options_Repository $repository ) {
		$this->repository = $repository;
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * Performs database update.
	 *
	 * @param $is_multisite
	 *
	 * @throws Migration_Exception
	 */
	public function update( $is_multisite ) {
		if ( $is_multisite ) {
			$sites = get_sites();
			/**
			 * WP site
			 *
			 * @var WP_Site $site
			 */
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$this->do_update();
				restore_current_blog();
			}
		} else {
			$this->do_update();
		}
	}

	/**
	 * Updates schema for current site.
	 *
	 * @throws Migration_Exception
	 */
	private function do_update() {
		$current_schema_version = $this->repository->get_schema_version();
		$current_plugin_version = Sendcloud::VERSION;

		if ( $current_plugin_version === $current_schema_version ) {
			return;
		}

		$migrator = new Migrator( $this->db, $current_schema_version );
		$migrator->execute();
		$this->repository->set_schema_version( $current_plugin_version );
	}
}
