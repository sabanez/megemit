<h3>bbPress Settings </h3>
<p></p> 
<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">Enable bbPress Integration</th>
            <td>
                <input type="checkbox" <?php echo $enable_bbpress;?> name="swpm-addon-enable-bbpress" value="checked='checked'"/>
                <p class="description">Enable/disable bbPress Integration</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Override bbPress User Profile URL</th>
            <td>
                <input type="checkbox" <?php echo $override_bbp_profile_url;?> name="override_bbp_profile_url" value="checked='checked'"/>
                <p class="description">Override the bbPress user profile URL so the user's profile links to the "Edit Profile" page of the simple membership plugin.</p>
            </td>
        </tr>        
    </tbody>
</table>