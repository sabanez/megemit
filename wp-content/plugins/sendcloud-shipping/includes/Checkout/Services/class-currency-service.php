<?php

namespace Sendcloud\Shipping\Checkout\Services;

use SendCloud\Checkout\Contracts\Services\CurrencyService;

class Currency_Service implements CurrencyService {

	/**
	 * Get default currency code
	 *
	 * @inheritDoc
	 */
	public function getDefaultCurrencyCode() {
		return get_option( 'woocommerce_currency' );
	}
}
