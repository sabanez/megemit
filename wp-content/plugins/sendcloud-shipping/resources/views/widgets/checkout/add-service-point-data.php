<?php
/**
 * Contains service point data.
 *
 * @var array $data
 */

use Sendcloud\Shipping\Utility\Shop_Helper;

?>
<script type='text/javascript'>
    var SENDCLOUDSHIPPING_LANGUAGE = '<?php echo esc_attr($data['language']); ?>';
    var SENDCLOUDSHIPPING_SELECT_SPP_LABEL = '<?php echo esc_attr($data['select_spp_label']); ?>';
    var SENDCLOUDSHIPPING_DIMENSIONS = '<?php echo esc_attr($data['cart_dimensions']); ?>';
    var SENDCLOUDSHIPPING_DIMENSIONS_UNIT = '<?php echo esc_attr($data['cart_dimensions_unit']); ?>';
    const SendcloudShippingData = <?php echo wp_json_encode( $data['shipping_data']); ?>;
    const SendcloudLocaleMessages = <?php echo wp_json_encode( $data['locale_messages'] ); ?>;
</script>
<input type="hidden" id="sendcloud-block-checkout-initialize-endpoint"
       value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'initialize_block_checkout' ); ?>"/>
<input type="hidden" id="sendcloud-block-checkout-save-delivery-method-data-endpoint"
       value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'save_delivery_method_data' ); ?>"/>

