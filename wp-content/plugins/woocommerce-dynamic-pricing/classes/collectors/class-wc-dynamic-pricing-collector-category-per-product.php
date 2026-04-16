<?php

class WC_Dynamic_Pricing_Collector_Category_Per_Product extends WC_Dynamic_Pricing_Collector {
	/**
	 * @var array Array of category ID's this collector should count in the cart.
	 */
	public $categories_to_match;

	/**
	 * WC_Dynamic_Pricing_Collector_Category_Per_Product constructor.
	 *
	 * @param $collector_data Array of collector configuration.
	 */
	public function __construct($collector_data) {
		parent::__construct($collector_data);
		$this->categories_to_match = (isset($collector_data['args']['cats']) && is_array($collector_data['args']['cats'])) ? $collector_data['args']['cats'] : false;
	}

	/**
	 * Collects quantity for a specific product within the categories we're tracking
	 * This is the key difference - we only count items from the same product
	 *
	 * @param array $cart_item The WooCommerce cart item
	 *
	 * @return int Quantity count
	 */
	public function collect_quantity( array $cart_item): int {
		$q = 0;

		if (!$this->categories_to_match) {
			return $q;
		}

		// Get the product ID (could be variation or parent)
		$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
		$parent_id = $cart_item['product_id']; // Always the parent product ID

		// For variations, we want to match by parent product ID
		$match_id = apply_filters('wc_dynamic_pricing_per_product_match_by_parent', true) ? $parent_id : $product_id;

		foreach (WC()->cart->get_cart() as $lck => $l_cart_item) {
			$l_product_id = !empty($l_cart_item['variation_id']) ? $l_cart_item['variation_id'] : $l_cart_item['product_id'];
			$l_parent_id = $l_cart_item['product_id'];

			// Check if this cart item belongs to the same product
			$l_match_id = apply_filters('wc_dynamic_pricing_per_product_match_by_parent', true) ? $l_parent_id : $l_product_id;

			if ($l_match_id == $match_id) {
				// Check if the product is in the categories we're looking for
				if (apply_filters('woocommerce_dynamic_pricing_is_object_in_terms',
					is_object_in_term($l_cart_item['product_id'], 'product_cat', $this->categories_to_match),
					$l_cart_item['product_id'],
					$this->categories_to_match)) {

					if (apply_filters('woocommerce_dynamic_pricing_count_categories_for_cart_item', true, $l_cart_item, $lck)) {
						$q += (int) $l_cart_item['quantity'];
					}
				}
			}
		}

		return $q;
	}
}
