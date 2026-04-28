<?php
/**
 * HubSpot Sync Module Loader
 *
 * Carga el script de captura del formulario y el handler de sincronización
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', 'mgmit_load_hubspot_form_capture');
function mgmit_load_hubspot_form_capture() {
    // Solo cargar en la página de registrierungsdetails (ID: 21568)
    if (!is_page(21568)) {
        return;
    }

    wp_enqueue_script(
        'mgmit-hubspot-form-capture',
        get_stylesheet_directory_uri() . '/inc/hubspot-sync/form-capture.js',
        [],
        filemtime(get_stylesheet_directory() . '/inc/hubspot-sync/form-capture.js'),
        true // Cargar en footer
    );

    write_log('[MGMIT_HS] Script de captura de formulario cargado en página 21568');
}

// Cargar el handler del endpoint REST
require_once get_stylesheet_directory() . '/inc/hubspot-sync/handler.php';
