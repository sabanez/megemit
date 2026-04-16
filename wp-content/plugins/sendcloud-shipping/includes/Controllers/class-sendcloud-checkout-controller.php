<?php

namespace Sendcloud\Shipping\Controllers;

use Sendcloud\Shipping\Checkout\Shipping\Block_Checkout_Handler;

class Sendcloud_Checkout_Controller extends Sendcloud_Base_Controller
{
    /**
     * @return void
     */
    public function initialize_block_checkout() {
	    WC()->session->set( 'sc_data', null );
        $raw = $this->get_raw_input();
        $payload = json_decode( $raw, true );
        $this->return_json( (new Block_Checkout_Handler())->initialize($payload) );
    }

	/**
	 * @return void
	 */
	public function save_delivery_method_data() {
		$raw = $this->get_raw_input();
		$payload = json_decode( $raw, true );

		WC()->session->set( 'sc_data', $payload );
	}
}