<?php
/**
 * Free shipping progress bar.
 *
 * @package basel
 */

namespace XTS\Modules\Shipping_Progress_Bar;

use XTS\Options;
use XTS\Singleton;

/**
 * Free shipping progress bar.
 */
class Main extends Singleton {
	/**
	 * Constructor.
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_options' ), 10 );
		add_action( 'init', array( $this, 'output_shipping_progress_bar' ), 20 );
	}

	/**
	 * Add options in theme settings.
	 */
	public function add_options() {
		Options::add_section(
			array(
				'id'       => 'shipping_progress_bar',
				'parent'   => 'shop_section',
				'name'     => esc_html__( 'Free shipping bar', 'basel' ),
				'priority' => 150,
			)
		);

		Options::add_field(
			array(
				'id'          => 'shipping_progress_bar_enabled',
				'name'        => esc_html__( 'Free shipping bar', 'basel' ),
				'description' => esc_html__( 'Display a free shipping progress bar on the website.', 'basel' ),
				'type'        => 'switcher',
				'section'     => 'shipping_progress_bar',
				'default'     => '0',
				'priority'    => 10,
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_calculation',
				'name'     => esc_html__( 'Calculation', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'shipping_progress_bar',
				'options'  => array(
					'custom' => array(
						'name'  => esc_html__( 'Custom', 'basel' ),
						'value' => 'custom',
					),
					'wc'     => array(
						'name'  => esc_html__( 'Based on WooCommerce zones', 'basel' ),
						'value' => 'wc',
					),
				),
				'default'  => 'custom',
				'priority' => 20,
			)
		);

		Options::add_field(
			array(
				'id'          => 'shipping_progress_bar_amount',
				'name'        => esc_html__( 'Goal amount', 'basel' ),
				'description' => esc_html__( 'Amount to reach 100% defined in your currency absolute value. For example: 300', 'basel' ),
				'type'        => 'text_input',
				'section'     => 'shipping_progress_bar',
				'default'     => '100',
				'requires'    => array(
					array(
						'key'     => 'shipping_progress_bar_calculation',
						'compare' => 'equals',
						'value'   => 'custom',
					),
				),
				'priority'    => 30,
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_include_coupon',
				'name'     => esc_html__( 'Coupon discount', 'basel' ),
				'type'     => 'buttons',
				'section'  => 'shipping_progress_bar',
				'options'  => array(
					'exclude' => array(
						'name'  => esc_html__( 'Exclude', 'basel' ),
						'value' => 'exclude',
					),
					'include' => array(
						'name'  => esc_html__( 'Include', 'basel' ),
						'value' => 'include',
					),
				),
				'default'  => 'exclude',
				'priority' => 40,
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_location_card_page',
				'name'     => esc_html__( 'Cart page', 'basel' ),
				'type'     => 'switcher',
				'section'  => 'shipping_progress_bar',
				'group'    => esc_html__( 'Locations', 'basel' ),
				'default'  => '1',
				'priority' => 50,
				'class'    => 'xts-col-12',
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_location_mini_cart',
				'name'     => esc_html__( 'Mini cart', 'basel' ),
				'type'     => 'switcher',
				'section'  => 'shipping_progress_bar',
				'group'    => esc_html__( 'Locations', 'basel' ),
				'default'  => '1',
				'priority' => 60,
				'class'    => 'xts-col-12',
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_location_checkout',
				'name'     => esc_html__( 'Checkout page', 'basel' ),
				'type'     => 'switcher',
				'section'  => 'shipping_progress_bar',
				'group'    => esc_html__( 'Locations', 'basel' ),
				'default'  => '0',
				'priority' => 70,
				'class'    => 'xts-col-12',
			)
		);

		Options::add_field(
			array(
				'id'       => 'shipping_progress_bar_location_single_product',
				'name'     => esc_html__( 'Single product', 'basel' ),
				'type'     => 'switcher',
				'section'  => 'shipping_progress_bar',
				'group'    => esc_html__( 'Locations', 'basel' ),
				'default'  => '0',
				'priority' => 80,
				'class'    => 'xts-col-12',
			)
		);

		Options::add_field(
			array(
				'id'          => 'shipping_progress_bar_message_initial',
				'name'        => esc_html__( 'Initial Message', 'basel' ),
				'description' => esc_html__( 'Message to show before reaching the goal. Use shortcode [remainder] to display the amount left to reach the minimum.', 'basel' ),
				'type'        => 'textarea',
				'wysiwyg'     => true,
				'section'     => 'shipping_progress_bar',
				'group'       => esc_html__( 'Message', 'basel' ),
				'default'     => '<p>Add [remainder] to cart and get free shipping!</p>',
				'priority'    => 90,
			)
		);

		Options::add_field(
			array(
				'id'          => 'shipping_progress_bar_message_success',
				'name'        => esc_html__( 'Success message', 'basel' ),
				'description' => esc_html__( 'Message to show after reaching 100%.', 'basel' ),
				'type'        => 'textarea',
				'wysiwyg'     => true,
				'section'     => 'shipping_progress_bar',
				'group'       => esc_html__( 'Message', 'basel' ),
				'default'     => '<p>Your order qualifies for free shipping!</p>',
				'priority'    => 100,
			)
		);
	}

	/**
	 * Output shipping progress bar.
	 */
	public function output_shipping_progress_bar() {
		if ( ! basel_get_opt( 'shipping_progress_bar_enabled' ) ) {
			return;
		}

		if ( basel_get_opt( 'shipping_progress_bar_location_card_page' ) ) {
			add_action( 'woocommerce_before_cart_table', array( $this, 'render_shipping_progress_bar' ) );
		}

		if ( basel_get_opt( 'shipping_progress_bar_location_mini_cart' ) ) {
			add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'render_shipping_progress_bar' ) );
		}

		if ( basel_get_opt( 'shipping_progress_bar_location_single_product' ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_shipping_progress_bar' ), 29 );
		}

		if ( basel_get_opt( 'shipping_progress_bar_location_checkout' ) ) {
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'render_shipping_progress_bar' ) );
		}

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'get_shipping_progress_bar_fragment' ), 40 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'get_shipping_progress_bar_fragment' ), 10 );
	}

	/**
	 * Add shipping progress bar fragment.
	 *
	 * @param array $array Fragments.
	 *
	 * @return array
	 */
	public function get_shipping_progress_bar_fragment( $array ) {
		ob_start();

		$this->render_shipping_progress_bar();

		$content = ob_get_clean();

		$array['div.basel-free-progress-bar'] = $content;

		return $array;
	}

	/**
	 * Render free shipping progress bar.
	 */
	public function render_shipping_progress_bar() {
		if ( ! basel_get_opt( 'shipping_progress_bar_enabled' ) ) {
			return;
		}

		$wrapper_classes = '';
		$limit           = 0;
		$calculation     = basel_get_opt( 'shipping_progress_bar_calculation', 'custom' );
		$percent         = 100;
		$free_shipping   = false;

		if ( ! is_object( WC() ) || ! property_exists( WC(), 'cart' ) || ! is_object( WC()->cart ) || ! method_exists( WC()->cart, 'get_displayed_subtotal' ) ) {
			$total       = 0;
			$calculation = 'custom';
		} else {
			$total = floatval( WC()->cart->get_displayed_subtotal() );
		}

		if ( 'wc' === $calculation ) {
			$packages = WC()->cart->get_shipping_packages();
			$package  = reset( $packages );
			$zone     = wc_get_shipping_zone( $package );

			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( 'free_shipping' === $method->id ) {
					$limit = $method->get_option( 'min_amount' );
				}
			}
		} elseif ( 'custom' === $calculation ) {
			$limit = basel_get_opt( 'shipping_progress_bar_amount' );
		}

		if ( $total && 'include' === basel_get_opt( 'shipping_progress_bar_include_coupon' ) && WC()->cart->get_coupons() ) {
			foreach ( WC()->cart->get_coupons() as $coupon ) {
				$total -= WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );

				if ( $coupon->get_free_shipping() ) {
					$free_shipping = true;
					break;
				}
			}
		}

		$limit = floatval( apply_filters( 'basel_shipping_progress_bar_amount', $limit ) );

		if ( $total < $limit && ! $free_shipping ) {
			$percent = floor( ( $total / $limit ) * 100 );
			$message = str_replace( '[remainder]', wc_price( $limit - $total ), basel_get_opt( 'shipping_progress_bar_message_initial' ) );
		} else {
			$message = basel_get_opt( 'shipping_progress_bar_message_success' );
		}

		if ( 0 === (int) $total || $percent < 0 ) {
			$wrapper_classes .= ' basel-progress-hide';
		}

		if ( ! $limit ) {
			return;
		}

		basel_enqueue_inline_style( 'woo-opt-free-progress-bar' );

		?>
		<div class="basel-progress-bar basel-free-progress-bar<?php echo esc_attr( $wrapper_classes ); ?>">
			<div class="progress-msg">
				<?php echo wp_kses( $message, 'post' ); ?>
			</div>
			<div class="progress-area">
				<div class="progress-bar" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
			</div>
		</div>
		<?php
	}
}

Main::get_instance();
