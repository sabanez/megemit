<?php
/**
 * Plugin Name: MeGeMIT HubSpot Bridge & Onboarding
 * Plugin URI: https://megemit.org
 * Description: Centraliza la integración de formularios con HubSpot y gestiona el flujo de onboarding obligatorio.
 * Version: 1.0.0
 * Author: MeGeMIT Technical Team
 * Text Domain: mgmit-hs-bridge
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin usando patrón Singleton
 */
class MGMIT_HubSpot_Bridge {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    private function define_constants() {
        define('MGMIT_HS_BRIDGE_PATH', plugin_dir_path(__FILE__));
        define('MGMIT_HS_BRIDGE_URL', plugin_dir_url(__FILE__));
        define('MGMIT_HS_BRIDGE_VERSION', '1.0.0');
        define('MGMIT_HS_BRIDGE_OPTION', 'mgmit_hubspot_config');
    }

    private function init_hooks() {
        // Carga de scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 20);

        // Registro de activación
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);

        // Cargar Admin UI si estamos en el backend
        if (is_admin()) {
            require_once MGMIT_HS_BRIDGE_PATH . 'includes/class-mgmit-admin-ui.php';
            new MGMIT_Admin_UI();
        }
    }

    /**
     * Acciones al activar el plugin
     */
    public function activate_plugin() {
        if (!get_option(MGMIT_HS_BRIDGE_OPTION)) {
            update_option(MGMIT_HS_BRIDGE_OPTION, $this->get_default_config());
        }
    }



    /**
     * Encolado de scripts del plugin
     */
    public function enqueue_scripts() {
        $map_js = 'assets/js/hubspot_map.js';
        $enforce_js = 'assets/js/onboarding-enforcement.js';

        if (file_exists(MGMIT_HS_BRIDGE_PATH . $map_js)) {
            wp_enqueue_script(
                'mgmit-hubspot-mapper',
                MGMIT_HS_BRIDGE_URL . $map_js,
                ['jquery'],
                filemtime(MGMIT_HS_BRIDGE_PATH . $map_js),
                true
            );
            
            // Configuración dinámica desde la base de datos
            $config = get_option(MGMIT_HS_BRIDGE_OPTION, $this->get_default_config());
            $config = apply_filters('mgmit_hs_bridge_config', $config);
            
            wp_localize_script('mgmit-hubspot-mapper', 'HS_CONFIG', $config);
        }


    }

    /**
     * Devuelve la configuración inicial por defecto
     */
    private function get_default_config() {
        return [
            [
                'formId' => '#registro-profesional-13, #swpm-registration-form, .swpm-registration-form',
                'hubspotFormName' => 'MeGeMIT_DE_Fachkreisbereich_Registration',
                'mapping' => [
                    'swpm-472' => 'firstname',
                    'swpm-474' => 'lastname',
                    'swpm-456' => 'email'
                ]
            ],
            [
                'formId' => '#profile-form-level-13-16',
                'hubspotFormName' => 'MeGeMIT_DE_Profile_Update',
                'mapping' => [
                    'swpm-526' => 'firstname',
                    'swpm-527' => 'lastname',
                    'swpm-531' => 'email'
                ]
            ]
        ];
    }

}

// Inicializar el plugin
add_action('plugins_loaded', function() {
    MGMIT_HubSpot_Bridge::get_instance();
});
