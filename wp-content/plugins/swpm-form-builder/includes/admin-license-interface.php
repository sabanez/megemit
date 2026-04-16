<?php
// This is the secret key for API authentication. You configured it in the settings menu of the license manager plugin.
define('SWPM_FB_SECRET_KEY', '5428d95d7ce397.02132089'); //Rename this constant name so it is specific to your plugin or theme.
// This is the URL where API query request will be sent to. This should be the URL of the site where you have installed the main license manager plugin. Get this value from the integration help page.
define('SWPM_FB_LICENSE_SERVER_URL', 'https://simple-membership-plugin.com'); //Rename this constant name so it is specific to your plugin or theme.
// This is a value that will be recorded in the license manager data so you can identify licenses for this item/product.
define('SWPM_FB_ITEM_REFERENCE', 'SWPM Form Builder'); //Rename this constant name so it is specific to your plugin or theme.

add_action('admin_menu', 'slm_sample_license_menu');


/* * * License activate button was clicked ** */
if (isset($_REQUEST['activate_license'])) {
    $license_key = $_REQUEST['swpm_fb_license_key'];

    // API query parameters
    $api_params = array(
        'slm_action' => 'slm_activate',
        'secret_key' => SWPM_FB_SECRET_KEY,
        'license_key' => $license_key,
        'registered_domain' => $_SERVER['SERVER_NAME'],
        'item_reference' => urlencode(SWPM_FB_ITEM_REFERENCE),
    );

    // Send query to the license manager server
    $response = wp_remote_get(add_query_arg($api_params, SWPM_FB_LICENSE_SERVER_URL), array('timeout' => 20, 'sslverify' => false));

    // Check for error in the response
    if (is_wp_error($response)) {
        echo "Unexpected Error! The query returned with an error.";
    }

    //var_dump($response);//uncomment it if you want to look at the full response
    // License data.
    $license_data = json_decode(wp_remote_retrieve_body($response));

    // TODO - Do something with it.
    //var_dump($license_data);//uncomment it to look at the data

    if ($license_data->result == 'success') {//Success was returned for the license activation
        //Uncomment the followng line to see the message that returned from the license server
        //echo '<br />The following message was returned from the server: ' . $license_data->message;        

        //Save the license key in the options table
        update_option('swpm_fb_license_key', $license_key);
        
        echo '<p style="color: green; font-size: 16px; font-weight: bold;">' . $license_data->message . '. You can start using the addon now.</p>';        
        return;
        
    } else {
        //Show error to the user. Probably entered incorrect license key.
        //Uncomment the followng line to see the message that returned from the license server
        echo '<p style="color: red; font-size: 16px; font-weight: bold;">' . $license_data->message . '</p>';
    }
}
/* * * End of license activation ** */

/* * * License activate button was clicked ** */
if (isset($_REQUEST['deactivate_license'])) {
    $license_key = $_REQUEST['swpm_fb_license_key'];

    // API query parameters
    $api_params = array(
        'slm_action' => 'slm_deactivate',
        'secret_key' => SWPM_FB_SECRET_KEY,
        'license_key' => $license_key,
        'registered_domain' => $_SERVER['SERVER_NAME'],
        'item_reference' => urlencode(SWPM_FB_ITEM_REFERENCE),
    );

    // Send query to the license manager server
    $response = wp_remote_get(add_query_arg($api_params, SWPM_FB_LICENSE_SERVER_URL), array('timeout' => 20, 'sslverify' => false));

    // Check for error in the response
    if (is_wp_error($response)) {
        echo "Unexpected Error! The query returned with an error.";
    }

    //var_dump($response);//uncomment it if you want to look at the full response
    // License data.
    $license_data = json_decode(wp_remote_retrieve_body($response));

    // TODO - Do something with it.
    //var_dump($license_data);//uncomment it to look at the data

    if ($license_data->result == 'success') {//Success was returned for the license activation
        //Uncomment the followng line to see the message that returned from the license server
        echo '<p style="color: green; font-size: 16px; font-weight: bold;">' . $license_data->message . '</p>';

        //Remove the licensse key from the options table. It will need to be activated again.
        update_option('swpm_fb_license_key', '');
    } else {
        //Show error to the user. Probably entered incorrect license key.
        //Uncomment the followng line to see the message that returned from the license server
        echo '<p style="color: red; font-size: 16px; font-weight: bold;">' . $license_data->message . '</p>';
    }
}
/* * * End of sample license deactivation ** */
?>
<p>Please enter the license key for this product to activate it. You were given a license key when you purchased this item.</p>
<form action="" method="post">
    <table class="form-table">
        <tr>
            <th style="width:100px;"><label for="swpm_fb_license_key">License Key</label></th>
            <td ><input class="regular-text" type="text" id="swpm_fb_license_key" name="swpm_fb_license_key"  value="<?php echo get_option('swpm_fb_license_key'); ?>" ></td>
        </tr>
    </table>
    <p class="submit">
        <?php
        $key = get_option('swpm_fb_license_key');
        if(!empty($key)){
            echo '<div style="color: #043B14;background-color: #CCF4D6;border: 1px solid #059B53; padding: 10px; margin: 10px 0;">License key is active on this install.</div>';
        }else{
            echo '<input type="submit" name="activate_license" value="Activate" class="button-primary" />';
        }
        ?>        
        <input type="submit" name="deactivate_license" value="Deactivate" class="button" />
    </p>
</form>
    <?php
