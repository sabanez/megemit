<?php
/**
 * Plugin Name: MeGeMIT HubSpot Bridge
 * Version: 1.5.0
 * Author: MeGeMIT Technical Team
 */

if (!defined('ABSPATH')) exit;

class MGMIT_HubSpot_Bridge {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    private function define_constants() {
        define('MGMIT_HS_BRIDGE_PATH',    plugin_dir_path(__FILE__));
        define('MGMIT_HS_BRIDGE_URL',     plugin_dir_url(__FILE__));
        define('MGMIT_HS_BRIDGE_VERSION', '1.5.0');
        define('MGMIT_HS_BRIDGE_OPTION',  'mgmit_hubspot_config');
        define('MGMIT_HS_ACCESS_TOKEN',   defined('MGMIT_HS_ACCESS_TOKEN_SECRET') ? MGMIT_HS_ACCESS_TOKEN_SECRET : '');
        define('MGMIT_HS_PORTAL_ID',      defined('MGMIT_HS_PORTAL_ID_SECRET')    ? MGMIT_HS_PORTAL_ID_SECRET    : '144893874');
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 1);
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);

        add_action('swpm_front_end_registration_complete_fb', [$this, 'handle_swpm_submission']);
        add_action('swpm_front_end_profile_edited_fb',        [$this, 'handle_swpm_submission']);

        if (is_admin()) {
            require_once MGMIT_HS_BRIDGE_PATH . 'includes/class-mgmit-admin-ui.php';
            new MGMIT_Admin_UI();
        }
    }

    public function enqueue_scripts() {
        $map_js = 'assets/js/hubspot_map.js';
        if (!file_exists(MGMIT_HS_BRIDGE_PATH . $map_js)) return;

        wp_enqueue_script(
            'mgmit-hubspot-mapper',
            MGMIT_HS_BRIDGE_URL . $map_js,
            array(),
            filemtime(MGMIT_HS_BRIDGE_PATH . $map_js),
            false
        );

        $config = get_option(MGMIT_HS_BRIDGE_OPTION, []);
        wp_localize_script('mgmit-hubspot-mapper', 'HS_CONFIG', $config);
    }

    public function handle_swpm_submission($swpm_data) {
        if (empty($_POST['mgmit_hs_form_id'])) return;

        $posted_form_id = sanitize_text_field($_POST['mgmit_hs_form_id']);
        $config = get_option(MGMIT_HS_BRIDGE_OPTION);
        if (empty($config)) return;

        foreach ($config as $mapping_rule) {
            $form_id   = isset($mapping_rule['formId'])   ? trim($mapping_rule['formId'])   : '';
            $form_guid = isset($mapping_rule['formGuid']) ? trim($mapping_rule['formGuid']) : '';

            if (empty($form_guid) || $form_id !== $posted_form_id) continue;

            $fields = array();
            foreach ($mapping_rule['mapping'] as $wp_field => $hs_prop) {
                $val = isset($_POST[$wp_field]) ? $_POST[$wp_field] : '';
                if (!empty($val)) {
                    $fields[] = array(
                        'name'  => sanitize_text_field($hs_prop),
                        'value' => sanitize_text_field($val),
                    );
                }
            }

            if (empty($fields)) continue;

            $has_email = false;
            foreach ($fields as $f) {
                if ($f['name'] === 'email') { $has_email = true; break; }
            }
            if (!$has_email) continue;

            $context = array(
                'hutk'      => isset($_COOKIE['hubspotutk']) ? sanitize_text_field($_COOKIE['hubspotutk']) : '',
                'ipAddress' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
                'pageUri'   => wp_get_referer() ? wp_get_referer() : '',
                'pageName'  => '',
            );

            wp_remote_post(
                'https://api.hsforms.com/submissions/v3/integration/secure/submit/' . MGMIT_HS_PORTAL_ID . '/' . $form_guid,
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . MGMIT_HS_ACCESS_TOKEN,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode(array('fields' => $fields, 'context' => $context)),
                    'timeout' => 15,
                )
            );

            break;
        }
    }

    public function activate_plugin() {
        if (!get_option(MGMIT_HS_BRIDGE_OPTION)) {
            update_option(MGMIT_HS_BRIDGE_OPTION, []);
        }
    }
}

add_action('plugins_loaded', function() {
    MGMIT_HubSpot_Bridge::get_instance();
});