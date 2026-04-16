<?php
namespace ebsso;

if (!defined('ABSPATH')) {
    exit('This is not the way to call me!');
}

class SsoManageMooLogin
{

    private $plugin_name;
    private $version;

    public function __construct($pluginName, $version)
    {
        $this->plugin_name = $pluginName;
        $this->version     = $version;
    }

    /**
     * Logging out user from moodle site.
     *
     * @since 1.0.0
     */
    public function mdlLoggedOut()
    {
        if (isset($_SESSION['eb_wp_user_id']) && '' != $_SESSION['eb_wp_user_id']) {
            $user_id = $_SESSION['eb_wp_user_id'];
            unset($_SESSION['eb_wp_user_id']);
        } else {
            return;
        }
        $moodle_user_id = get_user_meta($user_id, 'moodle_user_id', true);
        if ('' == $moodle_user_id) {
            return '';
        }

        $logout_url = site_url();

        if ( isset( $_SERVER['HTTP_REFERER'] ) && filter_var( $_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL ) ) {
            $logout_url = $_SERVER['HTTP_REFERER'];
        }

        $hash = hash('md5', rand( 10,1000 ) );
        $query = array(
            'moodle_user_id'   => $moodle_user_id,
            'logout_redirect'  => apply_filters( 'eb_sso_logout_url', $logout_url ),
            'wp_one_time_hash' => $hash
        );

        // encode array as querystring
        $final_url = generateMoodleLogoutUrl($query);
        if (filter_var($final_url, FILTER_VALIDATE_URL)) {

            // Send post data.
            $details        = http_build_query($query);
            
            $eb_moodle_url  = eb_get_mdl_url();
            $sso_secret_key = eb_get_mdl_token();
            $wdm_data       = encryptString( $details, $sso_secret_key );

            if ( ! empty( $eb_moodle_url ) ) {

                $final_url = $eb_moodle_url . ED_MOODLE_PLUGIN_URL;

                $request_args = array(
                    'body'    => array('wdm_data' => $wdm_data),
                    'timeout' => 100,
                );

                // Wdm data and url.
                // Set session in moodle.
                wp_remote_post( $eb_moodle_url . '/auth/wdmwpmoodle/login.php', $request_args );

                // Now redirect user with only Moodle user id.
                wp_redirect( $eb_moodle_url . '/auth/wdmwpmoodle/login.php?logout_id=' . $moodle_user_id . '&veridy_code=' . $hash );
                exit;
            }
        }
    }

    /**
     * Logged in user on moodle site.
     *
     * @since 1.0.0
     */
    public function mdlLoggedIn( $user_login, $user, $social_redirect = '', $redirect = '' )
    {
        //unnecessary variable
        unset( $user_login );
        $moodle_user_id = get_user_meta( $user->ID, 'moodle_user_id', true );
        if ( empty( $moodle_user_id ) ) {
            return;
        }

        $redirection = new SsoRedirection( $this->plugin_name, $this->version );
        $default_redirect = '';
        $ignore_setting_redirect_url = 0;

        if ( ! empty( $social_redirect ) ) {
            $default_redirect = $social_redirect;
        } elseif ( ! empty( $redirect ) ) {
            $ignore_setting_redirect_url = 1;
            $default_redirect = $redirect;
        }

        $hash  = hash( 'md5', rand( 10,1000 ) );
        $query = array(
            'moodle_user_id'   => $moodle_user_id,
            'login_redirect'   => $redirection->getLoginRedirectUrl( $user, $default_redirect, $ignore_setting_redirect_url ),
            'wp_one_time_hash' => $hash
        );
        
        // Send post data.
        $details        = http_build_query($query);
        $eb_moodle_url  = eb_get_mdl_url();
        $sso_secret_key = eb_get_mdl_token();
        $wdm_data       = encryptString( $details, $sso_secret_key );

        if ( ! empty( $eb_moodle_url ) ) {
            $final_url = $eb_moodle_url . ED_MOODLE_PLUGIN_URL;

            $request_args = array(
                'body'    => array('wdm_data' => $wdm_data),
                'timeout' => 100,
            );

            // Wdm data and url.
            // Set session in moodle.
            wp_remote_post( $eb_moodle_url . '/auth/wdmwpmoodle/login.php', $request_args );

            $final_url = $eb_moodle_url . '/auth/wdmwpmoodle/login.php?login_id=' . $moodle_user_id . '&veridy_code=' . $hash;
            // Now redirect user with only Moodle user id.
        }

        wp_redirect( $final_url );
        exit;
    }
}
