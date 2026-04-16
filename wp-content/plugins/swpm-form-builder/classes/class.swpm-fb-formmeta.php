<?php

/**
 * Description of class
 *
 * @author nur
 */
class SwpmFbFormmeta {

    private $table;
    private $ftable;
    public $id;
    public $key;
    public $title;
    public $type;
    public $for_level;
    public $success_type;
    public $success_message;
    public $notification_setting;
    public $notification_email_name;
    public $notification_subject;
    public $notification_message;
    public $label_alignment;
    public $fields = array();

    public function __construct() {
        global $wpdb;
        $this->error = array();
        $this->data = array();
        $this->custom = array();
        $this->table = $wpdb->prefix . 'swpm_form_builder_forms';
        $this->ftable = $wpdb->prefix . 'swpm_form_builder_fields';
    }

    public function create() {

    }

    public function save() {
        global $wpdb;
        $out = true;
        $data = array(
            'form_key' => $this->key,
            'form_title' => $this->title,
            'form_type' => $this->type,
            'form_membership_level' => $this->for_level,
            'form_success_type' => $this->success_type,
            'form_success_message' => $this->success_message,
            'form_notification_email_name' => $this->notification_email_name,
            'form_notification_subject' => $this->notification_subject,
            'form_notification_message' => $this->notification_message
        );
        // todo: validate here
        if (empty($this->id)) {
            // Create the form
            $data = array_filter($data);
            $out = $wpdb->insert($this->table, $data);
            // Get form ID to add our first field
            $this->id = $wpdb->insert_id;
            for ($i = 0; $i < count($this->fields); $i++) {
                $field = $this->fields[$i];
                $field->form_id = $this->id;
                //todo: set parent;
                $field->save();
            }
        } else {
            $wpdb->update($this->table, $data, array('form_id' => $this->id));
            for ($i = 0; $i < count($this->fields); $i++) {
                $field = $this->fields[$i];
                $field->form_id = $this->id;
                //todo: set parent;
                $field->save();
            }
        }
        return $out;
    }

    public function load($form_id, $load_fields = true) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT
            form_id AS id,
            form_key AS _key,
            form_title AS title,
            form_type AS type,
            form_membership_level AS for_level,
            form_success_type AS success_type,
            form_success_message AS success_message,
            form_notification_setting AS notification_setting,
            form_notification_email_name AS notification_email_name,
            form_notification_subject AS notification_subject,
            form_notification_message AS notification_message,
            form_label_alignment AS label_alignment
        FROM $this->table WHERE form_id=%d", $form_id);
        $data = $wpdb->get_row($query);
        foreach ($data as $key => $value) {
            if ($key == '_key') {
                $this->key = $value;
                continue;
            }
            $this->$key = $value;
        }
        if ($load_fields) {
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
                        FROM $this->ftable WHERE form_id= %d ORDER BY field_sequence ASC", $form_id
            );
            $data = $wpdb->get_results($query);
            foreach ($data as $field) {
                $obj = new SwpmFbFieldmeta();
                $obj->load($field);
                $this->fields[] = $obj;
            }
        }
    }

    public function validate() {

    }

    public static function get_registration_form_id_by_level_or_default($level) {
        global $wpdb;
        $query = $wpdb->prepare('SELECT form_id FROM ' .
                $wpdb->prefix . 'swpm_form_builder_forms WHERE form_type = %d AND (form_membership_level= %d OR form_membership_level=0 ) ORDER BY form_membership_level DESC LIMIT 0,1', SwpmFbFormCustom::REGISTRATION, $level
        );

        $form_id = $wpdb->get_var($query);
        return $form_id;
    }

    public static function get_registration_form_id_by_level($level) {
        global $wpdb;
        $query = $wpdb->prepare('SELECT form_id FROM ' .
                $wpdb->prefix . 'swpm_form_builder_forms WHERE form_type = %d AND form_membership_level=%d', SwpmFbFormCustom::REGISTRATION, $level
        );

        $form_id = $wpdb->get_var($query);
        return $form_id;
    }

    public static function get_profile_form_id_by_level_or_default($level) {
        global $wpdb;
        $query = $wpdb->prepare('SELECT form_id FROM ' .
                $wpdb->prefix . 'swpm_form_builder_forms WHERE form_type = %d AND (form_membership_level= %d OR form_membership_level=0 ) ORDER BY form_membership_level DESC LIMIT 0,1', SwpmFbFormCustom::PROFILE, $level
        );

        $form_id = $wpdb->get_var($query);
        return $form_id;
    }

    public static function get_profile_form_id_by_level($level) {
        global $wpdb;
        $query = $wpdb->prepare('SELECT form_id FROM ' .
                $wpdb->prefix . 'swpm_form_builder_forms WHERE form_type = %d AND form_membership_level=%d', SwpmFbFormCustom::PROFILE, $level
        );

        $form_id = $wpdb->get_var($query);
        return $form_id;
    }

}
