<?php
namespace ebsso;

if (!defined('ABSPATH')) {
    exit('This is not the way to call me!');
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
class SsoInit
{

    protected $plugin_name;
    protected $version;

    public function __construct($pluginName, $version)
    {
        $this->plugin_name = $pluginName;
        $this->version     = $version;
    }

    public function run()
    {
        $this->loadCommonDependancy();
        if (is_admin()) {
            $this->loadAdminDependancy();
        } else {
            $this->loadPublicDependancy();
        }
    }

    private function loadCommonDependancy()
    {
        include_once EBSSO_DIR_PATH . '/includes/ebsso-functions.php';
        include_once EBSSO_DIR_PATH . '/includes/class-sso-manage-moodle-login.php';
        include_once EBSSO_DIR_PATH . '/includes/class-sso-social-login-user-manager.php';
        include_once EBSSO_DIR_PATH . '/includes/social-login/facebook/class-sso-facebook-init.php';

        include_once EBSSO_DIR_PATH . '/includes/social-login/google/class-sso-google-plus-init.php';
        

        include_once EBSSO_DIR_PATH . '/public/shortcodes/class-sso-social-login.php';

        add_action('eb_after_shortcode_doc', array($this, 'addShortcodeDesc'));
        add_action('plugins_loaded', array($this, 'loadTxtDomain'));
        add_action('init', array($this, 'startSession'), 1);
        add_action('init', array($this, 'loadSocialLoginDependancy'), 2);
        // add_action('set_current_user', array($this, 'loadSocialLoginDependancy'), 1);

        add_action('clear_auth_cookie', array($this, 'clearAuthCookie'));
        add_action('admin_init', array($this, 'migratePreviousVersionData'));

        // plugin update notice.
        add_action('admin_notices', array($this, 'eb_sso_update_notice'));
        add_action('admin_init', array($this, 'eb_sso_update_notice_dismiss_handler')); 

        $this->addPluginShortcodes();

        //Test
        /*$socialLogin = new SsoSocoalLogin($this->plugin_name, $this->version);

        add_action('eb_login_form', array($socialLogin, 'output'));*/

    }


    public function migratePreviousVersionData()
    {
        $ssoSettings = get_option("eb_sso_settings_general");
        if (isset($ssoSettings['eb_sso_fb_enable']) && $ssoSettings['eb_sso_fb_enable'] == "yes") {
            $ssoSettings['eb_sso_fb_enable'] = "both";
        }
        if (isset($ssoSettings['eb_sso_gp_enable']) && $ssoSettings['eb_sso_gp_enable'] == "yes") {
            $ssoSettings['eb_sso_gp_enable'] = "both";
        }
        update_option("eb_sso_settings_general", $ssoSettings);
    }



    /**
     * this is no more needed
     * @since 1.3.1
     */
    public function loadSocialLoginDependancy()
    {
        // if (session_status() == PHP_SESSION_NONE) {

        $ssoSettings = get_option("eb_sso_settings_general");

        if ( isset( $_GET['action'] ) || isset( $_GET['code'] ) ) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            if ( isset( $_GET['action'] ) ) {

                if ( isset( $ssoSettings['eb_sso_fb_enable'] ) && $ssoSettings['eb_sso_fb_enable'] != "no" ) {
                    $fbSDK = new SsoFacebookInit( $this->plugin_name, $this->version );
                    $fbSDK->loadDepend();
                }
            } elseif ( isset( $_GET['code'] ) ) {
                if ( isset($ssoSettings['eb_sso_gp_enable']) && $ssoSettings['eb_sso_gp_enable'] != "no" ) {
                    $gpSDK = new SsoGooglePlusInit( $this->plugin_name, $this->version );
                    $gpSDK->loadDepend();
                }
            }

            session_write_close();
        }
    }

    private function loadPublicDependancy()
    {
        include_once EBSSO_DIR_PATH . '/public/class-sso-public.php';
        $publicSide = new SsoPublic($this->plugin_name, $this->version);
        $publicSide->initPublic();
    }

    private function loadAdminDependancy()
    {
        include_once EBSSO_DIR_PATH . '/admin/class-sso-admin.php';
        $adminSide = new SSOAdmin($this->plugin_name, $this->version);
        $adminSide->adminInit();
    }

    private function addPluginShortcodes()
    {
        /*if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }*/

        $socialLogin = new SsoSocoalLogin($this->plugin_name, $this->version);

        /**
         * Create shortcode to display social login widget.
         */
        add_shortcode("eb_sso_social_login", array($socialLogin, "output"));
        // session_write_close();

    }

    /**
     * Load plugin's textdomain.
     *
     * @since 1.2
     */
    public function loadTxtDomain()
    {
        load_plugin_textdomain("single_sign_on_text_domain", false, EBSSO_DIR_NAME . '/languages');
    }

    /**
     * Register session.
     *
     * @since 1.2
     */
    public function startSession()
    {
        global $eb_session_id;
        // if (!session_id()) {
        if( ! headers_sent() ) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $eb_session_id = session_id();
            session_write_close();
        }

    }

    public function clearAuthCookie()
    {
        $userinfo                  = wp_get_current_user();
        $user_id                   = $userinfo->ID;
        $_SESSION['eb_wp_user_id'] = $user_id;
    }



    public function addShortcodeDesc()
    {
        $html = "<div class='eb-shortcode-doc-wpra'>
                    <h3>Single Sign On Shortcode Options </h3>
                    <div class='eb-shortcode-doc'>
                        <h4>[eb_sso_social_login]</h4>
                        <div class='eb-shortcode-doc-desc'>
                            <p>
                                This shortcode shows Facebook and Goodle+ icons for login.
                            </p>
                        </div>
                    </div>
                    <div class='eb-shortcode-doc'>
                        <h4>[wdm_generate_link]</h4>
                        <div class='eb-shortcode-doc-desc'>
                            <p>
                            This shortcode redirects user to the Moodle site
                            </p>
                        </div>
                    </div>
                </div>";
        echo $html;
    }



    /**
     * handle notice dismiss
     *
     * @deprecated since 2.0.1 discontinued.
     * @since 1.3.1
     */
    public function eb_sso_update_notice_dismiss_handler() {
        if ( isset( $_GET['eb_sso_update_notice'] ) && $_GET['eb_sso_update_notice'] ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, 'eb_sso_update_notice', 'true', true );
        }
    }


    /**
     * show admin feedback notice
     *
     * @since 1.3.1
     */
    public function eb_sso_update_notice() {
        $redirection = add_query_arg( 'eb_sso_update_notice', true );

        $user_id = get_current_user_id();
        $version_compare = get_user_meta( $user_id, 'eb_sso_update_notice_version_wise' );

        if ( ! $version_compare ) {
            delete_user_meta( $user_id, 'eb_sso_update_notice' );
            update_user_meta( $user_id, 'eb_sso_update_notice_version_wise', 'true', true );
        }


        if ( ! get_user_meta( $user_id, 'eb_sso_update_notice' ) ) {
            echo '  <div class="notice  eb_sso_update_notice_message">
                        <div class="eb_sso_update_notice_message_cont">
                            <div class="eb_sso_update_notice_content">
                                ' . esc_html__( 'Please update your Moodle Edwiser SSO plugin to avoid any malfunctioning.', 'eb-textdomain' ) . '
                            </div>
                            <div style="font-size:13px; padding-top:4px;">
                                <a href="' . esc_html( $redirection ) . '">
                                    ' . esc_html__( ' Dismiss', 'eb-textdomain' ) . '
                                </a>
                            </div>
                        </div>
                    </div>';
        }
    }




}
