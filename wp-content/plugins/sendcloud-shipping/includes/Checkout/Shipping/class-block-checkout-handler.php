<?php

namespace Sendcloud\Shipping\Checkout\Shipping;

/**
 * Class Block_Checkout_Handler
 *
 * @package Sendcloud\Shipping\Checkout\Shipping
 */
class Block_Checkout_Handler extends Base_Checkout_Handler
{
    /**
     * Returns method details for all shipping methods rendered on checkout.
     *
     * @param array $ids - Shipping method IDs.
     *
     * @return array
     */
    public function initialize(array $ids)
    {
        $response = [];
        $response['locale'] = $this->get_locale();

	    $selected_shipping_method = $this->get_selected_shipping_method();
	    $response['selected_shipping_method'] = $selected_shipping_method;
	    if (!count($ids)) {
		    $response['method_details'][$selected_shipping_method] = $this->get_delivery_method_config( (int)$selected_shipping_method);

		    return $response;
	    }

        foreach ($ids as $id) {
            $response['method_details'][$id] = $this->get_delivery_method_config( $id );
        }

        return $response;
    }

	/**
	 * Get selected shipping method.
	 *
	 * @return mixed|string
	 */
	private function get_selected_shipping_method()
	{
		$chosen_shipping_methods = wc()->session->get( 'chosen_shipping_methods', '' );
		return explode(':', reset($chosen_shipping_methods))[1];
	}
}
