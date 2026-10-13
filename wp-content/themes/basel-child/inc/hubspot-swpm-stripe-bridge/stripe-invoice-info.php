<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Datos de la factura de Stripe para el PDF de factura (alta y renovación).
 *
 * Consulta la suscripción (subscr_id) en Stripe y devuelve:
 *  - invoice_number : número de factura de Stripe (p. ej. "ABCD1234-0001")
 *  - payment_label  : método de pago realmente usado, en alemán
 *  - address        : dirección que el cliente introdujo en Stripe al pagar (address, city, postcode, state, country)
 *
 * Si Stripe no responde, devuelve los campos vacíos y el PDF se genera igualmente.
 *
 * @param array $ipn_data Datos IPN de SWPM (subscr_id y, en el alta, payment_button_id).
 * @return array
 */
function mgmit_bridge_get_stripe_invoice_info( $ipn_data ) {
    $info = array(
        'invoice_number' => '',
        'payment_label'  => '',
        'address'        => array(), // dirección introducida en Stripe (vacía si no hay o no se pudo leer)
    );

    $sub_id = isset( $ipn_data['subscr_id'] ) ? sanitize_text_field( $ipn_data['subscr_id'] ) : '';
    if ( '' === $sub_id || ! class_exists( 'SwpmMiscUtils' ) ) {
        return $info;
    }

    $secret = mgmit_bridge_get_stripe_secret_key( $ipn_data, $sub_id );
    if ( '' === $secret ) {
        MGMIT_Webhook_Sender::debug_log( 'stripe_invoice_info: sin clave secreta de Stripe para ' . $sub_id );
        return $info;
    }

    try {
        SwpmMiscUtils::load_stripe_lib();
        \Stripe\Stripe::setApiKey( $secret );

        $sub = \Stripe\Subscription::retrieve(
            array(
                'id'     => $sub_id,
                'expand' => array( 'default_payment_method', 'customer' ),
            )
        );

        // Dirección que el cliente introdujo en Stripe al pagar: primero la del método de
        // pago (datos de facturación) y, si no tiene calle, la del cliente de Stripe.
        $stripe_addr = null;
        if ( isset( $sub->default_payment_method->billing_details->address ) ) {
            $stripe_addr = mgmit_bridge_stripe_address_to_array( $sub->default_payment_method->billing_details->address );
        }
        if ( ( ! $stripe_addr || '' === $stripe_addr['address'] ) && isset( $sub->customer->address ) ) {
            $stripe_addr = mgmit_bridge_stripe_address_to_array( $sub->customer->address );
        }
        if ( $stripe_addr && '' !== $stripe_addr['address'] ) {
            $info['address'] = $stripe_addr;
        }

        // Factura más reciente de la suscripción que ya tenga número. No se usa latest_invoice
        // (puede ser la siguiente factura, en borrador y sin número) ni se exige estado "paid":
        // en pagos SEPA la factura queda abierta hasta que se liquida, pero ya está numerada.
        $invoices = \Stripe\Invoice::all(
            array(
                'subscription' => $sub_id,
                'limit'        => 5,
            )
        );
        foreach ( $invoices->data as $invoice ) {
            if ( ! empty( $invoice->number ) && 'void' !== $invoice->status ) {
                $info['invoice_number'] = sanitize_text_field( $invoice->number );
                break;
            }
        }

        $pm_type = '';
        if ( isset( $sub->default_payment_method->type ) ) {
            $pm_type = $sub->default_payment_method->type;
        } elseif ( ! empty( $sub->default_payment_method ) && is_string( $sub->default_payment_method ) ) {
            $pm      = \Stripe\PaymentMethod::retrieve( $sub->default_payment_method );
            $pm_type = isset( $pm->type ) ? $pm->type : '';
        }
        $info['payment_label'] = mgmit_bridge_payment_method_label( $pm_type );
    } catch ( \Exception $e ) {
        MGMIT_Webhook_Sender::debug_log( 'stripe_invoice_info: error Stripe (' . $sub_id . '): ' . $e->getMessage() );
    }

    return $info;
}

/**
 * Dirección de Stripe (line1, line2, city, postal_code, state, country) → array propio.
 * La calle es line1 y, si existe, line2 (número, piso...).
 *
 * @param object $addr Objeto Address de Stripe.
 * @return array address, city, postcode, state, country (cadenas).
 */
function mgmit_bridge_stripe_address_to_array( $addr ) {
    $get = function ( $key ) use ( $addr ) {
        return isset( $addr->$key ) && is_string( $addr->$key ) ? sanitize_text_field( $addr->$key ) : '';
    };

    $street = trim( $get( 'line1' ) . ' ' . $get( 'line2' ) );

    return array(
        'address'  => $street,
        'city'     => $get( 'city' ),
        'postcode' => $get( 'postal_code' ),
        'state'    => $get( 'state' ),
        'country'  => $get( 'country' ),
    );
}

/**
 * Clave secreta de Stripe: la del botón de pago SWPM (alta: viene en el IPN;
 * renovación: se recupera de la transacción guardada) o, en su defecto, la global.
 */
function mgmit_bridge_get_stripe_secret_key( $ipn_data, $sub_id ) {
    $live      = ! SwpmSettings::get_instance()->get_value( 'enable-sandbox-testing' );
    $button_id = isset( $ipn_data['payment_button_id'] ) ? absint( $ipn_data['payment_button_id'] ) : 0;

    if ( ! $button_id ) {
        $txns = get_posts(
            array(
                'post_type'      => 'swpm_transactions',
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'   => 'subscr_id',
                        'value' => $sub_id,
                    ),
                ),
            )
        );
        if ( $txns ) {
            $button_id = absint( get_post_meta( $txns[0]->ID, 'payment_button_id', true ) );
        }
    }

    if ( $button_id ) {
        $keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $button_id, $live );
        if ( ! empty( $keys['secret'] ) ) {
            return $keys['secret'];
        }
    }

    $opts = get_option( 'swpm-settings' );
    $key  = $live ? 'stripe-live-secret-key' : 'stripe-test-secret-key';
    return isset( $opts[ $key ] ) ? (string) $opts[ $key ] : '';
}

/**
 * Dirección del socio para la factura, siempre de la base de datos: primero los datos
 * de facturación del usuario de WordPress (billing_*) y, si no tienen calle, la dirección
 * guardada en la ficha del socio de SWPM (address_street, address_zipcode...).
 *
 * @param object|null $swpm_member Fila de swpm_members_tbl (o null).
 * @param int|false   $wp_user_id  ID del usuario de WordPress (o false).
 * @return array address, city, postcode, state, country (cadenas, vacías si no hay dato).
 */
function mgmit_bridge_get_member_address( $swpm_member, $wp_user_id ) {
    $addr = array(
        'address'  => '',
        'city'     => '',
        'postcode' => '',
        'state'    => '',
        'country'  => '',
    );

    if ( $wp_user_id ) {
        $addr['address']  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_address_1', true ) );
        $addr['city']     = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_city', true ) );
        $addr['postcode'] = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_postcode', true ) );
        $addr['state']    = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_state', true ) );
        $addr['country']  = sanitize_text_field( get_user_meta( $wp_user_id, 'billing_country', true ) );
    }

    if ( '' === $addr['address'] && $swpm_member ) {
        $addr['address']  = isset( $swpm_member->address_street ) ? sanitize_text_field( $swpm_member->address_street ) : '';
        $addr['city']     = isset( $swpm_member->address_city ) ? sanitize_text_field( $swpm_member->address_city ) : '';
        $addr['postcode'] = isset( $swpm_member->address_zipcode ) ? sanitize_text_field( $swpm_member->address_zipcode ) : '';
        $addr['state']    = isset( $swpm_member->address_state ) ? sanitize_text_field( $swpm_member->address_state ) : '';
        $addr['country']  = isset( $swpm_member->country ) ? sanitize_text_field( $swpm_member->country ) : '';
    }

    return $addr;
}

/**
 * Dirección del contacto en HubSpot (address, city, zip, state, country), buscada por el
 * email del socio. Devuelve todo vacío si HubSpot no responde o el contacto no tiene dirección.
 *
 * @param string $email Email del contacto (el del socio, que está en HubSpot).
 * @return array address, city, postcode, state, country (cadenas).
 */
function mgmit_bridge_get_hubspot_address( $email ) {
    $addr = array(
        'address'  => '',
        'city'     => '',
        'postcode' => '',
        'state'    => '',
        'country'  => '',
    );

    if ( ! class_exists( 'MGMIT_HS_Contacts' ) || ! method_exists( 'MGMIT_HS_Contacts', 'get_properties' ) ) {
        return $addr;
    }

    $result = MGMIT_HS_Contacts::get_properties( $email, array( 'address', 'city', 'zip', 'state', 'country' ) );
    $values = isset( $result['values'] ) ? $result['values'] : array();

    $addr['address']  = isset( $values['address'] ) ? sanitize_text_field( $values['address'] ) : '';
    $addr['city']     = isset( $values['city'] ) ? sanitize_text_field( $values['city'] ) : '';
    $addr['postcode'] = isset( $values['zip'] ) ? sanitize_text_field( $values['zip'] ) : '';
    $addr['state']    = isset( $values['state'] ) ? sanitize_text_field( $values['state'] ) : '';
    $addr['country']  = isset( $values['country'] ) ? sanitize_text_field( $values['country'] ) : '';

    if ( '' === $addr['address'] && '' === $addr['city'] && '' === $addr['postcode'] && class_exists( 'MGMIT_Webhook_Sender' ) ) {
        MGMIT_Webhook_Sender::debug_log(
            'direccion: HubSpot sin dirección para ' . sanitize_email( $email ) . ' (http=' . ( isset( $result['http_code'] ) ? $result['http_code'] : '?' ) . ')'
        );
    }

    return $addr;
}

/**
 * Dirección del perfil del socio en SWPM (la que coincide con HubSpot), para la
 * confirmación de membresía. Solo la ficha SWPM, sin datos de facturación de WordPress.
 * Si el perfil no tiene dirección, queda constancia en el log del bridge.
 *
 * @param object|null $swpm_member Fila de swpm_members_tbl (o null).
 * @return array street, postcode, city, country (cadenas, vacías si no hay dato).
 */
function mgmit_bridge_get_swpm_profile_address( $swpm_member ) {
    $get = function ( $field ) use ( $swpm_member ) {
        return ( $swpm_member && isset( $swpm_member->$field ) ) ? trim( sanitize_text_field( $swpm_member->$field ), " \t\n\r\0\x0B," ) : '';
    };

    $addr = array(
        'street'   => $get( 'address_street' ),
        'postcode' => $get( 'address_zipcode' ),
        'city'     => $get( 'address_city' ),
        'country'  => $get( 'country' ),
    );

    if ( '' === $addr['street'] && '' === $addr['postcode'] && '' === $addr['city'] && class_exists( 'MGMIT_Webhook_Sender' ) ) {
        MGMIT_Webhook_Sender::debug_log(
            'confirmacion: el perfil SWPM del socio ' . ( $swpm_member && isset( $swpm_member->member_id ) ? $swpm_member->member_id : '(no encontrado)' ) . ' no tiene dirección'
        );
    }

    return $addr;
}

/**
 * Tipo de método de pago de Stripe → texto para la factura.
 */
function mgmit_bridge_payment_method_label( $type ) {
    $labels = array(
        'card'       => 'Kredit-/Debitkarte',
        'paypal'     => 'PayPal',
        'klarna'     => 'Klarna',
        'sepa_debit' => 'SEPA-Lastschrift',
        'link'       => 'Link',
        'bancontact' => 'Bancontact',
        'ideal'      => 'iDEAL',
        'eps'        => 'EPS',
        'sofort'     => 'Sofort',
    );
    if ( '' === $type ) {
        return '';
    }
    return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( str_replace( '_', ' ', $type ) );
}

/**
 * Variables comunes de la factura PDF: periodo (compra → +1 año − 1 día),
 * nº de factura Stripe, nº de pedido (ID de socio SWPM) y método de pago.
 *
 * @param array $ipn_data Datos IPN de SWPM.
 * @return array invoice_number, order_number, payment_label, period_label, stripe_address
 */
function mgmit_bridge_build_invoice_vars( $ipn_data ) {
    $info = mgmit_bridge_get_stripe_invoice_info( $ipn_data );

    $start = new DateTime( 'now', wp_timezone() );
    $end   = clone $start;
    $end->modify( '+1 year -1 day' );

    $swpm_id = isset( $ipn_data['swpm_id'] ) ? absint( $ipn_data['swpm_id'] ) : 0;
    if ( ! $swpm_id && isset( $ipn_data['member_id'] ) ) {
        $swpm_id = absint( $ipn_data['member_id'] );
    }

    return array(
        'invoice_number' => $info['invoice_number'],
        'order_number'   => $swpm_id ? (string) $swpm_id : '',
        'payment_label'  => $info['payment_label'],
        'period_label'   => $start->format( 'd-m-Y' ) . ' bis ' . $end->format( 'd-m-Y' ),
        'stripe_address' => $info['address'],
    );
}
