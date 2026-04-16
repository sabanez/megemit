<?php

use SendCloud\Checkout\API\Checkout\Delivery\Zone\DeliveryZone;
use SendCloud\Checkout\Utility\UnitConverter;
use Sendcloud\Shipping\Utility\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Contains data regarding shipping configuration
 *
 * @var array $data
 */

?>

<ul id="sendcloud-config-panel">
	<li class="sendcloud-side-content">
		<div id="sendcloud_shipping_connect" class="sendcloud-panel-box sendcloud-integration-info">
			<h3>
				<?php esc_html_e( 'Sendcloud Integration', 'sendcloud-shipping' ); ?>
			</h3>
			<div class="sendcloud-button-text-container">
				<p>
					<?php esc_html_e( 'Want to see your WooCommerce orders in the Sendcloud Panel?', 'sendcloud-shipping' ); ?>
				</p>
				<?php
				echo '<button class="sendcloud-button sendcloud-button--primary connect-button';
				if ( ! $data['permalinks_enabled'] ) {
					echo ' sendcloud-button-disabled';
				}
				echo '">';
				esc_html_e( 'Connect', 'sendcloud-shipping' );
				echo '</button>';
				?>
			</div>

			<div class="sendcloud-button-text-container">
				<p>
					<?php esc_html_e( 'Want to change any setting?', 'sendcloud-shipping' ); ?>
				</p>
				<a href="<?php echo esc_url( $data['panel_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<button class="sendcloud-button sendcloud-button--primary">
						<?php esc_html_e( 'Go to Sendcloud', 'sendcloud-shipping' ); ?>
					</button>
				</a>
			</div>
		</div>
	</li>
</ul>
