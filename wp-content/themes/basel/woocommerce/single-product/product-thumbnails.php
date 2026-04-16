<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     9.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product;

if ( ! $product || ! $product instanceof WC_Product ) {
	return '';
}

$is_quick_view = basel_loop_prop( 'is_quick_view' );

$attachment_ids = $product->get_gallery_image_ids();

$thums_position = basel_get_opt('thums_position');

$product_design = basel_product_design();


// Full size images for sticky product design
if( $product_design == 'sticky' ) {
	$thums_position = 'bottom';
}


if ( $attachment_ids && $product->get_image_id() ) {
	foreach ( $attachment_ids as $index => $attachment_id ) {
		$full_size_image  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$thumbnail        = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );
		$alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
		$alt_text          = ( empty( $alt_text ) && ( $product instanceof WC_Product ) ) ? woocommerce_get_alt_from_product_title_and_position( $product->get_title(), false, $index ) : $alt_text;

		$attributes = array(
			'title'                   => get_post_field( 'post_title', $attachment_id ),
			'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
			'data-src'                => isset( $full_size_image[0] ) ? $full_size_image[0] : '',
			'data-large_image'        => isset( $full_size_image[0] ) ? $full_size_image[0] : '',
			'data-large_image_width'  => isset( $full_size_image[1] ) ? $full_size_image[1] : '',
			'data-large_image_height' => isset( $full_size_image[2] ) ? $full_size_image[2] : '',
			'class'                   => apply_filters( 'basel_single_product_gallery_image_class', '' ),
			'alt'                     => $alt_text,
		);

		$html  = '<figure data-thumb="' . esc_url( $thumbnail[0] ) . '" data-thumb-alt="' . $alt_text . '" class="woocommerce-product-gallery__image"><a href="' . esc_url( $full_size_image[0] ) . '">';
		$html .= wp_get_attachment_image( $attachment_id, 'woocommerce_single', false, $attributes );
 		$html .= '</a></figure>';

		/**
		 * Filter product image thumbnail HTML string.
		 *
		 * @since 1.6.4
		 *
		 * @param string $html          Product image thumbnail HTML string.
		 * @param int    $attachment_id Attachment ID.
		 */
		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $attachment_id );
	}
}
