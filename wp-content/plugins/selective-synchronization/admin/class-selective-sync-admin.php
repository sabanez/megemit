<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Selective_Sync
 * @subpackage Selective_Sync/admin
 */

namespace ebSelectSync\admin;

use app\wisdmlabs\edwiserBridge as ed_parent;

/**
 * Selctive synch admin side handler.
 */
class Selective_Sync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param string $plugin_name       The name of this plugin.
	 * @param string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in SelectiveSyncLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The SelectiveSyncLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style(
			'select-datatable-css',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/css/datatable.css',
			array(),
			$this->version,
			'all'
		);
		wp_enqueue_style(
			'select-admin-css',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/css/eb-select-sync.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in SelectiveSyncLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The SelectiveSyncLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script(
			'select-datatable-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/jquery.dataTables.js',
			array(
				'jquery',
			),
			'2.0.0',
			1
		);

		wp_enqueue_script(
			'columnfilter-datatable-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/jquery.dataTables.columnFilter.js',
			array(
				'select-datatable-js',
			),
			'2.0.0',
			1
		);

		wp_register_script(
			'eb-ss-button-datatable-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/dataTables_buttons_min.js',
			array(
				'select-datatable-js',
			),
			'2.0.0',
			1
		);

		wp_register_script(
			'eb-ss-buttons-html5-datatable-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/buttons.html5.min.js',
			array(
				'select-datatable-js',
			),
			'2.0.0',
			1
		);

		wp_register_script(
			'eb-ss-button-print-datatable-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/buttons.print.min.js',
			array(
				'select-datatable-js',
			),
			'2.0.0',
			1
		);

		wp_register_script(
			'select-admin-js',
			SELECTIVE_SYNC_PLUGIN_URL . 'admin/assets/js/eb-select-sync.js',
			array(
				'jquery',
				'select-datatable-js',
				'edwiserbridge',
				'columnfilter-datatable-js',
			),
			'2.0.0',
			1
		);
	}

	/**
	 * Add "Selective Sync" tab in course synchronization after "User"
	 *
	 * @param  array $section List of section in synchronize tab.
	 * @return  array $section Modified array with "Product" tab.
	 * @since 1.0.2
	 */
	public function multiple_course_synchronization_section( $section ) {
		$section = array_merge(
			array_slice( $section, 0, 1 ),
			array( 'select_sync' => __( 'Selective Courses', 'selective-synch-td' ) ),
			array_slice( $section, 1, null )
		);

		return $section;
	}


	/**
	 * Add "Selective Sync" tab in settings.
	 *
	 * @param  array $settings  List of section in synchronize tab.
	 * @return array $settings Modified array with "Product" tab.
	 * @since 1.0.2
	 */
	public function add_selective_synch_tab( $settings ) {
		/*
		 * Class showing the settings page.
		 * @since    1.2.0
		 */
		$settings[] = include_once SELECTIVE_SYNC_PLUGIN_DIR . 'admin/settings/class-selective-synch-settings.php';
		return $settings;
	}
}
