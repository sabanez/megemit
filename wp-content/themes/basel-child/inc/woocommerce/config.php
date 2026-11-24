<?php
/**
 * Configuración de las integraciones WooCommerce de inc/woocommerce/.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Segundos durante los que se bloquea la entrega del webhook order.updated
 * tras la creación de un pedido (evita duplicado order.created + order.updated
 * en HubSpot). Ver webhook-filters.php.
 */
if ( ! defined( 'WEBHOOK_NEW_ORDER_TTL' ) ) {
    define( 'WEBHOOK_NEW_ORDER_TTL', 120 );
}

/**
 * Facturas por línea de pedido (ver invoice-split.php): solo se aplican a los
 * pedidos creados a partir de esta fecha (hora del sitio).
 */
if ( ! defined( 'MGMIT_INVOICE_SPLIT_SINCE' ) ) {
    define( 'MGMIT_INVOICE_SPLIT_SINCE', '2026-11-16 00:00:00' );
}

/** Meta del pedido donde se guarda la asignación línea -> número de factura. */
if ( ! defined( 'MGMIT_INVOICE_SPLIT_META' ) ) {
    define( 'MGMIT_INVOICE_SPLIT_META', '_mgmit_split_invoices' );
}

/**
 * Clave secreta para firmar (HMAC-SHA256) la URL del PDF de factura servida
 * por invoice-endpoint.php. Independiente de sesión/login.
 */
if ( ! defined( 'INVOICE_SECRET' ) ) {
    define( 'INVOICE_SECRET', '6ceb0f7850444f9ee5a77441656420e0e927cec29da0a8cfe34ec432dbfc0cd8' );
}

/**
 * Autocompletar pedidos pagados con tarjeta Stripe (ver autocomplete-card.php).
 * Apagado por defecto: poner a true para activarlo.
 */
if ( ! defined( 'MGMIT_AUTOCOMPLETE_CARD' ) ) {
    define( 'MGMIT_AUTOCOMPLETE_CARD', false );
}

/**
 * Oferta "producto WooCommerce => código promocional Stripe" (offer-promo-code.php).
 * Cada pedido completado con el producto genera un código de un solo uso sobre
 * un cupón de Stripe del 100 % (12 meses). Los IDs de cupón son distintos en
 * modo test y live: rellenar ambos. El modo se toma de SWPM (enable-sandbox-testing).
 */
if ( ! defined( 'MGMIT_OFFER_PRODUCT_ID' ) ) {
    define( 'MGMIT_OFFER_PRODUCT_ID', 23510 );
}
if ( ! defined( 'MGMIT_OFFER_STRIPE_COUPON_LIVE' ) ) {
    define( 'MGMIT_OFFER_STRIPE_COUPON_LIVE', 'gvD4JM9w' );
}
if ( ! defined( 'MGMIT_OFFER_STRIPE_COUPON_TEST' ) ) {
    define( 'MGMIT_OFFER_STRIPE_COUPON_TEST', '' ); // PENDIENTE: ID del cupón en Stripe test.
}
/** ID (post) del botón Stripe Subscription de SWPM: de él se toman las claves de Stripe. */
if ( ! defined( 'MGMIT_OFFER_SWPM_BUTTON_ID' ) ) {
    define( 'MGMIT_OFFER_SWPM_BUTTON_ID', 21962 );
}
/** Días de validez del código desde la compra. */
if ( ! defined( 'MGMIT_OFFER_CODE_DAYS' ) ) {
    define( 'MGMIT_OFFER_CODE_DAYS', 60 );
}
