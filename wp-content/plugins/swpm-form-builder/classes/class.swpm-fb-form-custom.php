<?php

/**
 * Description of class
 *
 * @author nur
 */
class SwpmFbFormCustom {

    const SPAM_SENSITIVITY = 4; //should come from settings.
    const REGISTRATION = 0;
    const PROFILE = 1;

    public $formmeta;
    public $error;
    public $custom_info;
    public $spam_score;
    public $custom;
    public $member_id;

    public function __construct() {
        $this->spam_score = 0;
        $this->error = array();
        $this->custom_info = array();
        $this->custom = array();
    }

    public function set_custom_field( $field_id, $value ) {
	$this->custom[ $field_id ] = $value;
	if (isset($this->custom_info[$field_id])) {
	    $this->custom_info[$field_id]->value=$value;
	}
    }

    public function get_custom_field( $field_id ) {
	$value = null;
	if ( isset( $this->custom_info[ $field_id ] ) ) {
	    $value = $this->custom_info[ $field_id ]->value;
	}
	return $value;
    }

    protected function get_custom($member_id) {
        global $wpdb;
        $query = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_form_builder_custom WHERE user_id= %d';
        $query = $wpdb->prepare($query, $member_id);
        foreach ($wpdb->get_results($query) as $row) {
            $id = empty($row->reg_field_id) ? $row->field_id : $row->reg_field_id;
            $this->custom_info[$id] = $row;
        }
        //also get profile_image data if needed
        $value = SwpmMemberUtils::get_member_field_by_id($member_id, 'profile_image');
        if (!empty($value) && isset($this->formmeta)) {
            //profile_image is set, let's see if we have a corresponding field in the form
            $query = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_form_builder_fields WHERE form_id=%d AND field_key="profile_image"';

            $query = $wpdb->prepare($query, $this->formmeta->id);
            foreach ($wpdb->get_results($query) as $row) {
                $id = empty($row->reg_field_id) ? $row->field_id : $row->reg_field_id;
                $obj = new stdClass();
                $obj->field_id = $id;
                $obj->value = $value;
                $obj->user_id = $member_id;
                $this->custom_info[$id] = $obj;
            }
        }
    }

    public function is_valid() {
        return empty($this->error);
    }

    public function admin_ui($fields) {
        $html = '<h3>Custom Fields</h3>';
        $html .= '<table class="form-table">';
        foreach ($fields as $field) {
            $id = $field->get_unique_value_id();
            $value = isset($this->custom_info[$id]) ? $this->custom_info[$id]->value : '';
            $html .= '<tr>  <th scope="row"><label for="swpm-' . $field->id . '">' . $field->name . '</label></th><td>';
            $html .= $field->toHTML($value, SwpmFbForm::PROFILE);
            $html .= '</td></tr>';
        }
        return $html . '</table>';
    }

    public function init($member_id) {
        $this->get_custom($member_id);
        $this->member_id = $member_id;
    }

    public function load_form($form_id) {
        $this->formmeta = new SwpmFbFormmeta();
        $this->formmeta->load($form_id, true);
    }

    public function process_custom($fields) {
        foreach ($fields as $field) {
            $type = str_replace('-', '_', $field->type);
            if (method_exists($this, $type)) {
                $this->$type($field);
            }
        }
    }

    protected function text($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = sanitize_text_field($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function checkbox($meta) {
        $args = array('swpm-' . $meta->id => array(
                'filter' => FILTER_SANITIZE_STRING,
                'flags' => FILTER_REQUIRE_ARRAY,
        ));
        $value = filter_input_array(INPUT_POST, $args);
        $value = $value['swpm-' . $meta->id];
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = $value;
    }

    protected function radio($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function country( $meta ) {
    	return $this->select( $meta );
    }

    protected function select($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function date($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        // todo: check date format.
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function time($meta) {
        $args = array('swpm-' . $meta->id => array(
                'filter' => FILTER_SANITIZE_STRING,
                'flags' => FILTER_REQUIRE_ARRAY,
        ));
        $value = filter_input_array(INPUT_POST, $args);
        $value = $value['swpm-' . $meta->id];
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = $value;
    }

    protected function phone($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = SwpmUtils::_($meta->name . ' Field is required');
            return;
        }
        $pattern = '/^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/';
        if (!empty($value) && (strlen($value) <= 6 || !preg_match($pattern, $value))) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' not a valid phone number');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function url($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        if (!empty($value) && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_('  not a valid url');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function number($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && $value==='') {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        if (!empty($value) && !is_numeric($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' must be a valid number');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function file_upload($meta) {
        //check if we have a delete image command first
        if ($meta->key == 'profile_image' && isset($_POST['swpm-delete-' . $meta->id])) {
            $res['type'] = 'profile_image';
            $res['action'] = 'delete';
            $this->custom[$meta->get_unique_value_id()] = $res;
            return;
        }
        $value = ( isset($_FILES['swpm-' . $meta->id]) ) ? $_FILES['swpm-' . $meta->id] : '';
        if (is_array($value) && $value['size'] > 0) {
            $status = SwpmFbUtils::handle_file_upload($value);
            if (isset($status['error'])) {
                $this->error[$meta->id] = $status['error'];
                return;
            }
            if ($meta->key === 'profile_image') {
                $res = Array();
                $res['attachment_id'] = $status['attachment_id'];
                $res['type'] = 'profile_image';
                $this->custom[$meta->get_unique_value_id()] = $res;
            } else {
                $this->custom[ $meta->get_unique_value_id() ] = $status[ 'attachment_id' ];
		//check if this is admin edit member action and admin has uploaded a file
		if ( is_admin() && ! empty( $status[ 'attachment_id' ] ) ) {
		    $swpm_user_row	 = SwpmMemberUtils::get_user_by_id( $this->member_id );
		    $username	 = $swpm_user_row->user_name;
		    $wp_user	 = get_user_by( 'login', $username );
		    $wp_user_id	 = $wp_user->ID;
		    if ( ! empty( $wp_user_id ) ) {
			// we need to assign proper user ID to attachment, otherwise it would have admin ID
			$att_id	 = $status[ 'attachment_id' ];
			$arg	 = array(
			    'ID'		 => $att_id,
			    'post_author'	 => $wp_user_id,
			);
			wp_update_post( $arg );
		    }
		}
	    }
        }
    }

    protected function address($meta) {
        if (isset($_POST['swpm-' . $meta->id])) {
            $address = $_POST['swpm-' . $meta->id];
            $allowed_html = array('br' => array());
            $address['address'] = wp_kses($address['address'], $allowed_html);
            $address['address-2'] = wp_kses($address['address-2'], $allowed_html);
            $address['city'] = wp_kses($address['city'], $allowed_html);
            $address['state'] = wp_kses($address['state'], $allowed_html);
            $address['zip'] = wp_kses($address['zip'], $allowed_html);
            $address['country'] = wp_kses($address['country'], $allowed_html);
            $this->custom[$meta->get_unique_value_id()] = json_encode($address);
            //todo: spam score
        } else if ($meta->required == 'yes') {
            $this->error[$meta->id] = SwpmUtils::_($meta->name . ' Field is required');
        }
    }

    protected function textarea($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_strip_all_tags($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function currency($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && $value==='') {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        if (!empty($value) && !is_numeric($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' must be a valid number');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_data($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function html($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = wp_kses_post($value);//Sanitizes content for allowed HTML tags for post content.
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    protected function email($meta) {
        $value = filter_input(INPUT_POST, 'swpm-' . $meta->id);
        if ($meta->required == 'yes' && empty($value)) {
            $this->error[$meta->id] = $meta->name . SwpmUtils::_(' Field is required');
            return;
        }
        $this->custom[$meta->get_unique_value_id()] = sanitize_email($value);
        $this->spam_score += SwpmFbUtils::calculate_spam_score($value);
    }

    public function error($key = '', $value = '') {
        if (empty($key)) {
            return $this->error;
        }
        $this->error[$key] = $value;
    }

    public function save() {
        global $wpdb;
        foreach ($this->custom as $field_id => $value) {
            if (is_array($value) && isset($value['type']) && $value['type'] === 'profile_image') {
                if (isset($value['action']) && $value['action'] == 'delete') {
                    // delete profile image action. Let's update member profile and delete the attachment.
                    $old_attachment_id = SwpmMemberUtils::get_member_field_by_id($this->member_id, 'profile_image');
                    $query = 'UPDATE ' . $wpdb->prefix . 'swpm_members_tbl SET profile_image="" WHERE member_id=%d';
                    $query = $wpdb->prepare($query, $this->member_id);
                    wp_delete_attachment($old_attachment_id, true);
                    continue;
                }
                if (isset($value['attachment_id'])) {
                    // this is new profile_image attachment id, we need to update user's data
                    $old_attachment_id = SwpmMemberUtils::get_member_field_by_id($this->member_id, 'profile_image');
                    $query = 'UPDATE ' . $wpdb->prefix . 'swpm_members_tbl SET profile_image=%d WHERE member_id=%d';
                    $query = $wpdb->prepare($query, $value['attachment_id'], $this->member_id);
                    $wpdb->query($query);
                    //let's also delete the old image file
                    continue;
                }
            }
            $v = is_array($value) ? serialize($value) : $value;
            if (isset($this->custom_info[$field_id])) {
                //Existing custom field with value. Updating the value only.
                $wpdb->update($wpdb->prefix . 'swpm_form_builder_custom', array(
                    'value' => $v
                        ), array('value_id' => $this->custom_info[$field_id]->value_id));
            } else {
                //First time value for this field maybe. Saving all the necessary parameters.
                $wpdb->insert($wpdb->prefix . 'swpm_form_builder_custom', array(
                    'value' => $v,
                    'user_id' => $this->member_id,
                    'field_id' => $field_id
                ));
            }
        }
    }

}
