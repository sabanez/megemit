<?php
/**
 * Quick buy.
 *
 * @package Basel
 */

namespace XTS\Modules\Quick_Buy;

use XTS\Options;
use XTS\Singleton;

/**
 * Quick buy.
 */
class Main extends Singleton {
	/**
	 * Constructor.
	 */
	public function init() {
		$this->include_files();

		add_action( 'init', array( $this, 'add_options' ) );

		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'output_quick_buy_button' ), 1 );
	}

	/**
	 * Include files.
	 */
	public function include_files() {
		require_once BASEL_THEMEROOT . '/inc/woocommerce/quick-buy/class-redirect.php';
	}

	/**
	 * Add options in theme settings.
	 */
	public function add_options() {
		Options::add_section(
			array(
				'id'       => 'single_product_buy_now',
				'name'     => esc_html__( 'Buy now', 'basel' ),
				'parent'   => 'product',
				'priority' => 20,
				'icon'     => BASEL_ASSETS . '/assets/images/dashboard-icons/settings.svg',
			)
		);

		Options::add_field(
			array(
				'id'          => 'buy_now_enabled',
				'name'        => esc_html__( 'Buy now button', 'basel' ),
				'description' => wp_kses( __( 'Add an extra button next to the “Add to cart” that will add the product to the cart and redirect it to the cart or checkout. Read more information in our <a href="https://xtemos.com/docs-topic/buy-now-button/">documentation</a>.', 'basel' ), 'default' ),
				'type'        => 'switcher',
				'section'     => 'single_product_buy_now',
				'default'     => false,
				'priority'    => 301,
			)
		);

		Options::add_field(
			array(
				'id'       => 'buy_now_redirect',
				'name'     => esc_html__( 'Redirect location', 'basel' ),
				'type'     => 'select',
				'section'  => 'single_product_buy_now',
				'default'  => 'checkout',
				'options'  => array(
					'checkout' => array(
						'name'  => esc_html__( 'Checkout page', 'basel' ),
						'value' => 'checkout',
					),
					'cart'     => array(
						'name'  => esc_html__( 'Cart page', 'basel' ),
						'value' => 'cart',
					),
				),
				'priority' => 305,
			)
		);
	}

	/**
	 * Output quick buy button.
	 */
	public function output_quick_buy_button() {
		if ( ! is_singular( 'product' ) && ! basel_loop_prop( 'is_quick_view' ) || ! basel_get_opt( 'buy_now_enabled' ) ) {
			return;
		}
		?>
			<button id="basel-add-to-cart" type="submit" name="basel-add-to-cart" value="<?php echo get_the_ID(); ?>" class="basel-buy-now-btn button alt">
				<?php esc_html_e( 'Buy now', 'basel' ); ?>
			</button>
		<?php
	}
}

Main::get_instance();
