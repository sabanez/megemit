<?php
/**
 * Puente entre WooCommerce y Simple WP Membership basado en Atributos
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Seguridad

/**
 * Función auxiliar para obtener el nivel de membresía de un producto.
 * Lee primero el meta _swpm_membership_level; fallback al atributo nivel_swpm.
 */
function swpm_get_level_from_attribute( $product_id ) {
    $meta = get_post_meta( $product_id, '_swpm_membership_level', true );
    if ( ! empty( $meta ) ) {
        return intval( $meta );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) return false;

    $nivel = $product->get_attribute( 'nivel_swpm' );
    return ! empty( $nivel ) ? intval( $nivel ) : false;
}

/**
 * A. CAMPO META DE PRODUCTO: _swpm_membership_level
 * Muestra un campo numérico en la pestaña General del producto WooCommerce.
 */
add_action( 'woocommerce_product_options_general_product_data', 'swpm_add_membership_level_field' );
function swpm_add_membership_level_field() {
    woocommerce_wp_text_input( array(
        'id'          => '_swpm_membership_level',
        'label'       => __( 'Nivel de membresía SWPM', 'woocommerce' ),
        'description' => __( 'ID del nivel de membresía en Simple WP Membership. Déjalo vacío si no es un producto de membresía.', 'woocommerce' ),
        'desc_tip'    => true,
        'type'        => 'number',
        'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
    ) );
}

add_action( 'woocommerce_process_product_meta', 'swpm_save_membership_level_field' );
function swpm_save_membership_level_field( $post_id ) {
    $value = isset( $_POST['_swpm_membership_level'] ) ? intval( $_POST['_swpm_membership_level'] ) : '';
    if ( $value > 0 ) {
        update_post_meta( $post_id, '_swpm_membership_level', $value );
    } else {
        delete_post_meta( $post_id, '_swpm_membership_level' );
    }
}

/**
 * B. GUARDAR NIVEL PREVIO AL CREAR LA ORDEN
 * Persiste el nivel SWPM actual del usuario en la orden para poder revertirlo si falla el pago.
 */
add_action( 'woocommerce_checkout_order_created', 'swpm_save_previous_level_on_order_created' );
function swpm_save_previous_level_on_order_created( $order ) {
    if ( ! class_exists( 'SwpmMemberUtils' ) ) return;

    $user_id = $order->get_user_id();
    if ( ! $user_id ) return;

    $wp_user = get_userdata( $user_id );
    if ( ! $wp_user ) return;

    $swpm_member = SwpmMemberUtils::get_user_by_email( $wp_user->user_email );
    if ( ! $swpm_member || ! isset( $swpm_member->membership_level ) ) return;

    $order->update_meta_data( '_swpm_previous_level', intval( $swpm_member->membership_level ) );
    $order->save();
}

/**
 * C. REVERTIR NIVEL AL CANCELAR / FALLAR / REEMBOLSAR
 * Devuelve al usuario su nivel de membresía anterior cuando el pago no se completa.
 */
add_action( 'woocommerce_order_status_changed', 'swpm_revert_level_on_order_status_change', 10, 3 );
function swpm_revert_level_on_order_status_change( $order_id, $old_status, $new_status ) {
    // "completed" lo gestiona swpm_update_level_after_payment; no actuar aquí.
    if ( $new_status === 'completed' ) return;

    $order   = wc_get_order( $order_id );
    $user_id = $order->get_user_id();
    if ( ! $user_id ) return;

    $tiene_membresia = false;
    foreach ( $order->get_items() as $item ) {
        if ( swpm_get_level_from_attribute( $item->get_product_id() ) ) {
            $tiene_membresia = true;
            break;
        }
    }
    if ( ! $tiene_membresia ) return;

    // Actualizar estado de la transacción SWPM usando los valores nativos del plugin.
    if ( class_exists( 'SwpmTransactions' ) ) {
        $order_key     = $order->get_order_key();
        $txn_existente = SwpmTransactions::get_transaction_row_by_subscr_id( $order_key );
        if ( $txn_existente ) {
            if ( $new_status === 'refunded' ) {
                SwpmTransactions::update_transaction_status( $txn_existente->ID, 'Refunded' );
            } elseif ( $new_status === 'cancelled' ) {
                update_post_meta( $txn_existente->ID, 'subscr_status', 'cancelled' );
            } else {
                // Estados sin equivalente SWPM: eliminar el registro para evitar confusión.
                $db_row_id = get_post_meta( $txn_existente->ID, 'db_row_id', true );
                if ( $db_row_id ) {
                    global $wpdb;
                    $wpdb->delete( $wpdb->prefix . 'swpm_payments_tbl', array( 'id' => intval( $db_row_id ) ) );
                }
                wp_delete_post( $txn_existente->ID, true );
            }
        }
    }

    // Revertir nivel de membresía al anterior a la compra en cualquier estado no-completado.
    if ( ! class_exists( 'SwpmMemberUtils' ) ) return;

    $wp_user     = get_userdata( $user_id );
    $swpm_member = $wp_user ? SwpmMemberUtils::get_user_by_email( $wp_user->user_email ) : false;
    if ( ! $swpm_member || ! isset( $swpm_member->member_id ) ) return;

    $nivel_previo = intval( $order->get_meta( '_swpm_previous_level' ) );
    if ( $nivel_previo > 0 ) {
        SwpmMemberUtils::update_membership_level_and_role( $swpm_member->member_id, $nivel_previo );
    }
}

/**
 * 1. REDIRECCIÓN CONDICIONAL
 * Solo al pago directo si el carro está vacío y es una membresía.
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'swpm_conditional_checkout_redirect' );
function swpm_conditional_checkout_redirect( $url ) {
    if ( isset( $_REQUEST['add-to-cart'] ) ) {
        $product_id = intval( $_REQUEST['add-to-cart'] );
        $nivel = swpm_get_level_from_attribute( $product_id );

        // Si el producto tiene atributo de membresía y el carrito estaba vacío (ahora tiene 1)
        if ( $nivel && WC()->cart->get_cart_contents_count() == 1 ) {
            return wc_get_checkout_url();
        }
    }
    return $url;
}

/**
 * 2. ACTUALIZACIÓN DE MEMBRESÍA TRAS PAGO COMPLETADO
 */
add_action( 'woocommerce_order_status_completed', 'swpm_update_level_after_payment', 10, 1 );
function swpm_update_level_after_payment( $order_id ) {
    $order   = wc_get_order( $order_id );
    $user_id = $order->get_user_id();

    if ( ! $user_id ) return;

    $nuevo_nivel = false;
    $product_id  = false;

    foreach ( $order->get_items() as $item ) {
        $pid   = $item->get_product_id();
        $nivel = swpm_get_level_from_attribute( $pid );
        if ( $nivel ) {
            $nuevo_nivel = $nivel;
            $product_id  = $pid;
            break;
        }
    }

    if ( ! $nuevo_nivel ) return;

    // Intentar actualizar mediante la API de SWPM para activar hooks y emails.
    if ( class_exists( 'SwpmMemberUtils' ) && class_exists( 'SwpmTransactions' ) ) {
        $wp_user    = get_userdata( $user_id );
        $swpm_member = $wp_user ? SwpmMemberUtils::get_user_by_email( $wp_user->user_email ) : false;

        if ( $swpm_member && isset( $swpm_member->member_id ) ) {
            $member_id         = $swpm_member->member_id;
            $old_level         = $swpm_member->membership_level;
            $old_account_state = $swpm_member->account_state;

            // a) Actualizar nivel y disparar hook swpm_membership_level_changed.
            SwpmMemberUtils::update_membership_level_and_role( $member_id, $nuevo_nivel );

            // b) Activar cuenta.
            SwpmMemberUtils::update_account_state( $member_id, 'active' );

            // c) Calcular fecha de inicio considerando renovación vs upgrade.
            $access_starts = SwpmMemberUtils::calculate_access_start_date_for_account_update( array(
                'swpm_id'           => $member_id,
                'membership_level'  => $nuevo_nivel,
                'old_membership_level' => $old_level,
                'old_account_state' => $old_account_state,
            ) );
            SwpmMemberUtils::update_access_starts_date( $member_id, $access_starts );

            // d) Registrar o actualizar transacción en SWPM.
            $txn_id    = $order->get_transaction_id();
            $order_key = $order->get_order_key();
            if ( empty( $txn_id ) ) {
                $txn_id = 'woo-' . $order_id;
            }

            $txn_existente = SwpmTransactions::get_transaction_row_by_subscr_id( $order_key );
            if ( $txn_existente ) {
                SwpmTransactions::update_transaction_status( $txn_existente->ID, 'Completed' );
            } else {
                $ipn_data = array(
                    'txn_id'      => $txn_id,
                    'subscr_id'   => $order_key,
                    'payer_email' => $order->get_billing_email(),
                    'first_name'  => $order->get_billing_first_name(),
                    'last_name'   => $order->get_billing_last_name(),
                    'mc_gross'    => $order->get_total(),
                    'gateway'     => 'woocommerce',
                    'status'      => 'Completed',
                    'custom'      => 'swpm_id=' . $member_id . '&subsc_ref=' . $nuevo_nivel,
                );
                SwpmTransactions::save_txn_record( $ipn_data );
            }

            // e) Enviar email de confirmación usando configuración del admin de SWPM.
            $level_update_type = ( $old_level == $nuevo_nivel ) ? 'renewal' : 'upgrade';
            swpm_send_woo_payment_email( $member_id, $order, $level_update_type );

            return;
        }
    }

    // Fallback: actualización directa si SWPM no está disponible o no hay registro.
    global $wpdb;
    $tabla = $wpdb->prefix . 'swpm_members_tbl';
    $wpdb->update(
        $tabla,
        array(
            'membership_level' => $nuevo_nivel,
            'account_state'    => 'active',
        ),
        array( 'user_id' => $user_id )
    );
}

/**
 * Envía el email de confirmación de pago usando la configuración del admin de SWPM.
 *
 * @param int      $member_id         ID de miembro SWPM.
 * @param WC_Order $order             Orden de WooCommerce.
 * @param string   $level_update_type 'renewal' o 'upgrade'.
 */
function swpm_send_woo_payment_email( $member_id, $order, $level_update_type ) {
    if ( ! class_exists( 'SwpmSettings' ) || ! class_exists( 'SwpmMiscUtils' ) ) return;

    $settings     = SwpmSettings::get_instance();
    $from_address = $settings->get_value( 'email-from' );
    $headers      = 'From: ' . $from_address . "\r\n";
    $email        = $order->get_billing_email();

    if ( $level_update_type === 'upgrade' ) {
        if ( $settings->get_value( 'disable-email-after-upgrade' ) ) return;
        $subject = $settings->get_value( 'upgrade-complete-mail-subject' );
        $body    = $settings->get_value( 'upgrade-complete-mail-body' );
        if ( empty( $subject ) ) $subject = 'Member Account Upgraded';
        if ( empty( $body ) )    $body    = 'Your account has been upgraded successfully';
        $body    = SwpmMiscUtils::replace_dynamic_tags( $body, $member_id, array() );
        $subject = apply_filters( 'swpm_email_upgrade_complete_subject', $subject );
        $body    = apply_filters( 'swpm_email_upgrade_complete_body', $body );
    } else {
        if ( $settings->get_value( 'disable-email-after-renew' ) ) return;
        $subject = $settings->get_value( 'renew-complete-mail-subject' );
        $body    = $settings->get_value( 'renew-complete-mail-body' );
        if ( empty( $subject ) ) $subject = 'Member Account Renewed';
        if ( empty( $body ) )    $body    = 'Your account has been renewed successfully';
        $body    = SwpmMiscUtils::replace_dynamic_tags( $body, $member_id, array() );
        $subject = apply_filters( 'swpm_email_renew_complete_subject', $subject );
        $body    = apply_filters( 'swpm_email_renew_complete_body', $body );
    }

    SwpmMiscUtils::mail( $email, $subject, $body, $headers );
}

/**
 * 3. VALIDACIÓN DE CARRITO: IMPEDIR PRODUCTOS MIXTOS
 */
add_filter( 'woocommerce_add_to_cart_validation', 'swpm_validate_cart_integrity', 10, 2 );
function swpm_validate_cart_integrity( $passed, $product_id ) {
    // Obtener info del producto que se intenta añadir
    $nivel_nuevo = swpm_get_level_from_attribute( $product_id );

    // Revisar carrito actual
    if ( WC() && WC()->cart ) {
        $cart_contents = WC()->cart->get_cart_contents();

        foreach ( $cart_contents as $cart_item ) {
            $nivel_existente = swpm_get_level_from_attribute( $cart_item['product_id'] );

            // Caso 1: Carrito tiene membresía, intentan añadir otro producto
            if ( $nivel_existente && ! $nivel_nuevo ) {
                wc_add_notice(
                    '⚠️ Ya tienes una membresía en el carrito. No puedes añadir otros productos con una membresía activa. Por favor, completa la compra de la membresía o vacía el carrito.',
                    'error'
                );
                return false;
            }

            // Caso 2: Carrito tiene productos, intentan añadir membresía
            if ( ! $nivel_existente && $nivel_nuevo ) {
                wc_add_notice(
                    '⚠️ No puedes añadir una membresía si ya hay otros productos en el carrito. Por favor, vacía el carrito primero.',
                    'error'
                );
                return false;
            }

            // Caso 3: Carrito tiene membresía, intentan añadir otra membresía diferente
            if ( $nivel_existente && $nivel_nuevo && $nivel_existente != $nivel_nuevo ) {
                wc_add_notice(
                    '⚠️ Ya tienes una membresía en el carrito. Solo puedes comprar una membresía a la vez.',
                    'error'
                );
                return false;
            }
        }
    }

    return $passed;
}

/**
 * 4. MOSTRAR ADVERTENCIA EN PÁGINA DE PRODUCTO
 */
add_action( 'woocommerce_before_add_to_cart_form', 'swpm_show_membership_cart_warning' );
function swpm_show_membership_cart_warning() {
    global $product;

    if ( ! $product ) return;

    $product_id = $product->get_id();
    $nivel = swpm_get_level_from_attribute( $product_id );

    // Si es membresía, mostrar advertencia
    if ( $nivel ) {
        ?>
        <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong style="color: #856404;">📌 Importante:</strong>
            <p style="color: #856404; margin: 8px 0 0 0;">
                Una vez añadas esta membresía al carrito, no podrás añadir otros productos.
                La membresía debe comprarse por separado para evitar problemas con los pagos.
            </p>
        </div>
        <?php
    }
}

/**
 * 5. FILTRAR MÉTODOS DE PAGO: SOLO SEPA PARA MEMBRESÍA
 */
add_filter( 'woocommerce_available_payment_gateways', 'swpm_filter_payment_gateways' );
function swpm_filter_payment_gateways( $gateways ) {
    if ( is_admin() ) return $gateways;

    $tiene_membresia = false;
    $tiene_otros = false;

    if ( WC() && WC()->cart ) {
        foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
            $nivel = swpm_get_level_from_attribute( $cart_item['product_id'] );
            if ( $nivel ) {
                $tiene_membresia = true;
            } else {
                $tiene_otros = true;
            }
        }
    }

    if ( $tiene_membresia && ! $tiene_otros ) {
        // Carrito con SOLO membresía: todos los métodos disponibles
    } else {
        // Otros casos: SEPA no disponible
        unset( $gateways['stripe_sepa_debit'] );
    }

    return $gateways;
}

/**
 * 6. VALIDACIÓN FINAL EN CHECKOUT
 */
add_action( 'woocommerce_checkout_process', 'swpm_validate_checkout_cart' );
function swpm_validate_checkout_cart() {
    if ( ! WC() || ! WC()->cart ) return;

    $tiene_membresia = false;
    $tiene_otros = false;

    foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
        $nivel = swpm_get_level_from_attribute( $cart_item['product_id'] );

        if ( $nivel ) {
            $tiene_membresia = true;
        } else {
            $tiene_otros = true;
        }
    }

    // Si hay mezcla, rechazar checkout
    if ( ( $tiene_membresia && $tiene_otros ) || ( ! $tiene_membresia && ! $tiene_otros && WC()->cart->get_cart_contents_count() > 0 ) ) {
        wc_add_notice(
            '❌ Error en el carrito: No puedes mezclar membresía con otros productos. Por favor, revisa tu carrito.',
            'error'
        );
    }
}

/**
 * 7. SHORTCODE PARA EL BOTÓN
 * Uso: [boton_membresia id="123" texto="Comprar" clase="mi-estilo"]
 */
add_shortcode( 'boton_membresia', 'swpm_woo_button_shortcode' );
function swpm_woo_button_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'id'    => '',
        'texto' => 'Comprar Membresía',
        'clase' => 'button alt',
    ), $atts );

    if ( empty( $a['id'] ) ) return '';

    $product_id = intval( $a['id'] );
    $nivel = swpm_get_level_from_attribute( $product_id );
    $button_disabled = false;
    $button_text = $a['texto'];
    $button_title = '';

    // Verificar si el producto ya está en el carrito
    if ( WC() && WC()->cart ) {
        $cart_contents = WC()->cart->get_cart_contents();
        $carrito_tiene_otro = false;

        foreach ( $cart_contents as $cart_item ) {
            if ( $cart_item['product_id'] === $product_id ) {
                $button_disabled = true;
                $button_text = 'Ya en el carrito';
                break;
            }

            // Verificar si carrito tiene otros productos
            $nivel_otro = swpm_get_level_from_attribute( $cart_item['product_id'] );
            if ( ! $nivel_otro ) {
                $carrito_tiene_otro = true;
            }
        }

        // Si carrito tiene otros productos, deshabilitar membresía
        if ( ! $button_disabled && $nivel && $carrito_tiene_otro ) {
            $button_disabled = true;
            $button_text = 'Vacía carrito primero';
            $button_title = 'El carrito contiene otros productos. Completa esa compra o vacía el carrito para añadir membresía.';
        }
    }

    // Verificar si el usuario ya tiene este nivel de membresía
    if ( ! $button_disabled && $nivel && class_exists( 'SwpmMemberUtils' ) ) {
        $swpm_member = null;
        $debug = array();

        // Verificar WordPress auth primero
        if ( is_user_logged_in() ) {
            $wp_user = get_userdata( get_current_user_id() );
            if ( $wp_user ) {
                $swpm_member = SwpmMemberUtils::get_user_by_email( $wp_user->user_email );
                $debug[] = "WP auth detected, email: {$wp_user->user_email}";
            }
        }
        // Fallback: verificar SWPM auth si WP auth no disponible
        elseif ( class_exists( 'SwpmAuth' ) ) {
            $swpm_auth = SwpmAuth::get_instance();
            if ( $swpm_auth->is_logged_in() ) {
                $member_email = $swpm_auth->get( 'email' );
                $swpm_member = SwpmMemberUtils::get_user_by_email( $member_email );
                $debug[] = "SWPM auth detected, email: $member_email";
            }
        }

        // Si encontramos miembro SWPM, verificar su nivel
        if ( $swpm_member && isset( $swpm_member->membership_level ) ) {
            $user_level = $swpm_member->membership_level;
            $debug[] = "Product nivel: $nivel, User level: $user_level";

            if ( intval( $user_level ) === $nivel ) {
                $button_disabled = true;
                $button_text = 'Ya tienes esta membresía';
            }
        } else {
            $debug[] = "No SWPM member found";
        }

        // Mostrar debug en comentario HTML (solo para admin)
        if ( current_user_can( 'manage_options' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            echo '<!-- DEBUG boton_membresia: ' . implode( ' | ', $debug ) . ' -->';
        }
    }

    // Generamos el botón
    if ( $button_disabled ) {
        $title_attr = $button_title ? sprintf( ' title="%s"', esc_attr( $button_title ) ) : '';
        return sprintf( '<button disabled class="%s" style="cursor: not-allowed; opacity: 0.6;"%s>%s</button>',
            esc_attr( $a['clase'] ),
            $title_attr,
            esc_html( $button_text )
        );
    }

    $url = wc_get_cart_url() . '?add-to-cart=' . $product_id;
    return sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $a['clase'] ), esc_html( $button_text ) );
}

/**
 * 8. AVISO EN CARRITO: MOSTRAR RESTRICCIÓN DE PRODUCTOS
 */
add_action( 'woocommerce_before_cart_contents', 'swpm_show_cart_warning' );
function swpm_show_cart_warning() {
    if ( ! WC() || ! WC()->cart ) return;

    $tiene_membresia = false;

    foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
        $nivel = swpm_get_level_from_attribute( $cart_item['product_id'] );
        if ( $nivel ) {
            $tiene_membresia = true;
            break;
        }
    }

    if ( $tiene_membresia ) {
        ?>
        <div style="background-color: #e7f3ff; border: 1px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong style="color: #0c5aa0;">ℹ️ Información importante:</strong>
            <p style="color: #0c5aa0; margin: 8px 0 0 0;">
                Tienes una membresía en el carrito. Por razones de seguridad en los pagos,
                <strong>no se puede añadir otros productos</strong> junto con la membresía.
                Por favor, completa esta compra primero.
            </p>
        </div>
        <?php
    }
}