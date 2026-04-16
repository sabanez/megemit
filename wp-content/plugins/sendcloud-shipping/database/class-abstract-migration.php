<?php

namespace SendCloud\Shipping\Database;

use SendCloud\Shipping\Database\Exceptions\Migration_Exception;
use wpdb;

abstract class Abstract_Migration {
	/**
	 * WordPress database
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Abstract_Migration constructor.
	 *
	 * @param wpdb $db
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Executes migration.
	 *
	 * @throws Migration_Exception
	 */
	abstract public function execute();
}
