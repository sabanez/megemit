<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'swpm_payment_ipn_processed', 'mgmit_bridge_payment_completed', 10, 1 );

function mgmit_bridge_payment_completed( $ipn_data ) {
    $email = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    if ( empty( $email ) ) {
        return;
    }

    $wp_user_id = email_exists( $email );
    if ( ! $wp_user_id ) {
        $wp_user_id = 0;
    }

    $first_name = isset( $ipn_data['first_name'] ) ? sanitize_text_field( $ipn_data['first_name'] ) : '';
    $last_name  = isset( $ipn_data['last_name'] )  ? sanitize_text_field( $ipn_data['last_name'] )  : '';
    $txn_id     = isset( $ipn_data['txn_id'] )     ? sanitize_text_field( $ipn_data['txn_id'] )     : '';
    $subscr_id  = isset( $ipn_data['subscr_id'] )  ? sanitize_text_field( $ipn_data['subscr_id'] )  : '';
    $amount     = isset( $ipn_data['mc_gross'] )   ? sanitize_text_field( $ipn_data['mc_gross'] )   : '0.00';
    $gateway    = isset( $ipn_data['gateway'] )    ? sanitize_text_field( $ipn_data['gateway'] )    : '';

    $level_id   = 0;
    $level_name = '';
    if ( isset( $ipn_data['member_id'] ) && $ipn_data['member_id'] ) {
        $level_id = SwpmMemberUtils::get_membership_level_id_of_a_member( (int) $ipn_data['member_id'] );
    }
    if ( $level_id ) {
        $level_row  = SwpmUtils::get_membership_level_row_by_id( $level_id );
        $level_name = ( $level_row && isset( $level_row->alias ) ) ? $level_row->alias : '';
    }

    $woo_product_id  = 0;
    $woo_product_sku = '';
    if ( $level_id ) {
        $woo_posts = get_posts( array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
            'meta_key'       => '_swpm_membership_level',
            'meta_value'     => $level_id,
            'fields'         => 'ids',
        ) );
        if ( ! empty( $woo_posts ) ) {
            $woo_product_id  = (int) $woo_posts[0];
            $woo_product_sku = get_post_meta( $woo_product_id, '_sku', true );
        }
    }

    $order_id     = abs( crc32( $txn_id ? $txn_id : ( $subscr_id . $wp_user_id ) ) );
    $order_number = $txn_id ? $txn_id : $subscr_id;
    $now_iso      = gmdate( 'Y-m-d\TH:i:s' );

    $payload = array(
        'id'             => $order_id,
        'number'         => $order_number,
        'status'         => 'completed',
        'currency'       => 'EUR',
        'total'          => $amount,
        'transaction_id' => $txn_id,
        'payment_method' => $gateway,
        'customer_id'    => $wp_user_id,
        'created_via'    => 'swpm',
        'date_created'   => $now_iso,
        'date_paid'      => $now_iso,
        'billing'        => array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
        ),
        'line_items'     => array(
            array(
                'name'       => $level_name ? $level_name : 'Membresia',
                'product_id' => $woo_product_id ? $woo_product_id : $level_id,
                'quantity'   => 1,
                'total'      => $amount,
                'subtotal'   => $amount,
                'price'      => floatval( $amount ),
                'sku'        => $woo_product_sku ? $woo_product_sku : 'MeGeMIT-Mitglied-' . $level_id,
            ),
        ),
        'meta_data'      => array(
            array( 'key' => '_stripe_intent_id',     'value' => $txn_id ),
            array( 'key' => '_stripe_customer_id',   'value' => $subscr_id ),
            array( 'key' => 'membership_level_id',   'value' => $level_id ),
            array( 'key' => 'membership_level_name', 'value' => $level_name ),
            array( 'key' => 'source',                'value' => 'swpm' ),
        ),
    );

    MGMIT_Webhook_Sender::send( $payload, 'order.created' );

    mgmit_bridge_capture_payment_meta( $ipn_data, $wp_user_id );

    $gender = '';
    if ( isset( $ipn_data['member_id'] ) && $ipn_data['member_id'] ) {
        global $wpdb;
        $gender_raw = $wpdb->get_var( $wpdb->prepare(
            "SELECT gender FROM {$wpdb->prefix}swpm_members_tbl WHERE member_id = %d LIMIT 1",
            (int) $ipn_data['member_id']
        ) );
        if ( $gender_raw ) {
            $gender = sanitize_text_field( $gender_raw );
        }
    }

    mgmit_bridge_send_membership_email( $ipn_data, $gender );
}

/**
 * Guarda el método de pago y la fecha de vencimiento de membresía en user meta.
 * Regla de octubre: pago en mes >= 10 → válido hasta 31/12 del año siguiente.
 */
function mgmit_bridge_capture_payment_meta( $ipn_data, $wp_user_id ) {
    if ( ! $wp_user_id ) {
        return;
    }

    $gateway   = isset( $ipn_data['gateway'] )   ? $ipn_data['gateway']   : '';
    $subscr_id = isset( $ipn_data['subscr_id'] ) ? sanitize_text_field( $ipn_data['subscr_id'] ) : '';

    if ( 'stripe-sca-subs' === $gateway && ! empty( $subscr_id ) ) {
        // swpm_stripe_sca_ipn_processed dispara ANTES que swpm_payment_ipn_processed,
        // por lo que el refinamiento a 'sepa' puede ya estar guardado. No sobreescribir.
        $existing_method = get_user_meta( $wp_user_id, 'mgmit_payment_method', true );
        if ( 'sepa' !== $existing_method ) {
            update_user_meta( $wp_user_id, 'mgmit_payment_method', 'card' );
        }
        update_user_meta( $wp_user_id, 'mgmit_stripe_subscription_id', $subscr_id );
    } elseif ( ! empty( $gateway ) ) {
        update_user_meta( $wp_user_id, 'mgmit_payment_method', 'transfer' );
    }

    $timezone         = new DateTimeZone( 'Europe/Madrid' );
    $now              = new DateTime( 'now', $timezone );
    $month            = (int) $now->format( 'n' );
    $year             = (int) $now->format( 'Y' );
    $valid_until_year = ( $month >= 10 ) ? ( $year + 1 ) : $year;
    $valid_until      = $valid_until_year . '-12-31';

    update_user_meta( $wp_user_id, 'mgmit_membership_valid_until', $valid_until );
    delete_user_meta( $wp_user_id, 'mgmit_renewal_notices_sent' );
    delete_user_meta( $wp_user_id, 'mgmit_show_renewal_button' );

    MGMIT_Webhook_Sender::debug_log(
        'capture_payment_meta: user=' . $wp_user_id .
        ' gateway=' . $gateway .
        ' valid_until=' . $valid_until
    );
}

/**
 * Reemplaza la suscripción creada por Stripe (aniversario) por una con trial_end = 31/12.
 * Se dispara en swpm_stripe_sca_ipn_processed, dentro del procesamiento de SWPM.
 * Usa un transient para evitar doble ejecución si SWPM también procesa con éxito.
 */
add_action( 'swpm_stripe_sca_ipn_processed', 'mgmit_bridge_replace_subscription', 10, 1 );

function mgmit_bridge_replace_subscription( $ipn_data ) {
    $email     = isset( $ipn_data['payer_email'] ) ? sanitize_email( $ipn_data['payer_email'] ) : '';
    $subscr_id = isset( $ipn_data['subscr_id'] )   ? sanitize_text_field( $ipn_data['subscr_id'] ) : '';

    if ( empty( $email ) || empty( $subscr_id ) ) {
        return;
    }

    // Solo suscripciones nuevas, no renovaciones de webhook
    if ( 0 !== strpos( $subscr_id, 'sub_' ) ) {
        return;
    }
    $txn_type = isset( $ipn_data['txn_type'] ) ? $ipn_data['txn_type'] : '';
    if ( 'stripe_subscription_new' !== $txn_type ) {
        return;
    }

    $wp_user_id = email_exists( $email );
    if ( ! $wp_user_id ) {
        return;
    }

    $price_id = get_option( 'mgmit_stripe_annual_price_id', '' );
    if ( empty( $price_id ) ) {
        MGMIT_Webhook_Sender::debug_log( 'replace_subscription: mgmit_stripe_annual_price_id no configurado' );
        return;
    }

    try {
        SwpmMiscUtils::load_stripe_lib();

        $settings        = SwpmSettings::get_instance();
        $sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );
        $secret_key      = $sandbox_enabled
            ? $settings->get_value( 'stripe-test-secret-key' )
            : $settings->get_value( 'stripe-live-secret-key' );

        \Stripe\Stripe::setApiKey( $secret_key );

        // Recuperar suscripción original del IPN
        $old_sub        = \Stripe\Subscription::retrieve( $subscr_id );
        $customer_id    = $old_sub->customer;
        $pm_id          = $old_sub->default_payment_method;
        $payment_method = 'card';

        if ( empty( $customer_id ) ) {
            MGMIT_Webhook_Sender::debug_log( 'replace_subscription: customer vacío para sub=' . $subscr_id );
            return;
        }

        if ( ! empty( $pm_id ) ) {
            $pm             = \Stripe\PaymentMethod::retrieve( $pm_id );
            $payment_method = ( 'sepa_debit' === $pm->type ) ? 'sepa' : 'card';
        }
        update_user_meta( $wp_user_id, 'mgmit_payment_method', $payment_method );

        // Calcular trial_end = 31/12 año vigente (regla de octubre: año+1 si mes >= 10)
        $timezone       = new DateTimeZone( 'Europe/Madrid' );
        $now            = new DateTime( 'now', $timezone );
        $month          = (int) $now->format( 'n' );
        $year           = (int) $now->format( 'Y' );
        $trial_end_year = ( $month >= 10 ) ? ( $year + 1 ) : $year;
        $trial_end      = new DateTime( $trial_end_year . '-12-31 23:59:59', new DateTimeZone( 'UTC' ) );

        // Crear nueva suscripción con trial hasta 31/12 PRIMERO
        $new_sub_params = array(
            'customer'         => $customer_id,
            'items'            => array( array( 'price' => $price_id ) ),
            'trial_end'        => $trial_end->getTimestamp(),
            'payment_settings' => array(
                'payment_method_types'        => array( 'sepa_debit', 'card' ),
                'save_default_payment_method' => 'on_subscription',
            ),
        );
        if ( ! empty( $pm_id ) ) {
            $new_sub_params['default_payment_method'] = $pm_id;
        }

        $new_sub    = \Stripe\Subscription::create( $new_sub_params );
        $new_sub_id = $new_sub->id;

        // Actualizar todos los datos ANTES de cancelar la suscripción antigua.
        // Así cuando llegue el webhook customer.subscription.deleted de Stripe,
        // SWPM ya no encontrará subscr_id=sub_ANTIGUA en ninguna tabla
        // y no desactivará al miembro ni marcará la transacción como cancelada.
        update_user_meta( $wp_user_id, 'mgmit_stripe_subscription_id', $new_sub_id );

        global $wpdb;
        if ( isset( $ipn_data['member_id'] ) && $ipn_data['member_id'] ) {
            // 1. Actualizar tabla de miembros SWPM
            $wpdb->update(
                $wpdb->prefix . 'swpm_members_tbl',
                array( 'subscr_id' => $new_sub_id ),
                array( 'member_id' => (int) $ipn_data['member_id'] ),
                array( '%s' ),
                array( '%d' )
            );

            // 2. Actualizar subscr_id en el CPT swpm_transactions para que SWPM
            //    no encuentre sub_ANTIGUA cuando procese el webhook de cancelación
            //    y no marque la transacción como 'canceled'.
            $txn_post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}postmeta
                 WHERE meta_key = 'subscr_id' AND meta_value = %s",
                $subscr_id
            ) );
            foreach ( $txn_post_ids as $txn_post_id ) {
                if ( 'swpm_transactions' === get_post_type( (int) $txn_post_id ) ) {
                    update_post_meta( (int) $txn_post_id, 'subscr_id', $new_sub_id );
                    MGMIT_Webhook_Sender::debug_log(
                        'replace_subscription: CPT post_id=' . $txn_post_id .
                        ' subscr_id actualizado a ' . $new_sub_id
                    );
                }
            }
        }

        // Cancelar la suscripción de aniversario AL FINAL, cuando ya no hay
        // ningún dato apuntando a ella en SWPM.
        $old_sub->cancel();

        MGMIT_Webhook_Sender::debug_log(
            'replace_subscription: old=' . $subscr_id .
            ' new=' . $new_sub_id .
            ' customer=' . $customer_id .
            ' pm=' . $payment_method .
            ' trial_end=' . $trial_end->format( 'Y-m-d' )
        );

    } catch ( \Throwable $e ) {
        MGMIT_Webhook_Sender::debug_log( 'replace_subscription error: ' . $e->getMessage() );
    }
}
