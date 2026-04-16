<?php

namespace Sendcloud\Shipping\Controllers;

use Sendcloud\Shipping\Services\BusinessLogic\Support_Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Sendcloud_Base_Controller
 *
 * @package Sendcloud\Shipping\Controllers
 */
class Sendcloud_Base_Controller
{
    /**
     * @return void
     */
    public function index() {
        $controller_name = $this->get_param( 'sendcloud_controller' );
        $class_name = '\Sendcloud\Shipping\Controllers\Sendcloud_' . $controller_name . '_Controller';

        if ( ! $this->validate_controller_name( $controller_name ) || ! class_exists( $class_name ) ) {
            status_header( 404 );
            nocache_headers();

            require get_404_template();

            exit();
        }

        /** @var Sendcloud_Base_Controller $controller */
        $controller = new $class_name();
        $controller->process();
    }

    /**
     * @param $action
     *
     * @return void
     */
    public function process( $action = '' ) {
        if ( empty( $action ) ) {
            $action = $this->get_param( 'action' );
        }

        if ( $action ) {
            if ( method_exists( $this, $action ) ) {
                $this->$action();
            } else {
                $this->return_json( array( 'error' => "Method $action does not exist!" ), 404 );
            }
        }
    }

    /**
     * Gets request parameter if exists. Otherwise, returns null.
     *
     * @param string $key Request parameter key.
     *
     * @return mixed
     */
    protected function get_param(string $key ) {
        if ( isset( $_REQUEST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
        }

        return null;
    }

    /**
     * Sets response header content type to json, echos supplied $data as a json string and terminates request.
     *
     * @param array $data Array to be returned as a json response.
     * @param int $status_code Response status code.
     */
    protected function return_json(array $data, int $status_code = 200 ) {
        wp_send_json( $data, $status_code );
    }

    /**
     * Gets raw request.
     *
     * @return string
     */
    protected function get_raw_input() {
        return file_get_contents( 'php://input' );
    }

    /**
     * Gets request method.
     *
     * @return string
     */
    protected function get_method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Validates controller name by checking whether it exists in the list of known controller names.
     *
     * @param string $controller_name Controller name from request input.
     *
     * @return bool
     */
    private function validate_controller_name( $controller_name ): bool
    {
        $allowed_controllers = ['Support', 'Checkout'];

        return in_array( $controller_name, $allowed_controllers, true );
    }
}
