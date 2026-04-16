<?php

class SwpmCustomPostTypeProtectionEnhanced {

    public function __construct() {
        
        if (class_exists('SimpleWpMembership')) {
            add_action('wp', array(&$this, 'initialize'));
        }
    }

    public function initialize() {
        $this->check_custom_post();
    }

    private function check_custom_post() {
        //Check if individial post display page.
        if(!is_single()){
            //This is not a single post (of any post type) view. So no need to do our check.
            return;
        }
        
        $args = array('public' => true, '_builtin' => false); //By setting '_builtin' to false, we will exclude the built-in WordPress post, page, and attachment.
        $post_types = get_post_types($args); //Lets get all the post types uses in this site.

        global $post;
        $post_id = $post->ID;
        if (empty($post_id)) {
            //Lets try to get the post ID using an alternative method
            $current_page_url = SwpmMiscUtils::get_current_page_url();
            $post_id = url_to_postid($current_page_url);
        }

        if (!in_array(get_post_type($post_id), $post_types)) {
            //This is not a custom post type so bail out.
            return;
        }
        
        $access =SwpmAccessControl::get_instance();
        if (!$access->can_i_read_post($post_id)){
            $error_page_title = SwpmUtils::_("Restricted Content");
            $error_page_title = apply_filters('swpm_cptp_restricted_page_title', $error_page_title);
            wp_die($access->why(), $error_page_title);
        }
    }
}
