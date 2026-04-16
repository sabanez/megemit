<?php
/**
 * Contains service point data.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Models\Service_Point_Delivery_Method_Meta_Data;

/**
 * Service_Point_Delivery_Method_Meta_data $delivery_method_data
 *
 * @var Service_Point_Delivery_Method_Meta_Data $delivery_method_data
 */
$delivery_method_data = $data['delivery_method_data'];
$address              = join( '<br>', explode( ',', $delivery_method_data->get_formatted_address() ) );

?>
<div class="col2-set addresses">
	<div class="col1">
		<header class="title">
			<h3><?php echo esc_html__( 'Service Point Address', 'sendcloud-shipping' ); ?></h3>
		</header>
		<address>
			<?php echo wp_kses( $address, array( 'br' => array() ) ); ?>
			<br><br>
			<?php echo esc_html( $delivery_method_data->get_post_number() ); ?>
		</address>
	</div>
</div>
