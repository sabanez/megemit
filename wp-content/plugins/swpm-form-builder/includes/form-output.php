<?php

// Add JavaScript files to the front-end, only once
if (!$this->add_scripts)
    $this->scripts();

$form = $this->form;
if ($form->is_fatal()) {
    $fatal = $form->fatal();
    $output .= '<div class="swpm-red-box">';
    foreach ($fatal as $each) {
        $output .= '<p>' . $each . '</p';
    }
    $output .= '</div>';
}
if (!$form->is_valid()) {

    $error = $form->error();

    if(!empty($error)){
        $output .= '<div class="swpm-warning swpm-yellow-box">';
        foreach ($error as $each) {
            $output .= '<p>' . $each . '</p>';
        }
        $output .= '</div>';
    }
}

// Get Fields
/*
$order_fields = sanitize_sql_orderby( 'field_sequence ASC' );
$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d ORDER BY $order_fields", $form_id ) );
*/

// Setup default variables
$count = 1;
$open_fieldset = $open_section = false;
$submit = 'Submit';
$verification = '';
$form_id = $form->formmeta->id;
$label_alignment = ( $form->formmeta->label_alignment !== '' ) ? esc_attr($form->formmeta->label_alignment) : '';

// Start form container
$output .= sprintf('<div id="swpm-form-%d" class="swpm-form-builder-container">', $form_id);

$output .= sprintf(
        '<form id="%1$s-%2$d" class="swpm-form-builder %3$s %4$s" method="post" enctype="multipart/form-data">
	<input type="hidden" name="form_id" value="%5$d" />', esc_attr($form->formmeta->key), $form_id, "swpm-form-$form_id", $label_alignment, absint($form->formmeta->id)
);

$membership_level = $form->get_level_info('id');
$level_identifier = md5($form->get_level_info('id'));
//Create the level has (for input verification)
$swpm_p_key = get_option('swpm_private_key_one');
$swpm_level_hash = md5($swpm_p_key . '|' . $membership_level);

$output .= sprintf('<input type ="hidden" name="level_identifier" value="%s" />', $level_identifier);
$output .= sprintf('<input type ="hidden" name="swpm_level_hash" value="%s" />', $swpm_level_hash);
$output .= sprintf('<input type ="hidden" name="membership_level" value="%s" />', $membership_level);

$has_verification=false;
foreach ($form->formmeta->fields as $field) :
    if (!SwpmUtils::is_admin() && $field->adminonly) {
        continue;
    } //
    extract($field->get_sanitized_options());
    // Close each section
    if ($open_section == true) :
        // If this field's parent does NOT equal our section ID
        if ($sec_id && $sec_id !== absint($field->parent)) :
            $output .= '</div><div class="swpm-clear"></div>';
            $open_section = false;
        endif;
    endif;

// Force an initial fieldset and display an error message to strongly encourage user to add one
    if ($count === 1 && $field_type !== 'fieldset') :
        $output .= sprintf('<fieldset class="swpm-fieldset"><div class="swpm-legend" style="background-color:#FFEBE8;border:1px solid #CC0000;"><h3>%1$s</h3><p style="color:black;">%2$s</p></div><ul class="section section-%3$d">', __('Oops! Missing Fieldset', 'swpm-form-builder'), __('If you are seeing this message, it means you need to <strong>add a Fieldset to the beginning of your form</strong>. Your form may not function or display properly without one.', 'swpm-form-builder'), $count
        );

        $count++;
    endif;

    if ($field_type == 'fieldset') :
        // Close each fieldset
        if ($open_fieldset == true)
            $output .= '</ul>&nbsp;</fieldset>';

        // Only display Legend if field name is not blank
        $legend = !empty($field_name) ? sprintf('<div class="swpm-legend"><h3>%s</h3></div>', $field_name) : '&nbsp;';

        $output .= sprintf(
                '<fieldset class="swpm-fieldset swpm-fieldset-%1$d %2$s %3$s" id="item-%4$s">%5$s<ul class="swpm-section swpm-section-%1$d">', $count, esc_attr($field->key), $css, $id_attr, $legend
        );

        $open_fieldset = true;
        $count++;

    elseif ($field_type == 'section') :

        $output .= sprintf(
                '<div id="item-%1$s" class="swpm-section-div %2$s"><h4>%3$s</h4>', $id_attr, $css, $field_name
        );

        // Save section ID for future comparison
        $sec_id = $field_id;
        $open_section = true;

    elseif (!in_array($field_type, array('verification', 'secret', 'submit'))) :

        $columns_choice = (!empty($field->size) && in_array($field_type, array('radio', 'checkbox')) ) ? esc_attr(" swpm-$field->size") : '';

        if ($field_type !== 'hidden') :

            // Don't add for attribute for certain form items
            $for = !in_array($field_type, array('checkbox', 'radio', 'time', 'address', 'instructions')) ? ' for="%4$s"' : '';

            $output .= sprintf(
                    '<li class="swpm-item swpm-item-%1$s %2$s %3$s" id="item-%4$s"><label' . $for . ' class="swpm-desc">%5$s %6$s</label>', $field_type, $columns_choice, $layout, $id_attr, $field_name, $required_span
            );
        endif;

    elseif (in_array($field_type, array('verification', 'secret'))) :

        if ($field_type == 'verification') {
            $has_verification=true;
            $verification_field_legend = $field_name;//Pass the verification field legend via the value parameter
            $verification = $field->toHTML($verification_field_legend, $form->formmeta->type, $form->formmeta->label_alignment);
        }

    endif;
    $value = $form->get_field_value($field);
    switch ($field_type) {
        case 'text' :
        case 'email' :
        case 'url' :
        case 'currency' :
        case 'number' :
        case 'phone' :
        case 'password':
        case 'textarea' :
        case 'select' :
        case 'radio' :
        case 'checkbox' :
        case 'address' :
        case 'date' :
        case 'time' :
        case 'html' :
        case 'file-upload' :
        case 'instructions' :
        case 'country' :
        case 'member_id' :
            $output .= $field->toHTML($value, $form->formmeta->type, $form->formmeta->label_alignment);
            break;
        case 'submit' :
            if (!$has_verification && $form->formmeta->type==0) {
                $field->type="verification";
                $verification=$field->toHTML('-1', $form->formmeta->type, $form->formmeta->label_alignment);
                $field->type="submit";
            }

            $submit = $field->toHTML($value, $form->formmeta->type, $form->formmeta->label_alignment);
            break;
        default:
            echo '';
    }

    // Closing </li>
    $output .= (!in_array($field_type, array('verification', 'secret', 'submit', 'fieldset', 'section')) ) ? '</li>' : '';
endforeach;


// Close user-added fields
$output .= '</ul>&nbsp;</fieldset>';

// Output our security test
$output .= sprintf(
        $verification .
        '<li style="display:none;"><label>%1$s:</label><div><input name="swpm-spam" /></div></li>
    %2$s</ul>
    </fieldset>', __('This box is for spam protection - <strong>please leave it blank</strong>', 'swpm-form-builder'), $submit
);
$output .= wp_referer_field(false);

// Close the form out
$output .= '</form>';

// Close form container
$output .= '</div> <!-- .swpm-form-builder-container -->';

// Force tags to balance
force_balance_tags($output);

return $output;
