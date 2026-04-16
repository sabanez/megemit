<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author nur
 */
class SwpmFbFieldmeta {

    private $table;
    private $mtable;
    public $id;
    public $form_id;
    public $key;
    public $type;
    public $options;
    public $description;
    public $name;
    public $sequence;
    public $parent;
    public $validation;
    public $required;
    public $size;
    public $css;
    public $layout;
    public $default;
    public $readonly;
    public $adminonly;
    public $reg_field_id;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'swpm_form_builder_fields';
        $this->mtable = $wpdb->prefix . 'swpm_form_builder_multivalue';
    }

    public function save() {
        global $wpdb;
        $data = array(
            'form_id' => $this->form_id,
            'field_key' => $this->key,
            'field_type' => $this->type,
            'field_name' => $this->name,
            'field_description' => $this->description,
            'field_size' => $this->size,
            'field_required' => $this->required,
            'field_parent' => $this->parent,
            'field_sequence' => $this->sequence,
            'field_options' => $this->options,
            'field_validation' => $this->validation,
            'field_css' => $this->css,
            'field_layout' => $this->layout,
            'field_default' => $this->default,
            'field_readonly' => $this->readonly,
            'field_adminonly' => $this->adminonly,
            'reg_field_id' => $this->reg_field_id
        );
        // todo: validate here
        $data = array_filter($data);
        if (empty($this->id)) {
            $wpdb->insert($this->table, $data);
        } else {
            $wpdb->update($this->table, $data, array('field_id', $this->id));
        }
    }

    public function load($field) {
        foreach ($field as $key => $value) {
            if ($key == '_key') {
                $this->key = $value;
            } elseif ($key == '_default') {
                $this->default = $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    public function load_from_db($field_id) {
        global $wpdb;
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
                        field_readonly AS readonly,
                        field_adminonly AS adminonly,
                        reg_field_id AS reg_field_id
                    FROM $this->table WHERE field_id= %d", $field_id
        );
        $data = $wpdb->get_row($query);
        $this->load($data);
    }

    public function validate() {

    }

    public function get_unique_value_id() {
        return empty($this->reg_field_id) ? $this->id : $this->reg_field_id;
    }

    public function toHTML($value = null, $form_type = 0, $label_align = "") {
        switch ($this->type) {
            case 'text':
                return $this->text($value, $form_type, $label_align);
                break;
            case 'select':
                return $this->select($value, $form_type, $label_align);
                break;
            case 'radio':
                return $this->radio($value, $form_type, $label_align);
                break;
            case 'fieldset':
                return $this->fieldset($form_type, $label_align);
                break;
            case 'section':
                return $this->section($form_type, $label_align);
                break;
            case 'secret':
                return $this->secret($form_type, $label_align);
                break;
            case 'submit':
                return $this->submit($form_type, $label_align);
                break;
            case 'verification':
                return $this->verification($value, $form_type, $label_align);
                break;
            case 'email':
                return $this->email($value, $form_type, $label_align);
                break;
            case 'url':
                return $this->url($value, $form_type, $label_align);
                break;
            case 'currency':
                return $this->currency($value, $form_type, $label_align);
                break;
            case 'number':
                return $this->number($value, $form_type, $label_align);
                break;
            case 'phone':
                return $this->phone($value, $form_type, $label_align);
                break;
            case 'password':
                return $this->password($form_type, $label_align);
                break;
            case 'textarea':
                return $this->textarea($value, $form_type, $label_align);
                break;
            case 'checkbox':
                return $this->checkbox($value, $form_type, $label_align);
                break;
            case 'address':
                return $this->address($value, $form_type, $label_align);
                break;
            case 'date':
                return $this->date($value, $form_type, $label_align);
                break;
            case 'time':
                return $this->time($value, $form_type, $label_align);
                break;
            case 'html':
                return $this->html($value, $form_type, $label_align);
                break;
            case 'file-upload':
                return $this->file_upload($value, $form_type, $label_align);
                break;
            case 'instructions':
                return $this->instructions($form_type, $label_align);
                break;
            case 'member_id':
                return $this->member_id($value, $form_type, $label_align);
				break;
			case 'country':
				return $this->country($value, $form_type, $label_align);
				break;
        }
    }

    private function text($value = null, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = empty($value) ? $options['default'] : $value;
        $key = str_replace('-', '_', $this->key);
        switch ($key) {
            case 'membership_level':
                return $this->membership_level($value, $form_type, $label_align);
            default:
        }
        // HTML5 types
        if (in_array($this->type, array('email', 'url'))) {
            $type = esc_attr($this->type);
        } elseif ('phone' == $this->type) {
            $type = 'tel';
        } else {
            $type = 'text';
        }
        if ($form_type == SwpmFbForm::REGISTRATION && $key == 'user_name') {
            $options['css'] = $options['css'] . " nowhitespace ";
        }
        $form_item = sprintf(
                '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />', $options['field_id'], $options['id_attr'], stripslashes($value), $options['size'], $options['required'], $options['validation'], $options['css'], $type, !is_admin() && $options['readonly'] ? 'disabled' : ''
        );
        if ($form_type == SwpmFbForm::PROFILE) {
            switch ($key) {
                case 'user_name':
                    $form_item = '<b>' . $value . '</b>';
                    break;
            }
        }
        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function membership_level($level, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = BPermission::get_instance($level)->get('alias');
        $id = BPermission::get_instance($level)->get('id');
        $type = 'hidden';
        $output_format = '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />';
        $form_item = sprintf($output_format, $options['field_id'], $options['id_attr'], $id, $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : '');
        $form_item .= sprintf('<div> %1$s</div>', $value);
        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function select($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $field_options = maybe_unserialize($this->options);
        $options = '';
        $value = empty($value) ? $default : $value;
        // Loop through each option and output
        foreach ($field_options as $option => $tvalue) {
            $label = esc_attr(trim(stripslashes($tvalue)));
            $thisvalue = strtolower($label);
            $options .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $thisvalue, selected($thisvalue, $value, 0), $label);
        }

        $form_item = sprintf('<select name="swpm-%1$d" id="%2$s" class="swpm-select %3$s %4$s %5$s">%6$s</select>', $field_id, $id_attr, $size, $required, $css, $options);

        return (!empty($description) ) ? sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $description) : $form_item;
    }

    private function radio($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $field_options = maybe_unserialize($this->options);

        $options = '';
        $value = empty($value) ? $default : $value;
        // Loop through each option and output
        foreach ($field_options as $option => $tvalue) {
            $option_format = '<div class="swpm-span"><input type="radio" name="swpm-%1$d" id="%2$s-%3$d" value="%6$s" class="swpm-radio %4$s %5$s"%8$s /><label for="%2$s-%3$d" class="swpm-choice">%7$s</label></div>';
            $options .= sprintf($option_format, $field_id, $id_attr, $option, $required, $css, esc_attr(trim(stripslashes($tvalue))), wp_specialchars_decode(stripslashes($tvalue)), checked($tvalue, $value, 0));
        }

        $form_item = $options;

        $output = '<div>';

        $output .= (!empty($description) ) ? sprintf('%1$s<span><p>%2$s</p></span>', $form_item, $description) : $form_item;

        $output .= '<div style="clear:both"></div></div>';
        return $output;
    }

    private function fieldset($form_type = 0, $label_align = "") {

    }

    private function section($form_type = 0, $label_align = "") {

    }

    private function secret($form_type = 0, $label_align = "") {

    }

    private function submit($form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());

        $submit_button_html = sprintf(
                '<p class="swpm-item swpm-item-submit swpm-edit-profile-submit-section" id="item-%2$s">
                <input type="submit" name="swpm-fb-submit" id="%2$s" value="%3$s" class="swpm-submit %4$s" />
                </p>', $field_id, $id_attr, wp_specialchars_decode(esc_html($field_name), ENT_QUOTES), $css
        );

        $delete_account_html = (($form_type == SwpmFbFormCustom::PROFILE) ? SwpmUtils::delete_account_button() : "");

        $output = $submit_button_html . $delete_account_html;
        return $output;
    }

    private function verification($value, $form_type = 0) {
        $verification = '';
        if ($form_type == SwpmFbFormCustom::PROFILE) {
            return $verification;
        }

        $captcha = apply_filters('swpm_before_registration_submit_button', '');
        //The verification field legend is passed to this function via the value parameter.
        //If value is -1, then user removed verification fields from the form, but we still need to see if we have reCaptcha addon enabled
        $verification_field_legend = ($value == "-1" ? "Verification" : $value);
        if (!empty($captcha)) {
            if ($value == "-1")
                $value == "Verification";
            $verification = sprintf(
                    '<fieldset class="swpm-fieldset swpm-verification" style="display:block">
                <div class="swpm-legend"><h3>%1$s</h3></div>
                <ul class="swpm-section swpm-section-%2$d">
                <li class="swpm-item swpm-item-text" style="display:block">
                <div align="center"> ' . $captcha . '</div>
                </li>', $verification_field_legend, $this->sequence
            );
            //$verification = '<div align="center">' . $captcha . '</div>';
        }
        // Only display verification if there verification field in the form
        else if ($value != "-1") {

            $verification = sprintf(
                    '<fieldset class="swpm-fieldset swpm-verification" style="display:block">
          <div class="swpm-legend"><h3>%1$s</h3></div>
          <ul class="swpm-section swpm-section-%2$d">
          <li class="swpm-item swpm-item-text" style="display:block">
          <label for="swpm-secret" class="swpm-desc">%3$s<span>*</span></label>
          <div><input type="text" name="swpm-secret" id="swpm-secret" class="swpm-text swpm-medium" style="display:block" /></div>
          </li>', $verification_field_legend, $this->sequence, __('Please enter any two digits with <strong>no</strong> spaces (Example: 12)', 'simple-membership')
            );
        }

        return $verification;
    }

    private function email($value = null, $form_type = 0) {
        $options = $this->get_sanitized_options();
        $value = empty($value) ? $options['default'] : $value;
        // HTML5 types
        $type = esc_attr($this->type);
        $form_item_format = '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />';
        $form_item = sprintf($form_item_format, $options['field_id'], $options['id_attr'], stripslashes($value), $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : '');
        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function url($value = null, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = empty($value) ? $options['default'] : $value;
        // HTML5 types
        $type = esc_attr($this->type);
        $form_item_format = '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />';
        $form_item = sprintf($form_item_format, $options['field_id'], $options['id_attr'], $value, $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : '');

        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function currency($value = null, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = (!isset($value) || $value==='') ? $options['default'] : $value;
        // HTML5 types
        $type = 'text';
        $form_item_format = '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />';
        $form_item = sprintf($form_item_format, $options['field_id'], $options['id_attr'], $value, $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : '');

        return (!empty($options['description']) ) ? sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function number($value = null, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = (!isset($value) || $value==='') ? $options['default'] : $value;
        // HTML5 types
        $type = 'text';
        $form_item = sprintf(
                '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />', $options['field_id'], $options['id_attr'], $value, $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : ''
        );

        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function phone($value = null, $form_type = 0, $label_align = "") {
        $options = $this->get_sanitized_options();
        $value = empty($value) ? $options['default'] : $value;
        // HTML5 types
        $type = 'tel';
        $form_item = sprintf(
                '<input type="%8$s" %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s"  />', $options['field_id'], $options['id_attr'], $value, $options['size'], $options['required'], $options['validation'], $options['css'], $type, $options['readonly'] ? 'disabled' : ''
        );

        return (!empty($options['description']) ) ?
                sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description']) : $form_item;
    }

    private function password($form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $required = $form_type == SwpmFbForm::PROFILE ? '' : $required;
	//check if we need to force strong password
	$settings=SwpmSettings::get_instance();
	$force_strong_pass=$settings->get_value('force-strong-passwords');
	$prev_validation=$validation;
	if (!empty($force_strong_pass)) {
	    if ($form_type == SwpmFbForm::PROFILE) {
		// profile edit form. These validation rules allow empty password
		$validation.="strongPass-edit passmin8-edit";
	    } else {
		$validation.="strongPass passmin8";
	    }
	}
        $form_item = sprintf(
                '<div><input type="password" placeholder="' . __('Type password here', 'simple-membership') . '" name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s" /></div>', $field_id, $id_attr, $default, $size, $required, $validation, $css
        );
	$validation=$prev_validation;
        $form_item .= sprintf(
                '<div><input type="password" placeholder="' . __('Retype password here', 'simple-membership') . '" name="swpm-%1$d_re" id="%2$s_re" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s" /></div>', $field_id, $id_attr, $default, $size, $required, $validation, $css
        );

        return (!empty($description) ) ?
                sprintf('<div class="swpm-span">%1$s<p>%2$s</p></div>', $form_item, $description) : $form_item;
    }

    private function textarea($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $value = empty($value) ? $default : $value;
        $form_item = sprintf(
                '<textarea name="swpm-%1$d" id="%2$s" class="swpm-textarea %4$s %5$s %6$s"%7$s>%3$s</textarea>', $field_id, $id_attr, $value, $size, $required, $css, !is_admin() && $readonly ? ' disabled' : ''
        );

        $output = '<div>';

        $output .= (!empty($description) ) ?
                sprintf('<span class="swpm-span"><p>%2$s</p></span>%1$s', $form_item, $description) : $form_item;

        $output .= '</div>';
        return $output;
    }

    private function checkbox($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $field_options = maybe_unserialize($this->options);

        $options = '';
        $saved_value = maybe_unserialize(empty($value) ? $default : $value);
        // Loop through each option and output
        $values = array_values((array) $saved_value);
        foreach ($field_options as $option => $value) {
            $options .= sprintf(
                    '<div class="swpm-span"><input type="hidden" name="swpm-%1$d[%3$d]" value="0"%10$s><input type="checkbox" name="swpm-%1$d[%3$d]" id="%2$s-%3$d" value="1" class="swpm-checkbox %4$s"%7$s%10$s /><label for="%2$s-%3$d" class="swpm-choice">%6$s</label></div>', $field_id, $id_attr, $option, $css, esc_attr(trim(stripslashes($option))), wp_specialchars_decode(stripslashes($value)), empty($values[$option]) ? '' : 'checked', $option, 0, !is_admin() && $form_type!=0 && $readonly ? ' disabled' : '');

       }

        $form_item = $options;

        $output = sprintf('<div class="%1$s">', empty($required) ? "" : "swpm-checkbox-required");

        $output .= (!empty($description) ) ? sprintf('%1$s<span><p>%2$s</p></span>', $form_item, $description) : $form_item;

        $output .= '<div style="clear:both"></div></div>';
        return $output;
    }

    private function address($value = array(), $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $address = '';
        $swpm_settings = get_option('swpm-settings');

        // Settings - Place Address labels above fields
        $settings_address_labels = isset($swpm_settings['address-labels']) ? false : true;
        $address_parts = array(
            'address' => array(
                'label' => __('Street Address', 'simple-membership'),
                'layout' => 'full',
                'value' => isset($value['address']) ? $value['address'] : ''
            ),
            'address-2' => array(
                'label' => __('Apt, Suite, Bldg. (optional)', 'simple-membership'),
                'layout' => 'full',
                'value' => isset($value['address-2']) ? $value['address-2'] : ''
            ),
            'city' => array(
                'label' => __('City', 'simple-membership'),
                'layout' => 'left',
                'value' => isset($value['city']) ? $value['city'] : ''
            ),
            'state' => array(
                'label' => __('State / Province / Region', 'simple-membership'),
                'layout' => 'right',
                'value' => isset($value['state']) ? $value['state'] : ''
            ),
            'zip' => array(
                'label' => __('Postal / Zip Code', 'simple-membership'),
                'layout' => 'left',
                'value' => isset($value['zip']) ? $value['zip'] : ''
            ),
            'country' => array(
                'label' => __('Country', 'simple-membership'),
                'layout' => 'right',
                'value' => isset($value['country']) ? $value['country'] : $default
            )
        );

        $address_parts = apply_filters('swpm_address_labels', $address_parts, $this->form_id);
        $label_placement = apply_filters('swpm_address_labels_placement', $settings_address_labels, $this->form_id);
        $placement_bottom = ( $label_placement ) ? '<span class="swpm-form-builder-address-label-bottom"><label for="%2$s-%4$s">%5$s</label></span>' : '';
        $placement_top = (!$label_placement ) ? '<span class="swpm-form-builder-address-label-top"><label for="%2$s-%4$s">%5$s</label></span>' : '';

        foreach ($address_parts as $parts => $part) {
            // Make sure the second address line is not required
            $addr_required = in_array($parts, array('address', 'city', 'country')) ? $required : '';
            if ('country' == $parts) {
                $options = '';
                foreach (SwpmFbUtils::$countries as $country) {
                    $options .= sprintf('<option value="%1$s"%2$s>%1$s</option>', $country, selected($part['value'], $country, 0));
                }
                $address_format = '<span class="swpm-%3$s">' . $placement_top . '<select name="swpm-%1$d[%4$s]" class="swpm-select %7$s %8$s" id="%2$s-%4$s">%6$s</select>' . $placement_bottom . '</span>';
                $address .= sprintf($address_format, $field_id, $id_attr, esc_attr($part['layout']), esc_attr($parts), esc_html($part['label']), $options, $addr_required, $css);
            } else {
                $address_format = '<span class="swpm-%3$s">' . $placement_top . '<input type="text" value="' . $part['value'] . '" name="swpm-%1$d[%4$s]" id="%2$s-%4$s" maxlength="150" class="swpm-text swpm-medium %7$s %8$s" />' . $placement_bottom . '</span>';
                $address .= sprintf($address_format, $field_id, $id_attr, esc_attr($part['layout']), esc_attr($parts), esc_html($part['label']), $size, $addr_required, $css);
            }
        }

        $output = '<div>';
        $output .= !empty($description) ? "<span class='swpm-span'><p>$description</p></span>$address" : $address;
        $output .= '</div>';
        return $output;
    }

    private function date($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        // Load jQuery UI datepicker library
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('swpm-datepicker-i18n');

        $options = maybe_unserialize($this->options);
        $dateFormat = ( $options ) ? $options['dateFormat'] : '';
        $value = empty($value) ? $default : $value;
        $form_item_format = '<input type="text" name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text swpm-date-picker %4$s %5$s %6$s" %8$s data-dp-dateFormat="%7$s" />';
        $form_item = sprintf($form_item_format, $field_id, $id_attr, $value, $size, $required, $css, $dateFormat, (!is_admin() && !empty($readonly)) ? 'disabled' : '');
        $output = (!empty($description) ) ? sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $description) : $form_item;
        return $output;
    }

    private function time($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $hour = $minute = $ampm = '';

        $saved_value = maybe_unserialize(empty($value) ? $default : $value);
        $hour_value = isset($saved_value['hour'])? $saved_value['hour'] : 0;
        $minute_value = isset($saved_value['min'])? $saved_value['min'] : 0;
        $ampm_value = isset($saved_value['ampm'])? $saved_value['ampm'] : 'AM';

        // Get the time format (12 or 24)
        $time_format = str_replace('time-', '', $validation);

        $time_format = apply_filters('swpm_time_format', $time_format, $this->form_id);
        $total_mins = apply_filters('swpm_time_min_total', 55, $this->form_id);
        $min_interval = apply_filters('swpm_time_min_interval', 5, $this->form_id);

        // Set whether we start with 0 or 1 and how many total hours
        $hour_start = ( $time_format == '12' ) ? 1 : 0;
        $hour_total = ( $time_format == '12' ) ? 12 : 23;

        // Hour
        for ($i = $hour_start; $i <= $hour_total; $i++) {
            if ($i == $hour_value){
                $hour .= sprintf('<option value="%1$02d" selected>%1$02d</option>', $i);
            } else {
                $hour .= sprintf('<option value="%1$02d">%1$02d</option>', $i);
            }
        }

        // Minute
        for ($i = 0; $i <= $total_mins; $i += $min_interval) {
            if ($i == $minute_value) {
                $minute .= sprintf('<option value="%1$02d" selected>%1$02d</option>', $i);
            } else {
                $minute .= sprintf('<option value="%1$02d">%1$02d</option>', $i);
            }
        }

        // AM/PM
        $ampm_format = '<span class="swpm-time"><select name="swpm-%1$d[ampm]" id="%2$s-ampm" class="swpm-select %5$s %6$s"><option value="AM" '. selected($ampm_value, 'AM', false) . '>AM</option><option value="PM" '. selected($ampm_value, 'PM', false) . '>PM</option></select><label for="%2$s-ampm">AM/PM</label></span>';
        if ($time_format == '12') {
            $ampm = sprintf($ampm_format, $field_id, $id_attr, $hour, $minute, $required, $css);
        }

        $form_item = sprintf(
                '<span class="swpm-time"><select name="swpm-%1$d[hour]" id="%2$s-hour" class="swpm-select %5$s %6$s">%3$s</select><label for="%2$s-hour">HH</label></span>' .
                '<span class="swpm-time"><select name="swpm-%1$d[min]" id="%2$s-min" class="swpm-select %5$s %6$s">%4$s</select><label for="%2$s-min">MM</label></span>' .
                '%7$s', $field_id, $id_attr, $hour, $minute, $required, $css, $ampm
        );

        $output = (!empty($description) ) ? sprintf('<span class="swpm-span"><p>%2$s</p></span>%1$s', $form_item, $description) : $form_item;

        $output .= '<div class="clear"></div>';
        return $output;
    }

    private function html($value = null, $form_type = 0, $label_align = "") {
        //Retrieve the various configuration options of this field.
        extract($this->get_sanitized_options());//This will output all the field returned array values into separate variables.
        //$options = $this->get_sanitized_options();

        //Check if read-only is enabled on this HTML type field.
        if ( !is_admin() && !empty($readonly) ){
            //Read-only HTML field. So only show the rendered HTML output in the edit profile section.
            $value = empty($value) ? $default : $value;
            $value_rendered = html_entity_decode($value, ENT_COMPAT, "UTF-8");
            $value_rendered = do_shortcode($value_rendered);
            $form_item = '<div id="'.$id_attr.'" class="swpm-html-readonly '.$size.' '.$css.'">'.$value_rendered.'</div>';
        }
        else{
            //Normal HTML type field. Show the full field witht the HTML editor.
            //
            //Load CKEditor library
            wp_enqueue_script('swpm-ckeditor');
            $value = empty($value) ? $default : $value;
            $form_item = sprintf(
                    '<textarea name="swpm-%1$d" id="%2$s" class="swpm-textarea ckeditor %4$s %5$s %6$s">%3$s</textarea>', $field_id, $id_attr, $value, $size, $required, $css
            );
        }

        $output = '<div>';

        $output .= (!empty($description) ) ? sprintf('<span class="swpm-span"><p>%2$s</p></span>%1$s', $form_item, $description) : $form_item;

        $output .= '</div>';
        return $output;
    }

    private function file_upload($value = null, $form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        $options = maybe_unserialize($this->options);
        $accept = (!empty($options[0]) ) ? " {accept:'$options[0]'}" : '';
        $form_item = sprintf(
                '<div><input type="file" name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-text %4$s %5$s %6$s %7$s %8$s" /></div>', $field_id, $id_attr, $default, $size, (($form_type == SwpmFbForm::PROFILE) && !empty($value) ) ? "" : $required, $validation, $css, $accept
        );

        $section = (!empty($description) ) ? sprintf('<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $description) : $form_item;
        if ($this->key == 'profile_image') {
            $url = wp_get_attachment_url($value);
            $profile_image = empty($url) ? get_avatar("", 128, "mm") : sprintf('<img src="%1$s" style="max-height:128px;" />', $url);
            $section = '<div>' . $profile_image . '</div>' . $section;
            if (is_admin()) {
                $section .= '<p>';
                $section .= '<label><input type="checkbox" name="swpm-delete-' . $field_id . '"> ' . __("Delete Image", "swpm") . '</label>';
                $section .= '<p class="description indicator-hint">' . __("Check this box to delete the image. The image will be deleted when you save the profile.", "swpm") . '</p>';
                $section .= '</p>';
            }
        } else {
	    $has_files = false;
	    //check if user has uploaded files
	    if ( ! is_admin() && SwpmMemberUtils::is_member_logged_in() ) {
		$swpm_id	 = SwpmMemberUtils::get_logged_in_members_id();
		$swpm_user_row	 = SwpmMemberUtils::get_user_by_id( $swpm_id );
		$username	 = $swpm_user_row->user_name;
		$wp_user	 = get_user_by( 'login', $username );
		$wp_user_id	 = $wp_user->ID;

		$upl_files_list = '';

		$args = array(
		    'post_type'	 => 'attachment',
		    'author__in'	 => array( $wp_user->ID ),
		    'posts_per_page' => -1,
		    'post_status'	 => 'any' );

		$atts = get_posts( $args );

		foreach ( $atts as $att ) {
		    $url		 = wp_get_attachment_url( $att->ID );
		    $upl_files_list	 .= sprintf( '<div class="swpm-fb-uploaded-file-delete"><input type="checkbox" name="swpm-fb-delete[%d]" class="swpm-checkbox"><label class="swpm-choice"><a href="%s" target="_blank">%s</a></div></label>', $att->ID, $url, esc_attr( basename( $url ) ) );
		}

		if ( ! empty( $upl_files_list ) ) {
		    $has_files	 = true;
		    $section	 .= '<div class="swpm-fb-uploaded-files">' .
		    '<div class="swpm-fb-uploaded-file-list-helptext">' . __( 'Following is a list of file(s) you uploaded. Mark the file(s) you want to delete and save the profile to delete it.', 'simple-membership' ) . '</div>' .
		    $upl_files_list
		    . '</div>';
		}
	    }
	    // let's see if this is admin area and if user has uploaded files
	    if ( is_admin() ) {
		//Let's get user WP user ID
		$swpm_id = isset( $_REQUEST[ 'member_id' ] )? sanitize_text_field( $_REQUEST[ 'member_id' ] ) : '';

                if ( empty ($swpm_id) ){
                    //This is not a valid member edit page/URL. Nothing to do.
                    return '';
                }

		$swpm_user_row	 = SwpmMemberUtils::get_user_by_id( $swpm_id );
		$username	 = $swpm_user_row->user_name;
		$wp_user	 = get_user_by( 'login', $username );
		$wp_user_id	 = $wp_user->ID;

		if ( ! empty( $wp_user_id ) ) {
		    //let's see if user has any attachments
		    $args = array(
			'post_type'	 => 'attachment',
			'author__in'	 => array( $wp_user_id ),
			'posts_per_page' => -1,
			'post_status'	 => 'any' );

		    $atts = get_posts( $args );

		    if ( ! empty( $atts ) ) {
			// There are attachments owned by the user, let's list them
			$has_files	 = true;
			$section	 .= '<div class="swpm-grey-box">';
			$section	 .= '<p><a target="_blank" href="' . get_admin_url() . 'upload.php?author=' . $wp_user_id . '">';
			$section	 .= __( "Click to view the uploaded files from this user in media library", "swpm" );
			$section	 .= '</a></p>';
			$section	 .= '</div>';
		    }
		}
	    }

	    if ( ! $has_files ) {
		//no files uploaded
		$section .= '<p>' . __( "No file uploaded.", "swpm" ) . '</p>';
	    }
	}
	return '<div>' . $section . '</div>';
    }

    private function instructions($form_type = 0, $label_align = "") {
        extract($this->get_sanitized_options());
        return wp_specialchars_decode(esc_html(stripslashes($description)), ENT_QUOTES);
	}

private function country( $value = null, $form_type = 0, $label_align = '' ) {
	$options   = $this->get_sanitized_options();
	$value     = empty( $value ) ? $options['default'] : $value;
	$form_item = sprintf(
		'<select %9$s name="swpm-%1$d" id="%2$s" value="%3$s" class="swpm-select %4$s %5$s %6$s %7$s">%10$s</select>',
		$options['field_id'],
		$options['id_attr'],
		stripslashes( $value ),
		$options['size'],
		$options['required'],
		$options['validation'],
		$options['css'],
		'',
		! is_admin() && $options['readonly'] ? 'disabled' : '',
		SwpmMiscUtils::get_countries_dropdown( $value )
	);
	return ( ! empty( $options['description'] ) ) ?
	sprintf( '<span class="swpm-span">%1$s<p>%2$s</p></span>', $form_item, $options['description'] ) : $form_item;
}


    private function member_id($value = null, $form_type = 0, $label_align = ""){
        //This field is used to show the member ID in the profile edit form.
        //The return value of this function is shown in the front-end.
        //extract($this->get_sanitized_options());
        if ($form_type == 1) {
            $member_id = SwpmMemberUtils::get_logged_in_members_id();
        } else {
           $member_id = 'Member ID is only available in the edit profile form';
        }
        return $member_id;
    }

    public function get_sanitized_options() {
        $css = is_admin() ? ((in_array($this->type, array('text', 'password', 'email'))) ? 'regular-text' : '') : $this->css;
        $description = $this->description;
        return array(
            'field_id' => absint($this->id),
            'field_type' => esc_html($this->type),
            'field_name' => esc_html(stripslashes($this->name)),
            'required_span' => (!empty($this->required) && $this->required === 'yes' ) ? ' <span class="swpm-required-asterisk">*</span>' : '',
            'required' => (!empty($this->required) && $this->required === 'yes' ) ? esc_attr(' required') : '',
            'validation' => (!empty($this->validation) ) ? esc_attr(" $this->validation") : '',
            'css' => (!empty($css) ) ? esc_attr(" $css") : '',
            'id_attr' => "swpm-" . absint($this->id),
            'size' => (!empty($this->size) ) ? esc_attr(" swpm-$this->size") : '',
            'layout' => (!empty($this->layout) ) ? esc_attr(" swpm-$this->layout") : '',
            'default' => (!empty($this->default) ) ? wp_specialchars_decode(esc_html(stripslashes($this->default)), ENT_QUOTES) : '',
            'description' => (!empty($description) ) ? wp_specialchars_decode(esc_html(stripslashes($description)), ENT_QUOTES) : '',
            'readonly' => ( $this->readonly == 1) ? 1 : 0,
            'adminonly' => ( $this->adminonly == 1) ? 1 : 0,
        );
    }

}
