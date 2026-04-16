<?php
/**
 * Contains widget data.
 *
 * @var array $data
 */

$clean_shipping_method_id = esc_attr( sanitize_title( $data['shipping_method_id'] ) );
$id_prefix                = sprintf( 'shipping_method_%1$d_%2$s', $data['index'], $clean_shipping_method_id );
?>
<div id="<?php echo esc_attr( $id_prefix ); ?>_mount_point" class="sc-delivery-method-mount-point"></div>
<input type="hidden" id="<?php echo esc_attr( $id_prefix ); ?>_locale"
	   value="<?php echo esc_attr( $data['locale'] ); ?>"/>
<input type="hidden" id="<?php echo esc_attr( $id_prefix ); ?>_submit_data"
	   name="sendcloudshipping_widget_submit_data[<?php echo esc_attr( $clean_shipping_method_id ); ?>]"/>
<script
		type="application/json"
		id="<?php echo esc_attr( $id_prefix ); ?>_delivery_method"
><?php echo wp_json_encode( $data['delivery_method_config'] ); ?></script>
