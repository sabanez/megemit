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
