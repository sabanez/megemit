<form method="POST">
    <input type="hidden" name="swpm-fb-settings[_save-settings]" value="1">
    <h2>General Settings</h2>
    <table class="form-table">
	<tr>
	    <th scope="row"><?php _e( 'Load a Light Version of the Form Builder CSS', 'swpm-form-builder' ); ?></th>
	    <td>
		<input type="checkbox" name="swpm-fb-settings[enable-light-css]"<?php SwpmFbSettings::checked( 'enable-light-css' ); ?>>
		<p class="description"><?php _e( "When this version is loaded, most of the input field styles come from your theme (instead of the form builder's CSS).", 'swpm-form-builder' ); ?></p>
	    </td>
	</tr>
    </table>
    <?php
    wp_nonce_field( 'swpm-fb-save-settings' );
    submit_button();
    ?>
</form>

