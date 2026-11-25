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

// Una factura independiente por línea de pedido (pedidos nuevos con 2+ productos).
require_once __DIR__ . '/invoice-split.php';

// Fase 2: cuadro "Rechnungen" en la ficha del pedido (descarga por factura).
require_once __DIR__ . '/invoice-metabox.php';

// Saludo formal alemán (courtesy_de / job_title_de de HubSpot) en los emails de cliente.
require_once __DIR__ . '/email-greeting.php';

// Fase 1 (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md): registro del documento "credit-note".
require_once __DIR__ . '/credit-note-document.php';

// Fase 2: endpoint firmado que sirve el PDF de la nota de abono on-demand.
require_once __DIR__ . '/credit-note-endpoint.php';

// Fase 3: notas de abono por factura en pedidos facturados por línea.
require_once __DIR__ . '/credit-note-split.php';

// Fase 4: email al cliente (BCC admin) con la nota de abono al cancelar el pedido.
// El caso de reembolso no necesita código: usa el email nativo de WooCommerce
// (customer_refunded_order) + el ajuste "Attach to:" de Credit Note en wp-admin.
require_once __DIR__ . '/credit-note-mailer.php';

// Autocompletar pedidos pagados con tarjeta Stripe (excluye membresías; interruptor en config.php).
require_once __DIR__ . '/autocomplete-card.php';

// Oferta: código promocional único de Stripe al comprar el producto de oferta.
require_once __DIR__ . '/offer-promo-code.php';
