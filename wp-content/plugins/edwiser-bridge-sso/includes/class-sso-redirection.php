<?php
namespace ebsso;

if (!defined('ABSPATH')) {
    exit('This is not the way to call me!');
}

class SsoRedirection
{

    private $version;
    private $plugin_name;

    public function __construct($plugin_name, $version)
    {
        $this->version     = $version;
        $this->plugin_name = $plugin_name;
    }

    /**
     * Get login redirect url.
     *
     * @return $redirect_url
     * @since 1.2
     */
    public function getLoginRedirectUrl( $user, $defaultRedirectUrl = '', $ignore_setting_redirect_url = 0 )
    {
        $post_content = null;
        $user_redirect_url_status = $this->getUserRedirectUrl( $user ); 
        $redirectUrl  = $user_redirect_url_status;

        if ( $ignore_setting_redirect_url && ! empty( $defaultRedirectUrl ) ) {
            $redirectUrl = $defaultRedirectUrl;
        } elseif ( empty( $redirectUrl ) ) {
            if ( ! empty( $defaultRedirectUrl ) ) {
                $redirectUrl = $defaultRedirectUrl;
            } else {
                $redirectUrl = get_site_url();
            }
        }

        $get = array();
        if ( isset( $_SERVER['HTTP_REFERER'] ) && filter_var( $_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL ) ) {
            $postid       = url_to_postid($_SERVER['HTTP_REFERER']);
            $post_content = $postid ? get_post($postid)->post_content : null;
            parse_str( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_QUERY ), $get );
        }


        if ( ! $user_redirect_url_status ) {
            $redirect_url = getRedirectUrl( $get, $post_content, $redirectUrl );
        } else {
            $redirect_url = $redirectUrl;
        }


        if (isset($post_content) && has_shortcode($post_content, 'bridge_woo_single_cart_checkout') && ! $ignore_setting_redirect_url ) {
            $redirect_url = $_SERVER['HTTP_REFERER'];
        } elseif (isset($post_content) && has_shortcode($post_content, 'woocommerce_checkout') && ! $ignore_setting_redirect_url ) {
            $redirect_url = $_SERVER['HTTP_REFERER'];
        } elseif ( isset( $get['login_action'] ) && 'moodle' == $get['login_action'] ) {
            $redirect_url = $get['redirect_to'];
        }

        if ( isset( $get['redirect_to'] ) && filter_var( $get['redirect_to'], FILTER_VALIDATE_URL ) && isset( $get['is_enroll'] ) ) {
            $redirect_url = $get['redirect_to'];
            $redirect_url = add_query_arg( 'auto_enroll', 'true', $redirect_url );
        }
        return apply_filters('eb_sso_login_url', $redirect_url);
    }

    private function getUserRedirectUrl( $user )
    {
        $redirectUrls = get_option( 'eb_sso_settings_redirection' );
        if ( isset( $redirectUrls['ebsso_role_base_redirect'] ) && $redirectUrls['ebsso_role_base_redirect'] == 'no' ) {
            return $this->getRedirectUrl( $redirectUrls, 'ebsso_login_redirect_url' );
        } else {
            return $this->getRedirectUrl( $redirectUrls, 'ebsso_login_redirect_url_' . $user->roles[0] );
        }
    }

    private function getRedirectUrl( $data, $role )
    {
        /*if (isset($data[$role]) && !empty($data[$role])) {
            return $data[$role];
        } else {
            return get_site_url();
        }*/
        $redirect = false;
        if( isset( $_GET['mdl_course_id'] ) && ! empty( $_GET['mdl_course_id'] ) ) {
            $redirect = eb_get_mdl_url() . '/course/view.php?id=' . $_GET['mdl_course_id'];
        } elseif ( isset( $data[$role] ) && ! empty( $data[$role] ) ) {
            $redirect = $data[$role];
        } elseif ( isset( $data['ebsso_login_redirect_url'] ) && ! empty( $data['ebsso_login_redirect_url'] ) ) {
            $redirect = $data['ebsso_login_redirect_url'];
        }

        return $redirect;
    }
}
