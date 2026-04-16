<?php
/**
 * Selective Synchronization courses settings.
 *
 * @link       https://edwiser.org
 * @since      1.2.0
 *
 * @package    Selective Synchronization
 * @subpackage Selective Synchronization/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

namespace ebSelectSync\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Selective_Synch_Courses_Settings' ) ) {

	/**
	 * Selctive synch course settings.
	 */
	class Selective_Synch_Courses_Settings {
		/**
		 * This returns array of the elements need to add in the course settings page
		 *
		 * @since 1.2.0
		 */
		public function get_settings() {
			$settings = array();

			$connection_options = get_option( 'eb_connection' );

			$eb_moodle_url = '';
			if ( isset( $connection_options['eb_url'] ) ) {
				$eb_moodle_url = $connection_options['eb_url'];
			}
			$eb_moodle_token = '';
			if ( isset( $connection_options['eb_access_token'] ) ) {
				$eb_moodle_token = $connection_options['eb_access_token'];
			}

			// new optimized code.
			$webservice_function = 'eb_get_courses';
			$request_data = array(
				'offset' => 0, // no offset.
				'limit'  => 0, // all courses.
				'search_string' => '', // no search string.
				'total_courses' => 1, // total number of courses.
			);
			$response          = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->connection_helper()->connect_moodle_with_args_helper( $webservice_function, $request_data );
			$category_response = \app\wisdmlabs\edwiserBridge\edwiserBridgeInstance()->courseManager()->getMoodleCourseCategories();
			
			if ( 1 === $response['success'] ) {
				if ( 1 === $category_response['success'] ) {
					$moodle_category_data = $category_response['response_data'];
				}

				$moodle_courses_data = $response['response_data']->courses;

				$settings = apply_filters(
					'eb_select_course_synchronization_settings',
					array(
						array(
							'type' => 'title',
							'id'   => 'select_sync_options',
						),
						array(
							'title'           => __( 'Synchronization Options', 'selective-synch-td' ),
							'desc'            => __( 'Update previously synchronized courses', 'selective-synch-td' ),
							'id'              => 'eb_update_selected_courses',
							'default'         => 'no',
							'type'            => 'checkbox',
							'show_if_checked' => 'option',
							'autoload'        => false,

						),
						array(
							'title'    => '',
							'desc'     => '',
							'id'       => 'eb_sync_selected_course_button',
							'default'  => 'Start Synchronization',
							'type'     => 'button',
							'desc_tip' => false,
							'class'    => 'button secondary',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'select_sync_options',
						),
					)
				);
			} else {
				$moodle_category_data = array();
				$moodle_courses_data  = array();
			}

			$category_list = array();

			// Template included to show the Moodle courses in the datatable.
			include_once SELECTIVE_SYNC_PLUGIN_DIR . 'admin/partials/eb-select-moodle-course-list.php';

			return array(
				'settings'      => $settings,
				'category_list' => $category_list,
			);
		}
	}
}
