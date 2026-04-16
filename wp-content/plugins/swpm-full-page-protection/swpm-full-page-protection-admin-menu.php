<?php

add_action('swpm_after_main_admin_menu', 'swpm_fpp_do_admin_menu');

function swpm_fpp_do_admin_menu($menu_parent_slug) {    
    $permission = 'manage_options';
    if (defined('SWPM_MANAGEMENT_PERMISSION')){
        $permission = SWPM_MANAGEMENT_PERMISSION;
    }    
    add_submenu_page($menu_parent_slug, __("Full Page Protection", 'simple-membership'), __("Full Page Protection", 'simple-membership'), $permission, 'swpm-fpp', 'swpm_fpp_settings_page');
    
}

function swpm_fpp_settings_page() {
    echo '<div class="wrap">';
    echo '<h2>Full Page Protection AddOn Settings</h2>';
    
    echo '<div id="poststuff"><div id="post-body">';
    
    if (isset($_POST['swpm_fpp_settings_update'])) {
        
        $options = array(
            'prot_alt_post_enabled' => isset($_POST["prot_alt_post_enabled"]) ? '1' : '',
            'prot_alt_page_enabled' => isset($_POST["prot_alt_page_enabled"]) ? '1' : '',
            'prot_alt_cpt_enabled' => isset($_POST["prot_alt_cpt_enabled"]) ? '1' : '',
            'prot_alt_show_header_footer' => isset($_POST["prot_alt_show_header_footer"]) ? '1' : '',
        );
        update_option('swpm_fpp_addon_settings', $options); //store the results in WP options table

        echo '<div id="message" class="updated fade"><p>';
        echo '<strong>Options Updated!';
        echo '</strong></p></div>';
    }
    
    $emp_options = get_option('swpm_fpp_addon_settings');
    $prot_alt_post_enabled = isset($emp_options['prot_alt_post_enabled'])? $emp_options['prot_alt_post_enabled']: '';
    $prot_alt_page_enabled = isset($emp_options['prot_alt_page_enabled'])? $emp_options['prot_alt_page_enabled']: '';
    $prot_alt_cpt_enabled = isset($emp_options['prot_alt_cpt_enabled'])? $emp_options['prot_alt_cpt_enabled']: '';
    $prot_alt_show_header_footer = isset($emp_options['prot_alt_show_header_footer'])? $emp_options['prot_alt_show_header_footer']: '';
    ?>

    <form method="post" action="">	

        <div class="postbox">
            <h3 class="hndle"><label for="title">Protection Preferences</label></h3>
            <div class="inside">
                
                <table class="form-table">

                    <tr valign="top"><td width="25%" align="left">
                            Handle Post Protection
                        </td><td align="left">
                            <input name="prot_alt_post_enabled" type="checkbox"<?php if ($prot_alt_post_enabled != '') echo ' checked="checked"'; ?> value="1"/>
                            <p class="description">Check this if you want to allow this addon to apply the full page protection to your posts.</p>
                        </td>
                    </tr>
                    <tr valign="top"><td width="25%" align="left">
                            Handle Page Protection
                        </td><td align="left">
                            <input name="prot_alt_page_enabled" type="checkbox"<?php if ($prot_alt_page_enabled != '') echo ' checked="checked"'; ?> value="1"/>
                            <p class="description">Check this if you want to allow this addon to apply the full page protection to your pages.</p>
                        </td>
                    </tr>
                    <tr valign="top"><td width="25%" align="left">
                            Handle Custom Post Type Protection
                        </td><td align="left">
                            <input name="prot_alt_cpt_enabled" type="checkbox"<?php if ($prot_alt_cpt_enabled != '') echo ' checked="checked"'; ?> value="1"/>
                            <p class="description">Check this if you want to allow this addon to apply the full page protection to your custom post type posts.</p>
                        </td>
                    </tr>
                    
                </table>
            </div></div>
        
        <div class="postbox">
            <h3 class="hndle"><label for="title">Other Settings</label></h3>
            <div class="inside">
                
                <table class="form-table">

                    <tr valign="top"><td width="25%" align="left">
                            Show Site Header and Footer
                        </td><td align="left">
                            <input name="prot_alt_show_header_footer" type="checkbox"<?php if ($prot_alt_show_header_footer != '') echo ' checked="checked"'; ?> value="1"/>
                            <p class="description">Check this if you want to show the site header and footer with the protection message. Your theme must support WordPress's standard get_header() and get_footer() functions for this option to work.</p>
                        </td>
                    </tr>
                    
                </table>
            </div></div>
        
        <div class="submit">
            <input type="submit" class="button-primary" name="swpm_fpp_settings_update" value="Update" />
        </div>  
    </form>
    <?php
    echo '</div></div>';
    echo '</div>';
}