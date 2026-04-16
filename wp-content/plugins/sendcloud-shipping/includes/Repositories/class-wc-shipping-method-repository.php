<?php

namespace Sendcloud\Shipping\Repositories;

use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Nominated_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\SameDay\Same_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\Standard\Standard_Shipping_Method;
use wpdb;

class WC_Shipping_Method_Repository {

	protected static $sc_shipping_methods_ids = array(
		Nominated_Day_Shipping_Method::ID,
		Standard_Shipping_Method::ID,
		Same_Day_Shipping_Method::ID,
	);
	/**
	 * WordPress database
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * WC_Shipping_Method_Repository constructor
	 *
	 * @param wpdb $db
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}


	public function disable_all() {
		$where_in = '(' . implode( ', ', array_fill( 0, count( static::$sc_shipping_methods_ids ), '%s' ) ) . ')';
		$sql = "UPDATE {$this->get_table_name()}
                SET is_enabled = 0
                WHERE method_id IN {$where_in}";

		$this->db->query($this->db->prepare($sql, static::$sc_shipping_methods_ids));
	}

	protected function get_table_name() {
		return $this->db->prefix . 'woocommerce_shipping_zone_methods';
	}
}
