<div class="wrap">
    <h2><?php _e('Settings', 'swpm-form-builder'); ?></h2>
    <form id="swpm-settings" method="post">
        <input name="action" type="hidden" value="swpm_settings" />
<?php wp_nonce_field('swpm-update-settings'); ?>
        <h3><?php _e('Global Settings', 'swpm-form-builder'); ?></h3>
        <p><?php _e('These settings will affect all forms on your site.', 'swpm-form-builder'); ?></p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('CSS', 'swpm-form-builder'); ?></th>
                <td>
                    <fieldset>
                        <?php
                        $disable = array(
                            'always-load-css' => __('Always load CSS', 'swpm-form-builder'),
                            'disable-css' => __('Disable CSS', 'swpm-form-builder'), // swpm-form-builder-css
                        );

                        foreach ($disable as $key => $title) :
                            $swpm_settings[$key] = isset($swpm_settings[$key]) ? $swpm_settings[$key] : '';
                        ?>
                            <label for="swpm-settings-<?php echo $key; ?>">
                                <input type="checkbox" name="swpm-settings[<?php echo $key; ?>]" id="swpm-settings-<?php echo $key; ?>" value="1" <?php checked($swpm_settings[$key], 1); ?> /> <?php echo $title; ?>
                            </label>
                            <br>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Form Output', 'swpm-form-builder'); ?></th>
                <td>
                    <fieldset>
<?php
$disable = array(
    'address-labels' => __('Place Address labels above fields', 'swpm-form-builder'), // swpm_address_labels_placement
    'show-version' => __('Disable meta tag version', 'swpm-form-builder'), // swpm_show_version
);

foreach ($disable as $key => $title) :

    $swpm_settings[$key] = isset($swpm_settings[$key]) ? $swpm_settings[$key] : '';
    ?>
                            <label for="swpm-settings-<?php echo $key; ?>">
                                <input type="checkbox" name="swpm-settings[<?php echo $key; ?>]" id="swpm-settings-<?php echo $key; ?>" value="1" <?php checked($swpm_settings[$key], 1); ?> /> <?php echo $title; ?>
                            </label>
                            <br>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="swpm-settings-spam-points"><?php _e('Spam word sensitivity', 'swpm-form-builder'); ?></label></th>
                <td>
<?php $swpm_settings['spam-points'] = isset($swpm_settings['spam-points']) ? $swpm_settings['spam-points'] : '4'; ?>
                    <input type="number" min="1" name="swpm-settings[spam-points]" id="swpm-settings-spam-points" value="<?php echo $swpm_settings['spam-points']; ?>" class="small-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="swpm-settings-max-upload-size"><?php _e('Max Upload Size', 'swpm-form-builder'); ?></label></th>
                <td>
<?php $swpm_settings['max-upload-size'] = isset($swpm_settings['max-upload-size']) ? $swpm_settings['max-upload-size'] : '25'; ?>
                    <input type="number" name="swpm-settings[max-upload-size]" id="swpm-settings-max-upload-size" value="<?php echo $swpm_settings['max-upload-size']; ?>" class="small-text" /> MB
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="swpm-settings-sender-mail-header"><?php _e('Sender Mail Header', 'swpm-form-builder'); ?></label></th>
                <td>
<?php
// Use the admin_email as the From email
$from_email = get_site_option('admin_email');

// Get the site domain and get rid of www.
$sitename = strtolower($_SERVER['SERVER_NAME']);
if (substr($sitename, 0, 4) == 'www.')
    $sitename = substr($sitename, 4);

// Get the domain from the admin_email
list( $user, $domain ) = explode('@', $from_email);

// If site domain and admin_email domain match, use admin_email, otherwise a same domain email must be created
$from_email = ( $sitename == $domain ) ? $from_email : "wordpress@$sitename";

$swpm_settings['sender-mail-header'] = isset($swpm_settings['sender-mail-header']) ? $swpm_settings['sender-mail-header'] : $from_email;
?>
                    <input type="text" name="swpm-settings[sender-mail-header]" id="swpm-settings-sender-mail-header" value="<?php echo $swpm_settings['sender-mail-header']; ?>" class="regular-text" />
                    <p class="description"><?php _e('Some server configurations require an existing email on the domain be used when sending emails.', 'swpm-form-builder'); ?></p>
                </td>
            </tr>
        </table>

                    <?php submit_button(__('Save', 'swpm-form-builder'), 'primary', 'submit', false); ?>
    </form>
</div>