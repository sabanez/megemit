<?php

namespace Sendcloud\Shipping\Controllers;

use Sendcloud\Shipping\Services\BusinessLogic\Support_Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Sendcloud_Support_Controller
 *
 * @package Sendcloud\Shipping\Controllers
 */
class Sendcloud_Support_Controller extends Sendcloud_Base_Controller
{
    private const METHOD_POST = 'POST';

    /**
     * @var Support_Service
     */
    private $support_service;

    /**
     * Return system configuration parameters.
     */
    public function display() {
        if ( $this->get_method() !== self::METHOD_POST ) {
            wp_send_json( [ 'message' => 'Invalid request method. Only POST requests are allowed.' ] );
        }

        $payload = json_decode( $this->get_raw_input(), true );

        wp_send_json( [ $this->get_support_service()->get($payload) ] );
    }

    /**
     * Updates system configuration parameters.
     */
    public function modify() {
        if ( $this->get_method() !== self::METHOD_POST ) {
            wp_send_json( [ 'message' => 'Invalid request method. Only POST requests are allowed.' ] );
        }

        $payload = json_decode( $this->get_raw_input(), true );

        wp_send_json( [ $this->get_support_service()->update( $payload ) ] );
    }

    /**
     * @return Support_Service
     */
    protected function get_support_service(): Support_Service
    {
        if ( $this->support_service === null ) {
            $this->support_service = new Support_Service();
        }

        return $this->support_service;
    }
}
