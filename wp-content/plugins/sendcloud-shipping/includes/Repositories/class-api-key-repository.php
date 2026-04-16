<?php

namespace Sendcloud\Shipping\Repositories;

use RuntimeException;
use Sendcloud\Shipping\Models\Api_Key;

class Api_Key_Repository {
	const API_DESCRIPTION = 'SendCloud API';

	const WC_API_KEYS_TABLE_NAME = 'woocommerce_api_keys';

	/**
	 * Gets fresh api_key
	 *
	 * @return Api_Key
	 */
	public function get_fresh_credentials() {
		$api_key = $this->get_api_key();

		if ( is_null( $api_key ) ) {
			$api_key = $this->create_api_key();
		} else {
			$api_key = $this->update_api_key( $api_key );
		}

		return $api_key;
	}

	/**
	 * Retrieves api key from woocommerce_api_keys table
	 *
	 * @return Api_Key|null
	 */
	private function get_api_key() {
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare( '
            SELECT key_id, user_id, consumer_secret
            FROM %1s
            WHERE user_id = %d AND description = %s
        ', $wpdb->prefix . self::WC_API_KEYS_TABLE_NAME, get_current_user_id(), self::API_DESCRIPTION ), ARRAY_A );

		return $result ? Api_Key::from_array( $result ) : null;
	}

	/**
	 * Provides API key by consumer key.
	 *
	 * @return Api_Key|null
	 */
	public function get_api_key_by_consumer_secret( $consumer_key ) {
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare( '
            SELECT key_id, user_id, description, permissions, consumer_key, consumer_secret, truncated_key, last_access
            FROM %1s
            WHERE consumer_secret = %d AND description = %s
        ', $wpdb->prefix . self::WC_API_KEYS_TABLE_NAME, $consumer_key, self::API_DESCRIPTION ), ARRAY_A );

		return $result ? Api_Key::from_array( $result ) : null;
	}

	/**
	 * Creates new api key
	 *
	 * @return Api_Key
	 */
	private function create_api_key() {
		global $wpdb;

		list( $consumer_key, $consumer_key_hash ) = $this->generate_consumer_key();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$data = array(
			'user_id'         => get_current_user_id(),
			'description'     => self::API_DESCRIPTION,
			'permissions'     => 'read_write',
			'consumer_key'    => $consumer_key_hash,
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, - 7 )
		);

		$wpdb->insert(
			$wpdb->prefix . self::WC_API_KEYS_TABLE_NAME,
			$data,
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		$api_key = $this->get_api_key();
		if ( is_null( $api_key ) ) {
			throw new RuntimeException( 'Creating api key failed. ' );
		}

		$api_key->set_consumer_key( $consumer_key );

		return $api_key;
	}

	/**
	 * Updates existing api key
	 *
	 * @param Api_Key $api_key
	 *
	 * @return Api_Key
	 */
	private function update_api_key( $api_key ) {
		global $wpdb;

		list( $consumer_key, $consumer_key_hash ) = $this->generate_consumer_key();
		$api_key->set_consumer_key( $consumer_key );

		$wpdb->update(
			$wpdb->prefix . self::WC_API_KEYS_TABLE_NAME,
			array( 'consumer_key' => $consumer_key_hash ),
			array( 'key_id' => $api_key->get_key_id() ),
			array( '%s' ),
			array( '%d' )
		);

		return $api_key;
	}

	/**
	 * Generates consumer key
	 *
	 * @return array
	 */
	private function generate_consumer_key() {
		$consumer_key      = 'ck_' . wc_rand_hash();
		$consumer_key_hash = wc_api_hash( $consumer_key );

		return array( $consumer_key, $consumer_key_hash );
	}
}
