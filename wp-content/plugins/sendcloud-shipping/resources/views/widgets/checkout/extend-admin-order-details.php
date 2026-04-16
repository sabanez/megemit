<?php
/**
 * Contains delivery method data.
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
<div class="address">
	<h3><?php echo esc_html__( 'Expected delivery date', 'sendcloud-shipping' ); ?></h3>
	<?php echo esc_html( $delivery_method_data->get_formatted_delivery_date() ); ?>
	<br>
	<span class="description">
	<?php
	echo wp_kses( wc_help_tip( __( "You can't change the selected delivery date",
			'sendcloud-shipping' ) ) . ' ' . __( 'Non editable', 'sendcloud-shipping' ),
		array( 'span' => array( 'data-tip' => array(), 'class' => array() ) ) );
	?>
				</span>
</div>
