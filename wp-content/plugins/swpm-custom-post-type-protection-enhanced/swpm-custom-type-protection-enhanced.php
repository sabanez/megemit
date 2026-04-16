<?php
/*
Plugin Name: SWPM Custom Post Type Protection
Version: v1.1
Plugin URI: https://simple-membership-plugin.com/simple-membership-addon-better-custom-post-type-protection/
Author: wp.insider
Author URI: https://simple-membership-plugin.com/
Description: Offers a better solution for protecting custom post type posts.
*/

//Slug - swpm_cptp

//Direct access to this file is not permitted
if (!defined('ABSPATH')){
    exit("Do not access this file directly.");
}

include_once('classes/class.swpm-custom-post-type-protection-enhanced.php');
define('SWPM_CPT_PROTECTION_ENHANCED_VER', '1.1');
define('SWPM_CPT_PROTECTION_ENHANCED_PATH', dirname(__FILE__) . '/');
define('SWPM_CPT_PROTECTION_ENHANCED_URL', plugins_url('', __FILE__));
add_action('plugins_loaded', 'load_swpm_cpt_protection_enhanced_object');

function load_swpm_cpt_protection_enhanced_object() {
    new SwpmCustomPostTypeProtectionEnhanced();
}