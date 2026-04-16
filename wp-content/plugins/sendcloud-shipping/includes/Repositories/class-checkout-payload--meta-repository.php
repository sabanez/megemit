<?php

namespace Sendcloud\Shipping\Repositories;

use Sendcloud\Shipping\Models\Delivery_Method_Meta_Data;
use Sendcloud\Shipping\Models\Service_Point_Delivery_Method_Meta_Data;

class Checkout_Payload_Meta_Repository {
	const CHECKOUT_PAYLOAD_META_FIELD_NAME = 'sendcloudshipping_checkout_payload_meta';

	/**
	 * Get service point based on order id
	 *
	 * @param $order_id
	 *
	 * @return Delivery_Method_Meta_Data|Service_Point_Delivery_Method_Meta_Data|null
	 */
	public function get_delivery_method_data( $order_id ) {
        $order = wc_get_order( $order_id );
        $data = $order->get_meta( self::CHECKOUT_PAYLOAD_META_FIELD_NAME );
		if ( empty( $data ) ) {
			return null;
		}

		// Compatibility with old payload
		$delivery_method_data = array_key_exists( 'nominated_day_delivery', $data ) ? $data['nominated_day_delivery'] : array();
		$delivery_method_data = array_key_exists( 'delivery_method_data', $data ) ? $data['delivery_method_data'] : $delivery_method_data;

		if ( empty( $delivery_method_data ) ) {
			return null;
		}

		if ( 'service_point_delivery' === $data['delivery_method_type'] ) {
			return Service_Point_Delivery_Method_Meta_Data::from_array( $delivery_method_data );
		}

		return Delivery_Method_Meta_Data::from_array( $delivery_method_data );
	}

	/**
	 * Get delivery method type
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_delivery_method_type( $order_id ) {
        $order = wc_get_order( $order_id );
        $data = $order->get_meta( self::CHECKOUT_PAYLOAD_META_FIELD_NAME );
		if ( empty( $data ) ) {
			return null;
		}

		return array_key_exists( 'delivery_method_type', $data ) ? $data['delivery_method_type'] : '';
	}

	/**
	 * Update service point
	 *
	 * @param $order_id
	 * @param array $raw_payload_data Raw checkout payload data array
	 *
	 */
	public function save_raw( $order_id, $raw_payload_data ) {
		$this->unset_access_token( $raw_payload_data );
        $order = wc_get_order( $order_id );
        $order->update_meta_data( self::CHECKOUT_PAYLOAD_META_FIELD_NAME, $raw_payload_data );
        $order->save();
	}

	/**
	 * Unset access_token and api_key in order to skip saving it into the database
	 *
	 * @param $raw_payload_data
	 *
	 * @return void
	 */
	private function unset_access_token( &$raw_payload_data ) {
		unset( $raw_payload_data['access_token'] );
		if ( array_key_exists( 'service_point_data', $raw_payload_data ) ) {
			unset( $raw_payload_data['service_point_data']['api_key'] );
		}
	}
}
