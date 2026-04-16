<?php

namespace ebsso;

if (!defined('ABSPATH')) {
    exit('This is not the way to call me!');
}

class SsoSocoalLogin
{
    private $plugin_name;
    private $version;

    public function __construct($pluginName, $version)
    {
        $this->plugin_name = $pluginName;
        $this->version     = $version;
              // add_action('login_form', array($this, 'output'), 100);
    }

    public function ebsso_check_if_google_plus_enabled()
    {
        $keys   = array(
            'eb_sso_gp_client_id',
            'eb_sso_gp_secret_key',
            // 'eb_sso_gp_app_name',
            'eb_sso_gp_enable'
        );
        $option = $this->getSettingData($keys);
        if ($option == false || !$this->checkIsSocialLoginEnabled($option, 'eb_sso_gp_enable')) {
            return false;
        }
        
        return true;
    }


    public function ebsso_check_if_fb_enabled()
    {
        
        $keys   = array(
            'eb_sso_fb_app_id',
            'eb_sso_fb_app_secret_key',
            'eb_sso_fb_enable'
        );
        $option = $this->getSettingData($keys);
        if ($option == false || !$this->checkIsSocialLoginEnabled($option, 'eb_sso_fb_enable')) {
            return false;
        }
        return true;
    }


    private function getSettingData($keys = array())
    {
        $option = get_option("eb_sso_settings_general");
        if ($option !== false) {
            foreach ($keys as $key) {
                if (!$this->checkIsSet($option, $key)) {
                    $option = false;
                }
            }
        }
        return $option;
    }

    private function checkIsSet($data, $key)
    {
        $value = false;
        if (isset($data[$key])) {
            $value = trim($data[$key]);
        }
        if (empty($value)) {
            $value = false;
        }
        return $value;
    }

    private function checkIsSocialLoginEnabled($data, $key)
    {
        if (isset($data[$key]) && $data[$key] == "no") {
            return false;
        }
        return true;
    }


    /**
     * function responsible for the social icons on the wordpress login page and the user-account page.
     * @return string
     */
    public function output($attr)
    {
        $gpLogin     = new SsoGooglePlusInit($this->plugin_name, $this->version);
        $fbLogin     = new SsoFacebookInit($this->plugin_name, $this->version);
        $ssoSettings = get_option("eb_sso_settings_general");

        /*if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }*/

        if ( is_user_logged_in() ) {
            return;
        }

        if (isset($attr['wploginform']) && !$attr['wploginform']) {
            ob_start();
            ?>
            <div>
                <ul class="eb-sso-cont-login-btns">
                    <li><?php

                    if ($gpLogin->loadDepend() && isset($ssoSettings['eb_sso_gp_enable']) && ($ssoSettings['eb_sso_gp_enable'] == 'both' || $ssoSettings['eb_sso_gp_enable'] == 'user_account')) {
                    // if ($this->ebsso_check_if_google_plus_enabled() && isset($ssoSettings['eb_sso_gp_enable']) && ($ssoSettings['eb_sso_gp_enable'] == 'both' || $ssoSettings['eb_sso_gp_enable'] == 'user_account')) {

                        echo $gpLogin->addGoogleLoginButton();
                    }
                    ?></li>
                    <li><?php


                    if ($fbLogin->loadDepend() && isset($ssoSettings['eb_sso_fb_enable']) && ($ssoSettings['eb_sso_fb_enable'] == 'both' || $ssoSettings['eb_sso_fb_enable'] == 'user_account')) {
                    // if ($this->ebsso_check_if_fb_enabled() && isset($ssoSettings['eb_sso_fb_enable']) && ($ssoSettings['eb_sso_fb_enable'] == 'both' || $ssoSettings['eb_sso_fb_enable'] == 'user_account')) {
                        echo $fbLogin->addFacebookLoginButton();
                    }
                    ?></li>
                    <?php do_action("eb-sso-add-more-social-login-options-user-accnt-page"); ?>
                </ul>
            </div>
            <?php
            // ob_flush();
            return ob_get_clean();
        } else {
            ob_start();
            ?>
            <div>
                <ul class="eb-sso-cont-login-btns">
                    <li><?php

                    if ($gpLogin->loadDepend() && isset($ssoSettings['eb_sso_gp_enable']) && ($ssoSettings['eb_sso_gp_enable'] == 'both' || $ssoSettings['eb_sso_gp_enable'] == 'wp_login_page')) {

                    // if ($this->ebsso_check_if_google_plus_enabled() && isset($ssoSettings['eb_sso_gp_enable']) && ($ssoSettings['eb_sso_gp_enable'] == 'both' || $ssoSettings['eb_sso_gp_enable'] == 'wp_login_page')) {
                        echo $gpLogin->addGoogleLoginButton();
                    }
                    ?></li>
                    <li><?php

                    if ($fbLogin->loadDepend() && isset($ssoSettings['eb_sso_fb_enable']) && ($ssoSettings['eb_sso_fb_enable'] == 'both' || $ssoSettings['eb_sso_fb_enable'] == 'wp_login_page')) {
                    // if ($this->ebsso_check_if_fb_enabled() && isset($ssoSettings['eb_sso_fb_enable']) && ($ssoSettings['eb_sso_fb_enable'] == 'both' || $ssoSettings['eb_sso_fb_enable'] == 'wp_login_page')) {
                        echo $fbLogin->addFacebookLoginButton();
                    }
                    ?></li>
                    <?php do_action("eb-sso-add-more-social-login-options-wp-login-page"); ?>
                </ul>
            </div>

            <?php
            // ob_flush();
            return ob_get_clean();
        }
    }
}
