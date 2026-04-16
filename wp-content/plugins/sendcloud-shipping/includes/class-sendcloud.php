<?php

namespace Sendcloud\Shipping;

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Sendcloud\Shipping\Checkout\Api\Checkout_Api_Service;
use Sendcloud\Shipping\Checkout\Shipping\Base_Checkout_Handler;
use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Checkout_Handler as Nominated_Day_Checkout_Handler;
use Sendcloud\Shipping\Checkout\Shipping\NominatedDay\Nominated_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\Order_View_Extender as Delivery_Method_Order_View_Extender;
use Sendcloud\Shipping\Checkout\Shipping\SameDay\Checkout_Handler as Same_Day_Checkout_Handler;
use Sendcloud\Shipping\Checkout\Shipping\SameDay\Same_Day_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Checkout_Handler as Service_Point_Checkout_Handler;
use Sendcloud\Shipping\Checkout\Shipping\ServicePoint\Service_Point_Shipping_Method;
use Sendcloud\Shipping\Checkout\Shipping\Standard\Checkout_Handler as Standard_Checkout_Handler;
use Sendcloud\Shipping\Checkout\Shipping\Standard\Standard_Shipping_Method;
use Sendcloud\Shipping\Controllers\Sendcloud_Api_Enable_Controller;
use Sendcloud\Shipping\Controllers\Sendcloud_Base_Controller;
use Sendcloud\Shipping\Controllers\Sendcloud_View_Controller;
use SendCloud\Shipping\Database\Exceptions\Migration_Exception;
use Sendcloud\Shipping\Integration\Api\Integration_Api_Service;
use Sendcloud\Shipping\Repositories\Plugin_Options_Repository;
use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Repositories\Service_Point_Configuration_Repository;
use Sendcloud\Shipping\Repositories\WC_Shipping_Method_Repository;
use Sendcloud\Shipping\ServicePoint\Api\SendCloudShipping_API_ServicePoint;
use Sendcloud\Shipping\ServicePoint\Checkout_Handler as Service_Point_Checkout_Handler_Legacy;
use Sendcloud\Shipping\ServicePoint\Email_Handler;
use Sendcloud\Shipping\ServicePoint\Shipping\SendCloudShipping_Service_Point_Shipping_Method;
use Sendcloud\Shipping\Utility\Database;
use Sendcloud\Shipping\Utility\Logger;
use Sendcloud\Shipping\Utility\Logging_Callable;
use Sendcloud\Shipping\Utility\View;

class Sendcloud {

    const VERSION = '2.4.5';

    const INTEGRATION_NAME = 'sendcloudshipping';
    const BASE_API_URI = '/sendcloudshipping';

    /**
     * Instance of Sendcloud
     *
     * @var Sendcloud
     */
    protected static $instance;

    /**
     * Path to Sendcloud plugin file
     *
     * @var string
     */
    private $sendcloud_plugin_file;

    /**
     * Service Point Checkout Handler Legacy
     *
     * @var Service_Point_Checkout_Handler_Legacy
     */
    private $service_point_checkout_handler_legacy;

    /**
     * Email Handler
     *
     * @var Email_Handler
     */
    private $email_handler;

    /**
     * Service Point Configuration Repository
     *
     * @var Service_Point_Configuration_Repository
     */
    private $service_point_config_repository;

    /**
     * Base_Checkout_Handler
     *
     * @var Base_Checkout_Handler
     */
    private $base_checkout_handler;

    /**
     * Nominated Day Checkout Handler
     *
     * @var Nominated_Day_Checkout_Handler
     */
    private $nominated_day_checkout_handler;

    /**
     * Service Point Checkout Handler
     *
     * @var Service_Point_Checkout_Handler
     */
    private $service_point_checkout_handler;

    /**
     * Delivery Method Order View Extender
     *
     * @var Delivery_Method_Order_View_Extender
     */
    private $deivery_method_order_view_extender;

    /**
     * Standard Checkout Handler
     *
     * @var Standard_Checkout_Handler
     */
    private $standard_checkout_handler;

    /**
     * Same Day Checkout Handler
     *
     * @var Same_Day_Checkout_Handler
     */
    private $same_day_checkout_handler;

    /**
     * Database
     *
     * @var Database
     */
    private $database;

    /**
     * Flag that signifies that the plugin is initialized.
     *
     * @var bool
     */
    private $is_initialized = false;

    /**
     * Sendcloud_Plugin constructor.
     *
     * @param string $sendcloud_plugin_file
     */
    private function __construct( $sendcloud_plugin_file ) {
        $this->sendcloud_plugin_file                 = $sendcloud_plugin_file;
        $this->base_checkout_handler                 = new Base_Checkout_Handler();
        $this->service_point_checkout_handler_legacy = new Service_Point_Checkout_Handler_Legacy();
        $this->nominated_day_checkout_handler        = new Nominated_Day_Checkout_Handler();
        $this->service_point_checkout_handler        = new Service_Point_Checkout_Handler();
        $this->deivery_method_order_view_extender    = new Delivery_Method_Order_View_Extender();
        $this->email_handler                         = new Email_Handler();
        $this->service_point_config_repository       = new Service_Point_Configuration_Repository();
        $this->standard_checkout_handler             = new Standard_Checkout_Handler();
        $this->same_day_checkout_handler             = new Same_Day_Checkout_Handler();
        $this->database                              = new Database( new Plugin_Options_Repository() );
    }

    /**
     * Initialize the plugin and returns instance of the plugin
     *
     * @param $sendcloud_plugin_file
     *
     * @return Sendcloud
     */
    public static function init( $sendcloud_plugin_file ) {
        if ( null === self::$instance ) {
            self::$instance = new self( $sendcloud_plugin_file );
        }

        self::$instance->initialize();

        return self::$instance;
    }

    /**
     * Defines global constants and hooks actions to appropriate events
     */
    private function initialize() {
        if ( $this->is_initialized ) {
            return;
        }

        register_activation_hook( $this->sendcloud_plugin_file, array( $this, 'sendcloudshipping_activate' ) );
        add_action( 'init', new Logging_Callable( array( $this, 'sendcloudshipping_init' ) ) );
        add_action( 'plugins_loaded', new Logging_Callable( array( $this, 'sendcloudshipping_bootstrap' ) ) );
        add_action( 'before_woocommerce_init', function() {
            if ( class_exists( FeaturesUtil::class ) ) {
                FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->sendcloud_plugin_file, true );
            }
        } );
        $this->load_sendcloud_admin_menu();
        $this->add_ajax_actions();

        try {
            $this->database->update( is_multisite() );
        } catch ( Migration_Exception $e ) {
            Logger::error( 'Unable to migrate database:' . $e->getMessage(), array( 'trace' => $e->getTraceAsString() ) );
        }

        $this->is_initialized = true;
    }

    /**
     * Adds CleverReach item to backend administrator menu.
     */
    private function load_sendcloud_admin_menu() {
        if ( is_admin() && ! is_network_admin() ) {
            add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
        }
    }

    public function create_admin_menu() {
        $controller      = new Sendcloud_View_Controller();
        $sendcloud_label = __( 'Sendcloud', 'sendcloud-shipping' );
        add_submenu_page(
            'woocommerce',
            $sendcloud_label,
            $sendcloud_label,
            'manage_woocommerce',
            'sendcloud-wc',
            array( $controller, 'render' )
        );
    }

    private function add_ajax_actions() {
        add_action( 'wp_ajax_get_redirect_sc_url', array(
            new Sendcloud_Api_Enable_Controller(),
            'generate_redirect_url'
        ) );
    }

    /**
     * @return void
     */
    private function handle_sendcloud_request() {
        $controller_name = $this->get_param('sendcloud_controller');
        if ( ! empty( $controller_name ) ) {
            $controller = new Sendcloud_Base_Controller();
            $controller->index();
        }
    }

    /**
     * SendCloud activate action
     */
    public function sendcloudshipping_activate() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
    }

    /**
     * Loads translations
     */
    public function sendcloudshipping_init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        load_plugin_textdomain( 'sendcloud-shipping', false, basename( dirname( $this->sendcloud_plugin_file ) ) . '/i18n/languages/' );
        $this->handle_sendcloud_request();
    }

    /**
     * Action on plugin loaded
     */
    public function sendcloudshipping_bootstrap() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            deactivate_plugins( plugin_basename( $this->sendcloud_plugin_file ) );
            add_action( 'admin_notices', new Logging_Callable( array(
                $this,
                'sendcloudshipping_deactivate_notice'
            ) ) );
        } else {
            $this->base_checkout_handler->init();
            $this->service_point_checkout_handler_legacy->init();
            $this->service_point_checkout_handler->init();
            $this->nominated_day_checkout_handler->init();
            $this->deivery_method_order_view_extender->init();
            $this->email_handler->init();
            $this->standard_checkout_handler->init();
            $this->same_day_checkout_handler->init();
            $this->add_currency_changed_hook();
            add_filter( 'woocommerce_api_classes', new Logging_Callable( array(
                $this,
                'sendcloudshipping_add_api'
            ) ) );
            add_filter( 'woocommerce_shipping_methods', new Logging_Callable( array(
                $this,
                'sendcloudshipping_register_shipping_methods',
            ) ) );

            register_shutdown_function( array( $this, 'log_errors' ) );
        }
    }

    public function add_currency_changed_hook() {
        $me = $this;
        add_action( 'update_option_woocommerce_currency', function () use ( $me ) {
            global $wpdb;
            $repository = new WC_Shipping_Method_Repository( $wpdb );
            $repository->disable_all();
            add_action( 'woocommerce_sections_general', new Logging_Callable( array(
                $me,
                'sendcloud_shipping_methods_disabled_notice'
            ) ) );

        } );
    }

    /**
     * Adds service point shipping methodnotice notice-warning
     *
     * @param $methods
     *
     * @return mixed
     */
    public function sendcloudshipping_register_shipping_methods( $methods ) {
        $script = $this->service_point_config_repository->get()->get_script();
        if ( ! empty( $script ) ) {
            $methods[ SendCloudShipping_Service_Point_Shipping_Method::ID ] = SendCloudShipping_Service_Point_Shipping_Method::CLASS_NAME;
        }

        $config_repository = new SC_Config_Repository();
        if($config_repository->get_last_published_time()) {
            $methods[ Nominated_Day_Shipping_Method::ID ] = Nominated_Day_Shipping_Method::CLASS_NAME;
            $methods[ Standard_Shipping_Method::ID ]      = Standard_Shipping_Method::CLASS_NAME;
            $methods[ Same_Day_Shipping_Method::ID ]      = Same_Day_Shipping_Method::CLASS_NAME;
            $methods[ Service_Point_Shipping_Method::ID ] = Service_Point_Shipping_Method::CLASS_NAME;
        }

        return $methods;
    }

    /**
     * Adds Api Service Point in WC Api classes
     *
     * @param $apis
     *
     * @return mixed
     */
    public function sendcloudshipping_add_api( $apis ) {
        $apis[] = SendCloudShipping_API_ServicePoint::CLASS_NAME;
        $apis[] = Checkout_Api_Service::CLASS_NAME;
        $apis[] = Integration_Api_Service::CLASS_NAME;

        return $apis;
    }

    /**
     * Renders message about WooCommerce being deactivated
     */
    public function sendcloudshipping_deactivate_notice() {
        echo wp_kses( View::file( '/plugin/deactivation-notice.php' )->render(),
            View::get_allowed_tags() );
    }

    /**
     * Renders message about shipping methods disabled
     */
    public function sendcloud_shipping_methods_disabled_notice() {
        echo wp_kses( View::file( '/plugin/methods-disabled-notice.php' )->render(),
            View::get_allowed_tags() );
    }

    /**
     * Returns base directory path
     *
     * @return string
     */
    public static function get_plugin_dir_path() {
        return rtrim( plugin_dir_path( __DIR__ ), '/' );
    }

    /**
     * Returns url for the provided directory
     *
     * @param $path
     *
     * @return string
     */
    public static function get_plugin_url( $path ) {
        return rtrim( plugins_url( "/{$path}/", __DIR__ ), '/' );
    }

    /**
     * Logs errors
     */
    public function log_errors() {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], array(
                E_ERROR,
                E_PARSE,
                E_COMPILE_ERROR,
                E_USER_ERROR,
                E_RECOVERABLE_ERROR
            ), true ) ) {
            Logger::critical( sprintf( '%1$s in %2$s on line %3$s', $error['message'], $error['file'], $error['line'] ) .
                PHP_EOL );
        }
    }

    /**
     * Gets request parameter if exists. Otherwise, returns null.
     *
     * @param string $key Request parameter key.
     *
     * @return mixed
     */
    private function get_param(string $key ) {
        if ( isset( $_REQUEST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
        }

        return null;
    }
}
