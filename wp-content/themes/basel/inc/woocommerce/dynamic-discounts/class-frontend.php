<?php
/**
 * Render dynamic discounts on frontend.
 *
 * @package basel
 */

namespace XTS\Modules\Dynamic_Discounts;

use WC_Product;
use XTS\Singleton;

/**
 * Dynamic discounts class.
 */
class Frontend extends Singleton {
	/**
	 * Init.
	 */
	public function init() {
		add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 10, 2 );
		add_filter( 'woocommerce_before_mini_cart_contents', array( $this, 'cart_item_price_on_ajax' ), 10, 2 );

		if ( basel_get_opt( 'show_discounts_table', 0 ) && ( ! basel_get_opt( 'login_prices' ) || is_user_logged_in() ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_dynamic_discounts_table' ), 25 );

			add_action( 'wp_ajax_basel_update_discount_dynamic_discounts_table', array( $this, 'update_dynamic_discounts_table' ) );
			add_action( 'wp_ajax_nopriv_basel_update_discount_dynamic_discounts_table', array( $this, 'update_dynamic_discounts_table' ) );
		}
	}

	/**
	 * Update price in mini cart on get_refreshed_fragments action.
	 *
	 * @return void
	 */
	public function cart_item_price_on_ajax() {
		if ( defined( 'WOOCS_VERSION' ) ) {
			return;
		}

		if ( wp_doing_ajax() && ! empty( $_GET['wc-ajax'] ) && 'get_refreshed_fragments' === $_GET['wc-ajax'] ) { // phpcs:ignore.
			WC()->cart->calculate_totals();
			WC()->cart->set_session();
			WC()->cart->maybe_set_cart_cookies();
		}
	}

	/**
	 * Update price in cart.
	 *
	 * @param string $price_html Product price.
	 * @param array  $cart_item Product data.
	 * @return string
	 */
	public function cart_item_price( $price_html, $cart_item ) {
		$product       = $cart_item['data'];
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_price();

		if ( $regular_price === $sale_price ) {
			return $price_html;
		}

		if ( wc_tax_enabled() ) {
			if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
				$sale_price = wc_get_price_including_tax( $product, array( 'price' => $sale_price ) );
			} else {
				$sale_price = wc_get_price_excluding_tax( $product, array( 'price' => $sale_price ) );
			}
		}

		ob_start();

		echo wc_price( $sale_price ); // phpcs:ignore.

		return ob_get_clean();
	}

	/**
	 * Render dynamic discounts table.
	 *
	 * @param false|int|string $product_id The product id for which you want to generate the dynamic discounts table. Default is equal false.
	 * @param string           $wrapper_classes Wrapper classes string.
	 * @return false|string
	 */
	public function render_dynamic_discounts_table( $product_id = false, $wrapper_classes = '' ) {
		if ( ! $product_id ) {
			$product_id = is_ajax() && ! empty( wp_unslash( $_GET['variation_id'] ) ) ? wp_unslash( $_GET['variation_id'] ) : false; // phpcs:ignore.
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || empty( $product->get_price() ) ) {
			return false;
		}

		$product_type = $product->get_type();
		$discount     = Manager::get_instance()->get_discount_rules( $product );
		$data         = array();

		if ( ! Manager::get_instance()->check_discount_exist( $product ) || in_array( $product_type, array( 'grouped', 'external' ), true ) || 'bulk' !== $discount['_basel_rule_type'] ) {
			return false;
		}

		// Add last rule for render table.
		$last_rules = end( $discount['discount_rules'] );

		if ( ! empty( $last_rules['_basel_discount_rules_to'] ) ) {
			$discount['discount_rules']['last'] = array(
				'_basel_discount_rules_from'       => $last_rules['_basel_discount_rules_to'] + 1,
				'_basel_discount_rules_to'         => '',
				'_basel_discount_type'             => 'amount',
				'_basel_discount_amount_value'     => 0,
				'_basel_discount_percentage_value' => '',
			);
		}

		foreach ( $discount['discount_rules'] as $id => $rules ) {
			// Quantity min.
			$data[ $id ]['min'] = $rules['_basel_discount_rules_from'];

			// Quantity max.
			$data[ $id ]['max'] = $rules['_basel_discount_rules_to'];

			// Quantity column.
			if ( $rules['_basel_discount_rules_from'] === $rules['_basel_discount_rules_to'] ) {
				$data[ $id ]['quantity'] = $rules['_basel_discount_rules_from'];
			} else {
				$data[ $id ]['quantity'] = sprintf(
					'%s%s%s',
					$rules['_basel_discount_rules_from'],
					array_key_last( $discount['discount_rules'] ) !== $id ? '-' : '',
					! empty( $rules['_basel_discount_rules_to'] ) ? $rules['_basel_discount_rules_to'] : '+'
				);
			}

			// Discount column.
			if ( 'amount' === $rules['_basel_discount_type'] ) {
				$data[ $id ]['discount'] = wc_price( apply_filters( 'basel_pricing_amount_discounts_value', $rules['_basel_discount_amount_value'] ) );
			} else {
				$data[ $id ]['discount'] = $rules['_basel_discount_percentage_value'] . '%';
			}

			// Price column.
			$product_price = Main::get_instance()->get_product_price(
				$product->get_price(),
				array(
					'type'  => $rules['_basel_discount_type'],
					'value' => 'amount' === $rules['_basel_discount_type'] ? apply_filters( 'basel_pricing_amount_discounts_value', $rules['_basel_discount_amount_value'] ) : $rules['_basel_discount_percentage_value'],
				)
			);

			if ( wc_tax_enabled() ) {
				if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
					$product_price = wc_get_price_including_tax( $product, array( 'price' => $product_price ) );
				} else {
					$product_price = wc_get_price_excluding_tax( $product, array( 'price' => $product_price ) );
				}
			}

			if ( $product_price < 0 ) {
				$product_price = 0;
			}

			$data[ $id ]['price'] = wc_price( $product_price );
		}

		if ( empty( $data ) ) {
			return false;
		}

		if ( is_ajax() ) {
			ob_start();
		}

		basel_enqueue_inline_style( 'woo-opt-dynamic-discounts' );
		?>
		<?php if ( ! is_ajax() ) : ?>
			<div class="basel-dynamic-discounts <?php echo esc_attr( $wrapper_classes ); ?>">
		<?php endif; ?>

		<?php
			wc_get_template(
				'single-product/price-table.php',
				array(
					'data' => $data,
				)
			);
		?>

		<div class="basel-loader-overlay basel-fill"></div>

		<?php if ( ! is_ajax() ) : ?>
			</div>
		<?php endif; ?>
		<?php
		if ( is_ajax() ) {
			return ob_get_clean();
		}
	}

	/**
	 * Send new price table html for current variation product.
	 *
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function update_dynamic_discounts_table() {
		$variation_id = wp_unslash( $_GET['variation_id'] ); // phpcs:ignore.

		if ( empty( $variation_id ) ) {
			return;
		}

		if ( ! wc_get_product( $variation_id ) instanceof WC_Product ) {
			return;
		}

		wp_send_json(
			apply_filters( 'basel_variation_dynamic_discounts_table', $this->render_dynamic_discounts_table( $variation_id ) )
		);
	}
}

Frontend::get_instance();
