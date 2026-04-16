<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

/*global $wpdb;

$form_table 	= $wpdb->prefix . 'swpm_form_builder_fields';
$fields_table 	= $wpdb->prefix . 'swpm_form_builder_forms';
$entries_table 	= $wpdb->prefix . 'swpm_form_builder_entries';

$wpdb->query( "DROP TABLE IF EXISTS $form_table" );
$wpdb->query( "DROP TABLE IF EXISTS $fields_table" );
$wpdb->query( "DROP TABLE IF EXISTS $entries_table" );

delete_option( 'swpm_db_version' );
delete_option( 'swpm-form-builder-screen-options' );
delete_option( 'swpm_dashboard_widget_options' );
delete_option( 'swpm-settings' );

$wpdb->query( "DELETE FROM " . $wpdb->prefix . "usermeta WHERE meta_key IN ( 'swpm-form-settings', 'swpm_entries_per_page', 'swpm_forms_per_page', 'manageswpm-form-builder_page_swpm-entriescolumnshidden' )" );
*/