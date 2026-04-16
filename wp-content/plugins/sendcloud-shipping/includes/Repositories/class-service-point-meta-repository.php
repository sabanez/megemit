<?php

namespace Sendcloud\Shipping\Repositories;

use Sendcloud\Shipping\Models\Service_Point_Meta;

class Service_Point_Meta_Repository {
	const SERVICE_POINT_META_FIELD_NAME = 'sendcloudshipping_service_point_meta';

	/**
	 * Get service point based on order id
	 *
	 * @param $order_id
	 *
	 * @return Service_Point_Meta|null
	 */
	public function get( $order_id ) {
        $order = wc_get_order( $order_id );
        $data = $order->get_meta( self::SERVICE_POINT_META_FIELD_NAME );
		if ( ! $data ) {
			return null;
		}

		return Service_Point_Meta::from_array( $data );
	}

	/**
	 * Update service point
	 *
	 * @param $order_id
	 * @param Service_Point_Meta $service_point
	 *
	 */
	public function save( $order_id, $service_point ) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( self::SERVICE_POINT_META_FIELD_NAME, $service_point->to_array() );
        $order->save();
	}
}
