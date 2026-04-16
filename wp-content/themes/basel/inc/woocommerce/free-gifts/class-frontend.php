<?php
/**
 * Free gifts class.
 *
 * @package basel
 */

namespace XTS\Modules\Free_Gifts;

use XTS\Singleton;

/**
 * Free gifts class.
 */
class Frontend extends Singleton {
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

		add_action( basel_get_opt( 'free_gifts_table_location', 'woocommerce_after_cart_table' ), array( $this, 'output_free_gifts_table' ), 11 );

		add_action( 'woocommerce_checkout_order_review', array( $this, 'output_free_gifts_table' ), 14 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ), 10100 );

		add_action( 'wp_ajax_basel_update_gifts_table', array( $this, 'update_gifts_table' ) );
		add_action( 'wp_ajax_nopriv_basel_update_gifts_table', array( $this, 'update_gifts_table' ) );

		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'cart_item_remove_link' ), 10, 2 );

		add_filter( 'woocommerce_order_item_name', array( $this, 'cart_item_name' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_item_name' ), 10, 2 );

		add_filter( 'woocommerce_cart_item_price', array( $this, 'set_cart_item_price' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'set_cart_item_subtotal' ), 10, 3 );

		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity' ), 10, 2 );
		add_filter( 'basel_show_widget_cart_item_quantity', array( $this, 'widget_cart_item_quantity' ), 10, 2 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'update_cart_item_quantity' ), 10, 4 );
	}

	/**
	 * Enqueue styles.
	 *
	 * @return void
	 */
	public function enqueue_style() {
		if ( basel_get_opt( 'free_gifts_enabled', 0 ) && ( is_cart() || is_checkout() ) ) {
			basel_force_enqueue_style( 'woo-opt-free-gifts' );
		}
	}

	/**
	 * Add render actions.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function output_free_gifts_table() {
		if ( ! basel_get_opt( 'free_gifts_enabled', 0 ) || basel_get_opt( 'free_gifts_limit', 5 ) < 1 || ( is_cart() && ! basel_get_opt( 'free_gift_on_cart', true ) ) || ( is_checkout() && ! basel_get_opt( 'free_gift_on_checkout' ) ) ) {
			return;
		}

		$wrapper_classes = '';

		ob_start();

		$this->render_free_gifts_table();

		$table_html = ob_get_clean();

		?>
		<div class="basel-fg<?php echo esc_attr( $wrapper_classes ); ?>"><?php echo $table_html; ?></div>
		<?php
	}

	/**
	 * Update gift table after updated cart.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function update_gifts_table() {
		ob_start();

		$this->render_free_gifts_table();

		$table_html = ob_get_clean();

		wp_send_json(
			array(
				'html' => $table_html,
			)
		);
		die();
	}

	/**
	 * Render free gifts table.
	 *
	 * @param array $settings Settings.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function render_free_gifts_table( $settings = array() ) {
		$manual_gifts_ids  = array();
		$allowed_rules     = array();
		$manual_gifts_rule = $this->manager->get_rules( 'manual' );

		foreach ( WC()->cart->get_cart() as $cart ) {
			if ( isset( $cart['basel_is_free_gift'] ) ) {
				continue;
			}

			$product = $cart['data'];

			foreach ( $manual_gifts_rule as $gift_rule_id => $gift_rule ) {
				if ( empty( $gift_rule['free_gifts'] ) ) {
					continue;
				}

				if ( ! in_array( $gift_rule_id, $allowed_rules, true ) && $this->manager->check_free_gifts_condition( $gift_rule, $product ) && $this->manager->check_free_gifts_totals( $gift_rule ) ) {
					$manual_gifts_ids = array_merge( $manual_gifts_ids, $gift_rule['free_gifts'] );
					$allowed_rules[]  = $gift_rule_id;
				}
			}
		}

		$manual_gifts_ids = array_unique( $manual_gifts_ids );

		if ( empty( $manual_gifts_ids ) ) {
			return;
		}

		wc_get_template(
			'cart/free-gifts-table.php',
			array(
				'data'     => $manual_gifts_ids,
				'settings' => $settings,
			)
		);
	}

	/**
	 * Get cart item remove link.
	 *
	 * @param string $remove_link Remove link.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return string
	 */
	public function cart_item_remove_link( $remove_link, $cart_item_key ) {
		if ( ! is_object( WC()->cart ) ) {
			return $remove_link;
		}

		$cart_items = WC()->cart->get_cart();

		if ( isset( $cart_items[ $cart_item_key ]['basel_is_free_gift_automatic'] ) ) {
			return '';
		}

		return $remove_link;
	}

	/**
	 * Update title in cart for free gifts product.
	 *
	 * @codeCoverageIgnore
	 * @param string $item_name Product title.
	 * @param array  $item Product data.
	 *
	 * @return string
	 */
	public function cart_item_name( $item_name, $item ) {
		if ( ! empty( $item['basel_is_free_gift'] ) ) {
			ob_start();

			?>
			<span class="basel-cart-label basel-fg-label basel-tooltip">
				<?php esc_html_e( 'Free gift', 'basel' ); ?>
			</span>
			<?php

			$item_name .= ob_get_clean();
		}

		return $item_name;
	}

	/**
	 * Set the cart item price html.
	 *
	 * @codeCoverageIgnore
	 * @param string $price Price html.
	 * @param array  $cart_item The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return string
	 */
	public function set_cart_item_price( $price, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['basel_is_free_gift'] ) ) {
			return $price;
		}

		return $this->get_gift_product_price( $price, $cart_item );
	}

	/**
	 * Set the cart item subtotal.
	 *
	 * @codeCoverageIgnore
	 * @param string $price Price html.
	 * @param array  $cart_item The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return string
	 */
	public function set_cart_item_subtotal( $price, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['basel_is_free_gift'] ) ) {
			return $price;
		}

		return $this->get_gift_product_price( $price, $cart_item, true );
	}

	/**
	 * Cart item quantity.
	 *
	 * @codeCoverageIgnore
	 * @param string $quantity Quantity content.
	 * @param string $cart_item_key Product key.
	 *
	 * @return string
	 */
	public function cart_item_quantity( $quantity, $cart_item_key ) {
		$item = WC()->cart->get_cart_item( $cart_item_key );

		if ( isset( $item['basel_is_free_gift'] ) && ! $item['data']->is_sold_individually() ) {
			return '<span>' . $item['quantity'] . '</span>';
		}

		return $quantity;
	}

	/**
	 * Widget cart item quantity.
	 *
	 * @param boolean $show Show quantity.
	 * @param string  $cart_item_key Product key.
	 *
	 * @return bool
	 */
	public function widget_cart_item_quantity( $show, $cart_item_key ) {
		$item = WC()->cart->get_cart_item( $cart_item_key );

		if ( isset( $item['basel_is_free_gift'] ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Set the quantity limit for gift products.
	 *
	 * @param string  $cart_item_key Item key.
	 * @param integer $quantity New quantity.
	 * @param integer $old_quantity Old quantity.
	 * @param object  $cart Cart data.
	 *
	 * @return void
	 */
	public function update_cart_item_quantity( $cart_item_key, $quantity, $old_quantity, $cart ) {
		if ( ! isset( $cart->cart_contents[ $cart_item_key ]['basel_is_free_gift'] ) || ( ! isset( $cart->cart_contents[ $cart_item_key ]['basel_is_free_gift_automatic'] ) && basel_get_opt( 'free_gifts_allow_multiple_identical_gifts' ) ) ) {
			return;
		}

		if ( $quantity > 1 ) {
			if ( ! isset( $cart->cart_contents[ $cart_item_key ]['basel_is_free_gift_automatic'] ) && ! wc_has_notice( $this->manager->get_notices( 'already_added' ), 'error' ) ) {
				wc_add_notice( $this->manager->get_notices( 'already_added' ), 'error' );
			}

			$cart->cart_contents[ $cart_item_key ]['quantity'] = 1;
		}
	}

	/**
	 * Get the gift product price.
	 *
	 * @codeCoverageIgnore
	 * @param string $price Price html.
	 * @param array  $cart_item The product in the cart.
	 * @param bool   $multiply_qty Is multiply qty.
	 *
	 * @return string
	 */
	public function get_gift_product_price( $price, $cart_item, $multiply_qty = false ) {
		if ( ! isset( $cart_item['basel_is_free_gift'] ) ) {
			return $price;
		}

		$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
		$product    = wc_get_product( $product_id );

		if ( ! is_object( $product ) ) {
			return $price;
		}

		$product_price = $multiply_qty ? (float) $cart_item['quantity'] * (float) $product->get_price() : $product->get_price();

		if ( 'discount' === basel_get_opt( 'free_gifts_price_format', 'text' ) ) {
			ob_start();
			?>
			<span class="price">
				<del><?php echo wc_price( $product_price ); // phpcs:ignore ?></del>
				<ins><?php echo wc_price( apply_filters( 'basel_free_gift_set_product_cart_price', 0, $cart_item ) ); // phpcs:ignore ?></ins>
			</span>
			<?php
			$display_price = ob_get_clean();
		} else {
			ob_start();
			?>
			<span class="amount">
				<?php esc_html_e( 'Free', 'basel' ); ?>
			</span>
			<?php
			$display_price = ob_get_clean();
		}

		return $display_price;
	}
}

Frontend::get_instance();
