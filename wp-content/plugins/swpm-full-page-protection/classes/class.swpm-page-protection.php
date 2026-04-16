<?php

class SWPM_FPP_Protection {

    public function __construct() {

        if (class_exists('SimpleWpMembership')) {
            add_action('wp', array(&$this, 'initialize'));
        }
    }

    public function initialize() {
        
        $emp_options = get_option('swpm_fpp_addon_settings');
        $prot_alt_post_enabled = isset($emp_options['prot_alt_post_enabled']) ? $emp_options['prot_alt_post_enabled'] : '';
        $prot_alt_page_enabled = isset($emp_options['prot_alt_page_enabled']) ? $emp_options['prot_alt_page_enabled'] : '';
        $prot_alt_cpt_enabled = isset($emp_options['prot_alt_cpt_enabled']) ? $emp_options['prot_alt_cpt_enabled'] : '';

        if (!empty($prot_alt_post_enabled)) {
            $this->check_post_protection();
        }
        if (!empty($prot_alt_page_enabled)) {
            $this->check_page_protection();
        }
        if (!empty($prot_alt_cpt_enabled)) {
            $this->check_custom_post();
        }
    }

    public function check_post_protection() {
        //Checks if a singular post is being displayed, which is the case when one of the following returns true: is_single(), is_page() or is_attachment(). 
        if (!is_singular()) {
            //This is not a single post/page view. So no need to do our check.
            return;
        }

        global $post;
        $post_id = $post->ID;
        if (empty($post_id)) {
            //Lets try to get the post ID using an alternative method
            $current_page_url = SwpmMiscUtils::get_current_page_url();
            $post_id = url_to_postid($current_page_url);
        }

        //Get the full post object
        $post = get_post($post_id);
        if (empty($post)) {
            return true;
        }

        //Check if this user has permission to view this page
        $permission_to_view = true;
        
        $swpm_protection = SwpmProtection::get_instance();
        $swpm_auth = SwpmAuth::get_instance();
        $permission = SwpmPermission::get_instance($swpm_auth->get('membership_level'));

        //First, check if this is a protected post in the first place.
        if ($swpm_protection->is_protected($post_id)) {

            //This is a protected page so lets see if this user has access to this specific page
            
            if (!$swpm_auth->is_logged_in()) {
                //Member not even logged in so this page needs to be protected and show the login message.
                swpm_fpp_show_not_logged_msg();
            } else {
                //Member is logged in so check user specific permission
                
                if ($swpm_auth->is_expired_account()) {
                    //Account is expired
                    $permission_to_view = false;
                }

                if ($permission->is_permitted($post_id)) {
                    //Has permission to view
                    $permission_to_view = true;
                } else {
		    $permission_to_view = false;
		}
            }
        } else {
            //This page is not even protected so allow access.
            $permission_to_view = true;
        }

        if (!$permission_to_view) {
            swpm_fpp_show_content_restricted_msg();
        }
    }

    public function check_page_protection() {
        //Checks if a singular post is being displayed, which is the case when one of the following returns true: is_single(), is_page() or is_attachment(). 
        if (!is_singular()) {
            //This is not a single post/page view. So no need to do our check.
            return;
        }

        global $post;
        $post_id = $post->ID;
        if (empty($post_id)) {
            //Lets try to get the post ID using an alternative method
            $current_page_url = SwpmMiscUtils::get_current_page_url();
            $post_id = url_to_postid($current_page_url);
        }

        //Get the full post object
        $post = get_post($post_id);
        if (empty($post)) {
            return true;
        }

        //Check if this user has permission to view this page
        $permission_to_view = true;
        
        $swpm_protection = SwpmProtection::get_instance();
        $swpm_auth = SwpmAuth::get_instance();
        $permission = SwpmPermission::get_instance($swpm_auth->get('membership_level'));

        //First, check if this is a protected post in the first place.
        if ($swpm_protection->is_protected($post_id)) {

            //This is a protected page so lets see if this user has access to this specific page
            
            if (!$swpm_auth->is_logged_in()) {
                //Member not even logged in so this page needs to be protected and show the login message.
                swpm_fpp_show_not_logged_msg();
            } else {
                //Member is logged in so check user specific permission
                
                if ($swpm_auth->is_expired_account()) {
                    //Account is expired
                    $permission_to_view = false;
                }

                if ($permission->is_permitted($post_id)) {
                    //Has permission to view
                    $permission_to_view = true;
                } else {
		    $permission_to_view = false;
		}
            }
        } else {
            //This page is not even protected so allow access.
            $permission_to_view = true;
        }

        if (!$permission_to_view) {
            swpm_fpp_show_content_restricted_msg();
        }
    }

    private function check_custom_post() {
        //Check if individial post display page.
        if (!is_single()) {
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

        //Check if this user has permission to view this page
        $permission_to_view = true;
        
        $swpm_protection = SwpmProtection::get_instance();
        $swpm_auth = SwpmAuth::get_instance();
        $permission = SwpmPermission::get_instance($swpm_auth->get('membership_level'));

        //First, check if this is a protected post in the first place.
        if ($swpm_protection->is_protected($post_id)) {

            //This is a protected page so lets see if this user has access to this specific page
            
            if (!$swpm_auth->is_logged_in()) {
                //Member not even logged in so this page needs to be protected and show the login message.
                swpm_fpp_show_not_logged_msg();
            } else {
                //Member is logged in so check user specific permission
                
                if ($swpm_auth->is_expired_account()) {
                    //Account is expired
                    $permission_to_view = false;
                }

                if ($permission->is_permitted($post_id)) {
                    //Has permission to view
                    $permission_to_view = true;
                } else {
		    $permission_to_view = false;
		}
            }
        } else {
            //This page is not even protected so allow access.
            $permission_to_view = true;
        }
        
        if (!$permission_to_view) {
            swpm_fpp_show_content_restricted_msg();
        }
    }
}

function swpm_fpp_get_login_link() {

    $login_url = SwpmSettings::get_instance()->get_value('login-page-url');
    $joinus_url = SwpmSettings::get_instance()->get_value('join-us-page-url');
    if (empty($login_url) || empty($joinus_url)) {
        return '<span style="color:red;">Simple Membership is not configured correctly. The login page or the join us page URL is missing in the settings configuration. '
                . 'Please contact <a href="mailto:' . get_option('admin_email') . '">Admin</a>';
    }

    //Create the login message
    $filtered_login_url = apply_filters('swpm_get_login_link_url', $login_url); //Addons can override the login URL value using this filter.
    $login_msg = '';
    $login_msg .= SwpmUtils::_('Please') . ' <a class="swpm-login-link" href="' . $filtered_login_url . '">' . SwpmUtils::_('Login') . '</a>. ';
    $login_msg .= SwpmUtils::_('Not a Member?') . ' <a href="' . $joinus_url . '">' . SwpmUtils::_('Join Us') . '</a>';

    //Create the full protection message for not logged-in users.
    $not_logged_prot_msg = '';
    $not_logged_prot_msg .= '<div class="swpm_full_page_protection_not_logged_msg">';
    $not_logged_prot_msg .= SwpmUtils::_('You need to login to view this content. ') . $login_msg;
    $not_logged_prot_msg .= '</div>';

    return $not_logged_prot_msg;
}

function swpm_fpp_show_not_logged_msg() {
    $msg = swpm_fpp_get_login_link();
    $title = "This Content is Restricted";
    
    $emp_options = get_option('swpm_fpp_addon_settings');
    $prot_alt_show_header_footer = isset($emp_options['prot_alt_show_header_footer'])? $emp_options['prot_alt_show_header_footer']: '';
    
    if(!empty($prot_alt_show_header_footer)){
        //Show site header and footer with the protection message output.
        get_header();
        echo $msg;
        get_footer();
        exit;
    } else {
        //Just the protection message output.
        wp_die($msg, $title);
    }

}

function swpm_fpp_show_content_restricted_msg() {
    $msg = "You do not have permission to view this content.";
    $title = "This Content is Restricted";
    
    $emp_options = get_option('swpm_fpp_addon_settings');
    $prot_alt_show_header_footer = isset($emp_options['prot_alt_show_header_footer'])? $emp_options['prot_alt_show_header_footer']: '';
    
    if(!empty($prot_alt_show_header_footer)){
        //Show site header and footer with the protection message output.
        get_header();
        echo $msg;
        get_footer();
        exit;
    } else {
        //Just the protection message output.
        wp_die($msg, $title);
    }

}
