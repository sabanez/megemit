<?php
$order = sanitize_sql_orderby('form_id DESC');
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_nav_selected_id));

if (!$form || $form->form_id !== $form_nav_selected_id)
    wp_die('You must select a form');

$title = isset($form->form_title)? $form->form_title: "";
$subject = isset($form->form_email_subject)? $form->form_email_subject : "";
$from_name = isset($form->form_email_from_name)? $form->form_email_from_name :"";
$email_from = isset($form->form_email_from)? $form->form_email_from : "";
$email_from_override = isset($form->form_email_from_override)? $form->form_email_from_override : "";
$email_from_name_override = isset($form->form_email_from_name_override)? $form->form_email_from_name_override: "";
$email_to = isset($form->form_email_to)? $form->form_email_to : "";
$success_type = isset($form->form_success_type)? $form->form_success_type: "";
$success_message = isset($form->form_success_message)? $form->form_success_message : "";
$notification_setting = isset($form->form_notification_setting)? $form->form_notification_setting: "";
$notification_email_name = isset($form->form_notification_email_name)? $form->form_notification_email_name : "";
$notification_email_from = isset($form->form_notification_email_from)? $form->form_notification_email_from: "";
$notification_email = isset($form->form_notification_email)? $form->form_notification_email : "";
$notification_subject = isset($form->form_notification_subject)? $form->form_notification_subject : "";
$notification_message = isset($form->form_notification_message)? $form->form_notification_message : "";
$notification_entry = isset($form->form_notification_entry)? $form->form_notification_entry : "";
$label_alignment = isset($form->form_label_alignment)? $form->form_label_alignment : "";
$form_id = $form->form_id;
$form_title = stripslashes($title);
$form_subject = stripslashes($subject);
$form_email_from_name = stripslashes($from_name);
$form_email_from = stripslashes($email_from);
$form_email_from_override = stripslashes($email_from_override);
$form_email_from_name_override = stripslashes($email_from_name_override);
$form_email_to = is_serialized($email_to)  ? unserialize($email_to) : explode(',', $email_to);
$form_success_type = stripslashes(empty($success_type) ? 'text' : $success_type );
$form_success_message = stripslashes($success_message);
$form_notification_setting = stripslashes($notification_setting);
$form_notification_email_name = stripslashes($notification_email_name);
$form_notification_email_from = stripslashes($notification_email_from);
$form_notification_email = stripslashes($notification_email);
$form_notification_subject = stripslashes($notification_subject);
$form_notification_message = stripslashes($notification_message);
$form_notification_entry = stripslashes($notification_entry);
$form_type = $form->form_type;
$form_label_alignment = stripslashes($label_alignment);

// Only show required text fields for the sender name override
$senders = $wpdb->get_results($wpdb->prepare("SELECT field_id, field_name FROM $this->field_table_name WHERE form_id = %d AND field_type IN( 'text', 'name' ) AND field_validation = '' AND field_required = 'yes'", $form_nav_selected_id));

// Only show required email fields for the email override
$emails = $wpdb->get_results($wpdb->prepare("SELECT field_id, field_name FROM $this->field_table_name WHERE (form_id = %d AND field_type='text' AND field_validation = 'email' AND field_required = 'yes') OR (form_id = %d AND field_type='email' AND field_validation = 'email' AND field_required = 'yes')", $form_nav_selected_id, $form_nav_selected_id));

$screen = get_current_screen();
$class = 'columns-' . get_current_screen()->get_columns();

$page_main = $this->_admin_pages['swpm'];
?>
<div id="swpm-form-builder-frame" class="metabox-holder <?php echo $class; ?>">
    <div id="swpm-postbox-container-1" class='swpm-postbox-container'>
        <form id="form-items" class="nav-menu-meta" method="post" action="">
            <input name="action" type="hidden" value="create_field" />
            <input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
            <?php
            wp_nonce_field('create-field-' . $form_nav_selected_id);
            do_meta_boxes($page_main, 'side', null);
            ?>
        </form>
    </div> <!-- .swpm-postbox-container -->

    <div id="swpm-postbox-container-2" class='swpm-postbox-container'>
        <div id="swpm-form-builder-main">
            <div id="swpm-form-builder-management">
                <div class="form-edit">
                    <form method="post" id="swpm-form-builder-update" action="">
                        <input name="action" type="hidden" value="update_form" />
                        <input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
                        <?php wp_nonce_field('swpm_update_form'); ?>
                        <div id="form-editor-header">
                            <div id="submitpost" class="submitbox">
                                <div class="swpm-major-publishing-actions">
                                    <label for="form-name" class="menu-name-label howto open-label">
                                        <span class="sender-labels"><?php _e('Form Name', 'swpm-form-builder'); ?></span>
                                        <input type="text" value="<?php echo ( isset($form_title) ) ? $form_title : ''; ?>" placeholder="<?php _e('Enter form name here', 'swpm-form-builder'); ?>" class="menu-name regular-text menu-item-textbox required" id="form-name" name="form_title" />
                                    </label>
                                    <br class="clear" />

                                    <?php
                                    // Get the Form Setting drop down and accordion settings, if any
                                    $user_form_settings = get_user_meta($user_id, 'swpm-form-settings');

                                    // Setup defaults for the Form Setting tab and accordion
                                    $settings_tab = 'closed';
                                    $settings_accordion = 'general-settings';

                                    // Loop through the user_meta array
                                    foreach ($user_form_settings as $set) {
                                        // If form settings exist for this form, use them instead of the defaults
                                        if (isset($set[$form_id])) {
                                            $settings_tab = $set[$form_id]['form_setting_tab'];
                                            //$settings_accordion = $set[$form_id]['setting_accordion'];//Commenting out to prevent the 'confirmation' settings accordion to be opened when page loads.
                                        }
                                    }

                                    // If tab is opened, set current class
                                    $opened_tab = ( $settings_tab == 'opened' ) ? 'current' : '';
                                    ?>


                                    <div class="swpm-button-group">
                                        <a href="#form-settings" id="form-settings-button" class="swpm-button swpm-settings <?php echo $opened_tab; ?>">
                                            <?php _e('Settings', 'swpm-form-builder'); ?>
                                            <span class="swpm-interface-icon swpm-interface-settings"></span>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=swpm-form-builder&amp;action=delete_form&amp;form=' . $form_nav_selected_id), 'delete-form-' . $form_nav_selected_id)); ?>" class="swpm-button swpm-delete swpm-last menu-delete">
                                            <?php _e('Delete', 'swpm-form-builder'); ?>
                                            <span class="swpm-interface-icon swpm-interface-trash"></span>
                                        </a>

                                        <?php submit_button(__('Save', 'swpm-form-builder'), 'primary', 'save_form', false); ?>
                                    </div>

                                    <div id="form-settings" class="<?php echo $opened_tab; ?>">
                                        <!-- General settings section -->
                                            <!--<a href="#general-settings" class="settings-links<?php echo ( $settings_accordion == 'general-settings' ) ? ' on' : ''; ?>"><?php _e('General', 'swpm-form-builder'); ?><span class="swpm-large-arrow"></span></a>
                                        <div id="general-settings" class="form-details<?php echo ( $settings_accordion == 'general-settings' ) ? ' on' : ''; ?>">
                                            <p class="description description-wide">
                                            <label for="form-label-alignment">
                                        <?php _e('Label Alignment', 'swpm-form-builder'); ?>
                                                <span class="swpm-tooltip" title="<?php esc_attr_e('About Label Alignment', 'swpm-form-builder'); ?>" rel="<?php esc_attr_e('Set the field labels for this form to be aligned either on top, to the left, or to the right.  By default, all labels are aligned on top of the inputs.'); ?>">(?)</span>
                                                                <br />
                                             </label>
                                                <select name="form_label_alignment" id="form-label-alignment" class="widefat">
                                                    <option value="" <?php selected($form_label_alignment, ''); ?>><?php _e('Top Aligned', 'swpm-form-builder'); ?></option>
                                                    <option value="left-label" <?php selected($form_label_alignment, 'left-label'); ?>><?php _e('Left Aligned', 'swpm-form-builder'); ?></option>
                                                    <option value="right-label" <?php selected($form_label_alignment, 'right-label'); ?>><?php _e('Right Aligned', 'swpm-form-builder'); ?></option>
                                                </select>
                                            </p>
                                            <br class="clear" />
                                        </div> --> <!-- #general-settings -->
                                        <!-- Confirmation section -->
                                        <a href="#confirmation" class="settings-links<?php echo ( $settings_accordion == 'confirmation' ) ? ' on' : ''; ?>"><?php _e('Confirmation', 'swpm-form-builder'); ?><span class="swpm-large-arrow"></span></a>
                                        <div id="confirmation-message" class="form-details<?php echo ( $settings_accordion == 'confirmation' ) ? ' on' : ''; ?>">
                                            <p><em><?php _e("After someone submits a form, you can control what is displayed. By default, it's a message but you can send them to another WordPress Page or a custom URL.", 'swpm-form-builder'); ?></em></p>
                                            <label for="form-success-type-text" class="menu-name-label open-label">
                                                <input type="radio" value="text" id="form-success-type-text" class="form-success-type" name="form_success_type" <?php checked($form_success_type, 'text'); ?> />
                                                <span><?php _e('Text', 'swpm-form-builder'); ?></span>
                                            </label>
                                            <label for="form-success-type-page" class="menu-name-label open-label">
                                                <input type="radio" value="page" id="form-success-type-page" class="form-success-type" name="form_success_type" <?php checked($form_success_type, 'page'); ?>/>
                                                <span><?php _e('Page', 'swpm-form-builder'); ?></span>
                                            </label>
                                            <label for="form-success-type-redirect" class="menu-name-label open-label">
                                                <input type="radio" value="redirect" id="form-success-type-redirect" class="form-success-type" name="form_success_type" <?php checked($form_success_type, 'redirect'); ?>/>
                                                <span><?php _e('Redirect', 'swpm-form-builder'); ?></span>
                                            </label>
                                            <br class="clear" />
                                            <p class="description description-wide">
                                                <?php
                                                $default_text = '';

                                                /* If there's no text message, make sure there is something displayed by setting a default */
                                                if ($form_success_message === '') {
                                                    $msg = ($form_type == SwpmFbForm::REGISTRATION) ? BUtils::_("Registration is complete. You can now log into the site.") : BUtils::_("Profile Updated.");
                                                    $default_text = sprintf('<p id="form_success">%s</p>', $msg);
                                                }
                                                ?>
                                                <textarea id="form-success-message-text" class="form-success-message<?php echo ( 'text' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_text"><?php echo $default_text; ?><?php echo ( 'text' == $form_success_type ) ? $form_success_message : ''; ?></textarea>

                                                <?php
                                                /* Display all Pages */
                                                wp_dropdown_pages(array(
                                                    'name' => 'form_success_message_page',
                                                    'id' => 'form-success-message-page',
                                                    'class' => 'widefat',
                                                    'show_option_none' => __('Select a Page', 'swpm-form-builder'),
                                                    'selected' => $form_success_message
                                                ));
                                                ?>
                                                <input type="text" value="<?php echo ( 'redirect' == $form_success_type ) ? $form_success_message : ''; ?>" id="form-success-message-redirect" class="form-success-message regular-text<?php echo ( 'redirect' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_redirect" placeholder="http://" />
                                            </p>
                                            <br class="clear" />

                                        </div>

                                        <!-- Notification section -->
                                        <a href="#notification" class="settings-links<?php echo ( $settings_accordion == 'notification' ) ? ' on' : ''; ?>"><?php _e('Notification', 'swpm-form-builder'); ?><span class="swpm-large-arrow"></span></a>
                                        <div id="notification" class="form-details<?php echo ( $settings_accordion == 'notification' ) ? ' on' : ''; ?>">
                                            <p><em><?php _e("When a user submits their entry, you can send a customizable notification email.", 'swpm-form-builder'); ?></em></p>
                                            <label for="form-notification-setting">
                                                <input type="checkbox" value="1" id="form-notification-setting" class="form-notification" name="form_notification_setting" <?php checked($form_notification_setting, '1'); ?> style="margin-top:-1px;margin-left:0;"/>
                                                <?php _e('Send Confirmation Email to User', 'swpm-form-builder'); ?>
                                            </label>
                                            <br class="clear" />
                                            <div id="notification-email">
                                                <p class="description description-wide">
                                                    <label for="form-notification-email-name">
                                                        <?php _e('From Email Address', 'swpm-form-builder'); ?>
                                                        <span class="swpm-tooltip" title="<?php esc_attr_e('Sender Email Address', 'swpm-form-builder'); ?>" rel="<?php esc_attr_e('Enter the sender email you would like to use for the email notification.', 'swpm-form-builder'); ?>">(?)</span>
                                                        <br />
                                                        <input type="text" value="<?php echo $form_notification_email_name; ?>" class="widefat" id="form-notification-email-name" name="form_notification_email_name" />
                                                    </label>
                                                </p>
                                                <br class="clear" />
                                                <p class="description description-wide">
                                                    <label for="form-notification-subject">
                                                        <?php _e('E-mail Subject', 'swpm-form-builder'); ?>
                                                        <span class="swpm-tooltip" title="<?php esc_attr_e('About E-mail Subject', 'swpm-form-builder'); ?>" rel="<?php esc_attr_e('This option sets the subject of the email that is sent to the emails you have set in the E-mail To field.', 'swpm-form-builder'); ?>">(?)</span>
                                                        <br />
                                                        <input type="text" value="<?php echo $form_notification_subject; ?>" class="widefat" id="form-notification-subject" name="form_notification_subject" />
                                                    </label>
                                                </p>
                                                <br class="clear" />
                                                <p class="description description-wide">
                                                    <label for="form-notification-message"><?php _e('Message', 'swpm-form-builder'); ?></label>
                                                    <span class="swpm-tooltip" title="<?php esc_attr_e('About Message', 'swpm-form-builder'); ?>" rel="<?php esc_attr_e('Insert a message to the user. This will be inserted into the beginning of the email body.', 'swpm-form-builder'); ?>">(?)</span>
                                                    <br />
                                                    <textarea id="form-notification-message" class="form-notification-message widefat" name="form_notification_message"><?php echo $form_notification_message; ?></textarea>
                                                </p>
                                                <br class="clear" />
                                                <!--<label for="form-notification-entry">
                                                <input type="checkbox" value="1" id="form-notification-entry" class="form-notification" name="form_notification_entry" <?php checked($form_notification_entry, '1'); ?> style="margin-top:-1px;margin-left:0;"/>
                                                <?php _e("Include a Copy of the User's Entry", 'swpm-form-builder'); ?>
                                            </label>-->
                                                <br class="clear" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="post-body">
                            <div id="post-body-content">
                                <div id="swpm-fieldset-first-warning" class="error"><?php printf('<p><strong>%1$s </strong><br>%2$s</p>', __('Warning &mdash; Missing Fieldset', 'swpm-form-builder'), __('Your form may not function or display correctly. Please be sure to add or move a Fieldset to the beginning of your form.', 'swpm-form-builder')); ?></div>
                                <!-- !Field Items output -->
                                <ul id="swpm-menu-to-edit" class="menu ui-sortable droppable">
                                    <?php echo $this->field_output($form_nav_selected_id); ?>
                                </ul>
                            </div>
                            <br class="clear" />
                        </div>
                        <br class="clear" />
                        <div id="form-editor-footer">
                            <div class="swpm-major-publishing-actions">
                                <div class="publishing-action">
                                    <?php submit_button(__('Save Form', 'swpm-form-builder'), 'primary', 'save_form', false); ?>
                                </div> <!-- .publishing-action -->
                            </div> <!-- .swpm-major-publishing-actions -->
                        </div> <!-- #form-editor-footer -->
                    </form>
                </div> <!-- .form-edit -->
            </div> <!-- #swpm-form-builder-management -->
        </div> <!-- swpm-form-builder-main -->
    </div> <!-- .swpm-postbox-container -->
</div> <!-- #swpm-form-builder-frame -->
<?php
wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
