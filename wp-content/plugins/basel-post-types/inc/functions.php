<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}
if ( ! function_exists( 'basel_widgets_init' ) ) {
	function basel_widgets_init() {
		if ( ! is_blog_installed() || ! class_exists( 'BASEL_WP_Nav_Menu_Widget' ) ) {
			return;
		}

		register_widget( 'BASEL_WP_Nav_Menu_Widget' );
		register_widget( 'BASEL_Banner_Widget' );
		register_widget( 'BASEL_Author_Area_Widget' );
		register_widget( 'BASEL_Instagram_Widget' );
		register_widget( 'BASEL_Static_Block_Widget' );
		register_widget( 'BASEL_Twitter' );
		register_widget( 'BASEL_Recent_Posts' );

		if ( basel_woocommerce_installed() ) {
			register_widget( 'BASEL_User_Panel_Widget' );
			register_widget( 'BASEL_Widget_Layered_Nav' );
			register_widget( 'BASEL_Widget_Sorting' );
			register_widget( 'BASEL_Widget_Price_Filter' );
			register_widget( 'BASEL_Widget_Search' );
			register_widget( 'BASEL_Stock_Status' );
		}

	}

	add_action( 'widgets_init', 'basel_widgets_init' );
}

if ( ! function_exists( 'basel_compress' ) ) {
	function basel_compress( $variable ) {
		return base64_encode( $variable );
	}
}

if ( ! function_exists( 'basel_get_file' ) ) {
	function basel_get_file( $variable ) {
		return file_get_contents( $variable );
	}
}

if ( ! function_exists( 'basel_decompress' ) ) {
	function basel_decompress( $variable ) {
		return base64_decode( $variable );
	}
}

/**
 * ------------------------------------------------------------------------------------------------
 * Add metaboxes to the product
 * ------------------------------------------------------------------------------------------------
 */
if ( ! function_exists( 'basel_product_360_view_meta' ) ) {
	add_action( 'add_meta_boxes', 'basel_product_360_view_meta', 50 );
	function basel_product_360_view_meta() {
		add_meta_box( 'woocommerce-product-360-images', __( 'Product 360 View Gallery (optional)', 'basel' ), 'basel_360_metabox_output', 'product', 'side', 'low' );
	}
}

/**
 * ------------------------------------------------------------------------------------------------
 * Add metaboxes
 * ------------------------------------------------------------------------------------------------
 */
if ( ! function_exists( 'basel_sguide_add_metaboxes' ) ) {
	function basel_sguide_add_metaboxes() {
		if ( function_exists( 'basel_get_opt' ) && ! basel_get_opt( 'size_guides' ) ) {
			return;
		}

		// Add table metaboxes to size guide
		add_meta_box( 'basel_sguide_metaboxes', esc_html__( 'Create/modify size guide table', 'basel' ), 'basel_sguide_metaboxes', 'basel_size_guide', 'normal', 'default' );
		// Add metaboxes to product
		add_meta_box( 'basel_sguide_dropdown_template', esc_html__( 'Choose size guide', 'basel' ), 'basel_sguide_dropdown_template', 'product', 'side' );
		// Add category metaboxes to size guide
		add_meta_box( 'basel_sguide_category_template', esc_html__( 'Choose product categories', 'basel' ), 'basel_sguide_category_template', 'basel_size_guide', 'side' );
		// Add hide table checkbox to size guide
		add_meta_box( 'basel_sguide_hide_table_template', esc_html__( 'Hide size guide table', 'basel' ), 'basel_sguide_hide_table_template', 'basel_size_guide', 'side' );
	}
	add_action( 'add_meta_boxes', 'basel_sguide_add_metaboxes' );
}


if ( ! function_exists( 'basel_get_svg' ) ) {
	function basel_get_svg( $file ) {
		if ( ! apply_filters( 'basel_svg_cache', true ) ) {
			return file_get_contents( $file );
		}

		$file_path = array_reverse( explode( '/', $file ) );
		$slug      = 'basel-svg-' . $file_path[2] . '-' . $file_path[1] . '-' . $file_path[0];
		$content   = get_transient( $slug );

		if ( ! $content ) {
			$file_get_contents = file_get_contents( $file );

			if ( strstr( $file_get_contents, '<svg' ) ) {
				$content = base64_encode($file_get_contents);
				set_transient($slug, $content, apply_filters('basel_svg_cache_time', 60 * 60 * 24 * 7));
			}
		}

		return base64_decode( $content );
	}
}

// **********************************************************************//
// ! It could be useful if you using nginx instead of apache
// **********************************************************************//
if ( ! function_exists( 'getallheaders' ) ) {
	function getallheaders() {
		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}
		return $headers;
	}
}
add_shortcode( 'basel_popup', 'basel_shortcode_popup' );
add_shortcode( 'basel_product_filters', 'basel_product_filters_shortcode' );
add_shortcode( 'basel_filter_categories', 'basel_filters_categories_shortcode' );
add_shortcode( 'basel_filters_attribute', 'basel_filters_attribute_shortcode' );
add_shortcode( 'basel_filters_price_slider', 'basel_filters_price_slider_shortcode' );
add_shortcode( 'basel_stock_status', 'basel_stock_status_shortcode' );
add_shortcode( 'basel_filters_orderby', 'basel_orderby_filter_template' );
add_shortcode( 'basel_responsive_text_block', 'basel_shortcode_responsive_text_block' );
add_shortcode( 'basel_compare', 'basel_compare_shortcode' );
add_shortcode( 'basel_slider', 'basel_shortcode_slider' );
add_shortcode( 'basel_title', 'basel_shortcode_title' );
add_shortcode( 'basel_button', 'basel_shortcode_button' );
add_shortcode( 'basel_instagram', 'basel_shortcode_instagram' );
add_shortcode( 'basel_google_map', 'basel_shortcode_google_map' );
add_shortcode( 'basel_portfolio', 'basel_shortcode_portfolio' );
add_shortcode( 'basel_blog', 'basel_shortcode_blog' );
remove_shortcode('gallery');
add_shortcode( 'gallery', 'basel_gallery_shortcode' );
add_shortcode( 'basel_gallery', 'basel_images_gallery_shortcode' );
add_shortcode( 'basel_categories', 'basel_shortcode_categories' );
add_shortcode( 'basel_shortcode_products_widget', 'basel_shortcode_products_widget' );
add_shortcode( 'basel_counter', 'basel_shortcode_animated_counter' );
add_shortcode( 'team_member', 'basel_shortcode_team_member' );
add_shortcode( 'testimonials', 'basel_shortcode_testimonials' );
add_shortcode( 'testimonial', 'basel_shortcode_testimonial' );
add_shortcode( 'pricing_tables', 'basel_shortcode_pricing_tables' );
add_shortcode( 'pricing_plan', 'basel_shortcode_pricing_plan' );
add_shortcode( 'products_tabs', 'basel_shortcode_products_tabs' );
add_shortcode( 'products_tab', 'basel_shortcode_products_tab' );
add_shortcode( 'basel_mega_menu', 'basel_shortcode_mega_menu' );
add_shortcode( 'user_panel', 'basel_shortcode_user_panel' );
add_shortcode( 'author_area', 'basel_shortcode_author_area' );
add_shortcode( 'promo_banner', 'basel_shortcode_promo_banner' );
add_shortcode( 'banners_carousel', 'basel_shortcode_banners_carousel' );
add_shortcode( 'basel_info_box', 'basel_shortcode_info_box' );
add_shortcode( 'basel_info_box_carousel', 'basel_shortcode_info_box_carousel' );
add_shortcode( 'basel_3d_view', 'basel_shortcode_3d_view' );
add_shortcode( 'basel_menu_price', 'basel_shortcode_menu_price' );
add_shortcode( 'basel_countdown_timer', 'basel_shortcode_countdown_timer' );
add_shortcode( 'social_buttons', 'basel_shortcode_social' );
add_shortcode( 'basel_posts_teaser', 'basel_shortcode_posts_teaser' );
add_shortcode( 'basel_posts', 'basel_shortcode_posts' );
add_shortcode( 'basel_products', 'basel_shortcode_products' );
add_shortcode( 'html_block', 'basel_html_block_shortcode');
add_shortcode( 'basel_row_divider', 'basel_row_divider' );
add_shortcode( 'basel_timeline', 'basel_timeline_shortcode' );
add_shortcode( 'basel_timeline_item', 'basel_timeline_item_shortcode' );
add_shortcode( 'basel_timeline_breakpoint', 'basel_timeline_breakpoint_shortcode' );
add_shortcode( 'basel_list', 'basel_list_shortcode' );
add_shortcode( 'extra_menu', 'basel_shortcode_extra_menu' );
add_shortcode( 'extra_menu_list', 'basel_shortcode_extra_menu_list' );
add_shortcode( 'basel_brands', 'basel_shortcode_brands' );
add_shortcode( 'basel_size_guide', 'basel_size_guide_shortcode' );

function basel_init_vc_fields() {
	if ( function_exists( 'vc_add_shortcode_param' ) ) {
		vc_add_shortcode_param( 'basel_gradient', 'basel_add_gradient_type' );
		vc_add_shortcode_param( 'basel_colorpicker', 'basel_get_colorpicker_param' );
		vc_add_shortcode_param( 'basel_css_id', 'basel_get_css_id_param' );
		vc_add_shortcode_param( 'basel_dropdown', 'basel_get_dropdown_param' );
		vc_add_shortcode_param( 'basel_image_select', 'basel_add_image_select_type' );
		vc_add_shortcode_param( 'basel_responsive_size', 'basel_get_responsive_size_param' );
		vc_add_shortcode_param( 'basel_slider', 'basel_get_slider_param' );
	}
}
add_action('init', 'basel_init_vc_fields');
