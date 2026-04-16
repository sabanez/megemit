<?php
/**
 * Frequently bought together class.
 *
 * @package basel
 */

namespace XTS\Modules\Frequently_Bought_Together;

use XTS\Singleton;

/**
 * Frontend class.
 */
class Frontend extends Singleton {
	/**
	 * Frequently bought together products.
	 *
	 * @var array
	 */
	protected $wfbt_products = array();

	/**
	 * Frequently bought together main product id.
	 *
	 * @var string
	 */
	protected $main_product_id = '';

	/**
	 * Bundle ID.
	 *
	 * @var string
	 */
	protected $bundle_id = '';

	/**
	 * Subtotal bundle products price.
	 *
	 * @var array
	 */
	protected $subtotal_products_price = array();

	/**
	 * Init.
	 */
	public function init() {
		add_action( 'woocommerce_after_main_content', array( $this, 'get_bought_together_products' ), 15 );

		add_action( 'wp_ajax_basel_update_frequently_bought_price', array( $this, 'update_frequently_bought_price' ) );
		add_action( 'wp_ajax_nopriv_basel_update_frequently_bought_price', array( $this, 'update_frequently_bought_price' ) );

		add_filter( 'basel_localized_string_array', array( $this, 'update_localized_string' ) );
	}

	/**
	 * Update localized settings
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function update_localized_string( $settings ) {
		$settings['frequently_bought'] = wp_create_nonce( 'basel-frequently-bought-together' );

		return $settings;
	}

	/**
	 * Update ajax frequently bought price.
	 *
	 * @return void
	 */
	public function update_frequently_bought_price() {
		if ( empty( $_POST['main_product'] ) || empty( $_POST['products_id'] ) || empty( $_POST['bundle_id'] ) ) {
			return;
		}

		$bundle_id    = sanitize_text_field( wp_unslash( $_POST['bundle_id'] ) );
		$main_product = sanitize_text_field( wp_unslash( $_POST['main_product'] ) );
		$products_id  = basel_clean( $_POST['products_id'] ); //phpcs:ignore
		$fbt_products = get_post_meta( $bundle_id, '_basel_fbt_products', true );
		$fragments    = array();

		$this->subtotal_products_price = array();

		if ( ! $fbt_products ) {
			return;
		}

		foreach ( $fbt_products as $fbt_product ) {
			$this->wfbt_products[ $fbt_product['id'] ] = $fbt_product;
		}

		$this->main_product_id = (int) $main_product;
		$this->bundle_id       = $bundle_id;

		if ( $products_id ) {
			foreach ( $products_id as $id => $variation_id ) {
				if ( ! isset( $this->wfbt_products[ $id ] ) && $id !== (int) $main_product && $variation_id !== (int) $main_product ) {
					continue;
				}

				if ( $variation_id ) {
					$variation_product = wc_get_product( $variation_id );

					$fragments[ 'div.basel-fbt-bundle-' . $this->bundle_id . ' .basel-product-' . $id . ' .price' ] = '<span class="price">' . $this->update_product_price( $variation_product->get_price_html(), $variation_product ) . '</span>';
				} else {
					$current_product = wc_get_product( $id );
					$this->update_product_price( $current_product->get_price_html(), $current_product );
				}
			}
		}

		$fbt_count = count( $this->subtotal_products_price );

		$fragments[ 'div.basel-fbt-bundle-' . $this->bundle_id . ' .basel-fbt-purchase .price' ]          = '<span class="price">' . $this->get_subtotal_bundle_price() . '</span>';
		$fragments[ 'div.basel-fbt-bundle-' . $this->bundle_id . ' .basel-fbt-purchase .basel-fbt-desc' ] = '<div class="basel-fbt-desc">' . sprintf( _n( 'For %s item', 'For %s items', $fbt_count, 'basel' ), $fbt_count ) . '</div>';

		wp_send_json(
			array(
				'fragments' => $fragments,
			)
		);
	}

	/**
	 * Get bought together products content.
	 *
	 * @param array $element_settings Settings.
	 *
	 * @return void
	 */
	public function get_bought_together_products() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		global $product;

		$main_product          = $product->get_id();
		$this->main_product_id = $main_product;
		$bundles_data          = array();

		$bundles_id = get_post_meta( $main_product, '_basel_fbt_bundles_id', true );

		if ( ! $bundles_id || ! is_array( $bundles_id ) ) {
			return;
		}

		foreach ( $bundles_id as $bundle_id ) {
			$bundle = get_post( $bundle_id );

			if ( ! $bundle ) {
				continue;
			}

			$wfbt_products = get_post_meta( $bundle->ID, '_basel_fbt_products', true );

			if ( ! $wfbt_products ) {
				continue;
			}

			$bundles_data[ $bundle->ID ] = $wfbt_products;
		}

		if ( ! $bundles_data ) {
			return;
		}

		basel_enqueue_inline_style( 'woo-opt-fbt' );

		add_filter( 'woocommerce_get_price_html', array( $this, 'update_product_price' ), 10, 2 );
		add_filter( 'basel_product_label_output', array( $this, 'added_sale_label' ) );
		add_filter( 'post_thumbnail_id', array( $this, 'update_variation_image' ), 10, 2 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

		?>
		<div class="container basel-fbt-wrap">
			<h4 class="title slider-title">
				<?php esc_html_e( 'Frequently bought together', 'basel' ); ?>
			</h4>
		<?php

		foreach ( $bundles_data as $bundle_id => $wfbt_products ) {
			$this->bundle_id               = $bundle_id;
			$this->wfbt_products           = array();
			$this->subtotal_products_price = array();

			basel_set_loop_prop( 'show_quick_shop', false );

			if ( get_post_meta( $bundle_id, '_basel_show_checkbox', true ) && ! $product->is_in_stock() ) {
				if ( get_post_meta( $bundle_id, '_basel_hide_out_of_stock_product', true ) ) {
					continue;
				} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
					continue;
				}
			}

			foreach ( $wfbt_products as $wfbt_product ) {
				if ( empty( $wfbt_product['id'] ) || $this->main_product_id === (int) $wfbt_product['id'] ) {
					continue;
				}

				$current_product = wc_get_product( $wfbt_product['id'] );

				if ( ! $current_product ) {
					continue;
				}

				if ( 'variation' === $current_product->get_type() && $current_product->get_parent_id() && $this->main_product_id === $current_product->get_parent_id() ) {
					continue;
				}

				if ( get_post_meta( $bundle_id, '_basel_show_checkbox', true ) && ! $current_product->is_in_stock() ) {
					if ( get_post_meta( $bundle_id, '_basel_hide_out_of_stock_product', true ) ) {
						continue;
					} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
						continue;
					}
				}

				$this->wfbt_products[ $wfbt_product['id'] ] = $wfbt_product;
			}

			$this->get_form_content();
		}

		echo '</div>';

		basel_set_loop_prop( 'show_quick_shop', true );

		remove_filter( 'woocommerce_get_price_html', array( $this, 'update_product_price' ), 10, 2 );
		remove_filter( 'basel_product_label_output', array( $this, 'added_sale_label' ) );
		remove_filter( 'post_thumbnail_id', array( $this, 'update_variation_image' ) );

		if ( basel_get_opt( 'catalog_mode' ) || ! is_user_logged_in() && basel_get_opt( 'login_prices' ) ) {
			return;
		}

		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	/**
	 * Get form content.
	 *
	 * @return void
	 */
	public function get_form_content() {
		global $product;

		$products_id = array_column( $this->wfbt_products, 'id' );

		array_unshift( $products_id, $product->get_id() );

		$atts = array(
			'query_post_type'        => array( 'product', 'product_variation' ),
			'post_type'              => 'ids',
			'include'                => implode( ',', $products_id ),
			'layout'                 => 'carousel',
			'orderby'                => 'post__in',
			'slides_per_view'        => basel_get_opt( 'bought_together_column', 3 ),
			'slides_per_view_tablet' => basel_get_opt( 'bought_together_column_tablet', 'auto' ),
			'slides_per_view_mobile' => basel_get_opt( 'bought_together_column_mobile', 'auto' ),
			'spacing'                => 30,
		);

		?>
			<div class="basel-fbt basel-design-side basel-fbt-bundle-<?php echo esc_attr( $this->bundle_id ); ?>">
				<?php

				echo basel_shortcode_products( $atts ); //phpcs:ignore

				$this->get_products_purchase();
				?>
			</div>
		<?php
	}

	/**
	 * Get purchase content.
	 *
	 * @return void
	 */
	protected function get_products_purchase() {
		global $product;

		if ( ! $product ) {
			$product = wc_get_product( $this->main_product_id );
		}

		$fbt_count      = count( $this->subtotal_products_price );
		$fbt_products   = array_column( $this->wfbt_products, 'id' );
		$show_checkbox  = get_post_meta( $this->bundle_id, '_basel_show_checkbox', true );
		$state_checkbox = get_post_meta( $this->bundle_id, '_basel_default_checkbox_state', true );
		$classes        = '';
		$button_classes = '';

		array_unshift( $fbt_products, $product->get_id() );

		if ( ! empty( $show_checkbox ) && 'uncheck' === $state_checkbox ) {
			$classes        .= ' basel-checkbox-uncheck';
			$button_classes .= ' disabled';
			$fbt_count       = 1;
		}

		if ( ! empty( $show_checkbox ) ) {
			$classes .= ' basel-checkbox-on';
		}

		?>
		<form class="basel-fbt-form<?php echo esc_attr( $classes ); ?>" method="post">
			<input type="hidden" name="basel-fbt-bundle-id" value="<?php echo esc_attr( $this->bundle_id ); ?>">
			<input type="hidden" name="basel-fbt-main-product" value="<?php echo esc_attr( $product->get_id() ); ?>">

			<div class="basel-fbt-products">
				<?php foreach ( $fbt_products as $id ) : ?>
					<?php
					$current_product = wc_get_product( $id );
					$product_id      = $current_product->get_id();
					$variation       = '';

					if ( 'variable' === $current_product->get_type() && $current_product->get_children() ) {
						$variation = wc_get_product( $this->get_default_variation_product_id( $current_product ) );
					}

					?>
					<div class="basel-fbt-product basel-product-<?php echo esc_attr( $product_id ); ?>" data-id="<?php echo esc_attr( $product_id ); ?>">
						<div class="basel-fbt-product-heading" for="basel-fbt-product-<?php echo esc_attr( $product_id ); ?>">
							<?php if ( ! empty( $show_checkbox ) ) : ?>
								<?php
								$checkbox_attr = '';

								if ( $product_id === $product->get_id() || ! $state_checkbox || 'check' === $state_checkbox ) {
									$checkbox_attr .= 'checked';
								}
								if ( $product_id === $product->get_id() ) {
									$checkbox_attr .= ' disabled';
								}

								?>
								<input type="checkbox" id="basel-fbt-product-<?php echo esc_attr( $product_id ); ?>" data-id="<?php echo esc_attr( $product_id ); ?>" <?php echo esc_attr( $checkbox_attr ); ?>>
							<?php endif; ?>
							<label for="basel-fbt-product-<?php echo esc_attr( $product_id ); ?>">
								<span class="basel-entities-title title">
									<?php echo esc_html( $current_product->get_name() ); ?>
								</span>
							</label>
							<span class="price">
								<?php if ( $variation ) : ?>
									<?php echo  $variation->get_price_html(); // phpcs:ignore ?>
								<?php else : ?>
									<?php echo $current_product->get_price_html(); // phpcs:ignore ?>
								<?php endif; ?>
							</span>
						</div>
						<?php if ( $variation ) : ?>
							<div class="basel-fbt-product-variation">
								<select>
									<?php foreach ( $current_product->get_children() as $variation_id ) : ?>
										<?php
										$variation_product = wc_get_product( $variation_id );
										$image_src         = wp_get_attachment_image_url( $variation_product->get_image_id(), 'woocommerce_thumbnail' );
										$image_srcset      = wp_get_attachment_image_srcset( $variation_product->get_image_id(), 'woocommerce_thumbnail' );
										?>

										<option value="<?php echo esc_attr( $variation_product->get_id() ); ?>"<?php echo esc_attr( $variation->get_id() === $variation_product->get_id() ? ' selected="selected"' : '' ); ?> data-image-src="<?php echo esc_url( $image_src ); ?>" data-image-srcset="<?php echo esc_attr( $image_srcset ); ?>">
											<?php echo esc_html( wc_get_formatted_variation( $variation_product, true, false, false ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="basel-fbt-purchase">
				<div class="price">
					<?php
					if ( ! empty( $show_checkbox ) && 'uncheck' === $state_checkbox ) {
						echo $product->get_price_html(); // phpcs:ignore
					} else {
						echo wp_kses( $this->get_subtotal_bundle_price(), true );
					}
					?>
				</div>
				<div class="basel-fbt-desc">
					<?php
					echo wp_kses(
						sprintf( _n( 'For %s item', 'For %s items', $fbt_count, 'basel' ), $fbt_count ),
						true
					);
					?>
				</div>
				<button class="basel-fbt-purchase-btn single_add_to_cart_button button<?php echo esc_attr( $button_classes ); ?>" type="submit">
					<?php esc_html_e( 'Add to cart', 'basel' ); ?>
				</button>
			</div>
			<div class="basel-loader-overlay basel-fill"></div>
		</form>
		<?php
	}

	/**
	 * Get subtotal products price in bundle.
	 *
	 * @return string
	 */
	private function get_subtotal_bundle_price() {
		global $product;

		if ( ! $product ) {
			$product = wc_get_product( $this->main_product_id );
		}

		$old_price = array_sum( array_column( $this->subtotal_products_price, 'old' ) );
		$new_price = array_sum( array_column( $this->subtotal_products_price, 'new' ) );

		if ( $old_price <= $new_price ) {
			return wc_price( $new_price ) . $this->get_product_price_suffix();
		}

		return wc_format_sale_price( $old_price, $new_price ) . $this->get_product_price_suffix();
	}

	/**
	 * Get products price suffix.
	 *
	 * @return mixed|null
	 */
	private function get_product_price_suffix() {
		global $product;

		if ( ! $product ) {
			$product = wc_get_product( $this->main_product_id );
		}

		$html              = '';
		$suffix            = get_option( 'woocommerce_price_display_suffix' );
		$sum_including_tax = 0;
		$sum_excluding_tax = 0;

		if ( $suffix && wc_tax_enabled() ) {
			$products = $this->wfbt_products + array( $this->main_product_id => array() );

			foreach ( $products as $product_id => $product_settings ) {
				$current_product = wc_get_product( $product_id );

				if ( 'taxable' !== $current_product->get_tax_status() ) {
					continue;
				}

				$discount  = $this->get_discount_product_bundle( $product_id );
				$old_price = (float) wc_get_price_to_display( $current_product, array( 'price' => $current_product->get_price() ) );

				$new_price          = $old_price - ( ( $old_price / 100 ) * $discount );
				$sum_including_tax += (float) wc_get_price_including_tax( $current_product, array( 'price' => $new_price ) );
				$sum_excluding_tax += (float) wc_get_price_excluding_tax( $current_product, array( 'price' => $new_price ) );
			}

			if ( $sum_including_tax || $sum_excluding_tax ) {
				$replacements = array(
					'{price_including_tax}' => wc_price( $sum_including_tax ),
					'{price_excluding_tax}' => wc_price( $sum_excluding_tax ),
				);

				$html = str_replace( array_keys( $replacements ), array_values( $replacements ), ' <small class="woocommerce-price-suffix">' . wp_kses_post( $suffix ) . '</small>' );
			}
		}

		return apply_filters( 'woocommerce_get_price_suffix', $html, $product, array_sum( array_column( $this->subtotal_products_price, 'new' ) ), 1 );
	}

	/**
	 * Update product price.
	 *
	 * @param string $price Product price HTML.
	 * @param object $product Product data.
	 *
	 * @return string
	 */
	public function update_product_price( $price, $product ) {
		$product_id = $product->get_ID();

		if ( 'variation' === $product->get_type() && ! isset( $this->wfbt_products[ $product_id ] ) ) {
			$product_parent = wc_get_product( $product->get_parent_id() );
			$product_id     = $product_parent->get_ID();
		}

		$discount = $this->get_discount_product_bundle( $product_id );

		$old_price          = (float) $product->get_price();
		$old_price_with_tax = (float) wc_get_price_to_display( $product, array( 'price' => $old_price ) );

		$this->subtotal_products_price[ $product_id ]['old'] = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );

		if ( ! $discount || 100 <= $discount ) {
			$this->subtotal_products_price[ $product_id ]['new'] = $old_price_with_tax;

			return $price;
		}

		$new_price          = $old_price - ( ( $old_price / 100 ) * $discount );
		$new_price_with_tax = wc_get_price_to_display( $product, array( 'price' => $new_price ) );

		$this->subtotal_products_price[ $product_id ]['new'] = $new_price_with_tax;

		if ( 'variable' === $product->get_type() ) {
			$prices = $product->get_variation_prices( true );

			if ( empty( $prices['price'] ) ) {
				return $price;
			} else {
				$min_price = (float) current( $prices['price'] );
				$max_price = (float) end( $prices['price'] );

				$min_price = $min_price - ( ( $min_price / 100 ) * $discount );
				$max_price = $max_price - ( ( $max_price / 100 ) * $discount );

				if ( $min_price !== $max_price ) {
					$price = wc_format_price_range( $min_price, $max_price );
				} else {
					$price = wc_format_sale_price( wc_price( end( $prices['regular_price'] ) ), wc_price( $min_price ) );
				}

				return $price . $product->get_price_suffix();
			}
		}

		return wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), $new_price_with_tax ) . $product->get_price_suffix( $new_price );
	}

	/**
	 * Added product sale label.
	 *
	 * @param array $content Labels.
	 *
	 * @return array
	 */
	public function added_sale_label( $content ) {
		global $product;

		$product_id = $product->get_ID();

		if ( 'variation' === $product->get_type() && ! isset( $this->wfbt_products[ $product_id ] ) ) {
			$product_parent = wc_get_product( $product->get_parent_id() );
			$product_id     = $product_parent->get_ID();
		}

		$discount = (int) $this->get_discount_product_bundle( $product_id );

		if ( ! $discount || 100 <= $discount ) {
			return $content;
		}

		if ( $product->is_on_sale() ) {
			$percentage = 0;

			if ( 'variable' === $product->get_type() ) {
				$available_variations = $product->get_variation_prices();
				$max_percentage       = 0;

				foreach ( $available_variations['regular_price'] as $key => $regular_price ) {
					$sale_price = $available_variations['sale_price'][ $key ];

					if ( $sale_price < $regular_price ) {
						$percentage = round( ( ( (float) $regular_price - (float) $sale_price ) / (float) $regular_price ) * 100 );

						if ( $percentage > $max_percentage ) {
							$max_percentage = $percentage;
						}
					}
				}

				$percentage = $max_percentage;
			} elseif ( in_array( $product->get_type(), array( 'simple', 'external', 'variation' ), true ) ) {
				$percentage = round( ( ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) / (float) $product->get_regular_price() ) * 100 );
			}

			$discount += (int) $percentage;
		}

		$label = '<span class="onsale product-label basel-fbt-sale-label">' . sprintf( _x( '-%d%%', 'sale percentage', 'basel' ), $discount ) . '</span>';

		array_unshift( $content, $label );

		return $content;
	}

	/**
	 * Get discount product price.
	 *
	 * @param integer $product_id Product ID.
	 *
	 * @return false|float
	 */
	private function get_discount_product_bundle( $product_id ) {
		if ( $this->main_product_id === $product_id ) {
			$discount = (float) get_post_meta( $this->bundle_id, '_basel_main_products_discount', true );
		} elseif ( isset( $this->wfbt_products[ $product_id ] ) ) {
			$discount = (float) $this->wfbt_products[ $product_id ]['discount'];
		} else {
			return false;
		}

		return $discount;
	}

	/**
	 * Get default variation product id.
	 *
	 * @param object $product Product data.
	 *
	 * @return false|mixed
	 */
	private function get_default_variation_product_id( $product ) {
		if ( $product->get_default_attributes() ) {
			if ( get_transient( 'fbt_default_variation_id_' . $product->get_id() ) ) {
				return get_transient( 'fbt_default_variation_id_' . $product->get_id() );
			} else {
				$is_default_variation = false;

				foreach ( $product->get_available_variations() as $variation_values ) {
					foreach ( $variation_values['attributes'] as $key => $attribute_value ) {
						$attribute_name = str_replace( 'attribute_', '', $key );
						$default_value  = $product->get_variation_default_attribute( $attribute_name );

						if ( $default_value === $attribute_value ) {
							$is_default_variation = true;
						} else {
							$is_default_variation = false;
						}
					}

					if ( $is_default_variation ) {
						set_transient( 'fbt_default_variation_id_' . $product->get_id(), $variation_values['variation_id'] );

						return $variation_values['variation_id'];
					}
				}
			}
		}

		return current( $product->get_children() );
	}

	/**
	 * Update main image for variable product.
	 *
	 * @param integer $image_id Product image ID.
	 * @return string
	 */
	public function update_variation_image( $image_id, $post ) {
		global $product;

		if ( ! $product || 'variable' !== $product->get_type() ) {
			return $image_id;
		}

		$variation_id = $this->get_default_variation_product_id( $product );
		$thumbnail_id = (int) get_post_meta( $variation_id, '_thumbnail_id', true );

		if ( ! $thumbnail_id ) {
			return $image_id;
		}

		return $thumbnail_id;
	}
}

Frontend::get_instance();
