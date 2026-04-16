<?php

/**
 * Description of class
 */
class SwpmBbpress {

    public function __construct() {
        if (class_exists('SimpleWpMembership')) {
            add_action('swpm_addon_settings_section', array(&$this, 'settings_ui'));
            add_action('swpm_addon_settings_save', array(&$this, 'settings_save'));
            add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
            add_action('bbp_loaded', array(&$this, 'bbp_loaded'), 10);                        
        }
    }

    public function bbp_loaded() {
        add_filter('bbp_get_reply_content', array(&$this, 'get_reply_content'));
        //add_filter('bbp_get_topic_content','wp_emember_enhance_forum_protection');
        add_action('save_post', array(&$this, 'update_membership_level_reply'));
        add_filter('bbp_user_can_view_forum', array(&$this, 'user_can_view_forum'), 10, 3);
        
        add_filter('bbp_get_user_profile_url', array(&$this, 'bbp_profile_url_override'), 10, 3);
    }

    public function plugins_loaded() {

    }

    public function bbp_profile_url_override($url, $user_id, $user_nicename){
        $override_bbp_profile_url = SwpmSettings::get_instance()->get_value('override_bbp_profile_url');
        if($override_bbp_profile_url){
            //Override feature is eanbled.
            $swpm_profile_page_url = SwpmSettings::get_instance()->get_value('profile-page-url');
            $url = $swpm_profile_page_url;
        }
        
        return $url;
    }
    
    public function user_can_view_forum($retval, $forum_id, $user_id) {
        global $post;
        $enable_bbpress = SwpmSettings::get_instance()->get_value('swpm-addon-enable-bbpress');
        if (empty($enable_bbpress)) {
            return $retval;
        }
        if ($this->is_forum_post_visible($post)) {
            return $retval;
        } else {
            //SWPM can also output *protected* message here
            bbp_get_template_part('feedback', 'no-access');
            $retval = false;
            return $retval;
        }
    }

    public function get_reply_content($content) {
        global $post;
        $enable_bbpress = SwpmSettings::get_instance()->get_value('swpm-addon-enable-bbpress');
        if (empty($enable_bbpress)) {
            return $content;
        }
        if ($this->is_forum_post_visible($post)) {
            return $content;
        }

        return apply_filters('swpm_restricted_post_msg', BUtils::_('You are not allowed to view this content'));
    }

    public function update_membership_level_reply($post_id) {
        $enable_bbpress = SwpmSettings::get_instance()->get_value('swpm-addon-enable-bbpress');
        if (empty($enable_bbpress)) {
            return;
        }
        if (!wp_is_post_revision($post_id)) {
            $post = get_post($post_id);
            if ($post->post_type != "reply") {
                return;
            }
            $parent = $post->post_parent;
            global $wpdb;
            $protected = SwpmProtection::get_instance();
            if (!$protected->is_protected($parent)) {
                return;
            }

            if (!$protected->is_protected($post_id)) {
                SwpmProtection::get_instance()->apply(array($post_id), 'custom_post')->save();
            }

            $levels = SwpmUtils::get_all_membership_level_ids();
            foreach ($levels as $level) {
                $permitted = $perms = SwpmPermission::get_instance($level);
                if ($permitted->is_permitted($post_id)) {
                    continue;
                }
                if ($permitted->is_permitted($parent)) {
                    $permitted->apply(array($post_id), 'custom_post')->save();
                }
            }
        }
    }

    public function is_forum_post_visible($post) {
        $id = $post->ID;
        $auth = SwpmAuth::get_instance();
        $protected = SwpmProtection::get_instance();
        $perms = SwpmPermission::get_instance($auth->get('membership_level'));
        if ($protected->is_protected($id)) {
            if (!$auth->is_logged_in()) {
                return false;
            }

            $expires = $auth->get('account_state');
            if ($expires == 'expired') {
                return false;
            }
            if (SwpmUtils::is_subscription_expired($auth->userData)) {
                return false;
            }

            return $perms->is_permitted($id);
        }
        $topic_id = bbp_get_reply_topic_id($id);
        if (!empty($topic_id) && $protected->is_protected($topic_id)) {
            if (!$auth->is_logged_in()) {
                return false;
            }
            $expires = $auth->get('account_state');
            if ($expires == 'expired') {
                return false;
            }
            if (SwpmUtils::is_subscription_expired($auth->userData)) {
                return false;
            }
            return $perms->is_permitted($topic_id);
        }
        $forum_id = bbp_get_topic_forum_id($id);
        if (empty($forum_id) || !$protected->is_protected($forum_id)) {
            return true;
        }
        if (!$auth->is_logged_in()) {
            return false;
        }
        $expires = $auth->get('account_state');
        if ($expires == 'expired') {
            return false;
        }
        if (SwpmUtils::is_subscription_expired($auth->userData)) {
            return false;
        }

        return $perms->is_permitted($forum_id);
    }

    public function settings_ui() {
        $enable_bbpress = SwpmSettings::get_instance()->get_value('swpm-addon-enable-bbpress');
        $override_bbp_profile_url = SwpmSettings::get_instance()->get_value('override_bbp_profile_url');
        require_once (SWPM_BBPRESS_PATH . 'views/settings.php');
    }

    public function settings_save() {
        $message = array('succeeded' => true, 'message' => '<p>'.BUtils::_('Settings updated!').'</p>');
        SwpmTransfer::get_instance()->set('status', $message);
        
        $settings = SwpmSettings::get_instance();
        $enable_bbpress = filter_input(INPUT_POST, 'swpm-addon-enable-bbpress');
        $settings->set_value('swpm-addon-enable-bbpress', empty($enable_bbpress) ? "" : $enable_bbpress);
        
        $override_bbp_profile_url = filter_input(INPUT_POST, 'override_bbp_profile_url');
        $settings->set_value('override_bbp_profile_url', empty($override_bbp_profile_url) ? "" : $override_bbp_profile_url);
        
        $settings->save();
    }

}
