<?php

namespace Sendcloud\Shipping\Utility;

/**
 * Class Shop_Helper
 *
 * @package Sendcloud\Shipping\Utility
 */
class Shop_Helper
{
    /**
     * Gets URL for Sendcloud controller.
     *
     * @param string $name Name of the controller without "Sendcloud" and "Controller".
     * @param string $action Name of the action.
     * @param array $params Associative array of parameters.
     *
     * @return string
     */
    public static function get_controller_url( $name, $action = '', array $params = array() ) {
        $query = array( 'sendcloud_controller' => $name );
        if ( ! empty( $action ) ) {
            $query['action'] = $action;
        }

        $query = array_merge( $query, $params );

        return get_home_url() . '/?' . http_build_query( $query );
    }
}
