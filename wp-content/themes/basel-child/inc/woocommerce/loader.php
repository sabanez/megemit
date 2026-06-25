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
