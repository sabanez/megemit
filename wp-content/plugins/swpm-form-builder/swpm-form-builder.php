<?php
/*
  Plugin Name: Simple Membership Form Builder
  Description: Simple Membership Addon to Dynamically Build Registration and Edit Profile Forms.
  Plugin URI: https://simple-membership-plugin.com/simple-membership-form-builder-addon/
  Author: wp.insider
  Author URI: https://simple-membership-plugin.com/
  Version: 5.0.1
 */

/***
 * Helpful pointers for dev
 * 1) The 'includes/form-output.php' file outputs and renders the front-end form and fields.
 * 2) The 'classes/class.swpm-fb-form.php file has a save() function that saves the data to the DB when rego or profile forms are submitted on the front-end.
 * 3) Front-end form submission (both rego and edit profile) is handled using the following sequence:
 * class.swpm-form-builder.php => Swpm_Form_Builder -> init() -> process_submitted_form() -> validate_and_save() -> save() -> parent::save() -> confirmation_redirect();
 * Search with the 'filter_input_array' keyword to find a sample of where the value is read from the REQUEST parameter. In this example it will show for the "checkbox" type field which reads an array value.
 * 4) Profile updated message shown by the following:
 * class.swpm-form-builder.php => Swpm_Form_Builder -> profile_override() -> confirmation_text();
 * 5) See save_add_new_form() for after a new form is created in admin.
***/
/***
 * To add a new field, check the "instructions" field's trail.
 * 1) views/button_palette_metabox.php has the button listings.
 * 2) includes/admin-field-options.php has the content that is shown in the field's admin configurtion options.
 * 3) classes/class.swpm-fb-fieldmeta.php file needs the function that will actually show the value of the field on the front-end.
 *    See the "private function text(...)" to get an understanding of how one field (text field in this case) is output on the edit profile form (or admin edit interface).
 * 4) includes/form-output.php calls the toHTML() which in turn calls the function in the class.swpm-fb-fieldmeta.php file to show the value.
 ***/
/***
 * Get custom feild details of a member using the following. $member_id is the ID of the member.
 * This function in the show member info has example code: swpm_smi_get_custom_field_info_by_id($column, $member_id)
 * or the following
 * $swpm_form_custom = new SwpmFbFormCustom();
 * $swpm_form_custom -> init($member_id);
 * print_r($swpm_form_custom->custom_info);
 * print_r($swpm_form_custom->custom);
 ***/

define( 'SWPMFB_VERSION', '5.0.1' );
define( 'SWPMFB_SCRIPT_DEBUG', true );
define( 'SWPM_FORM_BUILDER_PATH', dirname( __FILE__ ) . '/' );
define( 'SWPM_FORM_BUILDER_URL', plugins_url( '', __FILE__ ) );

require_once('classes/class.swpm-form-builder.php');
require_once('classes/class.swpm-fb-installer.php');
require_once('classes/class.swpm-fb-utils.php');
require_once('classes/class.swpm-fb-utils-custom-fields.php');
require_once('classes/class.swpm-fb-settings.php');

add_action( 'plugins_loaded', 'swpm_load_form_builder' );
register_activation_hook( SWPM_FORM_BUILDER_PATH . 'swpm-form-builder.php', 'SwpmFbInstaller::activate' );

function swpm_load_form_builder() {
    new Swpm_Form_Builder();
}
