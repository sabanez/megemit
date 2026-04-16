<?php
/**
 * Contains service point data.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Utility\Shop_Helper;

?>

<script type='text/javascript'>
    window.SendcloudShippingData = <?php echo wp_json_encode( $data['shipping_data']); ?>;
    window.SendcloudLocaleMessages = <?php echo wp_json_encode( $data['locale_messages'] ); ?>;
</script>
<input type="hidden" id="sendcloud-block-checkout-initialize-endpoint"
	   value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'initialize_block_checkout' ); ?>"/>
<input type="hidden" id="sendcloud-block-checkout-save-delivery-method-data-endpoint"
       value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'save_delivery_method_data' ); ?>"/>
