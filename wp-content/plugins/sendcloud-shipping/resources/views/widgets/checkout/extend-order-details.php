<?php
/**
 * Contains service point data.
 *
 * @var array $data
 */

$address = join( '<br>', explode( '|', $data['address'] ) )
?>
<div class="address">
	<h3><?php echo esc_html( __( 'Service Point Address', 'sendcloud-shipping' ) ); ?></h3>
	<?php echo wp_kses( $address, array( 'br' => array() ) ); ?>
	<br>
	<?php echo esc_html( $data['post_number'] ); ?>
	<span class="description">
	<?php
	echo wp_kses( wc_help_tip( __( "You can't change the selected Service Point", 'sendcloud-shipping' ) ) . ' '
				  . __( 'Non editable', 'sendcloud-shipping' ),
		array( 'span' => array( 'data-tip' => array(), 'class' => array() ) ) );
	?>
										 </span>
</div>
