<?php
/**
 * Plugin Name: WooCommerce Dynamic Pricing
 * Woo: 18643:9a41775bb33843f52c93c922b0053986
 * Plugin URI: https://woocommerce.com/products/dynamic-pricing/
 * Description: WooCommerce Dynamic Pricing lets you configure dynamic pricing rules for products, categories and members.
 * Version: 3.4.12
 * Author: Element Stark
 * Author URI: https://elementstark.com
 * Requires at least: 3.3
 * Tested up to: 6.9
 * Text Domain: woocommerce-dynamic-pricing
 * Domain Path: /i18n/languages/
 * Copyright: © 2012-2026 Element Stark LLC.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 8.0
 * WC tested up to: 10.5
 * Woo: 18643:9a41775bb33843f52c93c922b0053986
 */


/**
 * Required functions
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	require_once 'woo-includes/woo-functions.php';
}

$woocommerce_dynamic_pricing_enabled = true;

if ( is_woocommerce_active() && $woocommerce_dynamic_pricing_enabled ) {

	// Declare support for features
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}
	);

	/**
	 * Boot up dynamic pricing
	 */
	WC_Dynamic_Pricing::init();
}


class WC_Dynamic_Pricing {

	/**
	 * @var WC_Dynamic_Pricing
	 */
	private static $instance;

	public static function init() {
		if ( self::$instance == null ) {
			self::$instance = new WC_Dynamic_Pricing();
		}
	}

	/**
	 * @return WC_Dynamic_Pricing The instance of the plugin.
	 */
	public static function instance() {
		if ( self::$instance == null ) {
			self::init();
		}

		return self::$instance;
	}

	private $cached_adjustments = [];

	public $modules = [];

	public $db_version = '2.1';

	public function __construct() {

		$plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) );

		/* Helper Functions */
		require __DIR__ . '/functions.php';

		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );

		if ( is_admin() ) {
			require __DIR__ . '/admin/admin-init.php';

			//Include and boot up the installer.
			require __DIR__ . '/classes/class-wc-dynamic-pricing-installer.php';
			WC_Dynamic_Pricing_Installer::init();
		}

		//Include additional integrations
		if ( wc_dynamic_pricing_is_groups_active() ) {
			require __DIR__ . '/integrations/groups/groups.php';
		}

		if ( wc_dynamic_pricing_is_memberships_active() ) {
			require __DIR__ . '/integrations/memberships/memberships.php';
		}

		//Paypal express
		require __DIR__ . '/integrations/paypal-express.php';
		require __DIR__ . '/integrations/woocommerce-product-bundles.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-compatibility.php';

		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		$request_uri         = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$is_rest_api_request = str_contains( $request_uri ?? '', $rest_prefix );

		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $is_rest_api_request ) {
			$this->load_front_end( $plugin_dir );
		} else if ( $is_rest_api_request ) {
			$this->load_front_end( $plugin_dir );
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is checking for account creation on checkout.
		if ( isset( $_POST['createaccount'] ) ) {
			add_filter(
				'woocommerce_dynamic_pricing_is_rule_set_valid_for_user',
				[
					$this,
					'new_account_overrides',
				],
				10,
				2
			);
		}

		add_filter( 'woocommerce_dynamic_pricing_get_rule_amount', [ $this, 'convert_decimals' ], 99, 4 );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-dynamic-pricing' );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( 'woocommerce-dynamic-pricing', $dir . 'woocommerce-dynamic-pricing/woocommerce-dynamic-pricing-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-dynamic-pricing', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
	}

	public function load_rest_api_front_end() {
		//Include helper classes
		include 'classes/class-wc-dynamic-pricing-context.php';
		include 'classes/class-wc-dynamic-pricing-counter.php';
		include 'classes/class-wc-dynamic-pricing-tracker.php';
		include 'classes/class-wc-dynamic-pricing-cart-query.php';

		//Include the collectors.
		include 'classes/collectors/class-wc-dynamic-pricing-collector.php';
		include 'classes/collectors/class-wc-dynamic-pricing-collector-category.php';
		include 'classes/collectors/class-wc-dynamic-pricing-collector-category-inclusive.php';
		include 'classes/collectors/class-wc-dynamic-pricing-collector-category-per-product.php';

		//Include the adjustment sets.
		include 'classes/class-wc-dynamic-pricing-adjustment-set.php';
		include 'classes/class-wc-dynamic-pricing-adjustment-set-category.php';
		include 'classes/class-wc-dynamic-pricing-adjustment-set-product.php';
		include 'classes/class-wc-dynamic-pricing-adjustment-set-totals.php';
		include 'classes/class-wc-dynamic-pricing-adjustment-set-taxonomy.php';

		//The base pricing module.
		include 'classes/modules/class-wc-dynamic-pricing-module-base.php';

		//Include the advanced pricing modules.
		include 'classes/modules/class-wc-dynamic-pricing-advanced-base.php';
		include 'classes/modules/class-wc-dynamic-pricing-advanced-product.php';
		include 'classes/modules/class-wc-dynamic-pricing-advanced-category.php';
		include 'classes/modules/class-wc-dynamic-pricing-advanced-totals.php';
		include 'classes/modules/class-wc-dynamic-pricing-advanced-taxonomy.php';

		//Include the simple pricing modules.
		include 'classes/modules/class-wc-dynamic-pricing-simple-base.php';
		include 'classes/modules/class-wc-dynamic-pricing-simple-product.php';
		include 'classes/modules/class-wc-dynamic-pricing-simple-category.php';
		include 'classes/modules/class-wc-dynamic-pricing-simple-membership.php';
		include 'classes/modules/class-wc-dynamic-pricing-simple-taxonomy.php';

		//Boot up the instances of the pricing modules
		$modules['advanced_product']  = WC_Dynamic_Pricing_Advanced_Product::instance();
		$modules['advanced_category'] = WC_Dynamic_Pricing_Advanced_Category::instance();

		$modules['simple_product']    = WC_Dynamic_Pricing_Simple_Product::instance();
		$modules['simple_category']   = WC_Dynamic_Pricing_Simple_Category::instance();
		$modules['simple_membership'] = WC_Dynamic_Pricing_Simple_Membership::instance();

		if ( wc_dynamic_pricing_is_groups_active() ) {
			include 'integrations/groups/class-wc-dynamic-pricing-simple-group.php';
			$modules['simple_group'] = WC_Dynamic_Pricing_Simple_Group::instance();
		}

		if ( wc_dynamic_pricing_is_memberships_active() ) {
			include 'integrations/woocommerce-memberships.php';
			WC_Dynamic_Pricing_Memberships_Integration::register();
		}

		$this->modules = apply_filters( 'wc_dynamic_pricing_load_modules', $modules );

		/* Boot up required classes */
		WC_Dynamic_Pricing_Context::register();

		//Initialize the dynamic pricing counter.  Records various counts when items are restored from session.
		WC_Dynamic_Pricing_Counter::register();

		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ], 0 );
		add_filter( 'woocommerce_product_is_on_sale', [ $this, 'on_get_product_is_on_sale' ], 10, 2 );
		add_filter( 'woocommerce_composite_get_price', [ $this, 'on_get_composite_price' ], 10, 2 );
		add_filter( 'woocommerce_composite_get_base_price', [ $this, 'on_get_composite_base_price' ], 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid', [ $this, 'check_cart_coupon_is_valid' ], 99, 2 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', [ $this, 'check_coupon_is_valid' ], 99, 4 );
		add_filter(
			'woocommerce_get_variation_prices_hash',
			[
				$this,
				'on_woocommerce_get_variation_prices_hash',
			],
			99,
			1
		);

		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'on_cart_loaded_from_session' ], 98, 1 );

		//Add the actions dynamic pricing uses to trigger price adjustments
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'on_calculate_totals' ], 98, 1 );
	}

	public function load_front_end( $plugin_dir ) {
		// Include integration classes
		require __DIR__ . '/classes/class-wc-dynamic-pricing-product-addons-integration.php';
		WC_Dynamic_Pricing_Product_Addons_Integration::register();

		//Include helper classes
		require __DIR__ . '/classes/class-wc-dynamic-pricing-context.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-counter.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-tracker.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-cart-query.php';

		//Include the collectors.
		require __DIR__ . '/classes/collectors/class-wc-dynamic-pricing-collector.php';
		require __DIR__ . '/classes/collectors/class-wc-dynamic-pricing-collector-category.php';
		require __DIR__ . '/classes/collectors/class-wc-dynamic-pricing-collector-category-inclusive.php';
		require __DIR__ . '/classes/collectors/class-wc-dynamic-pricing-collector-category-per-product.php';

		//Include the adjustment sets.
		require __DIR__ . '/classes/class-wc-dynamic-pricing-adjustment-set.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-adjustment-set-category.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-adjustment-set-product.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-adjustment-set-totals.php';
		require __DIR__ . '/classes/class-wc-dynamic-pricing-adjustment-set-taxonomy.php';

		//The base pricing module.
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-module-base.php';

		//Include the advanced pricing modules.
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-advanced-base.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-advanced-product.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-advanced-category.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-advanced-totals.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-advanced-taxonomy.php';

		//Include the simple pricing modules.
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-simple-base.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-simple-product.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-simple-category.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-simple-membership.php';
		require __DIR__ . '/classes/modules/class-wc-dynamic-pricing-simple-taxonomy.php';

		//Include the UX module - This controls the display of discounts on cart items and products.
		require __DIR__ . '/classes/class-wc-dynamic-pricing-frontend-ux.php';

		//Boot up the instances of the pricing modules
		$modules['advanced_product']  = WC_Dynamic_Pricing_Advanced_Product::instance();
		$modules['advanced_category'] = WC_Dynamic_Pricing_Advanced_Category::instance();

		$modules['simple_product']    = WC_Dynamic_Pricing_Simple_Product::instance();
		$modules['simple_category']   = WC_Dynamic_Pricing_Simple_Category::instance();
		$modules['simple_membership'] = WC_Dynamic_Pricing_Simple_Membership::instance();

		if ( wc_dynamic_pricing_is_groups_active() ) {
			include 'integrations/groups/class-wc-dynamic-pricing-simple-group.php';
			$modules['simple_group'] = WC_Dynamic_Pricing_Simple_Group::instance();
		}

		if ( wc_dynamic_pricing_is_memberships_active() ) {
			include 'integrations/woocommerce-memberships.php';
			WC_Dynamic_Pricing_Memberships_Integration::register();
		}

		$modules['advanced_totals'] = WC_Dynamic_Pricing_Advanced_Totals::instance();

		$this->modules = apply_filters( 'wc_dynamic_pricing_load_modules', $modules );


		/* Boot up required classes */
		WC_Dynamic_Pricing_Context::register();

		//Initialize the dynamic pricing counter.  Records various counts when items are restored from session.
		WC_Dynamic_Pricing_Counter::register();

		//Initialize the FrontEnd UX modifications
		WC_Dynamic_Pricing_FrontEnd_UX::init();

		add_action( 'wp_loaded', [ $this, 'on_wp_loaded' ], 0 );
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ], 0 );
		add_filter( 'woocommerce_product_is_on_sale', [ $this, 'on_get_product_is_on_sale' ], 10, 2 );
		add_filter( 'woocommerce_composite_get_price', [ $this, 'on_get_composite_price' ], 10, 2 );
		add_filter( 'woocommerce_composite_get_base_price', [ $this, 'on_get_composite_base_price' ], 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid', [ $this, 'check_cart_coupon_is_valid' ], 99, 2 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', [ $this, 'check_coupon_is_valid' ], 99, 4 );
		add_filter(
			'woocommerce_get_variation_prices_hash',
			[
				$this,
				'on_woocommerce_get_variation_prices_hash',
			],
			99,
			1
		);

		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'on_cart_loaded_from_session' ], 98, 1 );

		//Add the actions dynamic pricing uses to trigger price adjustments
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'on_calculate_totals' ], 98, 1 );
	}


	public function on_woocommerce_get_variation_prices_hash( $price_hash ) {
		//Get a key based on role, since all rules use roles.
		$result = is_array( $price_hash ) ? $price_hash : [ $price_hash ];

		$roles = [];
		if ( is_user_logged_in() ) {
			$user = new WP_User( get_current_user_id() );
			if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
				foreach ( $user->roles as $role ) {
					$roles[ $role ] = $role;
				}
			}
		}

		if ( ! empty( $roles ) ) {
			$session_id = implode( '', $roles );
		} else {
			$session_id = 'norole';
		}

		$result[] = $session_id;

		return $result;
	}


	/**
	 * Add the price filters back in after mini-cart is done.
	 *
	 * @since 2.10.2
	 */
	public function add_price_filters() {

		add_filter( 'woocommerce_variation_prices_price', [ $this, 'on_get_variation_prices_price' ], 10, 3 );
		add_filter(
			'woocommerce_product_variation_get_price',
			[
				$this,
				'on_get_product_variation_price',
			],
			10,
			2
		);

		//Filters the regular product get price.
		add_filter( 'woocommerce_product_get_price', [ $this, 'on_get_price' ], 10, 2 );

		//Filters subscription prices.
		add_filter( 'woocommerce_subscriptions_product_price', [ $this, 'on_get_price' ], 10, 2 );
	}

	/**
	 * Remove the price filter when mini-cart is triggered.
	 *
	 * @since 2.10.2
	 */
	public function remove_price_filters() {
		if ( WC_Dynamic_Pricing_Compatibility::is_wc_version_gte_2_7() ) {
			//Filters the regular variation price
			remove_filter(
				'woocommerce_product_variation_get_price',
				[
					$this,
					'on_get_product_variation_price',
				],
				10,
				2
			);

			//Filters the regular product get price.
			remove_filter( 'woocommerce_product_get_price', [ $this, 'on_get_price' ], 10, 2 );

			//Filters subscription prices.
			remove_filter( 'woocommerce_subscriptions_product_price', [ $this, 'on_get_price' ], 10, 2 );

		} else {
			remove_filter( 'woocommerce_get_price', [ $this, 'on_get_price' ], 10, 2 );
		}
	}


	public function on_wp_loaded(): void {
		// Force calculation of totals so that they are updated in the mini-cart.  Super legacy WC versions have issues with this.
		if ( WC_Dynamic_Pricing_Compatibility::is_wc_version( '3.2.5' ) || WC_Dynamic_Pricing_Compatibility::is_wc_version( '3.2.4' ) || WC_Dynamic_Pricing_Compatibility::is_wc_version( '3.2.3' ) ) {
			if ( defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) {
				//phpcs:ignore WordPress.Security.NonceVerification -- WC AJAX action, nonce verified elsewhere.
				$action = isset( $_REQUEST['wc-ajax'] ) ? wc_clean( wp_unslash( $_REQUEST['wc-ajax'] ) ) : '';
				//if (! empty( $_REQUEST['wc-ajax'] ) && ( $_REQUEST['wc-ajax'] === 'get_refreshed_fragments' || $_REQUEST['wc-ajax'] === 'add_to_cart' || $_REQUEST['wc-ajax'] == 'remove_from_cart' ) ) {
				if ( in_array( $action, [ 'get_refreshed_fragments', 'add_to_cart', 'remove_from_cart' ], true ) ) {
					WC()->session->set( 'cart_totals', null );
				}
			}
		}
	}


	public function on_plugins_loaded(): void {

		require_once 'classes/class-wc-dynamic-pricing-compatibility-functions.php';
		$this->add_price_filters();

		$additional_taxonomies = apply_filters( 'wc_dynamic_pricing_get_discount_taxonomies', [
			'product_brand',
		] );

		if ( $additional_taxonomies ) {
			foreach ( $additional_taxonomies as $additional_taxonomy ) {

				$simple_taxonomy_module = apply_filters( 'wc_dynamic_pricing_get_taxonomy_simple_class', 'WC_Dynamic_Pricing_Simple_Taxonomy', $additional_taxonomy );
				if ( $simple_taxonomy_module && class_exists( $simple_taxonomy_module ) ) {
					$this->modules[ 'simple_taxonomy_' . $additional_taxonomy ] = $simple_taxonomy_module::instance( $additional_taxonomy );
				}

				$advanced_taxonomy_module = apply_filters( 'wc_dynamic_pricing_get_taxonomy_advanced_class', 'WC_Dynamic_Pricing_Advanced_Taxonomy', $additional_taxonomy );
				if ( $advanced_taxonomy_module && class_exists( $advanced_taxonomy_module ) ) {
					$this->modules[ 'advanced_taxonomy_' . $additional_taxonomy ] = $advanced_taxonomy_module::instance( $additional_taxonomy );
				}
			}
		}
	}

	public function check_coupon_is_valid( $valid, $product, $coupon, $values ) {
		if ( apply_filters( 'wc_dynamic_pricing_check_coupons', false ) ) {
			if ( $coupon->get_exclude_sale_items() && isset( $values['discounts'] ) && isset( $values['discounts']['applied_discounts'] ) && ! empty( $values['discounts']['applied_discounts'] ) ) {
				$valid = false;
			}
		}

		return $valid;
	}

	/**
	 * @param bool $valid
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 */
	public function check_cart_coupon_is_valid( $valid, $coupon ) {
		try {
			if ( apply_filters( 'wc_dynamic_pricing_check_coupons', false ) ) {
				if ( $coupon && $coupon->get_exclude_sale_items() ) {
					foreach ( WC()->cart->get_cart() as $values ) {
						if ( isset( $values['discounts'] ) && isset( $values['discounts']['applied_discounts'] ) && ! empty( $values['discounts']['applied_discounts'] ) ) {
							$valid = false;
						}
					}
				}
			}
		} catch ( Exception $e ) {
			// Do nothing
		}

		return $valid;
	}

	public function new_account_overrides( $result, $condition ) {
		switch ( $condition['type'] ) {
			case 'apply_to':
				if ( is_array( $condition['args'] ) && isset( $condition['args']['applies_to'] ) ) {
					if ( $condition['args']['applies_to'] == 'everyone' ) {
						$result = 1;
					} else if ( $condition['args']['applies_to'] == 'unauthenticated' ) {
						$result = 1; //The user wasn't logged in, but now will be.  Hardcode to true
					} else if ( $condition['args']['applies_to'] == 'authenticated' ) {
						$result = 0; //The user wasn't logged in previously.
					} else if ( $condition['args']['applies_to'] == 'roles' && isset( $condition['args']['roles'] ) && is_array( $condition['args']['roles'] ) ) {
						$result = 0;
					}
				}
				break;
			default:
				$result = 0;
				break;
		}

		return $result;
	}

	public function convert_decimals( $amount, $rule, $cart_item, $module ) {
		if ( function_exists( 'wc_format_decimal' ) ) {
			$amount = wc_format_decimal( str_replace( get_option( 'woocommerce_price_thousand_sep' ), '', $amount ) );
		}

		return $amount;
	}

	public function on_cart_loaded_from_session( WC_Cart $cart ) {
		$sorted_cart   = [];
		$cart_contents = $cart->get_cart();
		if ( sizeof( $cart_contents ) > 0 ) {
			foreach ( $cart_contents as $cart_item_key => &$values ) {
				if ( $values === null ) {
					continue;
				}

				if ( isset( $values[ $cart_item_key ]['discounts'] ) ) {
					unset( $values[ $cart_item_key ]['discounts'] );
				}

				$sorted_cart[ $cart_item_key ] = &$values;
			}
		}

		uasort( $cart_contents, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price' );
		$cart->set_cart_contents( $sorted_cart );

		/*
			* Dynamic Pricing 3.3.0 - Disable setting discounts on cart items when loaded from session.
			* Dynamic Pricing 3.3.1 - Added filter to allow setting discounts on cart items when loaded from session. Helps with the old cart widget
		*/
		if ( apply_filters( 'wc_dynamic_pricing_legacy_adjust_on_session_loaded', false ) ) {
			if ( empty( $sorted_cart ) ) {
				return;
			}

			//Sort the cart so that the lowest priced item is discounted when using block rules.
			$modules = apply_filters( 'wc_dynamic_pricing_load_modules', $this->modules );
			foreach ( $modules as $module ) {
				$module->adjust_cart( $cart_contents );
			}
		}
	}

	public function on_calculate_totals( $cart ): void {
		$sorted_cart = [];
		if ( sizeof( $cart->cart_contents ) > 0 ) {
			foreach ( $cart->cart_contents as $cart_item_key => $values ) {
				if ( $values != null ) {
					$sorted_cart[ $cart_item_key ] = $values;
				}
			}
		}

		if ( empty( $sorted_cart ) ) {
			return;
		}

		//Sort the cart so that the lowest priced item is discounted when using block rules.
		uasort( $sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price' );

		$modules = apply_filters( 'wc_dynamic_pricing_load_modules', $this->modules );
		foreach ( $modules as $module ) {
			$module->adjust_cart( $sorted_cart );
		}
	}

	public function on_get_composite_price( $base_price, $_product ) {
		return $this->on_get_price( $base_price, $_product );
	}

	public function on_get_composite_base_price( $base_price, $_product ) {
		return $this->on_get_price( $base_price, $_product );
	}


	public function on_get_product_variation_price( $base_price, $_product ) {
		return $this->on_get_price( $base_price, $_product, false );
	}

	/**
	 * @param mixed $source_price
	 * @param WC_Product $_product
	 * @param bool $force_calculation
	 *
	 * @return float
	 * @since 2.6.1
	 *
	 */
	public function on_get_price( $source_price, $_product, $force_calculation = false ) {
		$composite_ajax = did_action( 'wp_ajax_woocommerce_show_composited_product' ) | did_action( 'wp_ajax_nopriv_woocommerce_show_composited_product' ) | did_action( 'wc_ajax_woocommerce_show_composited_product' );

		$base_price = floatval( $source_price );
		if ( empty( $_product ) || $source_price === '' ) {
			return $source_price;
		}

		// If this is a product addon calculation, return the price as is.
		if ( class_exists( 'WC_Dynamic_Pricing_Product_Addons_Integration' ) && WC_Dynamic_Pricing_Product_Addons_Integration::is_product_addon( $_product ) ) {
			return $source_price;
		}

		if ( class_exists( 'WCS_ATT_Product' ) && WCS_ATT_Product::is_subscription( $_product ) ) {
			return $source_price;
		}

		$result_price = $base_price;

		if ( ! $force_calculation ) {
			//Cart items are discounted when loaded from session, check to see if the call to get_price is from a cart item,
			//if so, return the price on the cart item as it currently is.
			$cart_item = WC_Dynamic_Pricing_Context::instance()->get_cart_item_for_product( $_product );

			if ( $cart_item ) {

				//If no discounts applied just return the price passed to us.
				//This is to solve subscriptions passing the sign up fee though this filter.
				if ( ! isset( $cart_item['discounts'] ) ) {
					return $source_price;
				}

				//Make sure not to override the deposit amount.  It's already been configured when the cart was loaded from session.
				if ( isset( $cart_item['is_deposit'] ) ) {
					return $source_price;
				}

				$this->remove_price_filters();

				if ( WC_Dynamic_Pricing_Compatibility::is_wc_version_gte_2_7() ) {
					$cart_price = $cart_item['data']->get_price( 'edit' );
				} else {
					//Use price directly since 3.0.8 so extensions do not re-filter this value.
					//https://woothemes.zendesk.com/agent/tickets/564481
					$cart_price = $cart_item['data']->price;
				}

				$this->add_price_filters();

				return $cart_price;
			}
		}

		if ( is_object( $_product ) ) {
			$cache_id = $_product->get_id() . spl_object_hash( $_product );

			if ( ! $force_calculation ) {
				if ( isset( $this->cached_adjustments[ $cache_id ] ) && $this->cached_adjustments[ $cache_id ] === false ) {
					return $source_price;
				} else if ( isset( $this->cached_adjustments[ $cache_id ] ) && ! empty( $this->cached_adjustments[ $cache_id ] ) ) {
					return $this->cached_adjustments[ $cache_id ];
				}
			}

			$adjustment_applied = false;
			$discount_price     = false;
			$working_price      = $base_price;

			$modules = apply_filters( 'wc_dynamic_pricing_load_modules', $this->modules );

			$fake_cart_item = [ 'data' => $_product ];

			foreach ( $modules as $module ) {

				if ( $module->module_type == 'simple' ) {

					//Make sure we are using the price that was just discounted.

					$working_price = $module->get_product_working_price( $working_price, $_product );
					if ( $working_price !== false ) {
						$discount_price = $module->get_discounted_price_for_shop( $_product, $working_price );

						if ( $discount_price !== null && ( $discount_price === 0 || $discount_price === 0.0 || $discount_price ) && $discount_price != $working_price ) {
							if ( $_product->get_id() == 1213 ) {
								$x = 1;
							}

							$working_price      = $discount_price;
							$adjustment_applied = true;

							if ( ! apply_filters( 'woocommerce_dynamic_pricing_is_cumulative', true, $module->module_id, $fake_cart_item, '' ) ) {
								break;
							}
						}
					}
				}
			}

			if ( $adjustment_applied && $discount_price !== false && $discount_price != $base_price ) {
				$result_price = $discount_price;
				if ( ! $force_calculation ) {
					$this->cached_adjustments[ $cache_id ] = $result_price;
				}
			} else {
				$result_price = $source_price;
				if ( ! $force_calculation ) {
					$this->cached_adjustments[ $cache_id ] = false;
				}
			}
		}

		return $result_price;
	}

	/**
	 * @param mixed $base_price
	 * @param WC_Product $_product
	 *
	 * @return string|float
	 * @since 2.9.8
	 *
	 */
	private function get_discounted_price( $base_price, $_product ) {
		$discount_price = false;
		$modules        = apply_filters( 'wc_dynamic_pricing_load_modules', $this->modules );

		foreach ( $modules as $module ) {
			if ( $module->module_type == 'simple' ) {
				//Make sure we are using the price that was just discounted.
				$working_price = $discount_price !== false ? $discount_price : $base_price;
				$working_price = $module->get_product_working_price( $working_price, $_product );
				if ( floatval( $working_price ) || intval( $working_price ) == 0 ) {
					$discount_price = $module->get_discounted_price_for_shop( $_product, $working_price );
					$cumulative     = apply_filters( 'woocommerce_dynamic_pricing_is_cumulative', true, $module->module_id, [ 'data' => $_product ], '' );
					if ( $discount_price !== false && $discount_price != $base_price && ! $cumulative ) {
						break;
					}
				}
			}
		}

		if ( $discount_price !== false ) {
			return $discount_price;
		} else {
			return $base_price;
		}
	}

	/**
	 * Filters the variation price from WC_Product_Variable->get_variation_prices()
	 *
	 * @param float $price
	 * @param WC_Product_Variation $variation
	 *
	 * @return float
	 * @since 2.11.1
	 *
	 */
	public function on_get_variation_prices_price( $price, $variation ) {
		if ( $price !== '' ) {
			$price = $this->get_discounted_price( $price, $variation );
		}

		return $price;
	}

	/**
	 * @param float $price
	 * @param WC_Product $product
	 * @param string $min_or_max
	 * @param string $display
	 *
	 * @return float|mixed|string
	 */
	public function on_get_variation_price( $price, $product, $min_or_max, $display ) {

		$min_price        = $price;
		$max_price        = $price;
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		$children = $product->get_children();
		if ( isset( $children ) && ! empty( $children ) ) {
			foreach ( $children as $variation_id ) {
				if ( $display ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation ) {
						$this->remove_price_filters();

						$base_price     = $tax_display_mode == 'incl' ? wc_get_price_including_tax( $variation ) : wc_get_price_excluding_tax( $variation );
						$calc_price     = $base_price;
						$discount_price = $this->get_discounted_price( $base_price, $variation );
						if ( $discount_price && $base_price != $discount_price ) {
							$calc_price = $discount_price;
						}

						$this->add_price_filters();
					} else {
						$calc_price = '';
					}
				} else {
					$variation  = wc_get_product( $variation_id );
					$calc_price = $variation->get_price( 'view' );
				}

				if ( $min_price == null || $calc_price < $min_price ) {
					$min_price = $calc_price;
				}

				if ( $max_price == null || $calc_price > $max_price ) {
					$max_price = $calc_price;
				}
			}
		}

		if ( $min_or_max == 'min' ) {
			return $min_price;
		} else if ( $min_or_max == 'max' ) {
			return $max_price;
		} else {
			return $price;
		}
	}

	/**
	 * Overrides the default woocommerce is on sale to ensure sale badges show properly.
	 *
	 * @param bool $is_on_sale
	 * @param WC_Product $product
	 *
	 * @return bool
	 * @since 2.10.8
	 *
	 */
	public function on_get_product_is_on_sale( $is_on_sale, $product ) {

		if ( ! apply_filters( 'wc_dynamic_pricing_flag_is_on_sale', true, $product ) ) {
			return $is_on_sale;
		}

		if ( $is_on_sale ) {
			return $is_on_sale;
		}

		//TODO:  Review bundles and sales
		//if ( $product->is_type( 'bundle' ) && $product->per_product_pricing_active ) {
		//return $is_on_sale;
		//}

		if ( $product->is_type( 'variable' ) ) {
			$is_on_sale = false;

			$prices = $product->get_variation_prices();

			$regular       = array_map( 'strval', $prices['regular_price'] );
			$actual_prices = array_map( 'strval', $prices['price'] );

			$diff = array_diff_assoc( $regular, $actual_prices );

			if ( ! empty( $diff ) ) {
				$is_on_sale = true;
			}
		} else {

			//3.1.19 - just get the price sent though the regular get_price filter.
			$dynamic_price = $product->get_price( 'view' );

			$regular_price = $product->get_regular_price( 'view' );

			if ( empty( $regular_price ) || ( empty( $dynamic_price ) && floatval( $dynamic_price ) !== 0.00 ) ) {
				return $is_on_sale;
			} else {
				$is_on_sale = (float) $regular_price > (float) $dynamic_price;
			}
		}

		return $is_on_sale;
	}

	//Helper functions to modify the woocommerce cart.  Called from the individual modules.
	public static function apply_cart_item_adjustment( $cart_item_key, $original_price, $adjusted_price, $module, $set_id ) {

		do_action( 'wc_memberships_discounts_disable_price_adjustments' );
		self::instance()->remove_price_filters();

		$adjusted_price = apply_filters( 'wc_dynamic_pricing_apply_cart_item_adjustment', $adjusted_price, $cart_item_key, $original_price, $module );

		//Allow extensions to stop processing of applying the discount.  Added for subscriptions signup fee compatibility
		if ( $adjusted_price === false ) {
			return;
		}

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) && ! empty( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$_product = WC()->cart->cart_contents[ $cart_item_key ]['data'];

			if ( apply_filters( 'wc_dynamic_pricing_get_use_sale_price', true, $_product ) ) {
				$display_price = get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? wc_get_price_excluding_tax( $_product ) : wc_get_price_including_tax( $_product );
			} else {
				$display_price = get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? wc_get_price_excluding_tax( $_product, [ 'price' => $original_price ] ) : wc_get_price_including_tax( $_product, [ 'price' => $original_price ] );
			}

			WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $adjusted_price );

			if ( $_product->get_type() == 'composite' ) {
				WC()->cart->cart_contents[ $cart_item_key ]['data']->base_price = $adjusted_price;
			}

			if ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['discounts'] ) ) {

				$discount_data                                           = [
					'by'                => [ $module ],
					'set_id'            => $set_id,
					'price_base'        => $original_price,
					'display_price'     => $display_price,
					'price_adjusted'    => $adjusted_price,
					'applied_discounts' => [
						[
							'by'             => $module,
							'set_id'         => $set_id,
							'price_base'     => $original_price,
							'price_adjusted' => $adjusted_price,
						],
					],
				];
				WC()->cart->cart_contents[ $cart_item_key ]['discounts'] = $discount_data;
			} else {

				$existing = WC()->cart->cart_contents[ $cart_item_key ]['discounts'];

				$discount_data = [
					'by'                => $existing['by'],
					'set_id'            => $set_id,
					'price_base'        => $original_price,
					'display_price'     => $existing['display_price'],
					'price_adjusted'    => $adjusted_price,
					'applied_discounts' => $existing['applied_discounts'],
				];

				WC()->cart->cart_contents[ $cart_item_key ]['discounts'] = $discount_data;

				array_push( WC()->cart->cart_contents[ $cart_item_key ]['discounts']['by'], $module );
				WC()->cart->cart_contents[ $cart_item_key ]['discounts']['applied_discounts'][] = [
					'by'             => $module,
					'set_id'         => $set_id,
					'price_base'     => $original_price,
					'price_adjusted' => $adjusted_price,
				];
			}
		}
		do_action( 'wc_memberships_discounts_enable_price_adjustments' );
		do_action( 'woocommerce_dynamic_pricing_apply_cartitem_adjustment', $cart_item_key, $original_price, $adjusted_price, $module, $set_id );
		self::instance()->add_price_filters();
	}

	public static function remove_cart_item_adjustment( string $b_cart_item_key, string $module_id ) {
		$cart_item_key = $b_cart_item_key;
		$module        = $module_id;

		do_action( 'wc_memberships_discounts_disable_price_adjustments' );
		self::instance()->remove_price_filters();

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) && ! empty( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
			$db_product     = wc_get_product( WC()->cart->cart_contents[ $cart_item_key ]['data']->get_id() );
			$cart_product   = WC()->cart->cart_contents[ $cart_item_key ]['data'];
			$original_price = WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $db_product->get_price() );
		}

		do_action( 'wc_memberships_discounts_enable_price_adjustments' );
		do_action( 'woocommerce_dynamic_pricing_remove_cartitem_adjustment', $cart_item_key, $module_id );
		self::instance()->add_price_filters();
	}

	/** Helper functions ***************************************************** */

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

