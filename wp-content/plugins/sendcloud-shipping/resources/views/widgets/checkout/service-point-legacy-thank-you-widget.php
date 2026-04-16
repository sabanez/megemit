<?php
/**
 * Contains address info.
 *
 * @var array $data
 */

$address = implode( '<br>', explode( '|', $data['address'] ) )
?>
<div class="col2-set addresses">
	<div class="col1">
		<header class="title">
			<h3><?php echo esc_html(__( 'Service Point Address', 'sendcloud-shipping' )); ?></h3>
		</header>
		<address>
			<?php echo wp_kses($address, array('br' => array())); ?>
			<p><?php echo esc_html($data['post_number']); ?></p>
		</address>
	</div>
</div>
