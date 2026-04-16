<?php

/**
 * Contains service point data.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Models\Service_Point_Delivery_Method_Meta_Data;

/**
 * Service_Point_Delivery_Method_Meta_data $delivery_method_data.
 *
 * @var Service_Point_Delivery_Method_Meta_Data $delivery_method_data
 */
$delivery_method_data = $data['delivery_method_data'];
$address              = join( '<br>', explode( ',', $delivery_method_data->get_formatted_address() ) );
?>
<div class="address">
	<h3><?php echo esc_html__( 'Service Point Address', 'sendcloud-shipping' ); ?></h3>
	<?php echo wp_kses( $address, array( 'br' => array() ) ); ?>
	<br>
	<?php echo esc_html( $delivery_method_data->get_post_number() ); ?>
	<span class="description">
	<?php
	echo wp_kses(wc_help_tip( __( "You can't change the selected delivery date",
			'sendcloud-shipping' ) ) . ' ' . __( 'Non editable', 'sendcloud-shipping' ),
		array( 'span' => array( 'data-tip' => array(), 'class' => array() ) ));
	?>
				</span>
</div>
