<?php
// Version number to output as meta tag


/*
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; version 2 of the License.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-fieldmeta.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-formmeta.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-profile-formmeta.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-registration-formmeta.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-form.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-admin-custom-fields.php';
require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-form-custom.php';

// Swpm Form Builder class
class Swpm_Form_Builder {

	/**
	 * The DB version. Used for SQL install and upgrades.
	 *
	 * Should only be changed when needing to change SQL
	 * structure or custom capabilities.
	 *
	 * @since 1.0
	 * @var string
	 * @access protected
	 */
	protected $swpm_db_version = '1.0';

	/**
	 * Flag used to add scripts to front-end only once
	 *
	 * @since 1.0
	 * @var string
	 * @access protected
	 */
	protected $add_scripts = false;

	/**
	 * Admin page menu hooks
	 *
	 * @since 2.7.2
	 * @var array
	 * @access private
	 */
	private $_admin_pages         = array();
	private $_change_level_params = array();

	/**
	 * Flag used to display post_max_vars error when saving
	 *
	 * @since 2.7.6
	 * @var string
	 * @access protected
	 */
	protected $post_max_vars = false;
	protected $form;

	/**
	 * Array of field keys that must be unique in a form
	 *
	 * @since 4.8.6
	 * @var array
	 * @access public
	 */
	public $uniq_field_keys = array(
		'primary_phone' => array( 'msg' => 'There can only be one "Primary Phone" type field in a form. If you want to collect additional phone numbers, then add a custom text field to the form to collect it.' ),
		'primary_address' => array( 'msg' => 'There can only be one "Primary Address" type field in a form. If you want to collect additional address data, then add a custom textarea or text fields to the form to collect it.'),
                'profile_image' => array( 'msg' => 'There can only be one "Profile Image" type field in a form. If you want to collect an image from the member, use a custom "File Upload" type field.' ),
	);


	/**
	 * Constructor. Register core filters and actions.
	 *
	 * @access public
	 */
	public function __construct() {
		global $wpdb;
		$this->field_table_name  = $wpdb->prefix . 'swpm_form_builder_fields';
		$this->form_table_name   = $wpdb->prefix . 'swpm_form_builder_forms';
		$this->custom_table_name = $wpdb->prefix . 'swpm_form_builder_custom';

		if ( class_exists( 'SimpleWpMembership' ) ) {
			// Add suffix to load dev files
			$this->load_dev_files = ( defined( 'SWPMFB_SCRIPT_DEBUG' ) && SWPMFB_SCRIPT_DEBUG ) ? '' : '.min';

			// Saving functions
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			// Build options and settings pages.
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

			// Register AJAX functions
			$actions = array(
				// Form Builder
				'sort_field',
				'create_field',
				'delete_field',
				'form_settings',
					// Media button
			);

			// Add all AJAX functions
			foreach ( $actions as $name ) {
				add_action( "wp_ajax_swpm_form_builder_$name", array( &$this, "ajax_$name" ) );
			}

			// Adds custom avatar handling filter
			add_filter( 'get_avatar', array( &$this, 'custom_avatar_filter' ), 10, 5 );

			// Adds a Dashboard widget
			// add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget'));
			// Adds a Settings link to the Plugins page
			add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

			// Display update messages
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );

			// Print meta keyword
			add_action( 'wp_head', array( &$this, 'add_meta_keyword' ) );

			add_action( 'init', array( &$this, 'init' ), 10 );

			// Add CSS to the front-end
			add_action( 'wp_enqueue_scripts', array( &$this, 'css' ) );
			add_filter( 'swpm_registration_form_override', array( &$this, 'registration_override' ), 10, 2 );
			add_filter( 'swpm_profile_form_override', array( &$this, 'profile_override' ) );
			add_filter( 'swpm_admin_custom_fields', array( &$this, 'admin_profile_override' ), 10, 2 );
			add_filter( 'swpm_get_member_field_by_id', array( &$this, 'get_member_field_by_id' ), 10, 3 );
			add_filter( 'swpm_after_registration_redirect_url', array( $this, 'handle_after_rego_redirect_for_email_activation' ) );
			add_action( 'swpm_admin_edit_custom_fields', array( &$this, 'admin_save_custom_fields' ), 10, 2 );
			add_action( 'swpm_membership_level_changed', array( $this, 'handle_membership_level_changed_action' ) );
		}
	}

	private function _transfer_custom_fields_data( $oldLvlForm, $newLvlForm ) {
		foreach ( $oldLvlForm->formmeta->fields as $old_item ) {
			if ( $old_item->key === 'custom' ) {
				foreach ( $newLvlForm->formmeta->fields as $new_item ) {
					if ( $new_item->key === 'custom' && $new_item->type === $old_item->type && $new_item->name === $old_item->name ) {
						// we have custom field that seems to be matching the one from new level
						// let's transfer the data
						$old_field_id = $old_item->get_unique_value_id();
						$value        = $oldLvlForm->get_custom_field( $old_field_id );
						$new_field_id = $new_item->get_unique_value_id();
						if ( isset( $value ) ) {
							$newLvlForm->set_custom_field( $new_field_id, $value );
						}
					}
				}
			}
		}
		$newLvlForm->save();
	}

	public function transfer_custom_fields() {
		$params = $this->_change_level_params;
		if ( empty( $params ) ) {
			// no required data set
			return false;
		}
		if ( ! isset( $params['from_level'] ) || ! isset( $params['to_level'] ) || ! isset( $params['member_id'] ) ) {
			// no required data set
			return false;
		}
		if ( $params['from_level'] === $params['to_level'] ) {
			// no membership level change
			return false;
		}

		$oldLvlFormId = SwpmFbFormmeta::get_registration_form_id_by_level_or_default( $params['from_level'] );
		$oldCustom    = new SwpmFbFormCustom();
		$oldCustom->load_form( $oldLvlFormId );
		$oldCustom->init( $params['member_id'] );

		$newLvlFormId = SwpmFbFormmeta::get_registration_form_id_by_level_or_default( $params['to_level'] );
		$newCustom    = new SwpmFbFormCustom();
		$newCustom->load_form( $newLvlFormId );
		$newCustom->init( $params['member_id'] );

		$this->_transfer_custom_fields_data( $oldCustom, $newCustom );

		$oldLvlFormId = SwpmFbFormmeta::get_profile_form_id_by_level_or_default( $params['from_level'] );
		$oldCustom    = new SwpmFbFormCustom();
		$oldCustom->load_form( $oldLvlFormId );
		$oldCustom->init( $params['member_id'] );

		$newLvlFormId = SwpmFbFormmeta::get_profile_form_id_by_level_or_default( $params['to_level'] );
		$newCustom    = new SwpmFbFormCustom();
		$newCustom->load_form( $newLvlFormId );
		$newCustom->init( $params['member_id'] );

		$this->_transfer_custom_fields_data( $oldCustom, $newCustom );
	}

	public function handle_membership_level_changed_action( $params ) {
		$this->_change_level_params = $params;
		$this->transfer_custom_fields();
		// we have to add action in case this is member edit from admin dashboard
		add_action( 'swpm_fb_admin_save_custom_fields', array( $this, 'transfer_custom_fields' ) );
	}

	public function custom_avatar_filter( $avatar, $id_or_email, $size, $default, $alt ) {

		// If provided an email and it doesn't exist as WP user, return avatar since there can't be a custom avatar
		$email = is_object( $id_or_email ) ? $id_or_email->comment_author_email : $id_or_email;

		if ( is_email( $email ) && ! email_exists( $email ) ) {
			return $avatar;
		}

		if ( ! is_email( $email ) ) { // this is user ID, not email
			// let's try to get WP user email by ID
			$user_info = get_userdata( $email );
			if ( $user_info ) {
				$email = $user_info->user_email;
			} else {
				// No wp user record found so return the avatar.
				return $avatar;
			}
		}

		// now let's find if user with this email exists in SWPM users
		$swpm_user = SwpmMemberUtils::get_user_by_email( $email );
		if ( $swpm_user ) {
			$attachment_id = SwpmMemberUtils::get_member_field_by_id( $swpm_user->member_id, 'profile_image' );
		}

		if ( isset( $attachment_id ) ) {
			$custom_avatar = wp_get_attachment_url( $attachment_id );
		} else {
			$custom_avatar = false;
		}

		if ( $custom_avatar ) {
			$return = '<img style="max-width: 128px; max-height: 128px;" src="' . $custom_avatar . '" width="' . $size . '" height="' . $size . '" alt="' . $alt . '" />';
		} elseif ( $avatar ) {
			$return = $avatar;
		} else {
			$return = '<img src="' . $default . '" width="' . $size . '" height="' . $size . '" alt="' . $alt . '" />';
		}
		return $return;
	}

	public function get_member_field_by_id( $output, $id, $field ) {
		global $wpdb;

		$sql   = $wpdb->prepare(
			'SELECT  `value`
               FROM  `' . $wpdb->prefix . 'swpm_form_builder_custom` AS C
               LEFT JOIN  `' . $this->field_table_name . '` AS F ON ( C.field_id = F.field_id )
               WHERE C.user_id =%d AND F.field_name = %s',
			$id,
			$field
		);
		$value = $wpdb->get_var( $sql );
		return empty( $value ) ? $output : $value;
	}

	public function admin_save_custom_fields( $status, $member ) {
		$custom = new SwpmFbAdminCustomFields();
		if ( isset( $member['membership_level'] ) && isset( $member['member_id'] ) ) {
			$custom->init( $member['membership_level'] );
			$error = $custom->save( $member['member_id'] );
			do_action( 'swpm_fb_admin_save_custom_fields' );
			if ( ! empty( $error ) ) {
				return $error;
			}
		}
		return $status;
	}

	public function admin_profile_override( $output, $membership_level ) {
		$custom = new SwpmFbAdminCustomFields();
		$custom->init( $membership_level );
		return $custom->admin_ui();
	}

	public function profile_override( $output = '' ) {
		if ( SwpmFbForm::is_form_submitted() && ! $this->form->is_fatal() && $this->form->is_valid() ) {
			// When the profile edit form is submitted successfully, just show the success message.
			$output = $this->confirmation_text();
		} else {
			// Otherwise show the custom profile edit form.
			$this->form->init_by_level_for_profile();
			require SWPM_FORM_BUILDER_PATH . 'includes/form-output.php';
		}
		return $output;
	}

	public function registration_override( $output, $membership_level ) {

                $settings_configs  = SwpmSettings::get_instance();
                $free_rego_enabled = $settings_configs->get_value( 'enable-free-membership' );

		$paid_registration = SwpmUtils::is_paid_registration();
		if ( ! $paid_registration ) {
			// This is NOT a paid registration (request for free rego or level specific form shortcode).
			// Lets check if this is a level specific form shortcode
			$level_specific_form = false;
			if ( ! empty( $membership_level ) ) {
				$level_specific_form = true;
			}

			// Lets check to see if free registration is allowed on this site
			if ( ! $free_rego_enabled && ! $level_specific_form ) {
				// This site is not using the free level and this is not a level specific form shortcode. Show error message.
				// Show the appropriate message
				$joinuspage_url          = $settings_configs->get_value( 'join-us-page-url' );
				$joinuspage_link         = '<a href="' . $joinuspage_url . '">Join us</a>';
				$free_rego_disabled_msg  = '<p>';
				$free_rego_disabled_msg .= SwpmUtils::_( 'Free membership is disabled on this site. Please make a payment from the ' . $joinuspage_link . ' page to pay for a premium membership.' );
				$free_rego_disabled_msg .= '</p><p>';
				$free_rego_disabled_msg .= SwpmUtils::_( 'You will receive a unique link via email after the payment. You will be able to use that link to complete the premium membership registration.' );
				$free_rego_disabled_msg .= '</p>';
				return $free_rego_disabled_msg;
			}
		}

                if ($free_rego_enabled && empty ($membership_level)){
                    //This is an error condition. If free membership level is enabled, there should be a VALID membership level ID configured in the settings.
                    wp_die( 'Error! You have enabled free membership on this site but you did not entered a valid membership level ID in the "Free Membership Level ID" field of the settings menu!' );
                }

		if ( $membership_level == 1 || $membership_level == md5( 1 ) ) {
                    //Level 1 is reserved for content protection settings.
                    wp_die( 'Invalid membership level!' );
		}

		if ( SwpmFbForm::is_form_submitted() && ! $this->form->is_fatal() && $this->form->is_valid() ) {
			if ( defined( 'SWPM_FB_EMAIL_ACTIVATION' ) ) {
				$email_act_msg  = '<div class="swpm-registration-success-msg">';
				$email_act_msg .= SwpmUtils::_( 'You need to confirm your email address. Please check your email and follow instructions to complete your registration.' );
				$email_act_msg .= '</div>';
                                $email_act_msg = apply_filters( 'swpm_registration_email_activation_msg', $email_act_msg );//Can be added to the custom messages addon.
				$output = $email_act_msg;
			} else {
				$output = $this->confirmation_text();
			}
		} else {
			$this->form->init_by_level_for_registration( $membership_level );
			require SWPM_FORM_BUILDER_PATH . 'includes/form-output.php';
		}
		return $output;
	}

	public function admin_menu() {
		$this->add_admin();
		$this->additional_plugin_setup();
	}

	public function admin_init() {
		$this->save_add_new_form();
		$this->save_update_form();
		$this->save_trash_delete_form();
		$this->save_copy_form();
		$this->save_settings();
	}

	public function init() {
		$this->form = new SwpmFbForm();
		$this->languages();
		$this->process_submitted_form();
		$this->confirmation_redirect();

		$page = filter_input( INPUT_GET, 'page' );
		$swpm_members_page_slug = 'simple_wp_membership';
		$swpm_fb_page_slug = 'swpm-form-builder';

		if ( is_admin() && isset( $page ) && ( stripos( $page, $swpm_fb_page_slug ) !== false ) ) {
                    // Load the admin scripts in form builder's admin interface only
                    $this->admin_scripts();

                } elseif ( is_admin() && isset( $page ) && ( stripos( $page, $swpm_members_page_slug ) !== false ) ) {
                    // Register or load the required admin scripts in the core SWPM plugin's members admin interface only.
                    wp_register_script( 'swpm-ckeditor', SWPM_FORM_BUILDER_URL . '/js/ckeditor/ckeditor.js', array( 'jquery' ), SWPMFB_VERSION, true );
                    //The wp_enqueue_script() call for this script is done by the field itself. So it is only enqueued if the field exists on the form.

                } elseif ( ! is_admin() ) {
                    // Load the normal scripts on front-end
                    $this->scripts();
		}
	}

	/**
	 * Allow for additional plugin code to be run during admin_init
	 * which is not available during the plugin __construct()
	 *
	 * @since 2.7
	 */
	public function additional_plugin_setup() {

		$page_main = $this->_admin_pages['swpm'];

		if ( ! get_option( 'swpm_dashboard_widget_options' ) ) {
			$widget_options['swpm_dashboard_recent_entries'] = array(
				'items' => 5,
			);
			update_option( 'swpm_dashboard_widget_options', $widget_options );
		}
	}

	/**
	 * Output plugin version number to help with troubleshooting
	 *
	 * @since 2.7.5
	 */
	public function add_meta_keyword() {
		// Get global settings
		$swpm_settings = get_option( 'swpm-settings' );

		// Settings - Disable meta tag version
		$settings_meta = isset( $swpm_settings['show-version'] ) ? '' : '<!-- <meta name="swpm" version="' . SWPMFB_VERSION . '" /> -->' . "\n";

		echo apply_filters( 'swpm_show_version', $settings_meta );
	}

	/**
	 * Load localization file
	 *
	 * @since 2.7
	 */
	public function languages() {
		load_plugin_textdomain( 'swpm-form-builder', false, 'swpm-form-builder/languages' );
	}

	public function include_forms_list() {
		global $forms_list;

		// Load the Forms List class
		require_once SWPM_FORM_BUILDER_PATH . 'classes/class.swpm-fb-forms-list.php';
		$forms_list = new SwpmFbFormsList();
	}

	/**
	 * Add Settings link to Plugins page
	 *
	 * @since 1.8
	 * @return $links array Links to add to plugin name
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$links[] = '<a href="admin.php?page=swpm-form-builder">' . __( 'Settings', 'swpm-form-builder' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Adds the dashboard widget
	 *
	 * @since 2.7
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget( 'swpm-dashboard', __( 'Recent SWPM Form Builder Entries', 'swpm-form-builder' ), array( &$this, 'dashboard_widget' ), array( &$this, 'dashboard_widget_control' ) );
	}

	/**
	 * Displays the dashboard widget content
	 *
	 * @since 2.7
	 */
	public function dashboard_widget() {
		global $wpdb;

		// Get the date/time format that is saved in the options table
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$widgets     = get_option( 'swpm_dashboard_widget_options' );
		$total_items = isset( $widgets['swpm_dashboard_recent_entries'] ) && isset( $widgets['swpm_dashboard_recent_entries']['items'] ) ?
				absint( $widgets['swpm_dashboard_recent_entries']['items'] ) : 5;

		$forms = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->form_table_name}" );

		if ( ! $forms ) :
			echo sprintf(
				'<p>%1$s <a href="%2$s">%3$s</a></p>',
				__( 'You currently do not have any forms.', 'swpm-form-builder' ),
				esc_url( admin_url( 'admin.php?page=swpm-add-new' ) ),
				__( 'Get started!', 'swpm-form-builder' )
			);

			return;
		endif;

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT forms.form_title, entries.entries_id, entries.form_id, '
						. "entries.sender_name, entries.sender_email, entries.date_submitted FROM $this->form_table_name "
						. "AS forms INNER JOIN $this->entries_table_name AS entries ON entries.form_id = forms.form_id ORDER BY "
						. 'entries.date_submitted DESC LIMIT %d',
				$total_items
			)
		);

		if ( ! $entries ) :
			echo sprintf( '<p>%1$s</p>', __( 'You currently do not have any entries.', 'swpm-form-builder' ) );
		else :

			$content = '';

			foreach ( $entries as $entry ) :

				$content .= sprintf(
					'<li><a href="%1$s">%4$s</a> via <a href="%2$s">%5$s</a> <span class="rss-date">%6$s</span><cite>%3$s</cite></li>',
					esc_url(
						add_query_arg(
							array(
								'action' => 'view',
								'entry'  => absint( $entry->entries_id ),
							),
							admin_url( 'admin.php?page=swpm-entries' )
						)
					),
					esc_url( add_query_arg( 'form-filter', absint( $entry->form_id ), admin_url( 'admin.php?page=swpm-entries' ) ) ),
					esc_html( $entry->sender_name ),
					esc_html( $entry->sender_email ),
					esc_html( $entry->form_title ),
					date( "$date_format $time_format", strtotime( $entry->date_submitted ) )
				);

			endforeach;

			echo "<div class='rss-widget'><ul>$content</ul></div>";

		endif;
	}

	/**
	 * Displays the dashboard widget form control
	 *
	 * @since 2.7
	 */
	public function dashboard_widget_control() {
		if ( ! $widget_options = get_option( 'swpm_dashboard_widget_options' ) ) {
			$widget_options = array();
		}

		if ( ! isset( $widget_options['swpm_dashboard_recent_entries'] ) ) {
			$widget_options['swpm_dashboard_recent_entries'] = array();
		}

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['swpm-widget-recent-entries'] ) ) {
			$number = absint( $_POST['swpm-widget-recent-entries']['items'] );
			$widget_options['swpm_dashboard_recent_entries']['items'] = $number;
			update_option( 'swpm_dashboard_widget_options', $widget_options );
		}

		$number = isset( $widget_options['swpm_dashboard_recent_entries']['items'] ) ? (int) $widget_options['swpm_dashboard_recent_entries']['items'] : '';

		echo sprintf(
			'<p>
			<label for="comments-number">%1$s</label>
			<input id="comments-number" name="swpm-widget-recent-entries[items]" type="text" value="%2$d" size="3" />
			</p>',
			__( 'Number of entries to show:', 'swpm-form-builder' ),
			$number
		);
	}

	/**
	 * Register contextual help. This is for the Help tab dropdown
	 *
	 * @since 1.0
	 */
	public function help() {
		$screen = get_current_screen();
		SwpmFbUtils::help( $screen );
	}

	/**
	 * Adds the Screen Options tab to the Entries screen
	 *
	 * @since 1.0
	 */
	public function screen_options() {
		$screen = get_current_screen();

		$page_main = $this->_admin_pages['swpm'];

		switch ( $screen->id ) {
			case $page_main:
				if ( isset( $_REQUEST['form'] ) ) :
					add_screen_option(
						'layout_columns',
						array(
							'max'     => 2,
							'default' => 2,
						)
					);
				else :
					add_screen_option(
						'per_page',
						array(
							'label'   => __( 'Forms per page', 'swpm-form-builder' ),
							'default' => 20,
							'option'  => 'swpm_forms_per_page',
						)
					);
				endif;

				break;
		}
	}

	/**
	 * Saves the Screen Options
	 *
	 * @since 1.0
	 */
	public function save_screen_options( $status, $option, $value ) {
		if ( $option == 'swpm_forms_per_page' ) {
			return $value;
		}
	}

	/**
	 * Add meta boxes to form builder screen
	 *
	 * @since 1.8
	 */
	public function add_meta_boxes() {
		global $current_screen;

		$page_main = $this->_admin_pages['swpm'];

		if ( $current_screen->id == $page_main && isset( $_REQUEST['form'] ) ) {
			add_meta_box( 'swpm_form_items_meta_box', __( 'Form Items', 'swpm-form-builder' ), array( &$this, 'meta_box_form_items' ), $page_main, 'side', 'high' );
		}
	}

	/**
	 * Output for Form Items meta box
	 *
	 * @since 1.8
	 */
	public function meta_box_form_items() {
		include_once SWPM_FORM_BUILDER_PATH . 'views/button_palette_metabox.php';
	}

	/**
	 * Queue plugin scripts for sorting form fields
	 *
	 * @since 1.0
	 */
	public function admin_scripts() {

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'jquery-form-validation', SWPM_FORM_BUILDER_URL . '/js/jquery.validate.min.js', array( 'jquery' ), SWPMFB_VERSION, true );
		wp_enqueue_script( 'swpm-admin', SWPM_FORM_BUILDER_URL . "/js/swpm-admin$this->load_dev_files.js", array( 'jquery', 'jquery-form-validation' ), SWPMFB_VERSION, true );
		wp_enqueue_script( 'nested-sortable', SWPM_FORM_BUILDER_URL . "/js/jquery.ui.nestedSortable.js", array( 'jquery', 'jquery-ui-sortable' ), SWPMFB_VERSION, true );

		wp_enqueue_style( 'swpm-form-builder-style', SWPM_FORM_BUILDER_URL . "/css/swpm-form-builder-admin$this->load_dev_files.css", array(), SWPMFB_VERSION );

		wp_localize_script( 'swpm-admin', 'SwpmAdminPages', array( 'swpm_pages' => $this->_admin_pages ) );

		wp_localize_script( 'swpm-admin', 'swpmFbVars', array( 'uniq_fields' => $this->uniq_field_keys ) );
	}

	/**
	 * Queue form validation scripts
	 *
	 * Scripts loaded in form-output.php, when field is present:
	 *  jQuery UI date picker
	 *  CKEditor
	 *
	 * @since 1.0
	 */
	public function scripts() {
		// Make sure scripts are only added once via shortcode
		$this->add_scripts = true;

		wp_register_script( 'jquery-form-validation', SWPM_FORM_BUILDER_URL . '/js/jquery.validate.min.js', array( 'jquery' ), SWPMFB_VERSION, true );
		wp_register_script( 'swpm-form-builder-validation', SWPM_FORM_BUILDER_URL . "/js/swpm-validation.js", array( 'jquery', 'jquery-form-validation' ), SWPMFB_VERSION, true );
		wp_register_script( 'swpm-form-builder-metadata', SWPM_FORM_BUILDER_URL . '/js/jquery.metadata.js', array( 'jquery', 'jquery-form-validation' ), SWPMFB_VERSION, true );
		wp_register_script( 'swpm-ckeditor', SWPM_FORM_BUILDER_URL . '/js/ckeditor/ckeditor.js', array( 'jquery' ), SWPMFB_VERSION, true );

		wp_localize_script( 'jquery-form-validation', 'swpmFbValidation', array( 'str' => array( 'checkOne' => __( 'Please check at least one.', 'simple-membership' ) ) ) );

		wp_enqueue_script( 'jquery-form-validation' );
		wp_enqueue_script( 'swpm-form-builder-validation' );
		wp_enqueue_script( 'swpm-form-builder-metadata' );

		$locale       = get_locale();
		$translations = array(
			'cs_CS', // Czech
			'de_DE', // German
			'el_GR', // Greek
			'en_US', // English (US)
			'en_AU', // English (AU)
			'en_GB', // English (GB)
			'es_ES', // Spanish
			'fr_FR', // French
			'he_IL', // Hebrew
			'hu_HU', // Hungarian
			'id_ID', // Indonseian
			'it_IT', // Italian
			'ja_JP', // Japanese
			'ko_KR', // Korean
			'nl_NL', // Dutch
			'pl_PL', // Polish
			'pt_BR', // Portuguese (Brazilian)
			'pt_PT', // Portuguese (European)
			'ro_RO', // Romanian
			'ru_RU', // Russian
			'sv_SE', // Swedish
			'tr_TR', // Turkish
			'zh_CN', // Chinese
			'zh_TW', // Chinese (Taiwan)
		);

		// Load localized vaidation and datepicker text, if translation files exist
		if ( in_array( $locale, $translations ) ) {
			wp_register_script( 'swpm-validation-i18n', SWPM_FORM_BUILDER_URL . "/js/i18n/validate/messages-$locale.js", array( 'jquery-form-validation' ), '1.9.0', true );
			wp_register_script( 'swpm-datepicker-i18n', SWPM_FORM_BUILDER_URL . "/js/i18n/datepicker/datepicker-$locale.js", array( 'jquery-ui-datepicker' ), '1.0', true );

			wp_enqueue_script( 'swpm-validation-i18n' );
		}
		// Otherwise, load English translations
		else {
			wp_register_script( 'swpm-validation-i18n', SWPM_FORM_BUILDER_URL . '/js/i18n/validate/messages-en_US.js', array( 'jquery-form-validation' ), '1.9.0', true );
			wp_register_script( 'swpm-datepicker-i18n', SWPM_FORM_BUILDER_URL . '/js/i18n/datepicker/datepicker-en_US.js', array( 'jquery-ui-datepicker' ), '1.0', true );

			wp_enqueue_script( 'swpm-validation-i18n' );
		}
	}

	/**
	 * Add form CSS to wp_head
	 *
	 * @since 1.0
	 */
	public function css() {
		$swpm_settings = get_option( 'swpm-settings' );
		wp_register_style( 'swpm-jqueryui-css', apply_filters( 'swpm-date-picker-css', SWPM_FORM_BUILDER_URL . '/css/smoothness/jquery-ui-1.10.3.min.css' ), array(), SWPMFB_VERSION );
		// check if Light CSS option enabled
		$is_light = SwpmFbSettings::get_setting( 'enable-light-css' ) !== false ? '-light' : '';
		wp_register_style( 'swpm-form-builder-css', apply_filters( 'swpm-form-builder-css', SWPM_FORM_BUILDER_URL . "/css/swpm-form-builder$is_light$this->load_dev_files.css" ), array(), SWPMFB_VERSION );

		// Settings - Always load CSS
		// if (isset($swpm_settings['always-load-css'])) {
		wp_enqueue_style( 'swpm-form-builder-css' );
		wp_enqueue_style( 'swpm-jqueryui-css' );
		return;
		// }
		// Settings - Disable CSS
		if ( isset( $swpm_settings['disable-css'] ) ) {
			return;
		}

		// Get active widgets
		$widget = is_active_widget( false, false, 'swpm_widget' );

		// If no widget is found, test for shortcode
		if ( empty( $widget ) ) {
			// If WordPress 3.6, use internal function. Otherwise, my own
			if ( function_exists( 'has_shortcode' ) ) {
				global $post;

				// If no post exists, exit
				if ( ! $post ) {
					return;
				}

				if ( ! has_shortcode( $post->post_content, 'swpm' ) ) {
					return;
				}
			} elseif ( ! $this->has_shortcode( 'swpm' ) ) {
				return;
			}
		}

		wp_enqueue_style( 'swpm-form-builder-css' );
		wp_enqueue_style( 'swpm-jqueryui-css' );
	}

	/**
	 * Save new forms on the form builder > Add New Form page
	 *
	 * @access public
	 * @since 2.8.1
	 * @return void
	 */
	public function save_add_new_form() {
		$page   = filter_input( INPUT_GET, 'page' );
		$action = filter_input( INPUT_POST, 'action' );

		if ( 'swpm-form-builder' !== $page || 'create_form' !== $action ) {
			return;
		}
		$level = absint( filter_input( INPUT_POST, 'form_for_level' ) );
		$type  = absint( filter_input( INPUT_POST, 'form_type' ) );

		check_admin_referer( 'create_form' );
		$reg_form_id = SwpmFbFormmeta::get_registration_form_id_by_level( $level );
		if ( $type == SwpmFbFormCustom::REGISTRATION ) {
			if ( ! empty( $reg_form_id ) ) { // Rego form for given level exists so no need to auto-create the corresponding profile form.
				return;
			}
			$form = new SwpmFbRegistrationFormmeta();
		} elseif ( $type == SwpmFbFormCustom::PROFILE ) {
			$edit_form = SwpmFbFormmeta::get_profile_form_id_by_level( $level );

			if ( ! empty( $edit_form ) ) { // edit form for given level exists
				return;
			}
			$form = new SwpmFbProfileFormmeta();

			if ( empty( $reg_form_id ) ) {
				return;
			}
			$form->load( $reg_form_id, true );
		}

		$form->key       = sanitize_title( filter_input( INPUT_POST, 'form_title' ) );
		$form->title     = esc_html( filter_input( INPUT_POST, 'form_title' ) );
		$form->for_level = $level;
		$success         = $form->create();
		// Redirect to keep the URL clean (use AJAX in the future?)
		if ( $success ) {
			if ( $type == SwpmFbFormCustom::REGISTRATION ) {
				// Lets auto create the corresponding profile edit form also
				$edit_form = new SwpmFbProfileFormmeta();
				$edit_form->load( $form->id, true );

				// Lets remove Verification and Secret fields as those are not required for Profile form
				foreach ( $edit_form->fields as $key => $field ) {
					if ( $field->type == 'verification' || $field->type == 'secret' ) {
						unset( $edit_form->fields[ $key ] );
					}
				}
				$edit_form->fields = array_values( $edit_form->fields );

				$edit_form->title = 'Profile edit form for level: ' . $edit_form->for_level; // Set a meaningful title for this auto-created form.
				$edit_form->create();
			}
			wp_redirect( 'admin.php?page=swpm-form-builder&action=edit&form=' . $form->id );
			exit();
		}
	}

	private function get_submitted_form_definition( &$form_id ) {
		$form_id    = absint( $_REQUEST['form_id'] );
		$form_key   = sanitize_title( $_REQUEST['form_title'], $form_id );
		$form_title = $_REQUEST['form_title'];

		$form_notification_message    = isset( $_REQUEST['form_notification_message'] ) ? format_for_editor( $_REQUEST['form_notification_message'] ) : '';
		$form_notification_subject    = isset( $_REQUEST['form_notification_subject'] ) ? $_REQUEST['form_notification_subject'] : '';
		$form_notification_email_name = isset( $_REQUEST['form_notification_email_name'] ) ? $_REQUEST['form_notification_email_name'] : '';
		$form_notification_setting    = isset( $_REQUEST['form_notification_setting'] ) ? $_REQUEST['form_notification_setting'] : '';
		$form_success_type            = $_REQUEST['form_success_type'];
		$form_label_alignment         = 'left'; // $_REQUEST['form_label_alignment'];
		// Add confirmation based on which type was selected
		switch ( $form_success_type ) {
			case 'text':
				$form_success_message = format_for_editor( $_REQUEST['form_success_message_text'] );
				break;
			case 'page':
				$form_success_message = $_REQUEST['form_success_message_page'];
				break;
			case 'redirect':
				$form_success_message = $_REQUEST['form_success_message_redirect'];
				break;
		}

		return array(
			'form_key'                     => $form_key,
			'form_title'                   => $form_title,
			'form_success_type'            => $form_success_type,
			'form_success_message'         => $form_success_message,
			'form_notification_setting'    => $form_notification_setting,
			'form_notification_email_name' => $form_notification_email_name,
			'form_notification_subject'    => $form_notification_subject,
			'form_notification_message'    => $form_notification_message,
			'form_label_alignment'         => $form_label_alignment,
		);
	}

	private function get_submitted_field_details( $id ) {
		$id = absint( $id );

		$field_name = ( isset( $_REQUEST[ 'field_name-' . $id ] ) ) ? trim( $_REQUEST[ 'field_name-' . $id ] ) : '';
		// $field_key = sanitize_key(sanitize_title($field_name, $id));
		$field_desc       = ( isset( $_REQUEST[ 'field_description-' . $id ] ) ) ? trim( $_REQUEST[ 'field_description-' . $id ] ) : '';
		$field_options    = ( isset( $_REQUEST[ 'field_options-' . $id ] ) ) ? serialize( array_map( 'trim', $_REQUEST[ 'field_options-' . $id ] ) ) : '';
		$field_validation = ( isset( $_REQUEST[ 'field_validation-' . $id ] ) ) ? $_REQUEST[ 'field_validation-' . $id ] : '';
		$field_required   = ( isset( $_REQUEST[ 'field_required-' . $id ] ) ) ? $_REQUEST[ 'field_required-' . $id ] : '';
		$field_size       = ( isset( $_REQUEST[ 'field_size-' . $id ] ) ) ? $_REQUEST[ 'field_size-' . $id ] : '';
		$field_css        = ( isset( $_REQUEST[ 'field_css-' . $id ] ) ) ? $_REQUEST[ 'field_css-' . $id ] : '';
		$field_layout     = ( isset( $_REQUEST[ 'field_layout-' . $id ] ) ) ? $_REQUEST[ 'field_layout-' . $id ] : '';
		$field_default    = ( isset( $_REQUEST[ 'field_default-' . $id ] ) ) ? trim( $_REQUEST[ 'field_default-' . $id ] ) : '';
		$field_readonly   = ( isset( $_REQUEST[ 'field_readonly-' . $id ] ) ) ? $_REQUEST[ 'field_readonly-' . $id ] : '';
		$field_adminonly  = ( isset( $_REQUEST[ 'field_adminonly-' . $id ] ) ) ? $_REQUEST[ 'field_adminonly-' . $id ] : '';

		if ( empty( $field_readonly ) || $field_readonly == '0' ) {
			$field_readonly = 0;
		} else {
			$field_readonly = 1;
		}

		if ( empty( $field_adminonly ) || $field_adminonly == '0' ) {
			$field_adminonly = 0;
		} else {
			$field_adminonly = 1;
		}

		return array(
			'field_name'        => $field_name,
			'field_description' => $field_desc,
			'field_options'     => $field_options,
			'field_validation'  => $field_validation,
			'field_required'    => $field_required,
			'field_size'        => $field_size,
			'field_css'         => $field_css,
			'field_layout'      => $field_layout,
			'field_default'     => $field_default,
			'field_readonly'    => $field_readonly,
			'field_adminonly'   => $field_adminonly,
		);
	}

	/**
	 * Save the form
	 *
	 * @access public
	 * @since 2.8.1
	 * @return void
	 */
	public function save_update_form() {
		global $wpdb;
		$page   = filter_input( INPUT_GET, 'page' );
		$action = filter_input( INPUT_POST, 'action' );
		if ( empty( $page ) || empty( $action ) ) {
			return;
		}

		if ( 'swpm-form-builder' !== $page ) {
			return;
		}

		if ( 'update_form' !== $_REQUEST['action'] ) {
			return;
		}

		check_admin_referer( 'swpm_update_form' );
		$form_id   = 0;
		$form_data = $this->get_submitted_form_definition( $form_id );

		$where = array( 'form_id' => $form_id );

		// Update form details
		$wpdb->update( $this->form_table_name, $form_data, $where );

		$field_ids = array();

		// Get max post vars, if available. Otherwise set to 1000
		$max_post_vars = ( ini_get( 'max_input_vars' ) ) ? intval( ini_get( 'max_input_vars' ) ) : 1000;

		// Set a message to be displayed if we've reached a limit
		if ( count( $_POST, COUNT_RECURSIVE ) > $max_post_vars ) {
			$this->post_max_vars = true;
		}

		foreach ( $_REQUEST['field_id'] as $fields ) :
			$field_ids[] = $fields;
		endforeach;

		// Initialize field sequence
		$field_sequence = 0;
		/*
				$query = $wpdb->prepare("SELECT form_id FROM  $this->form_table_name WHERE form_type= 1 AND `form_membership_level` = "
		  . " (SELECT `form_membership_level` FROM $this->form_table_name WHERE `form_id` = %d)", $form_id);
		  $edit_form = $wpdb->get_var($query);
		 */
		$form = new SwpmFbFormmeta();
		$form->load( $form_id );
		$edit_profile_form_id = 0;
		if ( $form->type == SwpmFbFormCustom::REGISTRATION ) {
			$edit_profile_form_id = SwpmFbFormmeta::get_profile_form_id_by_level( $form->for_level );
		}

		// Loop through each field and update
		foreach ( $field_ids as $id ) :
			$field_data = $this->get_submitted_field_details( $id );

			$where = array(
				'form_id'  => $form_id,
				'field_id' => $id,
			);

			// Update all fields
			$wpdb->update( $this->field_table_name, $field_data, $where );
			// do this only when registration form is being saved.
			if ( ! empty( $edit_profile_form_id ) ) {
				// let's check if this is not 'verification' or 'secret' fields - those should be not transferred to Edit Profile Form
				$query = 'SELECT field_key FROM ' . $this->field_table_name . ' WHERE field_id=%d';
				$query = $wpdb->prepare( $query, array( $id ) );
				$res   = $wpdb->get_var( $query );
				if ( $res !== 'verification' && $res !== 'secret' ) {
					$field_data['form_id'] = $edit_profile_form_id;
					$this->create_field( $field_data, $id );
				}
			}

			$field_sequence++;
		endforeach;
	}

	/**
	 * Handle trashing and deleting forms
	 *
	 * This is a placeholder function since all processing is handled in includes/class-forms-list.php
	 *
	 * @access public
	 * @since 2.8.1
	 * @return void
	 */
	public function save_trash_delete_form() {
		global $wpdb;

		if ( ! isset( $_REQUEST['action'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( 'swpm-form-builder' !== $_GET['page'] ) {
			return;
		}

		if ( 'delete_form' !== $_REQUEST['action'] ) {
			return;
		}

		$id = absint( $_REQUEST['form'] );

		check_admin_referer( 'delete-form-' . $id );

		// Delete form and all fields
		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->form_table_name WHERE form_id = %d", $id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE form_id = %d", $id ) );

		// Redirect to keep the URL clean (use AJAX in the future?)
		wp_redirect( add_query_arg( 'action', 'deleted', 'admin.php?page=swpm-form-builder' ) );
		exit();
	}

	/**
	 * Handle form duplication
	 *
	 * @access public
	 * @since 2.8.1
	 * @return void
	 */
	public function save_copy_form() {
		global $wpdb;

		if ( ! isset( $_REQUEST['action'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( 'swpm-form-builder' !== $_GET['page'] ) {
			return;
		}

		if ( 'copy_form' !== $_REQUEST['action'] ) {
			return;
		}

		$id = absint( $_REQUEST['form'] );

		check_admin_referer( 'copy-form-' . $id );

		// Get all fields and data for the request form
		$fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d", $id ) );
		$forms     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d", $id ) );
		$override  = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT form_email_from_override, form_email_from_name_override, '
						. "form_notification_email FROM $this->form_table_name WHERE form_id = %d",
				$id
			)
		);
		$from_name = $wpdb->get_var( null, 1 );
		$notify    = $wpdb->get_var( null, 2 );

		// Copy this form and force the initial title to denote a copy
		foreach ( $forms as $form ) {
			$data               = (array) $form;
			$data['form_key']   = sanitize_title( $form->form_key . ' copy' );
			$data['form_title'] = form_title . ' Copy';
			$wpdb->insert( $this->form_table_name, $data );
		}

		// Get form ID to add our first field
		$new_form_selected = $wpdb->insert_id;

		// Copy each field and data
		foreach ( $fields as $field ) {
			$data            = (array) $field;
			$data['form_id'] = $new_form_selected;
			$wpdb->insert( $this->field_table_name, $data );

			// If a parent field, save the old ID and the new ID to update new parent ID
			if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) {
				$parents[ $field->field_id ] = $wpdb->insert_id;
			}

			if ( $override == $field->field_id ) {
				$wpdb->update( $this->form_table_name, array( 'form_email_from_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}

			if ( $from_name == $field->field_id ) {
				$wpdb->update( $this->form_table_name, array( 'form_email_from_name_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}

			if ( $notify == $field->field_id ) {
				$wpdb->update( $this->form_table_name, array( 'form_notification_email' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}
		}

		// Loop through our parents and update them to their new IDs
		foreach ( $parents as $k => $v ) {
			$wpdb->update(
				$this->field_table_name,
				array( 'field_parent' => $v ),
				array(
					'form_id'      => $new_form_selected,
					'field_parent' => $k,
				)
			);
		}
	}

	/**
	 * Save options on the VFB Pro > Settings page
	 *
	 * @access public
	 * @since 2.8.1
	 * @return void
	 */
	public function save_settings() {

		if ( ! isset( $_REQUEST['action'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( 'swpm-settings' !== $_GET['page'] ) {
			return;
		}

		if ( 'swpm_settings' !== $_REQUEST['action'] ) {
			return;
		}

		check_admin_referer( 'swpm-update-settings' );

		$data = array();

		foreach ( $_POST['swpm-settings'] as $key => $val ) {
			$data[ $key ] = esc_html( $val );
		}

		update_option( 'swpm-settings', $data );
	}

	/**
	 * The jQuery field sorting callback
	 *
	 * @since 1.0
	 */
	public function ajax_sort_field() {
		global $wpdb;

		$data = array();

		foreach ( $_REQUEST['order'] as $k ) :
			if ( 'root' !== $k['item_id'] && ! empty( $k['item_id'] ) ) :
				$data[] = array(
					'field_id' => $k['item_id'],
					'parent'   => $k['parent_id'],
				);
			endif;
		endforeach;

		foreach ( $data as $k => $v ) :
			// Update each field with it's new sequence and parent ID
			$wpdb->update(
				$this->field_table_name,
				array(
					'field_sequence' => $k,
					'field_parent'   => $v['parent'],
				),
				array( 'field_id' => $v['field_id'] ),
				'%d'
			);
		endforeach;

		die( 1 );
	}

	/**
	 * The jQuery create field callback
	 *
	 * @since 1.9
	 */
	public function ajax_create_field() {
		global $wpdb;

		$data          = array();
		$field_options = $field_validation = '';

		foreach ( $_REQUEST['data'] as $k ) {
			$data[ $k['name'] ] = $k['value'];
		}

		check_ajax_referer( 'create-field-' . $data['form_id'], 'nonce' );

		$form_id           = absint( $data['form_id'] );
		$field_key         = esc_html( $_REQUEST['field_key'] );
		$field_name        = ucwords( esc_html( str_replace( '_', ' ', $field_key ) ) );
		$field_type        = strtolower( sanitize_title( $_REQUEST['field_type'] ) );
		$field_description = ucwords( str_replace( '_', ' ', $field_key ) );

		// Set defaults for validation
		switch ( $field_type ) {
			case 'select':
				if ( $field_key == 'gender' ) {
					$field_options = serialize(
						array(
							'male'          => 'Male',
							'female'        => 'Female',
							'not specified' => 'Not Specified',
						)
					);
				} elseif ( $field_key == 'title' ) {
					$field_options = serialize(
						array(
							'mr'            => 'Mr',
							'mrs'           => 'Mrs',
							'ms'            => 'Ms',
							'dr'            => 'Dr',
							'not specified' => 'Not Specified',
						)
					);
				} else {
					$field_options = serialize( array( 'Option 1', 'Option 2', 'Option 3' ) );
				}
				break;
			case 'radio':
			case 'checkbox':
				$field_options = serialize( array( 'Option 1', 'Option 2', 'Option 3' ) );
				break;
			case 'email':
			case 'url':
			case 'phone':
				$field_validation = $field_type;
				break;

			case 'currency':
				$field_validation = 'number';
				break;

			case 'number':
				$field_validation = 'digits';
				break;

			case 'time':
				$field_validation = 'time-12';
				break;

			case 'file-upload':
				$field_options = ( $field_key == 'profile_image' ) ? serialize( array( 'png|jpe?g|gif' ) ) : '';
				break;
		}

		$newdata = array(
			'form_id'           => $form_id,
			'field_key'         => $field_key,
			'field_name'        => $field_name,
			'field_type'        => $field_type,
			'field_options'     => $field_options,
			'field_validation'  => $field_validation,
			'field_description' => $field_description,
		);

		$insert_id = $this->create_field( $newdata );

		$query     = $wpdb->prepare(
			"SELECT form_id FROM  $this->form_table_name WHERE form_type= 1 AND `form_membership_level` = "
				. " (SELECT `form_membership_level` FROM $this->form_table_name WHERE form_type= 0 AND `form_id` = %d)",
			$form_id
		);
		$edit_form = $wpdb->get_var( $query );
		if ( ! empty( $edit_form ) ) {
			$newdata['form_id'] = $edit_form;
			$this->create_field( $newdata, $insert_id );
		}

		echo $this->field_output( $form_id, $insert_id );

		die( 1 );
	}

	private function create_field( $form_data, $reg_field_id = 0 ) {
		global $wpdb;
		$form_id   = $form_data['form_id'];
		$insert_id = 0;
		if ( ! empty( $reg_field_id ) ) {
			$form_data['reg_field_id'] = $reg_field_id;
			$query                     = $wpdb->prepare( "SELECT field_id FROM $this->field_table_name WHERE reg_field_id = %d", $reg_field_id );
			$insert_id                 = $wpdb->get_var( $query );
		}

		if ( ! empty( $insert_id ) ) {
			$wpdb->update( $this->field_table_name, $form_data, array( 'field_id' => $insert_id ) );
		} else {
			// Get the last row's sequence that isn't a Submit
			$sequence_last_row = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT field_sequence FROM $this->field_table_name WHERE form_id = %d AND field_type = 'verification' ORDER BY field_sequence DESC LIMIT 1",
					$form_id
				)
			);
			if ( ! $sequence_last_row ) {
				// There is no 'verification' field in the form, let's look for 'submit' instead
				$sequence_last_row = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT field_sequence FROM $this->field_table_name WHERE form_id = %d AND field_type = 'submit' ORDER BY field_sequence DESC LIMIT 1",
						$form_id
					)
				);
			}
			// If it's not the first for this form, add 1
			$field_sequence              = ( ! empty( $sequence_last_row ) ) ? $sequence_last_row : 0;
			$form_data['field_sequence'] = $field_sequence;
			// Create the field
			$wpdb->insert( $this->field_table_name, $form_data );
			$insert_id = $wpdb->insert_id;
			// VIP fields
			$vip_fields = array( 'verification', 'secret', 'submit' );

			// Move the VIPs
			foreach ( $vip_fields as $update ) {
				$field_sequence++;
				$where = array(
					'form_id'    => absint( $form_id ),
					'field_type' => $update,
				);
				$wpdb->update( $this->field_table_name, array( 'field_sequence' => $field_sequence ), $where );
			}
		}

		return $insert_id;
	}

	/**
	 * The jQuery delete field callback
	 *
	 * @since 1.9
	 */
	public function ajax_delete_field() {
		global $wpdb;

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'swpm_form_builder_delete_field' ) {
			$form_id  = absint( $_REQUEST['form'] );
			$field_id = absint( $_REQUEST['field'] );

			check_ajax_referer( 'delete-field-' . $form_id, 'nonce' );

			$field_key = $wpdb->get_var( $wpdb->prepare( "SELECT field_key FROM $this->field_table_name WHERE field_id=%d", $field_id ) );
			if ( SwpmFbUtils::is_mandatory_field( $field_key ) ) {
				die( '0' ); // don't delete required fields
			}
			if ( isset( $_REQUEST['child_ids'] ) ) {
				foreach ( $_REQUEST['child_ids'] as $children ) {
					$parent = absint( $_REQUEST['parent_id'] );

					// Update each child item with the new parent ID
					$wpdb->update( $this->field_table_name, array( 'field_parent' => $parent ), array( 'field_id' => $children ) );
				}
			}

			// Delete the field
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE field_id = %d", $field_id ) );
		}

		die( 1 );
	}

	/**
	 * The jQuery form settings callback
	 *
	 * @since 2.2
	 */
	public function ajax_form_settings() {

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'swpm_form_builder_form_settings' ) {
			$form_id      = absint( $_REQUEST['form'] );
			$status       = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'opened';
			$accordion    = isset( $_REQUEST['accordion'] ) ? $_REQUEST['accordion'] : 'general-settings';
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;

			$form_settings = get_user_meta( $user_id, 'swpm-form-settings', true );

			$array = array(
				'form_setting_tab'  => $status,
				'setting_accordion' => $accordion,
			);

			// Set defaults if meta key doesn't exist
			if ( ! $form_settings || $form_settings == '' ) {
				$meta_value[ $form_id ] = $array;

				update_user_meta( $user_id, 'swpm-form-settings', $meta_value );
			} else {
				$form_settings[ $form_id ] = $array;

				update_user_meta( $user_id, 'swpm-form-settings', $form_settings );
			}
		}

		die( 1 );
	}

	/**
	 * All Forms output in admin
	 *
	 * @since 2.5
	 */
	public function all_forms() {
		$searched = isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ? true : false;
		global $wpdb, $forms_list;
		require SWPM_FORM_BUILDER_PATH . 'views/all_forms.php';
		$gen_settings = new SwpmFbSettings();
		$gen_settings->render_settings_page();
	}

	/**
	 * Build field output in admin
	 *
	 * @since 1.9
	 */
	public function field_output( $form_nav_selected_id, $field_id = null ) {
		require SWPM_FORM_BUILDER_PATH . 'includes/admin-field-options.php';
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0
	 */
	public function admin_notices() {
		if ( ! isset( $_REQUEST['action'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( ! in_array(
			$_GET['page'],
			array(
				'swpm-form-builder',
				'swpm-add-new',
				'swpm-email-design',
				'swpm-reports',
				'swpm-settings',
			)
		) ) {
			return;
		}

		switch ( $_REQUEST['action'] ) {
			case 'create_form':
				echo '<div id="message" class="error">';
				echo '<p>Error! Combination of membership level and form type must be unique.</p>';
				echo '<p>Each of your membership levels can have ONE registration type and ONE profile type forms. Do not try to create two forms of the same type for the same membership level.</p>';
				$form_type = absint( filter_input( INPUT_POST, 'form_type' ) );
				if ( $form_type == SwpmFbFormCustom::PROFILE ) {
					echo '<p>' . __( 'You must create a registration form before creating edit profile form.', 'swpm-form-builder' ) . '</p>';
				}
				echo '</div>';
				break;

			case 'update_form':
				echo '<div id="message" class="updated"><p>' .
				__( 'Form updated.', 'swpm-form-builder' ) . '</p></div>';

				if ( $this->post_max_vars ) :
					// Get max post vars, if available. Otherwise set to 1000
					$max_post_vars = ( ini_get( 'max_input_vars' ) ) ? intval( ini_get( 'max_input_vars' ) ) : 1000;

					echo '<div id="message" class="error"><p>' .
					sprintf(
						__(
							'Error saving form. The maximum amount of data allowed by your server has been reached. '
									. 'Please update <a href="%s" target="_blank">max_input_vars</a> in your php.ini '
									. 'file to allow more data to be saved. Current limit is <strong>%d</strong>',
							'swpm-form-builder'
						),
						'http://www.php.net/manual/en/info.configuration.php#ini.max-input-vars',
						$max_post_vars
					) . '</p></div>';
				endif;
				break;

			case 'deleted':
				echo '<div id="message" class="updated"><p>' .
				__( 'Item permanently deleted.', 'swpm-form-builder' ) . '</p></div>';
				break;

			case 'copy_form':
				echo '<div id="message" class="updated"><p>' .
				__( 'Item successfully duplicated.', 'swpm-form-builder' ) . '</p></div>';
				break;

			case 'swpm_settings':
				echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Settings saved.', 'swpm-form-builder' ) );
				break;
		}
	}

	/**
	 * Add options page to Settings menu
	 *
	 * @since 1.0
	 * @uses add_options_page() Creates a menu item under the Settings menu.
	 */
	public function add_admin() {
		$current_pages = array();

		$permission = 'manage_options';
		if ( defined( 'SWPM_MANAGEMENT_PERMISSION' ) ) {
			$permission = SWPM_MANAGEMENT_PERMISSION;
		}

		$current_pages['swpm'] = add_submenu_page( 'simple_wp_membership', __( 'Form Builder', 'simple-membership' ), __( 'Form Builder', 'simple-membership' ), $permission, 'swpm-form-builder', array( &$this, 'admin' ) );
		// All plugin page load hooks
		foreach ( $current_pages as $key => $page ) {
			// Load the jQuery and CSS we need if we're on our plugin page
			add_action( "load-$page", array( &$this, 'admin_scripts' ) );

			// Load the Help tab on all pages
			add_action( "load-$page", array( &$this, 'help' ) );
		}
		// Save pages array for filter/action use throughout plugin
		$this->_admin_pages = $current_pages;

		// Adds a Screen Options tab to the Entries screen
		add_action( 'load-' . $current_pages['swpm'], array( &$this, 'screen_options' ) );

		// Add meta boxes to the form builder admin page
		add_action( 'load-' . $current_pages['swpm'], array( &$this, 'add_meta_boxes' ) );

		add_action( 'load-' . $current_pages['swpm'], array( &$this, 'include_forms_list' ) );
	}

	/**
	 * Display Add New Form page
	 *
	 * @since 2.7.2
	 */
	public function admin_add_new() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Add New Form', 'swpm-form-builder' ); ?></h2>
			<?php
			include_once SWPM_FORM_BUILDER_PATH . 'includes/admin-new-form.php';
			?>
		</div>
		<?php
	}

	public function admin_license_menu() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Product License', 'swpm-form-builder' ); ?></h2>
			<?php
			include_once SWPM_FORM_BUILDER_PATH . 'includes/admin-license-interface.php';
			?>
		</div>
		<?php
	}

	/**
	 * admin_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_settings() {
		$swpm_settings = get_option( 'swpm-form-builder-settings' );
		include_once SWPM_FORM_BUILDER_PATH . 'views/settings.php';
	}

	/**
	 * Builds the options settings page
	 *
	 * @since 1.0
	 */
	public function admin() {
		global $wpdb;
		$action = filter_input( INPUT_GET, 'action' );

		echo '<div class="wrap"><h2>';
		BUtils::e( 'Simple Membership Form Builder' );
		echo '</h2>';
		// Save current user ID
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
		$current_tab  = empty( $action ) ? '' : $action;
		$tabs         = array(
			''        => 'Form List',
			'add'     => 'New Form',
			'license' => 'Product License',
		);
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="admin.php?page=swpm-form-builder&action=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
		switch ( $action ) {
			case 'add':
				$this->admin_add_new();
				break;
			case 'license':
				$this->admin_license_menu();
				break;
			case 'edit':
			default:
				$form_nav_selected_id = filter_input( INPUT_GET, 'form' );
				if ( empty( $form_nav_selected_id ) || $form_nav_selected_id == 0 ) {
					$this->all_forms();
				} else {
					include_once SWPM_FORM_BUILDER_PATH . 'includes/admin-form-creator.php';
				}
				break;
		}
		echo '</div>';
	}

	function confirmation_text() {
		if ( ! SwpmFbForm::is_form_submitted() || $this->form->is_fatal() || ! $this->form->is_valid() ) {
			return;
		}

		global $wpdb;

		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? (int) esc_html( $_REQUEST['form_id'] ) : '';

		// Get forms
		$order = sanitize_sql_orderby( 'form_id DESC' );
		$forms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

		foreach ( $forms as $form ) {
			// If text, return output and format the HTML for display
			if ( 'text' == $form->form_success_type ) {
				$msg = stripslashes( html_entity_decode( wp_kses_stripslashes( $form->form_success_message ) ) );// read the message from the form config.
				if ( empty( $msg ) ) {
					// Not configured in the form. Set a default message
					$msg = SwpmUtils::_( 'Profile updated successfully.' );
				}

				$relogin_msg = '';
				if ( isset( $_REQUEST['fb_password_updated'] ) && $_REQUEST['fb_password_updated'] == '1' ) {
					$relogin_msg = '<div class="swpm-fb-pw-change-relogin-msg">' . SwpmUtils::_( 'You will need to re-login since you changed your password.' ) . '</div>';
				}

				$output_msg = '<div class="swpm-fb-profile-update-success">' . $msg . $relogin_msg . '</div>';
				return $output_msg;
			}

			// If page, do a redirect. (the redirect is handled by the confirmation_redirect() function at "init" stage)

			if ( $form->form_type == SwpmFbFormCustom::PROFILE ) {
				return SwpmUtils::_( 'Profile updated successfully.' );
			} elseif ( $form->form_type == SwpmFbFormCustom::REGISTRATION ) {
				$after_rego_msg = SwpmUtils::_( 'Registration successful.' );
				$after_rego_msg = apply_filters( 'swpm_registration_success_msg', $after_rego_msg );
				return $after_rego_msg;
			} else {
				die( "Can't determine form type." );
			}
		}
	}

	/**
	 * Handle confirmation when form is submitted
	 *
	 * @since 1.3
	 */
	function confirmation_redirect() {
		if ( ! SwpmFbForm::is_form_submitted() || $this->form->is_fatal() || ! $this->form->is_valid() ) {
			return;
		}

		if ( defined( 'SWPM_FB_EMAIL_ACTIVATION' ) ) {
			return;
		}

		global $wpdb;

		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? (int) esc_html( $_REQUEST['form_id'] ) : '';

		// Get forms
		$order = sanitize_sql_orderby( 'form_id DESC' );
		$forms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

		foreach ( $forms as $form ) :
			// If text, return output and format the HTML for display
			if ( 'page' == $form->form_success_type ) {
				$page = get_permalink( $form->form_success_message );
				wp_redirect( $page );
				exit();
			}
			// If redirect, redirect to the URL
			elseif ( 'redirect' == $form->form_success_type ) {
				wp_redirect( esc_url( $form->form_success_message ) );
				exit();
			}

		endforeach;
	}

	public function process_submitted_form() {
		if ( SwpmFbForm::is_form_submitted() ) {
			$this->form->validate_and_save();
		}
	}

	/**
	 * Make sure the User Agent string is not a SPAM bot
	 *
	 * @since 1.3
	 */
	public function isBot() {
		$bots = apply_filters(
			'swpm_blocked_spam_bots',
			array(
				'<',
				'>',
				'&lt;',
				'%0A',
				'%0D',
				'%27',
				'%3C',
				'%3E',
				'%00',
				'href',
				'binlar',
				'casper',
				'cmsworldmap',
				'comodo',
				'diavol',
				'dotbot',
				'feedfinder',
				'flicky',
				'ia_archiver',
				'jakarta',
				'kmccrew',
				'nutch',
				'planetwork',
				'purebot',
				'pycurl',
				'skygrid',
				'sucker',
				'turnit',
				'vikspider',
				'zmeu',
			)
		);

		$isBot = false;

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_kses_data( $_SERVER['HTTP_USER_AGENT'] ) : '';

		do_action( 'swpm_isBot', $user_agent, $bots );

		foreach ( $bots as $bot ) {
			if ( stripos( $user_agent, $bot ) !== false ) {
				$isBot = true;
			}
		}

		return $isBot;
	}

	/**
	 * Check whether the content contains the specified shortcode
	 *
	 * @access public
	 * @param string $shortcode (default: '')
	 * @return void
	 */
	function has_shortcode( $shortcode = '' ) {

		$post_to_check = get_post( get_the_ID() );

		// false because we have to search through the post content first
		$found = false;

		// if no short code was provided, return false
		if ( ! $shortcode ) {
			return $found;
		}
		// check the post content for the short code
		if ( stripos( $post_to_check->post_content, '[' . $shortcode ) !== false ) {
			// we have found the short code
			$found = true;
		}

		// return our final results
		return $found;
	}

	function handle_after_rego_redirect_for_email_activation( $url ) {
		// check if we have constant defined with form id
		if ( ! defined( 'SWPM_EMAIL_ACTIVATION_FORM_ID' ) ) {
			return;
		}
		global $wpdb;

		$form_id = SWPM_EMAIL_ACTIVATION_FORM_ID;

		// Get forms
		$order = sanitize_sql_orderby( 'form_id DESC' );
		$forms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

		foreach ( $forms as $form ) {
			if ( 'page' == $form->form_success_type ) {
				$page = get_permalink( $form->form_success_message );
				return $page;
			}
			// If redirect, redirect to the URL
			elseif ( 'redirect' == $form->form_success_type ) {
				return( esc_url( $form->form_success_message ) );
			}
		}
		return $url;
	}

}

// The VFB widget
require SWPM_FORM_BUILDER_PATH . 'includes/class-widget.php';
