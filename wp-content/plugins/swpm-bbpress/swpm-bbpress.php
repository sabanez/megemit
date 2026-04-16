<?php
/*
Plugin Name: Simple Membership BBPress Addon
Version: v1.4
Plugin URI: https://simple-membership-plugin.com/
Author: smp7, wp.insider
Author URI: https://simple-membership-plugin.com/
Description: Adds bbPress forum integration with the simple membership plugin to offer members only forum functionality
*/

//Direct access to this file is not permitted
if (!defined('ABSPATH')){
    exit; //Exit if accessed directly
}

define('SWPM_BBPRESS_VER', '1.4');
define('SWPM_BBPRESS_SITE_HOME_URL', home_url());
define('SWPM_BBPRESS_PATH', dirname(__FILE__) . '/');
define('SWPM_BBPRESS_URL', plugins_url('',__FILE__));
define('SWPM_BBPRESS_DIRNAME', dirname(plugin_basename(__FILE__)));
require_once ('classes/class.swpm-bbpress.php');

new SwpmBbpress();
