<?php
/**
 * Frequently bought together class.
 *
 * @package basel
 */

namespace XTS\Modules\Frequently_Bought_Together;

use XTS\Metaboxes;
use XTS\Options;
use XTS\Singleton;

/**
 * Frequently bought together class.
 */
class Main extends Singleton {
	/**
	 * Init.
	 */
	public function init() {
		add_action( 'init', array( $this, 'include_files' ) );
		add_action( 'init', array( $this, 'add_options' ) );
		add_action( 'init', array( $this, 'add_metaboxes' ) );
	}

	/**
	 * Include files.
	 *
	 * @return void
	 */
	public function include_files() {
		if ( ! basel_woocommerce_installed() || ! basel_get_opt( 'bought_together_enabled', 1 ) ) {
			return;
		}

		$files = array(
			'class-controls',
			'class-frontend',
			'class-render',
		);

		if ( class_exists( 'WP_List_Table' ) ) {
			$files[] = 'class-table';
		}

		foreach ( $files as $file ) {
			require_once BASEL_THEMEROOT . '/inc/woocommerce/frequently-bought-together/' . $file . '.php';
		}
	}

	/**
	 * Add options in theme settings.
	 */
	public function add_options() {
		Options::add_section(
			array(
				'id'       => 'single_product_frequently_bought_together',
				'name'     => esc_html__( 'Frequently bought together', 'basel' ),
				'parent'   => 'product',
				'priority' => 30,
				'icon'     => BASEL_ASSETS . '/assets/images/dashboard-icons/settings.svg',
			)
		);

		Options::add_field(
			array(
				'id'          => 'bought_together_enabled',
				'name'        => esc_html__( 'Enable "Frequently bought together"', 'basel' ),
				'description' => wp_kses( __( 'You can configure your bundles in Dashboard -> Products -> Frequently Bought Together. Read more information in our <a href="https://xtemos.com/docs-topic/frequently-bought-together/" target="_blank">documentation</a>.', 'basel' ), true ),
				'type'        => 'switcher',
				'section'     => 'single_product_frequently_bought_together',
				'default'     => '1',
				'on-text'     => esc_html__( 'Yes', 'basel' ),
				'off-text'    => esc_html__( 'No', 'basel' ),
				'priority'    => 10,
			)
		);

		Options::add_field(
			array(
				'id'       => 'bought_together_column',
				'name'     => esc_html__( 'Products columns on desktop', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'single_product_frequently_bought_together',
				'options'  => array(
					1 => array(
						'name'  => '1',
						'value' => 1,
					),
					2 => array(
						'name'  => '2',
						'value' => 2,
					),
					3 => array(
						'name'  => '3',
						'value' => 3,
					),
					4 => array(
						'name'  => '4',
						'value' => 4,
					),
					5 => array(
						'name'  => '5',
						'value' => 5,
					),
					6 => array(
						'name'  => '6',
						'value' => 6,
					),
				),
				't_tab'    => array(
					'id'    => 'bought_together_column_tabs',
					'tab'   => esc_html__( 'Desktop', 'basel' ),
					'icon'  => 'xts-i-desktop',
					'style' => 'devices',
				),
				'default'  => '3',
				'priority' => 20,
			)
		);

		Options::add_field(
			array(
				'id'       => 'bought_together_column_tablet',
				'name'     => esc_html__( 'Products columns on tablet', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'single_product_frequently_bought_together',
				'options'  => array(
					'auto' => array(
						'name'  => esc_html__( 'Auto', 'basel' ),
						'value' => 'auto',
					),
					1      => array(
						'name'  => '1',
						'value' => 1,
					),
					2      => array(
						'name'  => '2',
						'value' => 2,
					),
					3      => array(
						'name'  => '3',
						'value' => 3,
					),
				),
				't_tab'    => array(
					'id'   => 'bought_together_column_tabs',
					'tab'  => esc_html__( 'Tablet', 'basel' ),
					'icon' => 'xts-i-tablet',
				),
				'default'  => 'auto',
				'priority' => 30,
			)
		);

		Options::add_field(
			array(
				'id'       => 'bought_together_column_mobile',
				'name'     => esc_html__( 'Products columns on mobile', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'single_product_frequently_bought_together',
				'options'  => array(
					'auto' => array(
						'name'  => esc_html__( 'Auto', 'basel' ),
						'value' => 'auto',
					),
					1      => array(
						'name'  => '1',
						'value' => 1,
					),
					2      => array(
						'name'  => '2',
						'value' => 2,
					),
				),
				't_tab'    => array(
					'id'   => 'bought_together_column_tabs',
					'tab'  => esc_html__( 'Mobile', 'basel' ),
					'icon' => 'xts-i-mobile',
				),
				'default'  => 'auto',
				'priority' => 40,
			)
		);

		Options::add_field(
			array(
				'id'        => 'bought_together_form_width',
				'name'      => esc_html__( 'Form width', 'basel' ),
				'type'      => 'responsive_range',
				'section'   => 'single_product_frequently_bought_together',
				'selectors' => array(
					'.basel-fbt.basel-design-side' => array(
						'--basel-form-width: {{VALUE}}{{UNIT}};',
					),
				),
				'devices'   => array(
					'desktop' => array(
						'value' => '',
						'unit'  => 'px',
					),
				),
				'range'     => array(
					'px' => array(
						'min'  => 250,
						'max'  => 600,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'priority'  => 50,
			)
		);
	}

	/**
	 * Add metaboxes.
	 */
	public function add_metaboxes() {
		if ( ! basel_woocommerce_installed() || ! basel_get_opt( 'bought_together_enabled', 1 ) ) {
			return;
		}

		$metabox = Metaboxes::add_metabox(
			array(
				'id'         => 'xts_woo_fbt_metaboxes',
				'title'      => esc_html__( 'Settings', 'basel' ),
				'post_types' => array( 'basel_woo_fbt' ),
			)
		);

		$metabox->add_section(
			array(
				'id'       => 'general',
				'name'     => esc_html__( 'General', 'basel' ),
				'icon'     => 'xts-i-footer',
				'priority' => 10,
			)
		);

		$metabox->add_field(
			array(
				'id'          => '_basel_main_products_discount',
				'name'        => esc_html__( 'Primary product discount', 'basel' ),
				'description' => esc_html__( 'Set a discount for the primary product.', 'basel' ),
				'group'       => esc_html__( 'Primary products', 'basel' ),
				'type'        => 'text_input',
				'attributes'  => array(
					'type' => 'number',
					'min'  => '0',
					'max'  => '100',
				),
				'section'     => 'general',
				'priority'    => 20,
				'class'       => 'xts-field-input-append xts-input-percent',
			)
		);

		$metabox->add_field(
			array(
				'id'           => '_basel_fbt_products',
				'type'         => 'select_with_table',
				'section'      => 'general',
				'name'         => '',
				'group'        => esc_html__( 'Bundle products', 'basel' ),
				'select2'      => true,
				'autocomplete' => array(
					'type'   => 'post',
					'value'  => '["product", "product_variation"]',
					'search' => 'basel_get_post_by_query_autocomplete',
					'render' => 'basel_get_post_by_ids_autocomplete',
				),
				'default'      => array(
					array(
						'id'       => '',
						'discount' => '',
					),
				),
				'priority'     => 30,
			)
		);

		$metabox->add_field(
			array(
				'id'          => '_basel_show_checkbox',
				'name'        => esc_html__( 'Allow customize', 'basel' ),
				'description' => esc_html__( 'Enable this option to allow users customize the bundle and check/uncheck some products.', 'basel' ),
				'group'       => esc_html__( 'Settings', 'basel' ),
				'type'        => 'switcher',
				'section'     => 'general',
				'default'     => '1',
				'on-text'     => esc_html__( 'Yes', 'basel' ),
				'off-text'    => esc_html__( 'No', 'basel' ),
				'priority'    => 40,
			)
		);

		$metabox->add_field(
			array(
				'id'       => '_basel_default_checkbox_state',
				'name'     => esc_html__( 'Default checkbox state', 'basel' ),
				'group'    => esc_html__( 'Settings', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'general',
				'options'  => array(
					'check'   => array(
						'name'  => esc_html__( 'Check', 'basel' ),
						'value' => 'check',
					),
					'uncheck' => array(
						'name'  => esc_html__( 'Uncheck', 'basel' ),
						'value' => 'uncheck',
					),
				),
				'default'  => 'check',
				'requires' => array(
					array(
						'key'     => '_basel_show_checkbox',
						'compare' => 'equals',
						'value'   => true,
					),
				),
				'priority' => 50,
			)
		);

		$metabox->add_field(
			array(
				'id'       => '_basel_hide_out_of_stock_product',
				'name'     => esc_html__( 'Hide out of stock product', 'basel' ),
				'group'    => esc_html__( 'Settings', 'basel' ),
				'type'     => 'switcher',
				'section'  => 'general',
				'on-text'  => esc_html__( 'Yes', 'basel' ),
				'off-text' => esc_html__( 'No', 'basel' ),
				'priority' => 60,
				'requires' => array(
					array(
						'key'     => '_basel_show_checkbox',
						'compare' => 'equals',
						'value'   => true,
					),
				),
			)
		);
	}
}

Main::get_instance();
