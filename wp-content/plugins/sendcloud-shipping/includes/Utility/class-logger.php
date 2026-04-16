<?php

namespace Sendcloud\Shipping\Utility;

use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Sendcloud;
use WC_Logger;

class Logger {
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Level strings mapped to integer severity.
     *
     * @var array
     */
    public static $level_to_severity = [
        self::EMERGENCY => 800,
        self::ALERT     => 700,
        self::CRITICAL  => 600,
        self::ERROR     => 500,
        self::WARNING   => 400,
        self::NOTICE    => 300,
        self::INFO      => 200,
        self::DEBUG     => 100
    ];

    /**
     * Instance of Logger class
     *
     * @var Logger
     */
    private static $instance;

    /**
     * WooCommerce logger
     *
     * @var WC_Logger
     */
    private $wc_logger;

    /**
     * @var SC_Config_Repository
     */
    private $config_repository;

    /**
     * Logger constructor.
     *
     * @param WC_Logger $wc_logger
     */
    public function __construct() {
        if ( version_compare( WC()->version, '2.7', '>=' ) ) {
            $this->wc_logger = wc_get_logger();
        } else {
            $this->wc_logger = new WC_Logger();
        }

        $this->config_repository = new SC_Config_Repository();
    }

    /**
     * Gets logger instance
     *
     * @return Logger
     */
    private static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Log info level message
     *
     * @param $message
     * @param array $context
     */
    public static function info( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::INFO)) {
            self::get_instance()->log( self::INFO, $message, $context );
        }
    }

    /**
     * Log debug level message
     *
     * @param $message
     * @param array $context
     */
    public static function debug( $message, $context = array() )
    {
        if (self::get_instance()->check_if_message_should_be_logged(self::DEBUG)) {
            self::get_instance()->log(self::DEBUG, $message, $context);
        }
    }

    /**
     * Log error level message
     *
     * @param $message
     * @param array $context
     */
    public static function error( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::ERROR)) {
            self::get_instance()->log(self::ERROR, $message, $context);
        }
    }

    /**
     * Log notice level message
     *
     * @param $message
     * @param array $context
     */
    public static function notice( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::NOTICE)) {
            self::get_instance()->log(self::NOTICE, $message, $context);
        }
    }

    /**
     * Log warning level message
     *
     * @param $message
     * @param array $context
     */
    public static function warning( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::WARNING)) {
            self::get_instance()->log(self::WARNING, $message, $context);
        }
    }

    /**
     * Log alert level message
     *
     * @param $message
     * @param array $context
     */
    public static function alert( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::ALERT)) {
            self::get_instance()->log(self::ALERT, $message, $context);
        }
    }

    /**
     * Log critical level message
     *
     * @param $message
     * @param array $context
     */
    public static function critical( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::CRITICAL)) {
            self::get_instance()->log(self::CRITICAL, $message, $context);
        }
    }

    /**
     * Log emergency level message
     *
     * @param $message
     * @param array $context
     */
    public static function emergency( $message, $context = array() ) {
        if (self::get_instance()->check_if_message_should_be_logged(self::EMERGENCY)) {
            self::get_instance()->log(self::EMERGENCY, $message, $context);
        }
    }

    /**
     * Log message
     *
     * @param $level
     * @param $message
     * @param array $context
     */
    private function log( $level, $message, $context = array() ) {
        if ( ! empty( $context['trace'] ) ) {
            $message .= PHP_EOL . 'Stack trace: ' . PHP_EOL . $context['trace'];
        }

        if ( version_compare( WC()->version, '2.7', '>=' ) ) {
            $context['source'] = Sendcloud::INTEGRATION_NAME;
            $this->wc_logger->log( $level, $message, $context );
        } else {
            $message = strtoupper( $level ) . ' ' . $message;
            $this->wc_logger->add( Sendcloud::INTEGRATION_NAME, $message );
        }
    }

    /**
     * @param int $log_level
     *
     * @return bool
     */
    private function check_if_message_should_be_logged(string $log_level): bool
    {
        $min_log_level = $this->config_repository->get_min_log_level() ?: self::$level_to_severity[self::WARNING];

        return self::$level_to_severity[$log_level] >= $min_log_level;
    }
}
