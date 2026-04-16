<?php
/**
 * Free gifts table.
 *
 * @var string $wrapper_classes String with wrapper classes.
 * @var array  $data Data for render table.
 *
 * @package Basel
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use XTS\Modules\Free_Gifts\Manager;

$add_gift_btn_disabled = false;

if ( basel_get_opt( 'free_gifts_allow_multiple_identical_gifts' ) && Manager::get_instance()->get_gifts_in_cart_count() >= basel_get_opt( 'free_gifts_limit', 5 ) ) {
	$add_gift_btn_disabled = true;
}

basel_enqueue_inline_style( 'woo-opt-free-gifts' );

?>

<?php do_action( 'basel_before_free_gifts_table' ); ?>

<?php if ( ! isset( $settings['show_title'] ) || 'yes' === $settings['show_title'] ) : ?>
	<h4 class="title">
		<?php echo esc_html( _n( 'Choose your gift', 'Choose your gifts', count( $data ), 'basel' ) ); ?>
	</h4>
<?php endif; ?>

<table class="basel-fg-table shop_table shop_table_responsive shop-table-with-img">
	<tbody>
	<?php foreach ( $data as $free_gift_id ) : ?>
		<?php
		$free_gift_id      = apply_filters( 'wpml_object_id', $free_gift_id, 'product', true, apply_filters( 'wpml_current_language', null ) );
		$free_gift_product = wc_get_product( $free_gift_id );
		$product_permalink = apply_filters( 'basel_free_gift_item_permalink', $free_gift_product->is_visible() ? $free_gift_product->get_permalink() : '', $free_gift_id );
		$product_name      = apply_filters( 'basel_free_gift_item_name', $free_gift_product->get_name(), $free_gift_id );

		if ( ! basel_get_opt( 'free_gifts_allow_multiple_identical_gifts' ) ) {
			$add_gift_btn_disabled = false;

			if ( Manager::get_instance()->check_is_gift_in_cart( $free_gift_id ) ) {
				$add_gift_btn_disabled = true;
			}
		}
		?>
		<tr>
			<td class="product-thumbnail">
				<?php
				if ( ! $product_permalink ) {
					echo apply_filters( 'basel_free_gift_item_thumbnail', $free_gift_product->get_image(), $free_gift_id );
				} else {
					printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), apply_filters( 'basel_free_gift_item_thumbnail', $free_gift_product->get_image(), $free_gift_id ) );
				}
				?>
			</td>
			<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'basel' ); ?>">
				<?php
				if ( ! $product_permalink ) {
					echo wp_kses_post( $product_name . '&nbsp;' );
				} else {
					/**
					 * This filter is documented above.
					 *
					 * @since 7.8.0
					 * @param string $product_url URL the product in the cart.
					 */
					echo wp_kses_post( apply_filters( 'basel_free_gift_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $free_gift_product->get_name() ), $free_gift_id ) );
				}
				?>
			</td>
			<td class="product-btn">
				<a class="button basel-add-gift-product<?php echo $add_gift_btn_disabled ? ' basel-disabled' : ''; ?>" data-product-id="<?php echo esc_attr( $free_gift_id ); ?>" data-security="<?php echo esc_attr( wp_create_nonce( 'basel_free_gift_' . $free_gift_id ) ); ?>" href="#">
					<?php echo esc_html__( 'Add to cart', 'basel' ); ?>
				</a>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<div class="basel-loader-overlay basel-fill"></div>

<?php do_action( 'basel_after_free_gifts_table' ); ?>
