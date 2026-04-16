<?php

namespace ebsso;

/*
 * EDW General Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('SSOSettingsGeneral')) {

    /**
     * WooIntSettings.
     */
    class SSOSettingsGeneral
    {

        public function getGeneralSettings()
        {
            global $current_tab;
            $option   = get_option('eb_' . $current_tab );
            $settings = apply_filters(
                'sso_social_login_settings_fields',
                array(
                    array(
                        'title' => __('General Settings', "single_sign_on_text_domain"),
                        'type'  => 'title',
                        'id'    => 'sso_options',
                    ),
                    array(
                        'title'             => __('Secret Key', "single_sign_on_text_domain"),
                        'desc'              => __('Enter your secret key here.', "single_sign_on_text_domain"),
                        'id'                => 'eb_sso_secret_key',
                        'default'           => $this->getOptionValue($option, 'eb_sso_secret_key'),
                        'type'              => 'text',
                        'desc_tip'          => true,
                    ),
                    array(
                        'title'    => '',
                        'desc'     => '',
                        'id'       => 'eb_sso_verify_key',
                        'default'  => __('Verify token with moodle', "single_sign_on_text_domain"),
                        'type'     => 'button',
                        'desc_tip' => false,
                        'class'    => 'button secondary',
                    ),
                    array(
                        'type' => 'sectionend',
                        'id'   => 'sso_sl_settings',
                    ),
                )
            );
            return $settings;
        }

        private function getOptionValue($data, $key, $default = "")
        {
            if (isset($data[$key])) {
                return $data[$key];
            } else {
                return $default;
            }
        }
    }
}
