<?php

class woocommerce_membership_pricing_rules_admin {

	public function __construct() {

	}

	public function on_init() {

	}

	public function basic_meta_box() {
		global $wp_roles;
		?>
        <div id="poststuff" class="woocommerce-roles-wrap">
			<?php settings_errors(); ?>
            <form method="post" action="options.php">
				<?php settings_fields( '_s_membership_pricing_rules' ); ?>
                    <?php $pricing_rules = get_option( '_s_membership_pricing_rules' ); ?>

                <table class="widefat">
                    <thead>
                    <th><?php esc_html_e( 'Enabled', 'woocommerce-dynamic-pricing' ); ?></th>
                    <th>
						<?php esc_html_e( 'Role', 'woocommerce-dynamic-pricing' ); ?>
                    </th>
                    <th>
						<?php esc_html_e( 'Type', 'woocommerce-dynamic-pricing' ); ?>
                    </th>
                    <th>
						<?php esc_html_e( 'Amount', 'woocommerce-dynamic-pricing' ); ?>
                    </th>

                    </thead>
                    <tbody>
					<?php
					if ( ! isset( $wp_roles ) ) {
						$wp_roles = new WP_Roles();
					}
					$all_roles = $wp_roles->roles;
					?>
					<?php $default = array(
						'type'          => 'percent',
						'direction'     => '+',
						'amount'        => '',
						'free_shipping' => 'no'
					); ?>
					<?php $set_index = 0; ?>
					<?php foreach ( $all_roles as $role_id => $role ) : ?>
						<?php
						$set_index ++;
						$name = 'set_' . $set_index;

						$condition_index = 0;
						$index           = 0;

						$rule_set = $pricing_rules[ $name ];
						$rule     = isset( $pricing_rules[ $name ] ) && isset( $pricing_rules[ $name ]['rules'][0] ) ? $pricing_rules[ $name ]['rules'][0] : array();
						$rule     = array_merge( $default, $rule );
						?>
						<?php $checked = isset( $rule_set['conditions'][0]['args']['roles'] ) && in_array( $role_id, $rule_set['conditions'][0]['args']['roles'] ); ?>
                        <tr>
                            <td>
                                <input type="hidden" name="pricing_rules[<?php echo esc_attr($name); ?>][conditions_type]" value="all"/>
                                <input type="hidden" name="pricing_rules[<?php echo esc_attr($name); ?>][conditions][<?php echo esc_attr($condition_index); ?>][type]" value="apply_to"/>
                                <input type="hidden" name="pricing_rules[<?php echo esc_attr($name); ?>][conditions][<?php echo esc_attr($condition_index); ?>][args][applies_to]" value="roles"/>
                                <input type="hidden" name="pricing_rules[<?php echo esc_attr($name); ?>][collector][type]" value="always"/>
                                <input class="checkbox" <?php echo checked($checked); ?> type="checkbox" id="role_<?php echo esc_attr($role_id); ?>" name="pricing_rules[<?php echo esc_attr($name); ?>][conditions][<?php echo esc_attr($condition_index); ?>][args][roles][]" value="<?php echo esc_attr($role_id); ?>"/>
                            </td>
                            <td>
                                <strong><?php echo esc_html($role['name']); ?></strong>
                            </td>
                            <td>
                                <select id="pricing_rule_type_value_<?php echo esc_attr($name . '_' . $index); ?>" name="pricing_rules[<?php echo esc_attr($name); ?>][rules][<?php echo esc_attr($index); ?>][type]">
                                    <option <?php $this->selected( 'true', empty( $checked ) ); ?>></option>
                                    <option <?php $this->selected( 'fixed_product', $rule['type'] ); ?> value="fixed_product"><?php esc_html_e( 'Price Discount', 'woocommerce-dynamic-pricing' ); ?></option>
                                    <option <?php $this->selected( 'percent_product', $rule['type'] ); ?> value="percent_product"><?php esc_html_e( 'Percentage Discount', 'woocommerce-dynamic-pricing' ); ?></option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="pricing_rules[<?php echo esc_attr($name); ?>][rules][<?php echo esc_attr($index); ?>][amount]" value="<?php echo esc_attr( $rule['amount'] ); ?>"/>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-dynamic-pricing' ); ?>"/>
                </p>
            </form>
        </div>
		<?php
	}

	private function selected( $value, $compare, $arg = true ) {
		if ( ! $arg ) {
			echo '';
		} else if ( (string) $value == (string) $compare ) {
			echo 'selected="selected"';
		}
	}

}
