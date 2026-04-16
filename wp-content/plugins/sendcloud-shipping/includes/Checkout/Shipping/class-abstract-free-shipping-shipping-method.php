<?php

namespace Sendcloud\Shipping\Checkout\Shipping;

use SendCloud\Checkout\Domain\Delivery\Availability\Order;
use SendCloud\Checkout\Domain\Delivery\Availability\OrderItem;
use SendCloud\Checkout\Domain\Delivery\Availability\Weight;
use SendCloud\Checkout\Exceptions\Unit\UnitNotSupportedException;
use Sendcloud\Shipping\Repositories\Delivery_Methods_Repository;
use Sendcloud\Shipping\Repositories\Shipping_Method_Options_Repository;
use Sendcloud\Shipping\Utility\Logging_Callable;
use WC_Product;
use WC_Shipping_Flat_Rate;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Free_Shipping_Shipping_Method extends WC_Shipping_Flat_Rate {
	const CLASS_NAME = __CLASS__;

	public $plugin_id = 'sendcloudshipping_';

	const SC_MIN_ORDER_AMOUNT           = 'min_order_amount';
	const SC_MIN_AMOUNT_OR_COUPON       = 'min_amount_or_coupon';
	const SC_MIN_AMOUNT_AND_COUPON      = 'min_amount_and_coupon';

	/**
	 * @var mixed
	 */
	protected static $instances = [];

	protected static $optionsCache = [];

	protected static $scShippingMethodsConfig = [];

	protected static $adminOptionsHtml = [];

	protected $features = [];

	/**
	 * Free shipping enabled
	 *
	 * @var string
	 */
	protected $free_shipping_enabled;
	/**
	 * Free shipping min amount
	 *
	 * @var float
	 */
	protected $free_shipping_min_amount;
	/**
	 * Free shipping requires
	 *
	 * @var string
	 */
	protected $free_shipping_requires;
	/**
	 * Ignore discounts
	 *
	 * @var string
	 */
	protected $ignore_discounts;

	protected static $is_loaded = false;

	public function __construct( $instance_id = 0 ) {
		$cls = static::class;
		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = $this;
		}
		parent::__construct( $instance_id );
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		add_filter( "woocommerce_shipping_instance_form_fields_{$this->id}",
			new Logging_Callable( array( $this, 'override_form_fields_config' ) ) );

		parent::init();

		$this->free_shipping_enabled    = $this->get_option( 'free_shipping_enabled' );
		$this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount' );
		$this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		$this->ignore_discounts         = $this->get_option( 'ignore_discounts' );

	}

	/**
	 * @return array
	 */
	public function get_all_configurations() {
		if ( ! static::$scShippingMethodsConfig ) {
			$repository                      = new Shipping_Method_Options_Repository();
			static::$scShippingMethodsConfig = $repository->get_all_methods_configurations();
		}

		return static::$scShippingMethodsConfig;
	}

	/**
	 * @inheritDoc
	 */
	public function get_option( $key, $empty_value = null ) {
		$instanceKey = $this->get_instance_option_key();
		if ( ! array_key_exists( 'option' . $instanceKey . '_' . $key, static::$optionsCache ) ) {
			$result = $this->get_all_configurations();
			if ( ! array_key_exists( $instanceKey, $result ) ) {
				$result[ $instanceKey ] = [];
			}
			if ( ! array_key_exists( $key, $result[ $instanceKey ] ) ) {
				$result[ $instanceKey ][ $key ] = $this->get_default_field( $key, $empty_value );
			}
			static::$optionsCache[ 'option' . $instanceKey . '_' . $key ] = $result[ $instanceKey ][ $key ];
		}

		return static::$optionsCache[ 'option' . $instanceKey . '_' . $key ];
	}

	/**
	 * @inheritDoc
	 */
	public function get_instance_form_fields() {
		$cls      = static::class;
		$instance = self::$instances[ $cls ];
		if ( ! array_key_exists( 'get_instance_form_fields' . $cls, $instance->features ) ) {
			$instance->features[ 'get_instance_form_fields' . $cls ] = parent::get_instance_form_fields();
		}

		return $instance->features[ 'get_instance_form_fields' . $cls ];
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_options_html() {
		$key = static::class . '_' . $this->instance_id;
		if ( ! array_key_exists( $key, self::$adminOptionsHtml ) || ! self::$adminOptionsHtml[ $key ] ) {
			self::$adminOptionsHtml[ $key ] = parent::get_admin_options_html();
		}

		return self::$adminOptionsHtml[ $key ];
	}

	/**
	 * @inheritDoc
	 */
	public function supports( $feature ) {
		$cls      = static::class;
		$instance = self::$instances[ $cls ];
		if ( ! array_key_exists( $feature, $instance->features ) ) {
			$instance->features[ $feature ] = parent::supports( $feature );
		}

		return $instance->features[ $feature ];
	}


	/**
	 * Overrides title default value and extends form fields
	 *
	 * @param $form_fields
	 *
	 * @return mixed
	 */
	public function override_form_fields_config( $form_fields ) {
		$cls      = static::class;
		$instance = self::$instances[ $cls ];
		if ( array_key_exists( 'override_form_fields_config' . $cls, $instance->features ) ) {
			return $instance->features[ 'override_form_fields_config' . $cls ];
		}

		$form_fields['title']['default'] = $this->method_title;
		$this->add_extra_fields( $form_fields );
		if ( ! empty( $form_fields['cost'] ) && isset( static::$scShippingMethodsConfig[$this->get_instance_option_key()]['disable_cost'] ) ) {
			$form_fields['cost']['disabled'] = static::$scShippingMethodsConfig[$this->get_instance_option_key()]['disable_cost'];
		}

		$label = __( 'You can see all shipping zones, shipping methods and shipping costs on <a href="/wp-admin/admin.php?page=sendcloud-wc">Sendcloud page</a>', 'sendcloud-shipping' );
		if ( ! empty( $form_fields['class_costs']['description'] ) ) {
			$form_fields['class_costs']['description'] = $label;
		} else {
			$form_fields['sc_call_to_action'] = [
				'title'       => '',
				'type'        => 'title',
				'default'     => '',
				'description' => $label,
			];
		}
		$instance->features[ 'override_form_fields_config' . $cls ] = $form_fields;

		return $form_fields;
	}

	/**
	 * Initialize form fields
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$this->add_extra_fields( $this->form_fields );
	}

	/**
	 * Calculates shipping costs
	 *
	 * @param array $package
	 *
	 * @throws UnitNotSupportedException
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->check_free_shipping() ) {
			$this->add_rate( array(
				'label'   => $this->title,
				'cost'    => 0,
				'taxes'   => false,
				'package' => $package,
			) );
		} else if ( ! empty( $this->get_option( 'sc_shipping_rates' ) ) ) {
			$this->add_rate( array(
				'label'    => $this->title,
				'cost'     => $this->calculate_shipping_cost( $package ),
				'taxes'    => '',
				'calc_tax' => 'per_order',
				'package'  => $package,
			) );

		} else {
			parent::calculate_shipping( $package );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function process_admin_options() {
		$options                                        = parent::process_admin_options();
		static::$scShippingMethodsConfig[static::class] = $this->instance_settings;
		$instanceKey                                    = $this->get_instance_option_key();
		foreach ($this->instance_settings as $key => $value){
			static::$optionsCache['option' . $instanceKey . '_' . $key] = $value;
		}

		return $options;
	}

	/**
	 * @param string $key
	 * @param $default
	 *
	 * @return string
	 */
	protected function get_default_field( string $key, $default ) {
		if ( $key === 'title' ) {
			return $this->method_title;
		}

		if ( $key === 'tax_status' ) {
			return $this->tax_status;
		}

		return $default;
	}

	/**
	 * Checks whether or not shipping is free
	 *
	 * @return bool
	 */
	protected function check_free_shipping() {
		if ( 'yes' !== $this->free_shipping_enabled || ! isset( WC()->cart->cart_contents_total ) ) {
			return false;
		}

		$has_coupon = $this->has_coupon();

		$total = WC()->cart->get_displayed_subtotal();
		if ( 'incl' === WC()->cart->get_tax_price_display_mode() ) {
			$total -= WC()->cart->get_cart_discount_tax_total();
		}

		if ( 'no' === $this->ignore_discounts ) {
			$total -= WC()->cart->get_discount_total();
		}

		$min_amount_condition = $total >= $this->free_shipping_min_amount;;

		if ( static::SC_MIN_AMOUNT_OR_COUPON === $this->free_shipping_requires ) {
			return $has_coupon || $min_amount_condition;
		}

		if ( static::SC_MIN_AMOUNT_AND_COUPON === $this->free_shipping_requires ) {
			return $has_coupon && $min_amount_condition;
		}

		return $min_amount_condition;
	}

	/**
	 * Check if there is a coupon in the cart
	 *
	 * @return bool
	 */
	protected function has_coupon() {
		if ( ! in_array( $this->free_shipping_requires, array(
			static::SC_MIN_AMOUNT_AND_COUPON,
			static::SC_MIN_AMOUNT_OR_COUPON
		), true ) ) {
			return false;
		}
		$coupons = WC()->cart->get_coupons();
		if ( empty( $coupons ) ) {
			return false;
		}

		foreach ( $coupons as $coupon ) {
			if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add extra fields
	 *
	 * @param $form_fields
	 */
	protected function add_extra_fields( &$form_fields ) {
		$form_fields['free_shipping_enabled'] = array(
			'title'   => __( 'Enable Free Shipping', 'sendcloud-shipping' ),
			'type'    => 'select',
			'class'   => 'wc-enhanced-select',
			'default' => '',
			'options' => array(
				'no'  => __( 'No', 'sendcloud-shipping' ),
				'yes' => __( 'Yes', 'sendcloud-shipping' ),
			),
		);

		$form_fields['free_shipping_requires'] = array(
			'title'   => __( 'Free shipping requires...', 'sendcloud-shipping' ),
			'type'    => 'select',
			'class'   => 'sc-free-shipping-requires',
			'default' => 'min_order_amount',
			'options' => array(
				static::SC_MIN_ORDER_AMOUNT      => __( 'A minimum order amount', 'sendcloud-shipping' ),
				static::SC_MIN_AMOUNT_OR_COUPON  => __( 'A minimum order amount OR a coupon', 'sendcloud-shipping' ),
				static::SC_MIN_AMOUNT_AND_COUPON => __( 'A minimum order amount AND a coupon', 'sendcloud-shipping' ),
			),
		);

		$form_fields['free_shipping_min_amount'] = array(
			'title'       => __( 'Minimum Order Amount for Free Shipping', 'sendcloud-shipping' ),
			'type'        => 'price',
			'placeholder' => wc_format_localized_price( 0 ),
			'description' => __( 'If enabled, users will need to spend this amount to get free shipping.', 'sendcloud-shipping' ),
			'default'     => '0',
			'desc_tip'    => true,
			'class'       => 'sc-free-shipping-min-amount'
		);

		$form_fields['ignore_discounts'] = array(
			'title'       => __( 'Coupons discounts', 'woocommerce' ),
			'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
			'type'        => 'checkbox',
			'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
			'default'     => 'no',
			'desc_tip'    => true,
			'class'       => 'sc-ignore-discounts'
		);

	}

	/**
	 * Calculate shipping cost
	 *
	 * @param $package
	 *
	 * @return int|mixed
	 * @throws \SendCloud\Checkout\Exceptions\Unit\UnitNotSupportedException
	 */
	protected function calculate_shipping_cost( $package ) {
		$weight         = $this->create_order( $package )->calculateWeight();
		$shipping_rates = $this->get_option( 'sc_shipping_rates' );
		if ( empty( $weight ) ) {
			return $shipping_rates['default_rate'];
		}

		foreach ( $shipping_rates['items'] as $shipping_rate ) {
			if ( $this->is_rate_enabled( $weight, $shipping_rate ) ) {
				return $shipping_rate['rate'];
			}
		}

		return 0;
	}

	/**
	 * Check if rate is enabled
	 *
	 * @param float $weight
	 * @param array $shipping_rate
	 *
	 * @return bool
	 */
	protected function is_rate_enabled( $weight, $shipping_rate ) {
		return $shipping_rate['is_enabled'] && $weight >= $shipping_rate['min_weight'] && $weight < $shipping_rate['max_weight'];
	}

	/**
	 * Retrieves delivery method from db
	 *
	 * @return \SendCloud\Checkout\Domain\Delivery\DeliveryMethod|null
	 */
	protected function get_delivery_method() {
		global $wpdb;
		$repo = new Delivery_Methods_Repository( $wpdb );

		return $repo->find_by_system_id( $this->instance_id );
	}

	/**
	 * Create an order object
	 *
	 * @param $package
	 *
	 * @return Order
	 */
	protected function create_order( $package ) {
		$items       = array();
		$weight_unit = get_option( 'woocommerce_weight_unit' );
		foreach ( $package['contents'] as $id => $content ) {
			/**
			 * WooCommerce product
			 *
			 * @var WC_Product $product
			 */
			$product = $content['data'];
			$items[] = new OrderItem( $id, new Weight( (float) $product->get_weight(), $weight_unit ), $content['quantity'] );
		}

		return new Order( null, $items );
	}
}
