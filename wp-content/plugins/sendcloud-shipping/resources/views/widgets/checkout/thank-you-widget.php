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
<div class="col2-set addresses">
	<div class="col1">
		<header class="title">
			<h3><?php echo esc_html__( 'Expected delivery date', 'sendcloud-shipping' ); ?></h3>
		</header>
		<address>
			<?php echo wp_kses( $delivery_method_data->get_formatted_delivery_date(), array( 'br' => array() ) ); ?>
		</address>
	</div>
</div>
