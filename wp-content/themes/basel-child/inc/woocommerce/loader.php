<?php
/**
 * WooCommerce Integrations Loader
 *
 * Punto de entrada para todas las integraciones y filtros WooCommerce
 * específicos de MeGeMIT. Añadir nuevos módulos aquí sin tocar functions.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/invoice-endpoint.php';
require_once __DIR__ . '/webhook-filters.php';

// Fase 1 (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md): registro del documento "credit-note".
require_once __DIR__ . '/credit-note-document.php';

// Fase 2: endpoint firmado que sirve el PDF de la nota de abono on-demand.
require_once __DIR__ . '/credit-note-endpoint.php';

// Fase 4: email al cliente (BCC admin) con la nota de abono al cancelar el pedido.
// El caso de reembolso no necesita código: usa el email nativo de WooCommerce
// (customer_refunded_order) + el ajuste "Attach to:" de Credit Note en wp-admin.
require_once __DIR__ . '/credit-note-mailer.php';
