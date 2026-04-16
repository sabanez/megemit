<?php
//Check license key before allowing form creation
$key = get_option('swpm_fb_license_key');
if(empty($key)){//valid key is not active
    echo '<div style="margin: 10px 0;padding: 5px 15px;background-color: #FFFFE0;border-color: #E6DB55;border-style: solid;border-width: 1px;">';
    echo '<p>Form builder addon needs a valid license key to function. <a href="admin.php?page=swpm-form-builder&action=license" class="button-primary">Click Here</a> to enter your license key and start using the addon.</p>';
    echo '</div>';
    return;
}
?>

<form method="post" id="swpm-form-builder-new-form" action="">
    <input name="action" type="hidden" value="create_form" />
    <?php
    wp_nonce_field('create_form');

    if (!current_user_can('manage_options'))
        wp_die(__('You do not have sufficient permissions to create a new form.', 'swpm-form-builder'));
    ?>
    <h3><?php _e('Create a form', 'swpm-form-builder'); ?></h3>

    <table class="form-table">
        <tbody>
            <!-- Form Name -->
            <tr valign="top">
                <th scope="row"><label for="form-name"><?php _e('Name the form', 'swpm-form-builder'); ?></label></th>
                <td>
                    <input type="text" autofocus="autofocus" class="regular-text required" id="form-name" name="form_title" />
                    <p class="description"><?php _e('Required. This name is used for admin purposes so you can identify the form.', 'swpm-form-builder'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="form-type"><?php _e('Type of the form', 'swpm-form-builder'); ?></label></th>
                <td>
                    <select name="form_type" id="form-type">
                        <option value ="0">Registration</option>
                        <option value ="1">Profile</option>
                    </select>
                    <p class="description"><?php _e('Required. Select which type of form you want to create. The registration form will be shown on the registration page. The profile form will be shown on the edit profile page.', 'swpm-form-builder'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="form-for-level"><?php _e('Membership level', 'swpm-form-builder'); ?></label></th>
                <td>
                    <select name="form_for_level" id="form-for-level">
                        <option value ="0" selected>All</option>
                        <?php echo SwpmUtils::membership_level_dropdown(); ?>
                    </select>
                    <p class="description"><?php _e('Required. Select membership level for this form. Use the "All" option if you want to create one custom form for all your membership levels.', 'swpm-form-builder'); ?></p>
                </td>
            </tr>
            <!-- Sender Name -->
            <!-- <tr valign="top">
                    <th scope="row"><label for="form-email-sender-name"><?php _e('Your Name or Company', 'swpm-form-builder'); ?></label></th>
                    <td>
                            <input type="text" value="" placeholder="" class="regular-text required" id="form-email-sender-name" name="form_email_from_name" />
                            <p class="description"><?php _e('Required. This option sets the "From" display name of the email that is sent.', 'swpm-form-builder'); ?></p>
                    </td>
            </tr>-->
            <!-- Reply-to Email -->
            <!-- <tr valign="top">
                    <th scope="row"><label for="form-email-from"><?php _e('Reply-To E-mail', 'swpm-form-builder'); ?></label></th>
                    <td>
                            <input type="text" value="" placeholder="" class="regular-text required" id="form-email-from" name="form_email_from" />
                            <p class="description"><?php _e('Required. Replies to your email will go here.', 'swpm-form-builder'); ?></p>
                            <p class="description"><?php _e('Tip: for best results, use an email that exists on this domain.', 'swpm-form-builder'); ?></p>
                    </td>
            </tr>-->
            <!-- Email Subject -->
            <!-- <tr valign="top">
                    <th scope="row"><label for="form-email-subject"><?php _e('E-mail Subject', 'swpm-form-builder'); ?></label></th>
                    <td>
                            <input type="text" value="" placeholder="" class="regular-text" id="form-email-subject" name="form_email_subject" />
                            <p class="description"><?php _e('This sets the subject of the email that is sent.', 'swpm-form-builder'); ?></p>
                    </td>
            </tr>-->
            <!-- E-mail To -->
            <!--<tr valign="top">
                    <th scope="row"><label for="form-email-to"><?php _e('E-mail To', 'swpm-form-builder'); ?></label></th>
                    <td>
                            <input type="text" value="" placeholder="" class="regular-text" id="form-email-to" name="form_email_to[]" />
                            <p class="description"><?php _e('Who to send the submitted data to. You can add more after creating the form.', 'swpm-form-builder'); ?></p>
                    </td>
            </tr>-->

        </tbody>
    </table>
    <?php submit_button(__('Create Form', 'swpm-form-builder')); ?>
</form>