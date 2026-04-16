<?php

/**
 * Contains service point data.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Models\Service_Point_Delivery_Method_Meta_Data;

$view_data = $data['view_data'];

/**
 * Service_Point_Delivery_Method_Meta_data $delivery_method_data.
 *
 * @var Service_Point_Delivery_Method_Meta_Data $delivery_method_data
 */
$delivery_method_data = $data['delivery_method_data'];
$address              = join( '<br>', explode( ',', $delivery_method_data->get_formatted_address() ) );
?>

<h3><?php echo esc_html__( 'Service Point Address', 'sendcloud-shipping' ); ?></h3>
<p><?php echo wp_kses( $address, array( 'br' => array() ) ); ?></p>
<?php echo '<p>' . esc_html( $delivery_method_data->get_post_number() ) . '</p>'; ?>
