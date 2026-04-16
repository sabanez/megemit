<?php
/**
 * Volume discounts table.
 *
 * @var array $data Data for render table.
 *
 * @package basel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php do_action( 'basel_before_dynamic_discounts_table' ); ?>

<table>
	<thead>
		<tr>
			<th scope="col">
				<?php echo esc_html__( 'Quantity', 'basel' ); ?>
			</th>
			<th scope="col">
				<?php echo esc_html__( 'Price', 'basel' ); ?>
			</th>
			<th scope="col">
				<?php echo esc_html__( 'Discount', 'basel' ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $data as $row ) : ?>
			<tr data-min="<?php echo esc_attr( $row['min'] ); ?>" data-max="<?php echo esc_attr( $row['max'] ); ?>">
				<td>
					<span>
						<?php echo esc_html( $row['quantity'] ); ?>
					</span>
				</td>
				<td>
					<?php echo $row['price']; //phpcs:ignore. ?>
				</td>
				<td>
					<span>
						<?php
							echo wp_kses(
								$row['discount'],
								array(
									'span' => array(
										'class' => array(),
									),
									'bdi'  => array(),
								)
							);
						?>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php do_action( 'basel_after_dynamic_discounts_table' ); ?>
