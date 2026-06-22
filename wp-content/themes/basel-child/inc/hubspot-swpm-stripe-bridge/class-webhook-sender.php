<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MGMIT_Webhook_Sender {

    const ENDPOINT    = 'https://vpsbridge.dimint.com/webhook/woocommerce/de';
    const LOG_FILE    = 'mgmit-bridge-log.txt';
    const SECRET_KEY  = 'mgmit_bridge_webhook_secret_de';

    public static function send( $payload, $event_type ) {
        $body   = wp_json_encode( $payload );
        $secret = get_option( self::SECRET_KEY, '' );

        $headers = array(
            'Content-Type'       => 'application/json',
            'x-wc-webhook-topic' => $event_type,
        );

        if ( $secret ) {
            $headers['X-WC-Webhook-Signature'] = base64_encode( hash_hmac( 'sha256', $body, $secret, true ) );
        }

        $args = array(
            'method'  => 'POST',
            'timeout' => 15,
            'headers' => $headers,
            'body'    => $body,
        );

        $response = wp_remote_post( self::ENDPOINT, $args );

        if ( is_wp_error( $response ) ) {
            self::log( 'WP_ERROR event=' . $event_type . ' msg=' . $response->get_error_message() );
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $resp_body = wp_remote_retrieve_body( $response );
            self::log( 'HTTP_' . $code . ' event=' . $event_type . ' response=' . substr( $resp_body, 0, 300 ) );
        }
    }

    public static function debug_log( $message ) {
        self::log( '[DEBUG] ' . $message );
    }

    private static function log( $message ) {
        $log_path = WP_CONTENT_DIR . '/uploads/' . self::LOG_FILE;
        $line     = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $message . PHP_EOL;
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $log_path, $line, FILE_APPEND | LOCK_EX );
    }
}
