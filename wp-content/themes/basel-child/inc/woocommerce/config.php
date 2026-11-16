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
 * Clave secreta para firmar (HMAC-SHA256) la URL del PDF de factura servida
 * por invoice-endpoint.php. Independiente de sesión/login.
 */
if ( ! defined( 'INVOICE_SECRET' ) ) {
    define( 'INVOICE_SECRET', '6ceb0f7850444f9ee5a77441656420e0e927cec29da0a8cfe34ec432dbfc0cd8' );
}
