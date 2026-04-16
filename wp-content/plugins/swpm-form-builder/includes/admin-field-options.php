<?php
global $wpdb;

$field_where = ( isset( $field_id ) && ! is_null( $field_id ) ) ? "AND field_id = $field_id" : '';
// Display all fields for the selected form
$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d $field_where ORDER BY field_sequence ASC", $form_nav_selected_id ) );

$depth  = 1;
$parent = $last = 0;
ob_start();

// Loop through each field and display
foreach ( $fields as &$field ) :
	// If we are at the root level
	if ( ! $field->field_parent && $depth > 1 ) {
		// If we've been down a level, close out the list
		while ( $depth > 1 ) {
			echo '</li></ul>';
			$depth--;
		}

		// Close out the root item
		echo '</li>';
	}
	// first item of <ul>, so move down a level
	elseif ( $field->field_parent && $field->field_parent == $last ) {
		echo '<ul class="parent">';
		$depth++;
	}
	// Close up a <ul> and move up a level
	elseif ( $field->field_parent && $field->field_parent != $parent ) {
		echo '</li></ul></li>';
		$depth--;
	}
	// Same level so close list item
	elseif ( $field->field_parent && $field->field_parent == $parent ) {
		echo '</li>';
	}

	// Store item ID and parent ID to test for nesting
	$last   = $field->field_id;
	$parent = $field->field_parent;
	?>
<li id="form_item_<?php echo $field->field_id; ?>" class="form-item<?php echo ( in_array( $field->field_type, array( 'submit', 'secret', 'verification' ) ) ) ? ' ui-state-disabled' : ''; ?><?php echo ( ! in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) ? ' mjs-nestedSortable-no-nesting' : ''; ?>" data-field-type="<?php echo $field->field_type; ?>" data-field-key="<?php echo $field->field_key; ?>">
	<dl class="menu-item-bar swpm-menu-item-inactive">
		<dt class="swpm-menu-item-handle swpm-menu-item-type-<?php echo esc_attr( $field->field_type ); ?>">
			<span class="item-title"><?php echo stripslashes( esc_attr( $field->field_name ) ); ?><?php echo ( $field->field_required == 'yes' ) ? ' <span class="is-field-required">*</span>' : ''; ?></span>
			<span class="item-controls">
				<span class="item-type"><?php echo strtoupper( str_replace( '-', ' ', $field->field_type ) ); ?></span>
				<a href="#" title="<?php _e( 'Edit Field Item', 'swpm-form-builder' ); ?>" id="edit-<?php echo $field->field_id; ?>" class="item-edit"><?php _e( 'Edit Field Item', 'swpm-form-builder' ); ?></a>
			</span>
		</dt>
	</dl>

	<div id="form-item-settings-<?php echo $field->field_id; ?>" class="menu-item-settings field-type-<?php echo $field->field_type; ?>" style="display: none;">
		<?php if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) : ?>

		<p class="description description-wide">
			<label for="edit-form-item-name-<?php echo $field->field_id; ?>"><?php echo ( in_array( $field->field_type, array( 'fieldset', 'verification' ) ) ) ? 'Legend' : 'Name'; ?>
				<span class="swpm-tooltip" rel="<?php esc_attr_e( 'For Fieldsets, a Legend is simply the name of that group. Use general terms that describe the fields included in this Fieldset.', 'swpm-form-builder' ); ?>" title="<?php esc_attr_e( 'About Legend', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>
		<p class="description description-wide">
			<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
				<?php _e( 'CSS Classes', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'swpm-form-builder' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
			</label>
		</p>

		<?php elseif ( $field->field_type == 'member_id' ) : ?>
		<!-- Instructions -->
		<p class="description description-wide">
			<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
				<?php _e( 'Name', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Name', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( "A field's name is the most visible and direct way to describe what that field is for.", 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>
		<!-- Description -->
		<p class="description description-wide">
		<div class="swpmfb_member_id_field_description">This field can be used to show the member ID of a member in the edit profile form.</div>
		</p>
		<!-- CSS Classes -->
		<p class="description description-thin">
			<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
				<?php _e( 'CSS Classes', 'swpm-form-builder-pro' ); ?>
				<span class="swpm-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'swpm-form-builder-pro' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'swpm-form-builder-pro' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
			</label>
		</p>

		<!-- Field Layout -->
		<p class="description description-thin">
			<label for="edit-form-item-layout">
				<?php _e( 'Field Layout', 'swpm-form-builder-pro' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Field Layout', 'swpm-form-builder-pro' ); ?>" rel="<?php esc_attr_e( 'Used to create advanced layouts. Align fields side by side in various configurations.', 'swpm-form-builder-pro' ); ?>">(?)</span>
				<br />
				<select name="field_layout-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-layout-<?php echo $field->field_id; ?>">

					<option value="" <?php selected( $field->field_layout, '' ); ?>><?php _e( 'Default', 'swpm-form-builder-pro' ); ?></option>
					<optgroup label="------------">
						<option value="left-half" <?php selected( $field->field_layout, 'left-half' ); ?>><?php _e( 'Left Half', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-half" <?php selected( $field->field_layout, 'right-half' ); ?>><?php _e( 'Right Half', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-third" <?php selected( $field->field_layout, 'left-third' ); ?>><?php _e( 'Left Third', 'swpm-form-builder-pro' ); ?></option>
						<option value="middle-third" <?php selected( $field->field_layout, 'middle-third' ); ?>><?php _e( 'Middle Third', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-third" <?php selected( $field->field_layout, 'right-third' ); ?>><?php _e( 'Right Third', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-two-thirds" <?php selected( $field->field_layout, 'left-two-thirds' ); ?>><?php _e( 'Left Two Thirds', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-two-thirds" <?php selected( $field->field_layout, 'right-two-thirds' ); ?>><?php _e( 'Right Two Thirds', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<?php apply_filters( 'swpm_admin_field_layout', $field->field_layout ); ?>
				</select>
			</label>
		</p>

		<?php elseif ( $field->field_type == 'instructions' ) : ?>
		<!-- Instructions -->
		<p class="description description-wide">
			<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
				<?php _e( 'Name', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Name', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( "A field's name is the most visible and direct way to describe what that field is for.", 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>
		<!-- Description -->
		<p class="description description-wide">
			<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
				<?php _e( 'Description (HTML tags allowed)', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Instructions Description', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'The Instructions field allows for long form explanations, typically seen at the beginning of Fieldsets or Sections. HTML tags are allowed.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<textarea name="field_description-<?php echo $field->field_id; ?>" class="widefat edit-menu-item-description" cols="20" rows="3" id="edit-form-item-description-<?php echo $field->field_id; ?>" /><?php echo stripslashes( $field->field_description ); ?></textarea>
			</label>
		</p>
		<!-- CSS Classes -->
		<p class="description description-thin">
			<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
				<?php _e( 'CSS Classes', 'swpm-form-builder-pro' ); ?>
				<span class="swpm-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'swpm-form-builder-pro' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'swpm-form-builder-pro' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
			</label>
		</p>

		<!-- Field Layout -->
		<p class="description description-thin">
			<label for="edit-form-item-layout">
				<?php _e( 'Field Layout', 'swpm-form-builder-pro' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Field Layout', 'swpm-form-builder-pro' ); ?>" rel="<?php esc_attr_e( 'Used to create advanced layouts. Align fields side by side in various configurations.', 'swpm-form-builder-pro' ); ?>">(?)</span>
				<br />
				<select name="field_layout-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-layout-<?php echo $field->field_id; ?>">

					<option value="" <?php selected( $field->field_layout, '' ); ?>><?php _e( 'Default', 'swpm-form-builder-pro' ); ?></option>
					<optgroup label="------------">
						<option value="left-half" <?php selected( $field->field_layout, 'left-half' ); ?>><?php _e( 'Left Half', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-half" <?php selected( $field->field_layout, 'right-half' ); ?>><?php _e( 'Right Half', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-third" <?php selected( $field->field_layout, 'left-third' ); ?>><?php _e( 'Left Third', 'swpm-form-builder-pro' ); ?></option>
						<option value="middle-third" <?php selected( $field->field_layout, 'middle-third' ); ?>><?php _e( 'Middle Third', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-third" <?php selected( $field->field_layout, 'right-third' ); ?>><?php _e( 'Right Third', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-two-thirds" <?php selected( $field->field_layout, 'left-two-thirds' ); ?>><?php _e( 'Left Two Thirds', 'swpm-form-builder-pro' ); ?></option>
						<option value="right-two-thirds" <?php selected( $field->field_layout, 'right-two-thirds' ); ?>><?php _e( 'Right Two Thirds', 'swpm-form-builder-pro' ); ?></option>
					</optgroup>
					<?php apply_filters( 'swpm_admin_field_layout', $field->field_layout ); ?>
				</select>
			</label>
		</p>

		<?php else : ?>

		<!-- Name -->
		<p class="description description-wide">
			<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
				<?php _e( 'Name', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Name', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( "A field's name is the most visible and direct way to describe what that field is for.", 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>
			<?php if ( $field->field_type == 'submit' ) : ?>
		<!-- CSS Classes -->
		<p class="description description-wide">
			<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
				<?php _e( 'CSS Classes', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'swpm-form-builder' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
			</label>
		</p>
		<?php elseif ( $field->field_type !== 'submit' ) : ?>
		<!-- Description -->
		<p class="description description-wide">
			<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
				<?php _e( 'Description', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Description', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'A description is an optional piece of text that further explains the meaning of this field. Descriptions are displayed below the field. HTML tags are allowed.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<textarea name="field_description-<?php echo $field->field_id; ?>" class="widefat edit-menu-item-description" cols="20" rows="3" id="edit-form-item-description-<?php echo $field->field_id; ?>" /><?php echo stripslashes( $field->field_description ); ?></textarea>
			</label>
		</p>

			<?php
					// Display the Options input only for radio, checkbox, and select fields
			if ( in_array( $field->field_type, array( 'radio', 'checkbox', 'select' ) ) ) :
				?>
		<!-- Options -->
		<p class="description description-wide">
				<?php _e( 'Options', 'swpm-form-builder' ); ?>
			<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Options', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'This property allows you to set predefined options to be selected by the user.  Use the plus and minus buttons to add and delete options.  At least one option must exist.', 'swpm-form-builder' ); ?>">(?)</span>
			<br />
				<?php
					// If the options field isn't empty, unserialize and build array
				if ( ! empty( $field->field_options ) ) {
					if ( is_serialized( $field->field_options ) ) {
						$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
					}
				}
					// Otherwise, present some default options
				else {
							$opts_vals = array( 'Option 1', 'Option 2', 'Option 3' );
				}

								// Basic count to keep track of multiple options
								$count = 1;
				?>
		<div class="swpm-cloned-options">
				<?php foreach ( $opts_vals as $options ) : ?>
			<div id="clone-<?php echo $field->field_id . '-' . $count; ?>" class="option">
				<label for="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" class="clonedOption">
					<input type="radio" value="<?php echo esc_attr( $count ); ?>" name="field_default-<?php echo $field->field_id; ?>" <?php checked( $field->field_default, $count ); ?> />
					<input type="text" value="<?php echo stripslashes( esc_attr( $options ) ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" />
				</label>

				<a href="#" class="deleteOption swpm-interface-icon swpm-interface-minus" title="Delete Option">
					<?php _e( 'Delete', 'swpm-form-builder' ); ?>
				</a>
				<span class="swpm-interface-icon swpm-interface-sort" title="<?php esc_attr_e( 'Drag and Drop to Sort Options', 'swpm-form-builder-pro' ); ?>"></span>
			</div>
					<?php
								$count++;
					endforeach;
				?>

		</div> <!-- .swpm-cloned-options -->
		<div class="clear"></div>
		<div class="swpm-add-options-group">
			<a href="#" class="swpm-button swpm-add-option" title="Add Option">
				<?php _e( 'Add Option', 'swpm-form-builder' ); ?>
				<span class="swpm-interface-icon swpm-interface-plus"></span>
			</a>
		</div>
		</p>
				<?php
				// Unset the options for any following radio, checkboxes, or selects
				unset( $opts_vals );
				endif;
			?>

			<?php if ( in_array( $field->field_type, array( 'file-upload' ) ) ) : ?>
		<!-- File Upload Accepts -->
		<p class="description description-wide">
				<?php
						$opts_vals = array( '' );

						// If the options field isn't empty, unserialize and build array
				if ( ! empty( $field->field_options ) ) {
					if ( is_serialized( $field->field_options ) ) {
						$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : unserialize( $field->field_options );
					}
				}

						// Loop through the options
				foreach ( $opts_vals as $options ) {
					?>
			<label for="edit-form-item-options-<?php echo $field->field_id; ?>">
					<?php _e( 'Accepted File Extensions', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Accepted File Extensions', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the types of files allowed.  Enter extensions without periods and separate multiples using the pipe character ( | ).', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $options ) ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id; ?>" />
			</label>
		</p>
					<?php
				}
						// Unset the options for any following radio, checkboxes, or selects
						unset( $opts_vals );
				endif;
			?>

			<?php if ( in_array( $field->field_type, array( 'date' ) ) ) : ?>
		<!-- Date Format -->
		<p class="description description-wide">
				<?php
						$opts_vals  = maybe_unserialize( $field->field_options );
						$dateFormat = ( isset( $opts_vals['dateFormat'] ) ) ? $opts_vals['dateFormat'] : 'mm/dd/yy';
				?>
			<label for="edit-form-item-date-dateFormat-<?php echo $field->field_id; ?>">
				<?php _e( 'Date Format', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Date Format', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Set the date format for each date picker.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo esc_attr( $dateFormat ); ?>" name="field_options-<?php echo $field->field_id; ?>[dateFormat]" class="widefat" id="edit-form-item-date-dateFormat-<?php echo $field->field_id; ?>" />
			</label>
		</p>
				<?php
						// Unset the options for any following radio, checkboxes, or selects
						unset( $opts_vals );
				endif;
			?>
		<!-- Validation -->
		<p class="description description-thin">
			<label for="edit-form-item-validation">
				<?php _e( 'Validation', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Validation', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Ensures user-entered data is formatted properly. For more information on Validation, refer to the Help tab at the top of this page.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />

				<?php if ( in_array( $field->field_type, array( 'text', 'time', 'number' ) ) ) : ?>
				<select name="field_validation-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-validation-<?php echo $field->field_id; ?>">
					<?php if ( $field->field_type == 'time' ) : ?>
					<option value="time-12" <?php selected( $field->field_validation, 'time-12' ); ?>><?php _e( '12 Hour Format', 'swpm-form-builder' ); ?></option>
					<option value="time-24" <?php selected( $field->field_validation, 'time-24' ); ?>><?php _e( '24 Hour Format', 'swpm-form-builder' ); ?></option>
					<?php elseif ( in_array( $field->field_type, array( 'number' ) ) ) : ?>
					<option value="number" <?php selected( $field->field_validation, 'number' ); ?>><?php _e( 'Number', 'swpm-form-builder' ); ?></option>
					<option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>><?php _e( 'Digits', 'swpm-form-builder' ); ?></option>
					<?php else : ?>
					<option value="" <?php selected( $field->field_validation, '' ); ?>><?php _e( 'None', 'swpm-form-builder' ); ?></option>
					<option value="email" <?php selected( $field->field_validation, 'email' ); ?>><?php _e( 'Email', 'swpm-form-builder' ); ?></option>
					<option value="url" <?php selected( $field->field_validation, 'url' ); ?>><?php _e( 'URL', 'swpm-form-builder' ); ?></option>
					<option value="date" <?php selected( $field->field_validation, 'date' ); ?>><?php _e( 'Date', 'swpm-form-builder' ); ?></option>
					<option value="number" <?php selected( $field->field_validation, 'number' ); ?>><?php _e( 'Number', 'swpm-form-builder' ); ?></option>
					<option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>><?php _e( 'Digits', 'swpm-form-builder' ); ?></option>
					<option value="phone" <?php selected( $field->field_validation, 'phone' ); ?>><?php _e( 'Phone', 'swpm-form-builder' ); ?></option>
					<?php endif; ?>
				</select>
					<?php
						else :
							$field_validation = '';

							switch ( $field->field_type ) {
								case 'email':
								case 'url':
								case 'phone':
									$field_validation = $field->field_type;
									break;

								case 'currency':
									$field_validation = 'number';
									break;

								case 'country':
									$field_validation = 'select';
									break;

								case 'number':
									$field_validation = 'digits';
									break;
							}
							?>
				<input type="text" class="widefat" name="field_validation-<?php echo $field->field_id; ?>" value="<?php echo $field_validation; ?>" readonly="readonly" />
				<?php endif; ?>

			</label>
		</p>

		<!-- Required -->
		<p class="field-link-target description description-thin">
			<label for="edit-form-item-required">
				<?php _e( 'Required', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Required', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Requires the field to be completed before the form is submitted. By default, all fields are set to No.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<select name="field_required-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-required-<?php echo $field->field_id; ?>">
					<option value="no" <?php selected( $field->field_required, 'no' ); ?>><?php _e( 'No', 'swpm-form-builder' ); ?></option>
					<option value="yes" <?php selected( $field->field_required, 'yes' ); ?>><?php _e( 'Yes', 'swpm-form-builder' ); ?></option>
				</select>
			</label>
		</p>

			<?php if ( ! in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
		<!-- Size -->
		<p class="description description-thin">
			<label for="edit-form-item-size">
				<?php _e( 'Size', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Size', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the size of the field.  By default, all fields are set to Medium.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>">
					<option value="small" <?php selected( $field->field_size, 'small' ); ?>><?php _e( 'Small', 'swpm-form-builder' ); ?></option>
					<option value="medium" <?php selected( $field->field_size, 'medium' ); ?>><?php _e( 'Medium', 'swpm-form-builder' ); ?></option>
					<option value="large" <?php selected( $field->field_size, 'large' ); ?>><?php _e( 'Large', 'swpm-form-builder' ); ?></option>
				</select>
			</label>
		</p>

		<?php elseif ( in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
		<!-- Options Layout -->
		<p class="description description-thin">
			<label for="edit-form-item-size">
				<?php _e( 'Options Layout', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Options Layout', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the layout of radio buttons or checkboxes.  By default, options are arranged in One Column.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>" <?php echo ( $field->field_type == 'time' ) ? ' disabled="disabled"' : ''; ?>>
					<option value="" <?php selected( $field->field_size, '' ); ?>><?php _e( 'One Column', 'swpm-form-builder' ); ?></option>
					<option value="two-column" <?php selected( $field->field_size, 'two-column' ); ?>><?php _e( 'Two Columns', 'swpm-form-builder' ); ?></option>
					<option value="three-column" <?php selected( $field->field_size, 'three-column' ); ?>><?php _e( 'Three Columns', 'swpm-form-builder' ); ?></option>
					<option value="auto-column" <?php selected( $field->field_size, 'auto-column' ); ?>><?php _e( 'Auto Width', 'swpm-form-builder' ); ?></option>
				</select>
			</label>
		</p>

		<?php endif; ?>
		<!-- Field Layout -->
		<p class="description description-thin">
			<label for="edit-form-item-layout">
				<?php _e( 'Field Layout', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Field Layout', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Used to create advanced layouts. Align fields side by side in various configurations.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<select name="field_layout-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-layout-<?php echo $field->field_id; ?>">

					<option value="" <?php selected( $field->field_layout, '' ); ?>><?php _e( 'Default', 'swpm-form-builder' ); ?></option>
					<optgroup label="------------">
						<option value="left-half" <?php selected( $field->field_layout, 'left-half' ); ?>><?php _e( 'Left Half', 'swpm-form-builder' ); ?></option>
						<option value="right-half" <?php selected( $field->field_layout, 'right-half' ); ?>><?php _e( 'Right Half', 'swpm-form-builder' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-third" <?php selected( $field->field_layout, 'left-third' ); ?>><?php _e( 'Left Third', 'swpm-form-builder' ); ?></option>
						<option value="middle-third" <?php selected( $field->field_layout, 'middle-third' ); ?>><?php _e( 'Middle Third', 'swpm-form-builder' ); ?></option>
						<option value="right-third" <?php selected( $field->field_layout, 'right-third' ); ?>><?php _e( 'Right Third', 'swpm-form-builder' ); ?></option>
					</optgroup>
					<optgroup label="------------">
						<option value="left-two-thirds" <?php selected( $field->field_layout, 'left-two-thirds' ); ?>><?php _e( 'Left Two Thirds', 'swpm-form-builder' ); ?></option>
						<option value="right-two-thirds" <?php selected( $field->field_layout, 'right-two-thirds' ); ?>><?php _e( 'Right Two Thirds', 'swpm-form-builder' ); ?></option>
					</optgroup>
				</select>
			</label>
		</p>

                <!-- Readonly field option -->
		<?php
                if ( in_array( $field->field_type, array( 'text', 'textarea', 'checkbox', 'date', 'currency', 'html' ) ) ) {
                    //Only field types of 'text', 'textarea', 'checkbox', 'date', 'currency' is eligible to have the "readonly" field option atm.
                    if ( ! in_array( $field->field_key, array('membership_level', 'primary_email' ) ) ) {
                        //Don't show the "readyonly" field option for fields: membership level, primary email.
                    ?>
                    <p class="description description-thin">
                            <label for="edit-form-read-only">
                                    <?php _e( 'Read Only', 'swpm-form-builder' ); ?>
                                    <span class="swpm-tooltip" title="<?php esc_attr_e( 'About Read Only', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Makes the field read only.', 'swpm-form-builder' ); ?>">(?)</span>
                                    <br />
                                    <select name="field_readonly-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-readonly-<?php echo $field->field_id; ?>">
                                            <option value="0" <?php selected( $field->field_readonly, 0 ); ?>><?php _e( 'No', 'swpm-form-builder' ); ?></option>
                                            <option value="1" <?php selected( $field->field_readonly, 1 ); ?>><?php _e( 'Yes', 'swpm-form-builder' ); ?></option>
                                    </select>
                            </label>
                    </p>
                    <?php
                    }
                }
                ?>
                <!-- END of Readonly field option -->

                <!-- Admin Only field option -->
		<?php if ( in_array( $field->field_type, array( 'text', 'textarea', 'checkbox', 'date', 'currency' ) ) ) { ?>
		<!-- Check if any of the mandatory fields. Then the Admin Only option cannot be shown. The mandatory fields must be shown on the form. -->
		<?php if ( ! in_array( $field->field_key, array( 'user_name', 'password', 'membership_level', 'primary_email' ) ) ) { ?>
		<p class="description description-thin">
			<label for="edit-form-admin-only">
					<?php _e( 'Show to Admin Only', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Show to Admin Only', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Makes the field visible on Admin Dashboard only.' ); ?>">(?)</span>
				<br />
				<select name="field_adminonly-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-adminonly-<?php echo $field->field_id; ?>">
					<option value="0" <?php selected( $field->field_adminonly, 0 ); ?>><?php _e( 'No', 'swpm-form-builder' ); ?></option>
					<option value="1" <?php selected( $field->field_adminonly, 1 ); ?>><?php _e( 'Yes', 'swpm-form-builder' ); ?></option>
				</select>
			</label>
		</p>
		<?php } ?>
		<?php } ?>


                <!-- Default Value field option -->
		<?php if ( ! in_array( $field->field_type, array( 'radio', 'select', 'checkbox', 'time', 'address', 'country' ) ) ) : ?>
		<p class="description description-wide">
			<label for="edit-form-item-default-<?php echo $field->field_id; ?>">
				<?php _e( 'Default Value', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Default Value', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Set a default value that will be inserted automatically.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_default ) ); ?>" name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>
		<?php elseif ( in_array( $field->field_type, array( 'address', 'country' ) ) ) : ?>
		<!-- Default Country -->
		<p class="description description-wide">
			<label for="edit-form-item-default-<?php echo $field->field_id; ?>">
				<?php _e( 'Default Country', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About Default Country', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'Select the country you would like to be displayed by default.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<select name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>">
					<?php
					foreach ( SwpmFbUtils::$countries as $country ) {
						echo '<option value="' . $country . '" ' . selected( $field->field_default, $country, 0 ) . '>' . $country . '</option>';
					}
					?>
				</select>
			</label>
		</p>
		<?php endif; ?>

		<!-- CSS Classes field option -->
		<p class="description description-wide">
			<label for="edit-form-item-css-<?php echo $field->field_id; ?>">
				<?php _e( 'CSS Classes', 'swpm-form-builder' ); ?>
				<span class="swpm-tooltip" title="<?php esc_attr_e( 'About CSS Classes', 'swpm-form-builder' ); ?>" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'swpm-form-builder' ); ?>">(?)</span>
				<br />
				<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" maxlength="255" />
			</label>
		</p>

		<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! in_array( $field->field_key, array( 'user_name', 'password', 'membership_level', 'primary_email', 'submit' ) ) ) : ?>
		<!-- Delete link -->
                <br style="clear: both" /><!-- clear any float so the delete button link is on it's own separate line -->
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=swpm-form-builder&amp;action=delete_field&amp;form=' . $form_nav_selected_id . '&amp;field=' . $field->field_id ), 'delete-field-' . $form_nav_selected_id ) ); ?>" class="swpm-button swpm-delete item-delete submitdelete deletion">
			<?php _e( 'Delete', 'swpm-form-builder' ); ?>
			<span class="swpm-interface-icon swpm-interface-trash"></span>
		</a>
		<?php endif; ?>

		<input type="hidden" name="field_id[<?php echo $field->field_id; ?>]" value="<?php echo $field->field_id; ?>" />
	</div>
	<?php
endforeach;

// This assures all of the <ul> and <li> are closed
if ( $depth > 1 ) {
	while ( $depth > 1 ) {
		echo '</li>
			</ul>';
		$depth--;
	}
}

// Close out last item
echo '</li>';
echo ob_get_clean();
