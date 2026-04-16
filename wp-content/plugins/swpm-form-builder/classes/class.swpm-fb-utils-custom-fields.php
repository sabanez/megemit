<?php

/**
 * SwpmFbUtilsCustomFields class
 * 
 * More helpful functions can be found in the following addon's code (the older one in the backup).
 * swpm-member-directory/classes/class.swpm-member-directory.php
 */
class SwpmFbUtilsCustomFields {

    /**
     * Retrieves custom field data for given member
     */
    public static function get_custom_data_by_member_id($member_id) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT C.field_id,F.field_name,  value FROM " .
                $wpdb->prefix . "swpm_form_builder_custom C  INNER JOIN " .
                $wpdb->prefix . "swpm_form_builder_fields F"
                . " ON (C.field_id = F.field_id) WHERE user_id = %d", $member_id);
        $result = $wpdb->get_results($query, ARRAY_A);

        $custom_data = array();
        foreach ($result as $data) {
            $field_id = $data['field_name'];
            $value = $data['value'];
            $custom_data[$field_id] = $value;
        }

        return $custom_data;
    }

    /**
     * Retrieves custom data column headers.
     */
    public static function get_custom_data_field_headers() {

        global $wpdb;
        $header = array();
        $query = "SELECT field_id, field_name FROM " . $wpdb->prefix . "swpm_form_builder_fields WHERE field_key = 'custom' AND field_type NOT IN ('file-upload')";
        $result = $wpdb->get_results($query, ARRAY_A);
        $added = array();
        foreach ($result as $value) {
            $field_name = $value['field_name'];
            if (!isset($added[$field_name])) {
                $added[$field_name] = $field_name;
                $header[] = $field_name;
            }
        }
        return $header;
    }

    public static function get_custom_fields_set_for_given_member($member_id) {
        global $wpdb;
        $fields = array();
        $field_table = $wpdb->prefix . 'swpm_form_builder_fields';
        if (empty($member_id)) {
            return '<p>Error! This function requires a member_id value to be passed to it.</p>';
        }

        $swpm_user = SwpmMemberUtils::get_user_by_id($member_id);

        $membership_level = $swpm_user->membership_level;

        $form_id = SwpmFbFormmeta::get_profile_form_id_by_level_or_default($membership_level);
        if (empty($form_id)) {
            $form_id = SwpmFbFormmeta::get_registration_form_id_by_level_or_default($membership_level);
        }
        if (empty($form_id)) {
            return;
        }
        $query = $wpdb->prepare(
                "SELECT
                            field_id AS id,
                            form_id,
                            field_key AS _key,
                            field_type AS type,
                            field_options AS options,
                            field_description AS description,
                            field_name AS name,
                            field_sequence AS sequence,
                            field_parent AS parent,
                            field_validation AS validation,
                            field_required AS required,
                            field_size AS size,
                            field_css AS css,
                            field_layout AS layout,
                            field_default AS _default,
                            field_readonly As readonly,
                            field_adminonly AS adminonly,
                            reg_field_id AS reg_field_id
                        FROM $field_table WHERE form_id= %d and field_key='custom' ORDER BY field_sequence ASC", $form_id
        );
        $data = $wpdb->get_results($query);
        foreach ($data as $field) {
            $obj = new SwpmFbFieldmeta();
            $obj->load($field);
            $fields[] = $obj;
        }
        
        //Uncomment the following to see the fields in a nicely formatted table.
        //echo '<pre>';
        //print_r($fields);
        //echo '<pre>';

        return $fields;
    }

}