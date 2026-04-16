<?php

/**
 * Description of class
 *
 * @author nur
 */
class SwpmFbAdminCustomFields {

    private $fields;
    private $membership_level;
    private $form_id;

    public function init($membership_level) {
        global $wpdb;
        $this->fields = array();
        $this->membership_level = $membership_level;
        $field_table = $wpdb->prefix . 'swpm_form_builder_fields';

        $form_id = SwpmFbFormmeta::get_profile_form_id_by_level_or_default($membership_level);
        if (empty($form_id)) {
            $form_id = SwpmFbFormmeta::get_registration_form_id_by_level_or_default($membership_level);
        }

        $this->form_id = $form_id;
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
                    FROM $field_table WHERE form_id= %d and (field_key='custom' OR field_key='profile_image') ORDER BY field_sequence ASC", $form_id
        );
        $data = $wpdb->get_results($query);
        foreach ($data as $field) {
            $obj = new SwpmFbFieldmeta();
            $obj->load($field);
            $this->fields[] = $obj;
        }
    }

    public function admin_ui() {
        if (!is_admin() && !current_user_can(SWPM_MANAGEMENT_PERMISSION)) {
            return '';
        }
        $custom = new SwpmFbFormCustom();
        $custom->load_form($this->form_id);
        $member_id = filter_input(INPUT_GET, 'member_id');
        if (empty($member_id)) {
            return'';
        }
        $custom->init($member_id);

        return $custom->admin_ui($this->fields);
    }

    public function save($user_id) {
        $custom = new SwpmFbFormCustom();
        $custom->load_form($this->form_id);
        $custom->init($user_id);
        $custom->process_custom($this->fields);
        if ($custom->is_valid()) {
            $custom->save();
            return array();
        }
        return $custom->error();
    }

}
