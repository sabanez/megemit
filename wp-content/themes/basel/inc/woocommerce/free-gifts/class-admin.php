<?php
/**
 * Add Free gifts settings on wp admin page.
 *
 * @package basel
 */

namespace XTS\Modules\Free_Gifts;

use XTS\Options\Status_Button;
use XTS\Singleton;
use XTS\Metaboxes;
use WC_Product;

/**
 * Add Free gifts settings on wp admin page.
 */
class Admin extends Singleton {
	/**
	 * Metabox class instance.
	 *
	 * @var Metabox instance.
	 */
	public $metabox;

	/**
	 * Manager instance.
	 *
	 * @var Manager instance.
	 */
	public $manager;

	/**
	 * Init.
	 */
	public function init() {
		$this->manager = Manager::get_instance();

		add_action( 'new_to_publish', array( $this, 'clear_transients_on_publish' ) );
		add_action( 'save_post', array( $this, 'clear_transients' ), 10, 2 );
		add_action( 'edit_post', array( $this, 'clear_transients' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'clear_transients' ), 10, 2 );
		add_action( 'basel_change_post_status', array( $this, 'clear_transients_on_ajax' ) );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'clear_transients_on_change_product_state' ), 10, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'clear_transients_on_change_product_state' ), 10, 3 );

		add_action( 'init', array( $this, 'add_metaboxes' ) );

		new Status_Button( 'basel_woo_free_gifts', 2 );

		add_action( 'manage_basel_woo_free_gifts_posts_columns', array( $this, 'admin_columns_titles' ) );
		add_action( 'manage_basel_woo_free_gifts_posts_custom_column', array( $this, 'admin_columns_content' ), 10, 2 );
	}

	/**
	 * Clear transients on create post.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function clear_transients_on_publish( $post ) {
		$this->clear_transients( 0, $post );
	}

	/**
	 * Clear transients.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function clear_transients( $post_id, $post ) {
		if ( ! $post || 'basel_woo_free_gifts' !== $post->post_type ) {
			return;
		}

		delete_transient( $this->manager->transient_free_gifts_all_rules );
		delete_transient( $this->manager->transient_free_gifts_ids );
		delete_transient( $this->manager->transient_free_gifts_rule . '_' . $post->ID );
	}

	/**
	 * Clear transients on ajax action.
	 *
	 * @return void
	 */
	public function clear_transients_on_ajax() {
		if ( ! wp_doing_ajax() || empty( $_POST['action'] ) || empty( $_POST['id'] ) || 'basel_change_post_status' !== $_POST['action'] ) {
			return;
		}

		$post = get_post( $_POST['id'] );

		if ( ! $post || 'basel_woo_free_gifts' !== $post->post_type ) {
			return;
		}

		delete_transient( $this->manager->transient_free_gifts_all_rules );
		delete_transient( $this->manager->transient_free_gifts_ids );
		delete_transient( $this->manager->transient_free_gifts_rule . '_' . $post->ID );
	}

	/**
	 * Clear transients on change product state status.
	 *
	 * @param integer $product_id Product ID.
	 * @param string  $stock_status Stock status product.
	 * @param object  $product Data product.
	 *
	 * @return void
	 */
	public function clear_transients_on_change_product_state( $product_id, $stock_status, $product ) {
		if ( 'variable' === $product->get_type() ) {
			return;
		}

		$ids = $this->manager->get_all_rule_posts_ids();

		foreach ( $ids as $id ) {
			delete_transient( $this->manager->transient_free_gifts_rule . '_' . $id );
		}

		delete_transient( $this->manager->transient_free_gifts_ids );
		delete_transient( $this->manager->transient_free_gifts_all_rules );
	}

	/**
	 * Add metaboxes for free gifts.
	 *
	 * @return void
	 */
	public function add_metaboxes() {
		$metabox = Metaboxes::add_metabox(
			array(
				'id'         => 'basel_woo_free_gifts_metaboxes',
				'title'      => esc_html__( 'Settings', 'basel' ),
				'post_types' => array( 'basel_woo_free_gifts' ),
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
				'id'          => 'free_gifts_rule_type',
				'type'        => 'select',
				'section'     => 'general',
				'name'        => esc_html__( 'Rule type', 'basel' ),
				'description' => esc_html__( 'Choose the method for applying gift rules: either automatically add the gift to the cart or display it in a table for manual addition by the customer.', 'basel' ),
				'group'       => esc_html__( 'General', 'basel' ),
				'options'     => array(
					'manual'    => array(
						'name'  => esc_html__( 'Manual Gifts', 'basel' ),
						'value' => 'manual',
					),
					'automatic' => array(
						'name'  => esc_html__( 'Automatic Gifts', 'basel' ),
						'value' => 'automatic',
					),
				),
				'priority'    => 10,
			)
		);

		$metabox->add_field(
			array(
				'id'           => 'free_gifts',
				'type'         => 'select',
				'section'      => 'general',
				'name'         => esc_html__( 'Free gifts', 'basel' ),
				'description'  => esc_html__( 'Select the products that customers can choose from as free gifts with their purchase, allowing them to pick their preferred option.', 'basel' ),
				'group'        => esc_html__( 'General', 'basel' ),
				'select2'      => true,
				'multiple'     => true,
				'autocomplete' => array(
					'type'   => 'post',
					'value'  => '["product", "product_variation"]',
					'search' => 'basel_get_post_by_query_autocomplete',
					'render' => 'basel_get_post_by_ids_autocomplete',
				),
				'priority'     => 20,
			)
		);

		$metabox->add_field(
			array(
				'id'       => 'free_gifts_condition',
				'group'    => esc_html__( 'Products in cart condition', 'basel' ),
				'type'     => 'conditions',
				'section'  => 'general',
				'priority' => 30,
			)
		);

		$metabox->add_field(
			array(
				'id'          => 'free_gifts_cart_price_type',
				'type'        => 'select',
				'section'     => 'general',
				'name'        => esc_html__( 'Base price', 'basel' ),
				'description' => esc_html__( "Select whether the gift eligibility is based on the cart's total amount (including taxes and discounts) or the subtotal amount (excluding taxes and discounts).", 'basel' ),
				'group'       => esc_html__( 'Cart price condition', 'basel' ),
				'options'     => array(
					'subtotal' => array(
						'name'  => esc_html__( 'Subtotal', 'basel' ),
						'value' => 'subtotal',
					),
					'total'    => array(
						'name'  => esc_html__( 'Total', 'basel' ),
						'value' => 'total',
					),
				),
				'default'     => 'subtotal',
				'class'       => 'xts-col-12',
				'priority'    => 40,
			)
		);

		$metabox->add_field(
			array(
				'id'          => 'free_gifts_cart_total_min',
				'name'        => esc_html__( 'Cart amount min', 'basel' ),
				'description' => esc_html__( 'Set the minimum cart amount required for customers to qualify for a free gift, ensuring that the gift is only offered on purchases above a specified amount.', 'basel' ),
				'group'       => esc_html__( 'Cart price condition', 'basel' ),
				'type'        => 'text_input',
				'attributes'  => array(
					'type' => 'number',
					'min'  => '0',
					'step' => '1',
				),
				'default'     => 0,
				'section'     => 'general',
				'priority'    => 50,
			)
		);

		$metabox->add_field(
			array(
				'id'          => 'free_gifts_cart_total_max',
				'name'        => esc_html__( 'Cart amount max', 'basel' ),
				'description' => esc_html__( 'Define the maximum cart amount for which a free gift is available, ensuring the offer is valid only within a specified purchase range.', 'basel' ),
				'group'       => esc_html__( 'Cart price condition', 'basel' ),
				'type'        => 'text_input',
				'attributes'  => array(
					'type' => 'number',
					'min'  => '0',
					'step' => '1',
				),
				'section'     => 'general',
				'priority'    => 60,
			)
		);

		Manager::get_instance()->set_meta_boxes_fields_keys(
			array(
				'free_gifts_rule_type',
				'free_gifts',
				'free_gifts_condition',
				'free_gifts_cart_price_type',
				'free_gifts_cart_total_min',
				'free_gifts_cart_total_max',
			)
		);
	}


	/**
	 * Columns header.
	 *
	 * @param array $posts_columns Columns.
	 *
	 * @return array
	 */
	public function admin_columns_titles( $posts_columns ) {
		$offset = 3;

		return array_slice( $posts_columns, 0, $offset, true ) + array(
			'gifts' => esc_html__( 'Gifts', 'basel' ),
		) + array_slice( $posts_columns, $offset, null, true );
	}

	/**
	 * Columns content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post id.
	 *
	 * @return void
	 */
	public function admin_columns_content( $column_name, $post_id ) {
		if ( 'gifts' === $column_name ) {
			$gift_ids    = get_post_meta( $post_id, 'free_gifts', true );
			$gift_titles = array();

			if ( empty( $gift_ids ) ) {
				return;
			}

			foreach ( $gift_ids as $gift_id ) {
				$gift_product = wc_get_product( $gift_id );

				if ( ! $gift_product instanceof WC_Product ) {
					continue;
				}

				$gift_titles[] = '<a href="' . get_permalink( $gift_id ) . '">' . $gift_product->get_title() . '</a>';
			}

			echo wp_kses( implode( ' | ', $gift_titles ), true );
		}
	}
}

Admin::get_instance();
