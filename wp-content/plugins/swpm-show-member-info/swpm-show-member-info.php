<?php
/*
  Plugin Name: SWPM Show Member Info
  Description: Simple Membership extension for showing various member info on your site using shortcodes
  Plugin URI: https://simple-membership-plugin.com/simple-membership-addon-show-member-info/
  Author: smp7, wp.insider
  Author URI: https://simple-membership-plugin.com/
  Version: 1.8
 */

//Slug - swpm_smi
//Direct access to this file is not permitted
if (!defined('ABSPATH')) {
    exit; //Exit if accessed directly
}

add_shortcode("swpm_show_member_info", "swpm_smi_show_member_info");

function swpm_smi_show_member_info($args) {
    extract(shortcode_atts(array(
        'column' => '',
        'member_id' => '',
                    ), $args));

    //Check column name
    if (empty($column)) {
        return '<div style="color: red;">Error! This shortcode requires a value for the "column" field</div>';
    }

    //Check member_id
    if (empty($member_id)) {
        //Show info of the currently logged in member. Lets get the currently logged-in member's ID.
        $member_id = SwpmMemberUtils::get_logged_in_members_id();
    } else {
        //Show info of the given member ID.
    }

    $column_value = swpm_smi_get_member_info_by_id($column, $member_id);
    return $column_value;
}

function swpm_smi_get_member_info_by_id($column, $member_id) {
    global $wpdb;
    $query = "SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE member_id = %d";
    $userData = $wpdb->get_row($wpdb->prepare($query, $member_id));
    
    $date_format = get_option('date_format');
    if (empty($date_format)) {
        //WordPress's date form settings is not set. Lets set a default format.
        $date_format = 'Y-m-d';
    }
        
    //Lets first check if this is for a special column
    if ($column == "membership_level_name") {//Membership level name
	$level_id = $userData->membership_level;
        $level_name = SwpmMembershipLevelUtils::get_membership_level_name_by_level_id($level_id);
	return $level_name;
    }
    
    if ($column == "expiry_date") {//Expiry Date
        //TODO - In the future use function - SwpmMemberUtils::get_formatted_expiry_date_by_user_id($member_id)
        
        $expiry_timestamp = SwpmMemberUtils::get_expiry_date_timestamp_by_user_id($member_id);
        if($expiry_timestamp == PHP_INT_MAX){
            //No Expiry Setting
            $formatted_expiry_date = __("No Expiry", "simple-membership");
        } else {
            $expiry_date = date('Y-m-d', $expiry_timestamp);
            //Alternatively use function SwpmUtils::get_formatted_and_translated_date_according_to_wp_settings()
            $formatted_expiry_date = date_i18n( $date_format, strtotime( $expiry_date ) );
        }
	return $formatted_expiry_date;
    }    
    
    if ($column == "member_since_formatted") {//Member Since Date formatted
        $member_since_date = $userData->member_since;
        //Alternatively use function SwpmUtils::get_formatted_and_translated_date_according_to_wp_settings()
        $formatted_member_since_date = date_i18n( $date_format, strtotime( $member_since_date ) );
	return $formatted_member_since_date;
    }  
    
    //If this is a core field then return that value
    if (isset($userData->$column)) {
        return $userData->$column;
    }

    //There is no core field for the given column name.
    //Lets check if form builder is being used then retrieve custom field data.
    $custom_field_value = swpm_smi_get_custom_field_info_by_id($column, $member_id);

    return $custom_field_value;
}

function swpm_smi_get_custom_field_info_by_id($column, $member_id) {
    if (!class_exists('Swpm_Form_Builder')) {
        //Not using the form builder. Nothing to retrieve.
        return '';
    }

    //Read the custom field value.
    global $wpdb;
    $sql = $wpdb->prepare("SELECT value FROM " . $wpdb->prefix . "swpm_form_builder_custom C
        INNER JOIN " . $wpdb->prefix . "swpm_form_builder_fields  F ON (C.field_id = F.field_id)
        WHERE C.user_id=%d AND F.Field_name=%s", $member_id, $column);

    $value = $wpdb->get_var($sql);
    return empty($value) ? "" : $value;
}
