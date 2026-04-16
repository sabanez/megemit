<?php

/**
 * Contains delivery info.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Models\Delivery_Method_Meta_Data;

/**
 * Delivery_Method_Meta_Data $delivery_method_data.
 *
 * @var Delivery_Method_Meta_Data $delivery_method_data
 */
$delivery_method_data = $data['delivery_method_data'];
?>
<h3><?php echo esc_html__( 'Expected delivery date', 'sendcloud-shipping' ); ?></h3>
<p><?php echo esc_html( $delivery_method_data->get_formatted_delivery_date() ); ?></p>
