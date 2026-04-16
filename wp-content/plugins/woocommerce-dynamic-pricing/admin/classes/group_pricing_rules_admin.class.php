<?php

class woocommerce_group_pricing_rules_admin {

	public function __construct() {

	}

	public function on_init() {

	}

	public function basic_meta_box() {
		?>
		<div id="poststuff" class="woocommerce-roles-wrap">
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( '_s_group_pricing_rules' ); ?>
				<?php $pricing_rules = get_option( '_s_group_pricing_rules' ); ?>

				<table class="widefat">
					<thead>
					<th><?php esc_html_e('Enabled', 'woocommerce-dynamic-pricing'); ?></th>
					<th>
						<?php esc_html_e('Group', 'woocommerce-dynamic-pricing'); ?>
					</th>
					<th style="display:none;"><?php esc_html_e('Free Shipping?', 'woocommerce-dynamic-pricing'); ?></th>
					<th>
						<?php esc_html_e('Type', 'woocommerce-dynamic-pricing'); ?>
					</th>
					<th>
						<?php esc_html_e('Amount', 'woocommerce-dynamic-pricing'); ?>
					</th>

					</thead>
					<tbody>
						<?php
						$results = wc_dynamic_pricing_groups_get_all_groups();
						if ( !empty( $results ) && !is_wp_error( $results ) ):
							?>
							<?php $default = array('type' => 'percent', 'direction' => '+', 'amount' => '', 'free_shipping' => 'no'); ?>
							<?php $set_index = 0; ?>
							<?php foreach ( $results as $group ) : ?>
								<?php

                                $group_id = $group['group_id'];
                                $escaped_group_id = esc_attr( $group_id );
								$set_index++;
								$name = 'set_' . $set_index;

                                $escaped_name = esc_attr( $name );
								$condition_index = 0;
								$index = 0;

								$rule_set = isset( $pricing_rules[$name] ) ? $pricing_rules[$name] : array();
								$rule = isset( $pricing_rules[$name] ) && isset( $pricing_rules[$name]['rules'][0] ) ? $pricing_rules[$name]['rules'][0] : array();
								$rule = array_merge( $default, $rule );
								?>
								<?php $checked = isset( $rule_set['conditions'][0]['args']['groups'] ) && in_array( $group_id, $rule_set['conditions'][0]['args']['groups'] ) ? 'checked="checked"' : ''; ?>
								<tr>
									<td>
										<input type="hidden" name="pricing_rules[<?php echo esc_attr( $name );; ?>][conditions_type]" value="all" />
										<input type="hidden" name="pricing_rules[<?php echo esc_attr( $name );; ?>][conditions][<?php echo esc_attr(intval($condition_index)); ?>][type]" value="apply_to" />
										<input type="hidden" name="pricing_rules[<?php echo esc_attr( $name );; ?>][conditions][<?php echo esc_attr(intval($condition_index)); ?>][args][applies_to]" value="groups" />
										<input type="hidden" name="pricing_rules[<?php echo esc_attr( $name );; ?>][collector][type]" value="always" />
										<input class="checkbox" <?php echo esc_attr($checked); ?> type="checkbox" id="group_<?php echo esc_attr($group_id); ?>" name="pricing_rules[<?php echo esc_attr( $name );; ?>][conditions][<?php echo esc_attr(intval($condition_index)); ?>][args][groups][]" value="<?php esc_attr_e($escaped_group_id); ?>" />
									</td>
									<td>
										<strong><?php echo esc_html($group['name']); ?></strong>
									</td>
									<td style="display:none;">

										<input <?php checked( 'yes', $rule['free_shipping'] ); ?> type="checkbox" name="pricing_rules[<?php echo esc_attr($escaped_name); ?>][rules][<?php echo esc_attr($index); ?>][free_shipping]" value="yes" />

									</td>
									<td>
										<select id="pricing_rule_type_value_<?php echo esc_attr($escaped_name . '_' . $index); ?>" name="pricing_rules[<?php echo esc_attr($escaped_name); ?>][rules][<?php echo esc_attr($index); ?>][type]">
											<option <?php $this->selected( 'true', empty( $checked ) ); ?>></option>
											<option <?php $this->selected( 'fixed_product', $rule['type'] ); ?> value="fixed_product"><?php esc_html_e('Price Discount', 'woocommerce-dynamic-pricing'); ?></option>
											<option <?php $this->selected( 'percent_product', $rule['type'] ); ?> value="percent_product"><?php esc_html_e('Percentage Discount', 'woocommerce-dynamic-pricing'); ?></option>
										</select>
									</td>
									<td>
										<input type="text" name="pricing_rules[<?php echo esc_attr($escaped_name); ?>][rules][<?php echo esc_attr($index); ?>][amount]" value="<?php echo esc_attr( $rule['amount'] ); ?>" />
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-dynamic-pricing' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	private function selected( $value, $compare, $arg = true ) {
		if ( !$arg ) {
			echo '';
		} else if ( (string) $value == (string) $compare ) {
			echo 'selected="selected"';
		}
	}

}
