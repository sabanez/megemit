<?php
/**
 * Puente entre WooCommerce y Simple WP Membership basado en Atributos
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Seguridad

/**
 * Función auxiliar para obtener el nivel desde el atributo 'nivel_swpm'
 */
function swpm_get_level_from_attribute( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) return false;

    // Buscamos el valor del atributo 'nivel_swpm'
    $nivel = $product->get_attribute( 'nivel_swpm' );

    return !empty( $nivel ) ? intval( $nivel ) : false;
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
    $order = wc_get_order( $order_id );
    $user_id = $order->get_user_id();

    if ( ! $user_id ) return;

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $nuevo_nivel = swpm_get_level_from_attribute( $product_id );

        if ( $nuevo_nivel ) {
            global $wpdb;
            $tabla = $wpdb->prefix . "swpm_members_tbl";

            $wpdb->update(
                $tabla,
                array( 
                    'membership_level' => $nuevo_nivel, 
                    'account_state'    => 'active' 
                ),
                array( 'user_id' => $user_id )
            );
            break; // Solo procesar la primera membresía encontrada
        }
    }
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