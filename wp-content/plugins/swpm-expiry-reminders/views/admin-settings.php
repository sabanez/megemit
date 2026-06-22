<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
global $wpdb;
$all_levels    = $wpdb->get_results( "SELECT id, alias FROM {$wpdb->prefix}swpm_membership_tbl ORDER BY id ASC" );
$current_scope = get_option( 'swpm_expiry_reminder_scope', 'all' );
$saved_ids     = get_option( 'swpm_expiry_reminder_level_ids', array() );
?>

<div class="wrap">
    <h1><?php _e( 'SWPM Expiry Reminders', 'swpm-er' ); ?></h1>

    <?php if ( isset( $_GET['saved'] ) && $_GET['saved'] === '1' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Settings saved.', 'swpm-er' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="swpm_er_save_settings">
        <?php wp_nonce_field( 'swpm_er_settings_nonce' ); ?>

        <table class="form-table" role="presentation">

            <!-- Enable -->
            <tr>
                <th scope="row"><?php _e( 'Enable Reminders', 'swpm-er' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="swpm_expiry_reminders_enabled" value="1"
                            <?php checked( 1, get_option( 'swpm_expiry_reminders_enabled', 0 ) ); ?>>
                        <?php _e( 'Send expiry reminder emails to members', 'swpm-er' ); ?>
                    </label>
                </td>
            </tr>

            <!-- Membership Level Scope -->
            <tr>
                <th scope="row"><?php _e( 'Apply To', 'swpm-er' ); ?></th>
                <td>
                    <label style="display:block;margin-bottom:6px;">
                        <input type="radio" name="swpm_expiry_reminder_scope" value="all"
                            <?php checked( 'all', $current_scope ); ?> id="swpm_er_scope_all">
                        <?php _e( 'All membership levels', 'swpm-er' ); ?>
                    </label>
                    <label style="display:block;">
                        <input type="radio" name="swpm_expiry_reminder_scope" value="specific"
                            <?php checked( 'specific', $current_scope ); ?> id="swpm_er_scope_specific">
                        <?php _e( 'Specific membership levels', 'swpm-er' ); ?>
                    </label>

                    <div id="swpm_er_level_list" style="margin-top:12px;padding:12px;background:#f9f9f9;border:1px solid #ddd;max-width:400px;<?php echo $current_scope === 'specific' ? '' : 'display:none;'; ?>">
                        <?php if ( empty( $all_levels ) ) : ?>
                            <em><?php _e( 'No membership levels found.', 'swpm-er' ); ?></em>
                        <?php else : ?>
                            <?php foreach ( $all_levels as $level ) : ?>
                                <label style="display:block;margin-bottom:4px;">
                                    <input type="checkbox"
                                        name="swpm_expiry_reminder_level_ids[]"
                                        value="<?php echo esc_attr( $level->id ); ?>"
                                        <?php checked( in_array( (int) $level->id, (array) $saved_ids, true ), true ); ?>>
                                    <?php echo esc_html( $level->alias ); ?>
                                    <span style="color:#999;font-size:11px;">(ID: <?php echo esc_html( $level->id ); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <script>
                    (function() {
                        function toggleLevelList() {
                            var specific = document.getElementById('swpm_er_scope_specific');
                            var list     = document.getElementById('swpm_er_level_list');
                            if ( list ) {
                                list.style.display = specific && specific.checked ? '' : 'none';
                            }
                        }
                        var radios = document.querySelectorAll('input[name="swpm_expiry_reminder_scope"]');
                        for ( var i = 0; i < radios.length; i++ ) {
                            radios[i].addEventListener('change', toggleLevelList);
                        }
                    })();
                    </script>
                </td>
            </tr>

            <!-- Days -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_reminder_days"><?php _e( 'Reminder Days', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <input type="text" id="swpm_expiry_reminder_days" name="swpm_expiry_reminder_days"
                        value="<?php echo esc_attr( get_option( 'swpm_expiry_reminder_days', '45,30,15' ) ); ?>"
                        class="regular-text">
                    <p class="description"><?php _e( 'Days before expiration to send a reminder. Separate multiple values with commas. Example: 45,30,15', 'swpm-er' ); ?></p>
                </td>
            </tr>

            <!-- Renewal URL -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_renewal_url"><?php _e( 'Renewal Page URL', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <input type="url" id="swpm_expiry_renewal_url" name="swpm_expiry_renewal_url"
                        value="<?php echo esc_attr( get_option( 'swpm_expiry_renewal_url', '' ) ); ?>"
                        class="large-text">
                    <p class="description"><?php _e( 'URL used in the {renewal_url} variable in the email.', 'swpm-er' ); ?></p>
                </td>
            </tr>

            <!-- From Name -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_from_name"><?php _e( 'From Name', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <input type="text" id="swpm_expiry_from_name" name="swpm_expiry_from_name"
                        value="<?php echo esc_attr( get_option( 'swpm_expiry_from_name', get_bloginfo( 'name' ) ) ); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- From Email -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_from_email"><?php _e( 'From Email', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <input type="email" id="swpm_expiry_from_email" name="swpm_expiry_from_email"
                        value="<?php echo esc_attr( get_option( 'swpm_expiry_from_email', get_option( 'admin_email' ) ) ); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Email Subject -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_email_subject"><?php _e( 'Email Subject', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <input type="text" id="swpm_expiry_email_subject" name="swpm_expiry_email_subject"
                        value="<?php echo esc_attr( get_option( 'swpm_expiry_email_subject', swpm_er_default_subject() ) ); ?>"
                        class="large-text">
                    <p class="description"><?php _e( 'Available variables: {first_name}, {last_name}, {email}, {membership_level}, {expiry_date}, {days_remaining}, {renewal_url}', 'swpm-er' ); ?></p>
                </td>
            </tr>

            <!-- Email Body -->
            <tr>
                <th scope="row">
                    <label for="swpm_expiry_email_body"><?php _e( 'Email Body', 'swpm-er' ); ?></label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        get_option( 'swpm_expiry_email_body', swpm_er_default_body() ),
                        'swpm_expiry_email_body',
                        array(
                            'textarea_name' => 'swpm_expiry_email_body',
                            'textarea_rows' => 12,
                            'media_buttons' => false,
                            'teeny'         => false,
                        )
                    );
                    ?>
                    <p class="description"><?php _e( 'Available variables: {first_name}, {last_name}, {email}, {membership_level}, {expiry_date}, {days_remaining}, {renewal_url}', 'swpm-er' ); ?></p>
                </td>
            </tr>

        </table>

        <!-- Variable reference -->
        <h2><?php _e( 'Available Variables', 'swpm-er' ); ?></h2>
        <table class="widefat striped" style="max-width:600px;">
            <thead>
                <tr>
                    <th><?php _e( 'Variable', 'swpm-er' ); ?></th>
                    <th><?php _e( 'Description', 'swpm-er' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>{first_name}</code></td><td><?php _e( "Member's first name", 'swpm-er' ); ?></td></tr>
                <tr><td><code>{last_name}</code></td><td><?php _e( "Member's last name", 'swpm-er' ); ?></td></tr>
                <tr><td><code>{email}</code></td><td><?php _e( "Member's email address", 'swpm-er' ); ?></td></tr>
                <tr><td><code>{membership_level}</code></td><td><?php _e( 'Name of the membership level', 'swpm-er' ); ?></td></tr>
                <tr><td><code>{expiry_date}</code></td><td><?php _e( 'Exact expiration date (WP date format)', 'swpm-er' ); ?></td></tr>
                <tr><td><code>{days_remaining}</code></td><td><?php _e( 'Days remaining until expiration', 'swpm-er' ); ?></td></tr>
                <tr><td><code>{renewal_url}</code></td><td><?php _e( 'Renewal page URL (configured above)', 'swpm-er' ); ?></td></tr>
            </tbody>
        </table>

        <br>
        <?php submit_button( __( 'Save Settings', 'swpm-er' ), 'primary', 'submit', false ); ?>
        <button type="submit" name="action" value="swpm_er_preview_email" class="button button-secondary"
            formtarget="_blank">
            <?php _e( 'Preview Email', 'swpm-er' ); ?>
        </button>
    </form>
</div>
