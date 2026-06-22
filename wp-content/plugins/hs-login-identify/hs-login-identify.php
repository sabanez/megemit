<?php
/**
 * Plugin Name: HubSpot Login Identify
 * Description: Asocia la sesión de HubSpot con el contacto tras el login mediante la Forms Submission API. Sin API key.
 * Version:     3.5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HSLI_OPTION', 'hsli_settings' );

// ─── Valores por defecto ─────────────────────────────────────────────────────

function hsli_defaults() {
    return array(
        'portal_id'    => '',
        'form_guid'    => '',
        'integrations' => array( 'swpm' ),
    );
}

function hsli_settings() {
    return wp_parse_args( (array) get_option( HSLI_OPTION, array() ), hsli_defaults() );
}

// ─── Registro de hooks de login ──────────────────────────────────────────────
//
// SWPM dispara 'swpm_after_login_authentication' dentro de su propio hook init.
// Si registramos el listener también en init (priority 10) podemos llegar tarde.
// Solución: registrar el hook SWPM directamente aquí, en tiempo de carga del plugin,
// garantizando que está registrado antes de que cualquier init se ejecute.

$hsli_settings     = hsli_settings();
$hsli_integrations = isset( $hsli_settings['integrations'] ) ? (array) $hsli_settings['integrations'] : array();

if ( in_array( 'swpm', $hsli_integrations, true ) ) {
    add_action( 'swpm_after_login_authentication', 'hsli_swpm_catch_login' );
}

// Ultimate Membership Pro también dispara su hook en init; se registra aquí por la misma razón.
if ( in_array( 'ump', $hsli_integrations, true ) ) {
    add_action( 'ihc_login_success', 'hsli_ump_catch_login' );
}

// wp_login se dispara mucho después de init, sin riesgo de condición de carrera.
add_action( 'init', 'hsli_register_wp_login_hooks', 1 );

function hsli_register_wp_login_hooks() {
    $settings     = hsli_settings();
    $integrations = isset( $settings['integrations'] ) ? (array) $settings['integrations'] : array();

    if ( in_array( 'wp', $integrations, true ) || in_array( 'woocommerce', $integrations, true ) ) {
        add_action( 'wp_login', 'hsli_wp_catch_login', 10, 2 );
    }
}

// ─── Handlers de login ───────────────────────────────────────────────────────

function hsli_swpm_catch_login( $username ) {
    global $wpdb;
    $email = $wpdb->get_var( $wpdb->prepare(
        'SELECT email FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE user_name = %s LIMIT 1',
        $username
    ) );
    if ( ! empty( $email ) && is_email( $email ) ) {
        hsli_submit_to_hubspot( sanitize_email( $email ) );
    }
}

function hsli_ump_catch_login( $user_id ) {
    $user = get_userdata( (int) $user_id );
    if ( $user && ! empty( $user->user_email ) && is_email( $user->user_email ) ) {
        hsli_submit_to_hubspot( sanitize_email( $user->user_email ) );
    }
}

function hsli_wp_catch_login( $user_login, $user ) {
    if ( is_object( $user ) && ! empty( $user->user_email ) && is_email( $user->user_email ) ) {
        hsli_submit_to_hubspot( sanitize_email( $user->user_email ) );
    }
}

// ─── Envío a la Forms Submission API ─────────────────────────────────────────

function hsli_submit_to_hubspot( $email ) {
    $settings  = hsli_settings();
    $portal_id = trim( $settings['portal_id'] );
    $form_guid = trim( $settings['form_guid'] );

    if ( empty( $portal_id ) || empty( $form_guid ) ) {
        return;
    }

    $url = 'https://api.hsforms.com/submissions/v3/integration/submit/'
        . rawurlencode( $portal_id ) . '/' . rawurlencode( $form_guid );

    $context = array();

    if ( ! empty( $_COOKIE['hubspotutk'] ) ) {
        $context['hutk'] = sanitize_text_field( wp_unslash( $_COOKIE['hubspotutk'] ) );
    }

    $page_uri = home_url( '/' );
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $page_uri = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    }
    $context['pageUri']  = $page_uri;
    $context['pageName'] = 'Login (' . wp_parse_url( home_url(), PHP_URL_HOST ) . ')';

    $payload = array(
        'fields'  => array(
            array(
                'objectTypeId' => '0-1',
                'name'         => 'email',
                'value'        => $email,
            ),
        ),
        'context' => $context,
    );

    $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

    $response = wp_remote_post( $url, array(
        'method'   => 'POST',
        'timeout'  => $debug ? 10 : 5,
        'blocking' => $debug,
        'headers'  => array( 'Content-Type' => 'application/json' ),
        'body'     => wp_json_encode( $payload ),
    ) );

    if ( $debug ) {
        if ( is_wp_error( $response ) ) {
            error_log( 'HSLI error: ' . $response->get_error_message() );
        } else {
            error_log( 'HSLI HTTP ' . wp_remote_retrieve_response_code( $response ) . ' → ' . wp_remote_retrieve_body( $response ) );
        }
    }
}

// ─── Test de conexión (AJAX) ─────────────────────────────────────────────────

add_action( 'wp_ajax_hsli_test_connection',  'hsli_ajax_test_connection' );
add_action( 'wp_ajax_hsli_test_login',       'hsli_ajax_test_login' );

function hsli_ajax_test_connection() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( '', '', array( 'response' => 403 ) );
    }
    check_ajax_referer( 'hsli_test_nonce', 'nonce' );

    $settings  = hsli_settings();
    $portal_id = trim( $settings['portal_id'] );
    $form_guid = trim( $settings['form_guid'] );

    if ( empty( $portal_id ) || empty( $form_guid ) ) {
        wp_send_json_error( array( 'message' => 'Portal ID o Form GUID no configurados.' ) );
    }

    // Dirección IP del servidor para diagnóstico
    $server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'desconocida';

    $url = 'https://api.hsforms.com/submissions/v3/integration/submit/'
        . rawurlencode( $portal_id ) . '/' . rawurlencode( $form_guid );

    $payload = array(
        'fields'  => array(
            array(
                'objectTypeId' => '0-1',
                'name'         => 'email',
                'value'        => 'hsli-test-connection@test.invalid',
            ),
        ),
        'context' => array(
            'pageUri'  => home_url( '/' ),
            'pageName' => 'HSLI Test Connection',
        ),
    );

    $response = wp_remote_post( $url, array(
        'method'   => 'POST',
        'timeout'  => 15,
        'blocking' => true,
        'headers'  => array( 'Content-Type' => 'application/json' ),
        'body'     => wp_json_encode( $payload ),
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message'   => 'wp_remote_post falló: ' . $response->get_error_message(),
            'server_ip' => $server_ip,
            'url'       => $url,
        ) );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if ( $code >= 200 && $code < 300 ) {
        wp_send_json_success( array(
            'message'   => 'Conexión correcta. HubSpot respondió HTTP ' . $code . '.',
            'http_code' => $code,
            'server_ip' => $server_ip,
            'url'       => $url,
        ) );
    } else {
        wp_send_json_error( array(
            'message'   => 'HubSpot respondió HTTP ' . $code . '.',
            'http_code' => $code,
            'body'      => $body,
            'server_ip' => $server_ip,
            'url'       => $url,
        ) );
    }
}

function hsli_ajax_test_login() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( '', '', array( 'response' => 403 ) );
    }
    check_ajax_referer( 'hsli_test_nonce', 'nonce' );

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( empty( $email ) || ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Email no válido.' ) );
    }

    $settings  = hsli_settings();
    $portal_id = trim( $settings['portal_id'] );
    $form_guid = trim( $settings['form_guid'] );

    if ( empty( $portal_id ) || empty( $form_guid ) ) {
        wp_send_json_error( array( 'message' => 'Portal ID o Form GUID no configurados.' ) );
    }

    $url = 'https://api.hsforms.com/submissions/v3/integration/submit/'
        . rawurlencode( $portal_id ) . '/' . rawurlencode( $form_guid );

    $context = array(
        'pageUri'  => home_url( '/' ),
        'pageName' => 'HSLI Test Login (' . wp_parse_url( home_url(), PHP_URL_HOST ) . ')',
    );

    if ( ! empty( $_COOKIE['hubspotutk'] ) ) {
        $context['hutk'] = sanitize_text_field( wp_unslash( $_COOKIE['hubspotutk'] ) );
    }

    $payload = array(
        'fields'  => array(
            array(
                'objectTypeId' => '0-1',
                'name'         => 'email',
                'value'        => $email,
            ),
        ),
        'context' => $context,
    );

    $response = wp_remote_post( $url, array(
        'method'   => 'POST',
        'timeout'  => 15,
        'blocking' => true,
        'headers'  => array( 'Content-Type' => 'application/json' ),
        'body'     => wp_json_encode( $payload ),
    ) );

    $server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'desconocida';

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message'   => 'Error: ' . $response->get_error_message(),
            'server_ip' => $server_ip,
        ) );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if ( $code >= 200 && $code < 300 ) {
        $hutk_used = ! empty( $context['hutk'] ) ? 'sí (' . substr( $context['hutk'], 0, 8 ) . '…)' : 'no (sin cookie hubspotutk)';
        wp_send_json_success( array(
            'message'   => 'Envío correcto. HubSpot respondió HTTP ' . $code . '. Revisa el CRM en 1-2 minutos.',
            'http_code' => $code,
            'email'     => $email,
            'hutk'      => $hutk_used,
            'server_ip' => $server_ip,
        ) );
    } else {
        wp_send_json_error( array(
            'message'   => 'HubSpot respondió HTTP ' . $code . '.',
            'http_code' => $code,
            'body'      => $body,
            'server_ip' => $server_ip,
        ) );
    }
}

// ─── Página de ajustes ───────────────────────────────────────────────────────

add_action( 'admin_menu', 'hsli_admin_menu' );

function hsli_admin_menu() {
    add_options_page(
        __( 'HubSpot Login Identify', 'hsli' ),
        __( 'HubSpot Login ID', 'hsli' ),
        'manage_options',
        'hsli-settings',
        'hsli_settings_page'
    );
}

add_action( 'admin_init', 'hsli_admin_init' );

function hsli_admin_init() {
    register_setting( 'hsli_group', HSLI_OPTION, array(
        'sanitize_callback' => 'hsli_sanitize_settings',
    ) );

    add_settings_section(
        'hsli_api',
        __( 'Conexión con HubSpot', 'hsli' ),
        '__return_false',
        'hsli-settings'
    );

    add_settings_field(
        'hsli_portal_id',
        __( 'Portal ID', 'hsli' ),
        'hsli_field_portal_id',
        'hsli-settings',
        'hsli_api'
    );

    add_settings_field(
        'hsli_form_guid',
        __( 'Form GUID', 'hsli' ),
        'hsli_field_form_guid',
        'hsli-settings',
        'hsli_api'
    );

    add_settings_section(
        'hsli_integrations',
        __( 'Formularios de login activos', 'hsli' ),
        'hsli_integrations_description',
        'hsli-settings'
    );

    add_settings_field(
        'hsli_integrations',
        __( 'Integraciones', 'hsli' ),
        'hsli_field_integrations',
        'hsli-settings',
        'hsli_integrations'
    );
}

function hsli_sanitize_settings( $input ) {
    $output = hsli_defaults();

    if ( isset( $input['portal_id'] ) ) {
        $output['portal_id'] = preg_replace( '/[^0-9]/', '', $input['portal_id'] );
    }

    if ( isset( $input['form_guid'] ) ) {
        $guid = sanitize_text_field( $input['form_guid'] );
        // Validar formato UUID
        if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $guid ) ) {
            $output['form_guid'] = strtolower( $guid );
        } else {
            add_settings_error( HSLI_OPTION, 'invalid_guid', __( 'El Form GUID no tiene un formato UUID válido.', 'hsli' ) );
            $output['form_guid'] = '';
        }
    }

    $allowed = array_keys( hsli_available_integrations() );
    if ( isset( $input['integrations'] ) && is_array( $input['integrations'] ) ) {
        $output['integrations'] = array_values( array_intersect( $input['integrations'], $allowed ) );
    } else {
        $output['integrations'] = array();
    }

    return $output;
}

// ─── Campos del formulario de ajustes ────────────────────────────────────────

function hsli_field_portal_id() {
    $settings = hsli_settings();
    ?>
    <input type="text" name="<?php echo esc_attr( HSLI_OPTION ); ?>[portal_id]"
           value="<?php echo esc_attr( $settings['portal_id'] ); ?>"
           class="regular-text" placeholder="ej. 144893874">
    <p class="description"><?php esc_html_e( 'Número de tu cuenta HubSpot (Hub ID). Lo encuentras en Configuración → Cuenta → ID de cuenta.', 'hsli' ); ?></p>
    <?php
}

function hsli_field_form_guid() {
    $settings = hsli_settings();
    ?>
    <input type="text" name="<?php echo esc_attr( HSLI_OPTION ); ?>[form_guid]"
           value="<?php echo esc_attr( $settings['form_guid'] ); ?>"
           class="regular-text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
    <p class="description"><?php esc_html_e( 'UUID del formulario HubSpot usado para identificación. Créalo en Marketing → Formularios con un único campo email.', 'hsli' ); ?></p>
    <?php
}

function hsli_integrations_description() {
    echo '<p>' . esc_html__( 'Selecciona qué sistemas de login deben enviar la identificación a HubSpot al iniciar sesión.', 'hsli' ) . '</p>';
}

function hsli_available_integrations() {
    return array(
        'swpm'        => 'SWPM (Simple WP Membership)',
        'ump'         => 'Ultimate Membership Pro',
        'wp'          => 'WordPress nativo',
        'woocommerce' => 'WooCommerce',
    );
}

function hsli_field_integrations() {
    $settings     = hsli_settings();
    $active       = isset( $settings['integrations'] ) ? (array) $settings['integrations'] : array();
    $integrations = hsli_available_integrations();

    foreach ( $integrations as $key => $label ) {
        $checked = in_array( $key, $active, true );
        ?>
        <label style="display:block;margin-bottom:6px;">
            <input type="checkbox"
                   name="<?php echo esc_attr( HSLI_OPTION ); ?>[integrations][]"
                   value="<?php echo esc_attr( $key ); ?>"
                   <?php checked( $checked ); ?>>
            <?php echo esc_html( $label ); ?>
        </label>
        <?php
    }
}

// ─── Render de la página ─────────────────────────────────────────────────────

function hsli_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings  = hsli_settings();
    $portal_id = $settings['portal_id'];
    $form_guid = $settings['form_guid'];
    $ready     = ! empty( $portal_id ) && ! empty( $form_guid );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'HubSpot Login Identify', 'hsli' ); ?></h1>

        <?php if ( $ready ) : ?>
            <div class="notice notice-success inline">
                <p>&#10003; <?php esc_html_e( 'Configurado correctamente. El plugin enviará la identificación tras cada login activo.', 'hsli' ); ?></p>
            </div>
        <?php else : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e( 'Completa el Portal ID y el Form GUID para activar el plugin.', 'hsli' ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'hsli_group' );
            do_settings_sections( 'hsli-settings' );
            submit_button( __( 'Guardar ajustes', 'hsli' ) );
            ?>
        </form>

        <?php if ( $ready ) : ?>
        <hr>
        <h2><?php esc_html_e( 'Diagnóstico', 'hsli' ); ?></h2>
        <p><?php esc_html_e( 'Prueba si el servidor puede conectar con la API de HubSpot. No crea contactos reales.', 'hsli' ); ?></p>
        <?php $nonce_val = wp_create_nonce( 'hsli_test_nonce' ); ?>
        <?php $ajax_url  = admin_url( 'admin-ajax.php' ); ?>

        <p><strong><?php esc_html_e( 'Test 1 — Conexión con HubSpot', 'hsli' ); ?></strong></p>
        <p class="description"><?php esc_html_e( 'Verifica que el servidor puede alcanzar la API. Usa un email de prueba inválido, no crea contactos reales.', 'hsli' ); ?></p>
        <button id="hsli-test-btn" class="button button-secondary">
            <?php esc_html_e( 'Probar conexión', 'hsli' ); ?>
        </button>
        <span id="hsli-test-spinner" class="spinner" style="float:none;margin-top:0;vertical-align:middle;visibility:hidden;"></span>
        <div id="hsli-test-result" style="margin-top:12px;"></div>

        <br>
        <p><strong><?php esc_html_e( 'Test 2 — Envío real de identificación', 'hsli' ); ?></strong></p>
        <p class="description"><?php esc_html_e( 'Simula exactamente lo que ocurre tras un login: envía el email a HubSpot incluyendo la cookie de tracking si existe. Usa el email de un contacto real que ya esté en HubSpot.', 'hsli' ); ?></p>
        <input type="email" id="hsli-login-email" class="regular-text" placeholder="contacto@ejemplo.com">
        &nbsp;
        <button id="hsli-test-login-btn" class="button button-secondary">
            <?php esc_html_e( 'Simular login', 'hsli' ); ?>
        </button>
        <span id="hsli-login-spinner" class="spinner" style="float:none;margin-top:0;vertical-align:middle;visibility:hidden;"></span>
        <div id="hsli-login-result" style="margin-top:12px;"></div>

        <script>
        (function () {
            var nonce   = '<?php echo esc_js( $nonce_val ); ?>';
            var ajaxUrl = '<?php echo esc_js( $ajax_url ); ?>';

            function renderResult( containerId, json ) {
                var ok    = json.success;
                var data  = json.data || {};
                var color = ok ? '#00a32a' : '#d63638';
                var icon  = ok ? '✔' : '✘';
                var html  = '<div style="border-left:4px solid ' + color + ';padding:8px 12px;background:#f9f9f9;">'
                          + '<strong style="color:' + color + ';">' + icon + ' ' + ( data.message || '' ) + '</strong>';
                if ( data.http_code ) { html += '<br>HTTP: '              + data.http_code; }
                if ( data.email     ) { html += '<br>Email enviado: '     + data.email;     }
                if ( data.hutk      ) { html += '<br>Cookie hutk: '       + data.hutk;      }
                if ( data.server_ip ) { html += '<br>IP del servidor: '   + data.server_ip; }
                if ( data.url       ) { html += '<br>URL: '               + data.url;       }
                if ( data.body      ) { html += '<br>Respuesta: <code>'   + data.body + '</code>'; }
                html += '</div>';
                document.getElementById( containerId ).innerHTML = html;
            }

            function doRequest( action, extraData, btnId, spinnerId, resultId ) {
                var btn     = document.getElementById( btnId );
                var spinner = document.getElementById( spinnerId );
                btn.disabled             = true;
                spinner.style.visibility = 'visible';
                document.getElementById( resultId ).innerHTML = '';

                var fd = new FormData();
                fd.append( 'action', action );
                fd.append( 'nonce',  nonce );
                for ( var k in extraData ) { fd.append( k, extraData[k] ); }

                fetch( ajaxUrl, { method: 'POST', body: fd } )
                    .then( function (r) { return r.json(); } )
                    .then( function (json) { renderResult( resultId, json ); } )
                    .catch( function (err) {
                        document.getElementById( resultId ).innerHTML =
                            '<div style="border-left:4px solid #d63638;padding:8px 12px;background:#f9f9f9;">'
                            + '<strong style="color:#d63638;">✘ Error de red: ' + err.message + '</strong></div>';
                    } )
                    .finally( function () {
                        btn.disabled             = false;
                        spinner.style.visibility = 'hidden';
                    } );
            }

            document.getElementById('hsli-test-btn').addEventListener('click', function (e) {
                e.preventDefault();
                doRequest( 'hsli_test_connection', {}, 'hsli-test-btn', 'hsli-test-spinner', 'hsli-test-result' );
            });

            document.getElementById('hsli-test-login-btn').addEventListener('click', function (e) {
                e.preventDefault();
                var email = document.getElementById('hsli-login-email').value.trim();
                if ( ! email ) { alert('Introduce un email.'); return; }
                doRequest( 'hsli_test_login', { email: email }, 'hsli-test-login-btn', 'hsli-login-spinner', 'hsli-login-result' );
            });
        })();
        </script>
        <?php endif; ?>

    </div>
    <?php
}
