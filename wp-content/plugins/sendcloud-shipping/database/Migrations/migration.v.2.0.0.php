<?php

namespace SendCloud\Shipping\Database\Migrations;

use SendCloud\Shipping\Database\Abstract_Migration;

/**
 * Class Migration_2_0_0
 *
 * @package SendCloud\Shipping\Database\Migrations
 */
class Migration_2_0_0 extends Abstract_Migration {

	public function execute() {
		$this->create_delivery_zone_table();
		$this->create_delivery_method_table();
	}

	/**
	 * Creates delivery zone table.
	 */
	private function create_delivery_zone_table() {
		$table_name = $this->db->prefix . 'sc_delivery_zones';
		$query      = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
  				       `id` INT NOT NULL AUTO_INCREMENT,
  				       `external_id` VARCHAR(64) NOT NULL,
  				       `system_id` BIGINT(20) UNSIGNED NOT NULL,
  			           `data` LONGTEXT NULL,
  	                   PRIMARY KEY (`id`),
  	                   UNIQUE INDEX `external_id_UNIQUE` (`external_id` ASC),
  	                   UNIQUE INDEX `system_id_UNIQUE` (`system_id` ASC))';

		$this->db->query( $query );
	}

	/**
	 * Creates delivery method table.
	 */
	private function create_delivery_method_table() {
		$table_name = $this->db->prefix . 'sc_delivery_methods';
		$query      = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
                      `id` INT NOT NULL AUTO_INCREMENT,
                      `external_id` VARCHAR(64) NOT NULL,
                      `system_id` BIGINT(20) UNSIGNED NOT NULL,
                      `delivery_zone_id` VARCHAR(64) NOT NULL,
                      `data` LONGTEXT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE INDEX `external_id_UNIQUE` (`external_id` ASC),
                      UNIQUE INDEX `system_id_UNIQUE` (`system_id` ASC),
                      INDEX `delivery_zone_idx` (`delivery_zone_id` ASC))';

		$this->db->query( $query );
	}
}
