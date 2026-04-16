<?php

/**
 * SwpmFbUtils class
 */
class SwpmFbUtils {

    public static $countries = array("", "Afghanistan", "Albania", "Algeria", "Andorra",
        "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia",
        "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados",
        "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia",
        "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
        "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde",
        "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros",
        "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d\'Ivoire", "Croatia",
        "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica",
        "Dominican Republic", "East Timor (Timor Timur)", "Ecuador", "Egypt",
        "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini", "Ethiopia",
        "Fiji", "Finland", "France", "Gabon", "Gambia, The", "Georgia", "Germany",
        "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau",
        "Guyana", "Haiti", "Honduras", "Hungary", "Iceland", "India", "Indonesia",
        "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan",
        "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait",
        "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya",
        "Liechtenstein", "Lithuania", "Luxembourg", "Macedonia", "Madagascar",
        "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands",
        "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco",
        "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar", "Namibia",
        "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria",
        "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay",
        "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda",
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino",
        "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles",
        "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands",
        "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname",
        "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan",
        "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia",
        "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates",
        "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu",
        "Vatican City", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe");
    public static $exploits = array('content-type', 'bcc:', 'cc:', 'document.cookie', 'onclick', 'onload', 'javascript', 'alert');
    public static $profanity = array('beastial', 'bestial', 'blowjob', 'clit', 'cock', 'cum', 'cunilingus', 'cunillingus', 'cunnilingus',
        'cunt', 'ejaculate', 'fag', 'felatio', 'fellatio', 'fuck', 'fuk', 'fuks', 'gangbang', 'gangbanged', 'gangbangs', 'hotsex', 'jism',
        'jiz', 'kock', 'kondum', 'kum', 'kunilingus', 'orgasim', 'orgasims', 'orgasm', 'orgasms', 'phonesex', 'phuk', 'phuq', 'porn', 'pussies',
        'pussy', 'spunk', 'xxx');
    public static $spamwords = array('viagra', 'phentermine', 'tramadol', 'adipex', 'advai', 'alprazolam', 'ambien', 'ambian',
        'amoxicillin', 'antivert', 'blackjack', 'backgammon', 'holdem', 'poker', 'carisoprodol', 'ciara', 'ciprofloxacin', 'debt', 'dating', 'porn');

    public static function calculate_spam_score($value) {
        $points = 0;
        // Add up points for each spam hit
        if (preg_match('/(' . implode('|', SwpmFbUtils::$exploits) . ')/i', $value)) {
            $points += 2;
        } elseif (preg_match('/(' . implode('|', SwpmFbUtils::$profanity) . ')/i', $value)) {
            $points += 1;
        } elseif (preg_match('/(' . implode('|', SwpmFbUtils::$spamwords) . ')/i', $value)) {
            $points += 1;
        }
        return $points;
    }

    public static function help($screen) {
        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-getting-started',
            'title' => 'Getting Started',
            'content' => '<ul>
						<li>Click on the "New Form" link to create a new custom form.</li>
                                                <li>Give the form a name (example: my all level registration form).</li>
                                                <li>Chose the type of form to create (you should create a registration form first).</li>
						<li>Select form fields from the box on the left and click a field to add it to your form.</li>
						<li>Edit the information for each form field by clicking on the down arrow.</li>
						<li>Drag and drop the elements to put them in order.</li>
						<li>Click Save Form to save your changes.</li>
					</ul>'
        ));

        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-item-config',
            'title' => 'Form Item Configuration',
            'content' => "<ul>
						<li><em>Name</em> will change the display name of your form input.</li>
						<li><em>Description</em> will be displayed below the associated input.</li>
						<li><em>Validation</em> allows you to select from several of jQuery's Form Validation methods for text inputs. For more about the types of validation, read the <em>Validation</em> section below.</li>
						<li><em>Required</em> is either Yes or No. Selecting 'Yes' will make the associated input a required field and the form will not submit until the user fills this field out correctly.</li>
						<li><em>Options</em> will only be active for Radio and Checkboxes.  This field contols how many options are available for the associated input.</li>
						<li><em>Size</em> controls the width of Text, Textarea, Select, and Date Picker input fields.  The default is set to Medium but if you need a longer text input, select Large.</li>
						<li><em>CSS Classes</em> allow you to add custom CSS to a field.  This option allows you to fine tune the look of the form.</li>
					</ul>"
        ));

        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-validation',
            'title' => 'Validation',
            'content' => "<p>SWPM Form Builder uses the jQuery Form Validation plugin to perform clientside form validation.</p>
					<ul>

						<li><em>Email</em>: makes the element require a valid email.</li>
						<li><em>URL</em>: makes the element require a valid url.</li>
						<li><em>Date</em>: makes the element require a date.
						<li><em>Number</em>: makes the element require a decimal number.</li>
						<li><em>Digits</em>: makes the element require digits only.</li>
						<li><em>Phone</em>: makes the element require a US or International phone number. Most formats are accepted.</li>
						<li><em>Time</em>: choose either 12- or 24-hour time format (NOTE: only available with the Time field).</li>
					</ul>"
        ));

        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-confirmation',
            'title' => 'Confirmation',
            'content' => "<p>Each form allows you to customize the confirmation by selecing either a Text Message, a WordPress Page, or to Redirect to a URL.</p>
					<ul>
						<li><em>Text</em> allows you to enter a custom formatted message that will be displayed on the page after your form is submitted. HTML is allowed here.</li>
						<li><em>Page</em> displays a dropdown of all WordPress Pages you have created. Select one to redirect the user to that page after your form is submitted.</li>
						<li><em>Redirect</em> will only accept URLs and can be used to send the user to a different site completely, if you choose.</li>
					</ul>"
        ));

        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-notification',
            'title' => 'Notification',
            'content' => "<p>Send a customized notification email to the user when the form has been successfully submitted.</p>
					<ul>
						<li><em>From Email Address</em>: the email that will be used as the Reply To email.</li>
						<li><em>Subject</em>: the subject of the email.</li>
						<li><em>Message</em>: additional text that can be displayed in the body of the email. HTML tags are allowed.</li>
					</ul>"
        ));

        $screen->add_help_tab(array(
            'id' => 'swpm-help-tab-tips',
            'title' => 'Tips',
            'content' => "<ul>
						<li>Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.</li>
						<li>Security verification is automatically included on very form. It's a simple logic question and should keep out most, if not all, spam bots.</li>
						<li>There is a hidden spam field, known as a honey pot, that should also help deter potential abusers of your form.</li>
						<li>Nesting is allowed underneath fieldsets and sections.  Sections can be nested underneath fieldsets.  Nesting is not required, however, it does make reorganizing easier.</li>
					</ul>"
        ));
        return $screen;
    }

    public static function is_mandatory_field($key) {
        return in_array($key, array('user_name', 'primary_email', 'password', 'membership_level'));
    }

    public static function handle_file_upload($value, $destination = null) {
        $status = array();
        // Settings - Max Upload Size
        $settings_max_upload = 25;
        if (is_array($value) && $value['size'] > 0) {
            // 25MB is the max size allowed
            $size = apply_filters('swpm_max_file_size', $settings_max_upload); // change file size limit using filter.
            $max_attach_size = $size * 1048576;

            // Display error if file size has been exceeded
            if ($value['size'] > $max_attach_size) {
                $status['error'] = sprintf(SwpmUtils::_("File size exceeds %dMB. Please decrease the file size and try again.", 'swpm-form-builder'), $size);
                return $status;
            }

            // Options array for the wp_handle_upload function. 'test_form' => false
            $upload_overrides = array('test_form' => false);

            // We need to include the file that runs the wp_handle_upload function
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

            // Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
            $uploaded_file = wp_handle_upload($value, $upload_overrides);

            // If the wp_handle_upload call returned a local path for the image
            if (isset($uploaded_file['file'])) {
                // Retrieve the file type from the file name. Returns an array with extension and mime type
                $wp_filetype = wp_check_filetype(basename($uploaded_file['file']), null);

                // Return the current upload directory location
                $wp_upload_dir = wp_upload_dir();

                $media_upload = array(
                    'guid' => $wp_upload_dir['url'] . '/' . basename($uploaded_file['file']),
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                // Insert attachment into Media Library and get attachment ID
                $attach_id = wp_insert_attachment($media_upload, $uploaded_file['file']);

                // Include the file that runs wp_generate_attachment_metadata()
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                // Setup attachment metadata
                $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);

                // Update the attachment metadata
                wp_update_attachment_metadata($attach_id, $attach_data);

                /* $attachments[ 'swpm-' . $field->id ] = $uploaded_file['file'];

                  $data[] = array(
                  'id' 		=> $field->id,
                  'slug' 		=> $field->key,
                  'name' 		=> $field->name,
                  'type' 		=> $field->type,
                  'options' 	=> $field->options,
                  'parent_id' => $field->parent,
                  'value' 	=> $uploaded_file['url']
                  );

                  $body .= sprintf(
                  '<tr>
                  <td><strong>%1$s: </strong></td>
                  <td><a href="%2$s">%2$s</a></td>
                  </tr>' . "\n",
                  stripslashes( $field->name ),
                  $uploaded_file['url']
                  ); */
                return array('attachment_id' => $attach_id);
            }
            $status['error'] = SwpmUtils::_('File not found.');
        } else {
            $status['error'] = SwpmUtils::_('Invalid file.');
        }
        return $status;
    }

    /**
     * Make sure the User Agent string is not a SPAM bot
     *
     * @since 1.3
     */
    public static function isBot() {
        $bots = apply_filters('swpm_blocked_spam_bots', array(
            '<', '>', '&lt;', '%0A', '%0D', '%27', '%3C', '%3E', '%00', 'href',
            'binlar', 'casper', 'cmsworldmap', 'comodo', 'diavol',
            'dotbot', 'feedfinder', 'flicky', 'ia_archiver', 'jakarta',
            'kmccrew', 'nutch', 'planetwork', 'purebot', 'pycurl',
            'skygrid', 'sucker', 'turnit', 'vikspider', 'zmeu',
                )
        );

        $isBot = false;

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? wp_kses_data($_SERVER['HTTP_USER_AGENT']) : '';

        do_action('swpm_isBot', $user_agent, $bots);

        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                $isBot = true;
            }
        }

        return $isBot;
    }

    public static function fb_get_free_level() {
            $encrypted = sanitize_text_field( $_POST['level_identifier'] );
            if ( ! empty( $encrypted ) ) {
                //We already checked using hash that the membership_level value is authentic. Now check the level_identifier against the membership_level.
                $level_value = sanitize_text_field( $_POST['membership_level'] );
                $hash_val = md5( $level_value );
                if ( $hash_val != $encrypted ) {//level_identifier validation failed.
                        $msg  = '<p>Error! Security check failed for membership level identifier validation.</p>';
                        $msg .= '<p>The submitted membership level data does not seem to be authentic.</p>';
                        $msg .= '<p>If you are using caching please empty the cache data and try again.</p>';
                        wp_die( $msg );
                }

                return SwpmPermission::get_instance( $encrypted )->get( 'id' );
            }

            $is_free    = SwpmSettings::get_instance()->get_value( 'enable-free-membership' );
            $free_level = absint( SwpmSettings::get_instance()->get_value( 'free-membership-id' ) );

            return ( $is_free ) ? $free_level : null;
    }
}
