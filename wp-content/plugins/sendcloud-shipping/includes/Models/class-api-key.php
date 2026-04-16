<?php

namespace Sendcloud\Shipping\Models;

class Api_Key {

	/**
	 * Key ID
	 *
	 * @var int
	 */
	private $key_id;

	/**
	 * User ID
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Consumer key
	 *
	 * @var string
	 */
	private $consumer_key;

	/**
	 * Consumer secret
	 *
	 * @var string
	 */
	private $consumer_secret;

	/**
	 * Get key ID
	 *
	 * @return int
	 */
	public function get_key_id() {
		return $this->key_id;
	}

	/**
	 * Set key ID
	 *
	 * @param int $key_id
	 */
	public function set_key_id( $key_id ) {
		$this->key_id = $key_id;
	}

	/**
	 * Get user ID
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Set user ID
	 *
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get consumer key
	 *
	 * @return string
	 */
	public function get_consumer_key() {
		return $this->consumer_key;
	}

	/**
	 * Set consumer key
	 *
	 * @param string $consumer_key
	 */
	public function set_consumer_key( $consumer_key ) {
		$this->consumer_key = $consumer_key;
	}

	/**
	 * Get consumer secret
	 *
	 * @return string
	 */
	public function get_consumer_secret() {
		return $this->consumer_secret;
	}

	/**
	 * Set consumer secret
	 *
	 * @param string $consumer_secret
	 */
	public function set_consumer_secret( $consumer_secret ) {
		$this->consumer_secret = $consumer_secret;
	}

	/**
	 * Transforms array into an object
	 *
	 * @param $data
	 *
	 * @return Api_Key
	 */
	public static function from_array( $data ) {
		$api_key                  = new self();
		$api_key->key_id          = $data['key_id'];
		$api_key->user_id         = $data['user_id'];
		$api_key->consumer_secret = $data['consumer_secret'];

		return $api_key;
	}
}
