<?php
/**
 * Oferta: la compra del producto MGMIT_OFFER_PRODUCT_ID entrega un código
 * promocional de Stripe único (1 uso, caducidad MGMIT_OFFER_CODE_DAYS días)
 * sobre el cupón del 100 % durante 12 meses.
 *
 * - Se genera al completarse el pedido (pago verificado), una vez por pedido.
 * - Idempotente: el código se guarda en el meta del pedido.
 * - Se muestra en el email "pedido completado" del cliente y en la ficha del pedido.
 * - Sin first_time_transaction: lo pueden canjear también miembros que renuevan.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MGMIT_OFFER_META_CODE    = '_mgmit_offer_promo_code';
const MGMIT_OFFER_META_EXPIRES = '_mgmit_offer_promo_expires';

/** ¿El pedido contiene el producto de oferta? */
function mgmit_offer_order_has_product( WC_Order $order ): bool {
    foreach ( $order->get_items() as $item ) {
        if ( (int) $item->get_product_id() === (int) MGMIT_OFFER_PRODUCT_ID ) {
            return true;
        }
    }
    return false;
}

/** Modo y credenciales de Stripe según el botón de SWPM. @return array{secret:string,coupon:string,mode:string}|null */
function mgmit_offer_stripe_config(): ?array {
    if ( ! class_exists( 'SwpmMiscUtils' ) || ! class_exists( 'SwpmSettings' ) || ! MGMIT_OFFER_SWPM_BUTTON_ID ) {
        return null;
    }
    $sandbox = (bool) SwpmSettings::get_instance()->get_value( 'enable-sandbox-testing' );
    $keys    = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( MGMIT_OFFER_SWPM_BUTTON_ID, ! $sandbox );
    $coupon  = $sandbox ? MGMIT_OFFER_STRIPE_COUPON_TEST : MGMIT_OFFER_STRIPE_COUPON_LIVE;

    if ( empty( $keys['secret'] ) || '' === $coupon ) {
        return null;
    }
    return array(
        'secret' => $keys['secret'],
        'coupon' => $coupon,
        'mode'   => $sandbox ? 'test' : 'live',
    );
}

/** Texto del código, p. ej. MGM-K7Q2-X9PD (sin caracteres ambiguos). */
function mgmit_offer_random_code(): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max      = strlen( $alphabet ) - 1;
    $part     = static function () use ( $alphabet, $max ): string {
        $s = '';
        for ( $i = 0; $i < 4; $i++ ) {
            $s .= $alphabet[ random_int( 0, $max ) ];
        }
        return $s;
    };
    return 'MGM-' . $part() . '-' . $part();
}

/**
 * Crea el código promocional al completarse el pedido (antes del envío del email, prioridad 5).
 */
add_action( 'woocommerce_order_status_completed', 'mgmit_offer_generate_code', 5, 2 );
function mgmit_offer_generate_code( int $order_id, $order = null ): void {
    $order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
    if ( ! $order || ! mgmit_offer_order_has_product( $order ) ) {
        return;
    }
    if ( $order->get_meta( MGMIT_OFFER_META_CODE ) ) {
        return; // Ya generado.
    }

    // Bloqueo corto contra ejecuciones concurrentes del hook.
    $lock = 'mgmit_offer_lock_' . $order_id;
    if ( get_transient( $lock ) ) {
        return;
    }
    set_transient( $lock, 1, 60 );

    $cfg = mgmit_offer_stripe_config();
    if ( ! $cfg ) {
        $order->add_order_note( 'Oferta: no se generó el código promocional (faltan botón SWPM, claves o ID de cupón de Stripe).' );
        delete_transient( $lock );
        return;
    }

    SwpmMiscUtils::load_stripe_lib();
    $expires = time() + ( (int) MGMIT_OFFER_CODE_DAYS * DAY_IN_SECONDS );

    try {
        $stripe = new \Stripe\StripeClient( array(
            'api_key'        => $cfg['secret'],
            'stripe_version' => '2025-02-24.acacia', // Misma versión que SWPM (parámetro `coupon` de primer nivel).
        ) );

        $created = null;
        for ( $try = 0; $try < 3 && ! $created; $try++ ) {
            try {
                $created = $stripe->promotionCodes->create( array(
                    'coupon'          => $cfg['coupon'],
                    'code'            => mgmit_offer_random_code(),
                    'max_redemptions' => 1,
                    'expires_at'      => $expires,
                    'metadata'        => array( 'wc_order_id' => (string) $order_id ),
                ) );
            } catch ( \Stripe\Exception\InvalidRequestException $e ) {
                // Código duplicado: reintenta con otro; cualquier otro error se propaga.
                if ( false === stripos( $e->getMessage(), 'already exists' ) ) {
                    throw $e;
                }
            }
        }
        if ( ! $created ) {
            throw new RuntimeException( 'No se pudo generar un código único tras 3 intentos.' );
        }

        $order->update_meta_data( MGMIT_OFFER_META_CODE, $created->code );
        $order->update_meta_data( MGMIT_OFFER_META_EXPIRES, $expires );
        $order->add_order_note( sprintf( 'Oferta: código promocional Stripe %s (modo %s, caduca %s).', $created->code, $cfg['mode'], wp_date( 'd/m/Y', $expires ) ) );
        $order->save();
    } catch ( \Throwable $e ) {
        $order->add_order_note( 'Oferta: ERROR al crear el código promocional en Stripe: ' . $e->getMessage() );
        wc_get_logger()->error( 'offer-promo-code pedido #' . $order_id . ': ' . $e->getMessage(), array( 'source' => 'mgmit-offer-promo' ) );
    }
    delete_transient( $lock );
}

/** Bloque con el código en el email "pedido completado" del cliente. */
add_action( 'woocommerce_email_order_meta', 'mgmit_offer_email_block', 20, 4 );
function mgmit_offer_email_block( $order, $sent_to_admin = false, $plain_text = false, $email = null ): void {
    if ( $sent_to_admin || ! $order instanceof WC_Order || ! $email || 'customer_completed_order' !== $email->id ) {
        return;
    }
    $code    = $order->get_meta( MGMIT_OFFER_META_CODE );
    $expires = (int) $order->get_meta( MGMIT_OFFER_META_EXPIRES );
    if ( ! $code ) {
        return;
    }
    $date = wp_date( 'd.m.Y', $expires );

    if ( $plain_text ) {
        echo "\nIhr persönlicher Aktionscode: " . $code . "\nGültig bis: " . $date . "\n";
        echo "Geben Sie den Code beim Abschluss Ihrer Mitgliedschaft im Feld \"Aktionscode\" ein. Der Code ist nur einmal einlösbar.\n";
        return;
    }
    ?>
    <div style="margin:0 0 30px;padding:20px;border:2px dashed #999;text-align:center;">
        <p style="margin:0 0 8px;"><strong>Ihr persönlicher Aktionscode</strong></p>
        <p style="margin:0 0 8px;font-size:24px;letter-spacing:2px;"><strong><?php echo esc_html( $code ); ?></strong></p>
        <p style="margin:0;">Gültig bis <?php echo esc_html( $date ); ?>. Geben Sie den Code beim Abschluss Ihrer Mitgliedschaft im Feld „Aktionscode“ ein. Der Code ist nur einmal einlösbar.</p>
    </div>
    <?php
}

/** Código visible en la ficha del pedido (admin). */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'mgmit_offer_admin_display' );
function mgmit_offer_admin_display( $order ): void {
    $code = $order instanceof WC_Order ? $order->get_meta( MGMIT_OFFER_META_CODE ) : '';
    if ( $code ) {
        echo '<p><strong>Código oferta Stripe:</strong> ' . esc_html( $code ) . '</p>';
    }
}
