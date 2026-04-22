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
 * 3. SHORTCODE PARA EL BOTÓN
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

    // Verificar si el producto ya está en el carrito
    if ( WC() && WC()->cart ) {
        $cart_contents = WC()->cart->get_cart_contents();
        foreach ( $cart_contents as $cart_item ) {
            if ( $cart_item['product_id'] === $product_id ) {
                $button_disabled = true;
                $button_text = 'Ya en el carrito';
                break;
            }
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
        return sprintf( '<button disabled class="%s" style="cursor: not-allowed; opacity: 0.6;">%s</button>',
            esc_attr( $a['clase'] ),
            esc_html( $button_text )
        );
    }

    $url = wc_get_cart_url() . '?add-to-cart=' . $product_id;
    return sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $a['clase'] ), esc_html( $button_text ) );
}