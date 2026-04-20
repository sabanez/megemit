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
        // Inicialización de sesiones PHP (Prioridad alta)
        add_action('init', [$this, 'maybe_start_session'], 1);
        
        // Carga de scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 20);

        // Lógica de Onboarding (Migrada de functions.php)
        add_action('user_register', [$this, 'mark_user_for_onboarding'], 5, 1);
        add_action('template_redirect', [$this, 'enforce_onboarding_redirection'], 1);
        add_action('init', [$this, 'handle_onboarding_completion'], 10);

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
     * Asegura que la sesión PHP esté activa
     */
    public function maybe_start_session() {
        if (!session_id() && !is_admin()) {
            session_start();
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

        if (file_exists(MGMIT_HS_BRIDGE_PATH . $enforce_js)) {
            wp_enqueue_script(
                'mgmit-onboarding-enforcement',
                MGMIT_HS_BRIDGE_URL . $enforce_js,
                ['jquery'],
                filemtime(MGMIT_HS_BRIDGE_PATH . $enforce_js),
                true
            );
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

    /**
     * Marca al usuario recién registrado para completar el onboarding
     */
    public function mark_user_for_onboarding($user_id) {
        update_user_meta($user_id, 'mgmit_hs_details_pending', '1');
        
        if (!session_id()) { session_start(); }
        $_SESSION['mgmit_hs_user_id'] = $user_id;
        $_SESSION['mgmit_hs_pending'] = 1;

        $login_token = isset($_POST['mgmit_hs_token']) ? sanitize_text_field($_POST['mgmit_hs_token']) : (isset($_COOKIE['mgmit_hs_login_token']) ? $_COOKIE['mgmit_hs_login_token'] : '');
        if (!empty($login_token)) {
            update_user_meta($user_id, 'mgmit_hs_login_token', $login_token);
        }
    }

    /**
     * Redirección forzosa si el perfil está pendiente
     */
    public function enforce_onboarding_redirection() {
        if (strstr($_SERVER['REQUEST_URI'], 'action=logout') || is_admin()) return;

        $is_pending = false;

        if (is_user_logged_in()) {
            if (get_user_meta(get_current_user_id(), 'mgmit_hs_details_pending', true) === '1') {
                $is_pending = true;
            }
        } 
        
        if (!$is_pending) {
            if (!session_id()) { session_start(); }
            if ((isset($_SESSION['mgmit_hs_pending']) && $_SESSION['mgmit_hs_pending'] == 1) || 
                (isset($_COOKIE['mgmit_hs_pending']) && $_COOKIE['mgmit_hs_pending'] === '1')) {
                $is_pending = true;
            }
        }

        if ($is_pending) {
            $forced_page_id = 21568; 
            if (!is_page($forced_page_id) && !is_page('registrierungsdetails') && !is_page('registrierung')) {
                wp_redirect(get_permalink($forced_page_id) . '?enforced=1');
                exit;
            }
        }
    }

    /**
     * Maneja la finalización del onboarding y el auto-login
     */
    public function handle_onboarding_completion() {
        $is_registration_post = isset($_POST['swpm_registration_submit']) || 
                                isset($_POST['swpm-fb-submit']) || 
                                (isset($_POST['swpm_registr_level_id']) && !empty($_POST['swpm_registr_level_id']));

        if (!session_id()) { session_start(); }

        if ($is_registration_post) {
            setcookie('mgmit_hs_pending', '1', time() + 86400, '/', '', false, false);
            $_COOKIE['mgmit_hs_pending'] = '1';
            $_SESSION['mgmit_hs_pending'] = 1;
        }

        if (isset($_GET['hs_finish']) || isset($_GET['hs_test'])) {
            $user_id = isset($_SESSION['mgmit_hs_user_id']) ? absint($_SESSION['mgmit_hs_user_id']) : 0;
            $user = $user_id > 0 ? get_userdata($user_id) : null;
            
            if ($user) {
                update_user_meta($user->ID, 'mgmit_hs_details_pending', '0');
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID, true);
                
                if (class_exists('SwpmMemberAuth')) {
                    $auth = SwpmMemberAuth::get_instance();
                    if (method_exists($auth, 'login')) {
                        $auth->login($user->user_login, '', true);
                    }
                }
            }

            unset($_SESSION['mgmit_hs_user_id'], $_SESSION['mgmit_hs_pending']);
            setcookie('mgmit_hs_pending', '', time() - 3600, '/');
            
            wp_redirect(home_url('/fachkreisbereich/'));
            exit;
        }
    }
}

// Inicializar el plugin
add_action('plugins_loaded', function() {
    MGMIT_HubSpot_Bridge::get_instance();
});
