<?php
/**
 * Clase para la interfaz de administración (Fase 4 - Visual Mapper UI)
 */

if (!defined('ABSPATH')) {
    exit;
}

class MGMIT_Admin_UI {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_mgmit_save_mapping', [$this, 'ajax_save_mapping']);
        add_action('wp_ajax_mgmit_delete_mapping', [$this, 'ajax_delete_mapping']);
        // Handler legacy para retrocompatibilidad (Fase 3)
        add_action('wp_ajax_mgmit_save_hubspot_config', [$this, 'ajax_save_config']);
        add_action('wp_ajax_mgmit_save_credentials',    [$this, 'ajax_save_credentials']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'MeGeMIT HubSpot Bridge',
            'HubSpot Bridge',
            'manage_options',
            'mgmit-hubspot-bridge',
            [$this, 'render_admin_page'],
            'dashicons-share-alt',
            80
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_mgmit-hubspot-bridge' !== $hook) {
            return;
        }
        wp_enqueue_script(
            'mgmit-admin-mapper',
            MGMIT_HS_BRIDGE_URL . 'assets/js/admin-mapper.js',
            ['jquery'],
            MGMIT_HS_BRIDGE_VERSION,
            true
        );
        wp_localize_script('mgmit-admin-mapper', 'MGMIT_Admin', [
            'ajaxurl'       => admin_url('admin-ajax.php'),
            'nonce_save'    => wp_create_nonce('mgmit-save-mapping'),
            'nonce_delete'  => wp_create_nonce('mgmit-delete-mapping'),
            'list_url'      => admin_url('admin.php?page=mgmit-hubspot-bridge'),
            'strings'       => [
                'confirm_delete' => '¿Seguro que quieres eliminar este mapeo? Esta acción no se puede deshacer.',
                'saving'         => 'Guardando...',
                'deleting'       => 'Eliminando...',
                'error'          => 'Error inesperado. Por favor, inténtalo de nuevo.',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper: obtener config con inicialización de defaults si la opción no existe
    // -------------------------------------------------------------------------

    private function get_config() {
        $config = get_option(MGMIT_HS_BRIDGE_OPTION);
        if ($config === false || !is_array($config)) {
            // La opción nunca fue guardada - inicializar defaults desde el bridge
            MGMIT_HubSpot_Bridge::get_instance()->activate_plugin();
            $config = get_option(MGMIT_HS_BRIDGE_OPTION, []);
        }
        return $config;
    }

    // -------------------------------------------------------------------------
    // Routing principal
    // -------------------------------------------------------------------------

    public function render_admin_page() {
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'list';

        if ($view === 'edit') {
            $this->render_edit_view();
        } else {
            $this->render_list_view();
        }
    }

    // -------------------------------------------------------------------------
    // Vista: Lista de mapeos
    // -------------------------------------------------------------------------

    private function render_list_view() {
        $config  = $this->get_config();
        $add_url = admin_url('admin.php?page=mgmit-hubspot-bridge&view=edit');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-share-alt" style="font-size:28px;width:28px;height:28px;vertical-align:middle;margin-right:6px;"></span>
                MeGeMIT: HubSpot Bridge
            </h1>
            <a href="<?php echo esc_url($add_url); ?>" class="page-title-action">&#43; Añadir Mapeo</a>
            <hr class="wp-header-end">

            <?php if (isset($_GET['saved'])): ?>
            <div class="notice notice-success is-dismissible"><p><strong>Mapeo guardado correctamente.</strong></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible"><p><strong>Mapeo eliminado correctamente.</strong></p></div>
            <?php endif; ?>

            <?php if (empty($config)): ?>
                <div class="notice notice-info" style="margin-top:20px;">
                    <p>No hay mapeos configurados todavía. <a href="<?php echo esc_url($add_url); ?>"><strong>Crea el primero ahora</strong></a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary" style="width:22%">Nombre del Mapeo</th>
                            <th scope="col" class="manage-column" style="width:28%">Selector del Formulario</th>
                            <th scope="col" class="manage-column" style="width:28%">Form GUID HubSpot</th>
                            <th scope="col" class="manage-column" style="width:10%" title="Número de campos mapeados">Campos</th>
                            <th scope="col" class="manage-column" style="width:12%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <?php foreach ($config as $index => $mapping):
                            $id          = isset($mapping['id']) ? $mapping['id'] : strval($index);
                            $name        = isset($mapping['name']) && $mapping['name'] !== '' ? $mapping['name'] : '(Sin nombre)';
                            $form_id     = isset($mapping['formId']) ? $mapping['formId'] : '';
                            $hs_name     = isset($mapping['formGuid']) ? $mapping['formGuid'] : (isset($mapping['hubspotFormName']) ? $mapping['hubspotFormName'] : '');
                            $field_count = isset($mapping['mapping']) && is_array($mapping['mapping']) ? count($mapping['mapping']) : 0;
                            $edit_url    = admin_url('admin.php?page=mgmit-hubspot-bridge&view=edit&mapping_id=' . urlencode($id));
                        ?>
                        <tr>
                            <td class="column-primary" data-colname="Nombre">
                                <strong>
                                    <a href="<?php echo esc_url($edit_url); ?>" class="row-title">
                                        <?php echo esc_html($name); ?>
                                    </a>
                                </strong>
                            </td>
                            <td data-colname="Selector">
                                <code style="font-size:12px;word-break:break-all;"><?php echo esc_html($form_id); ?></code>
                            </td>
                            <td data-colname="HubSpot">
                                <code style="font-size:12px;"><?php echo esc_html($hs_name); ?></code>
                            </td>
                            <td data-colname="Campos">
                                <span style="background:#e5f5fa;color:#00607a;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;">
                                    <?php echo intval($field_count); ?>
                                </span>
                            </td>
                            <td data-colname="Acciones">
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Editar</a>
                                <button
                                    type="button"
                                    class="button button-small mgmit-delete-mapping"
                                    data-id="<?php echo esc_attr($id); ?>"
                                    style="color:#b32d2e;border-color:#b32d2e;margin-left:4px;">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="col">Nombre del Mapeo</th>
                            <th scope="col">Selector del Formulario</th>
                            <th scope="col">Form GUID HubSpot</th>
                            <th scope="col">Campos</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

            <div style="margin-top:24px;background:#e5f5fa;padding:12px 16px;border-left:4px solid #00a0d2;font-size:13px;">
                <strong>Nota técnica:</strong> Los mapeos se transfieren automáticamente al frontend mediante la variable
                <code>HS_CONFIG</code>, manteniendo retrocompatibilidad total con <code>hubspot_map.js</code>.
            </div>

            <?php $this->render_credentials_section(); ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Vista: Editor de mapeo (añadir / editar)
    // -------------------------------------------------------------------------

    private function render_edit_view() {
        $config     = $this->get_config();
        $mapping_id = isset($_GET['mapping_id']) ? sanitize_text_field($_GET['mapping_id']) : null;
        $mapping    = null;
        $is_new     = true;

        if ($mapping_id !== null) {
            // Buscar por campo 'id' (entradas nuevas con UUID)
            foreach ($config as $m) {
                if (isset($m['id']) && $m['id'] === $mapping_id) {
                    $mapping = $m;
                    $is_new  = false;
                    break;
                }
            }
            // Fallback: buscar por índice numérico (entradas legacy sin 'id')
            if ($mapping === null && is_numeric($mapping_id)) {
                $idx = intval($mapping_id);
                if (isset($config[$idx])) {
                    $mapping = $config[$idx];
                    $is_new  = false;
                }
            }
        }

        $name       = $is_new ? '' : (isset($mapping['name']) ? $mapping['name'] : '');
        $form_sel   = $is_new ? '' : (isset($mapping['formId']) ? $mapping['formId'] : '');
        $hs_name    = $is_new ? '' : (isset($mapping['formGuid']) ? $mapping['formGuid'] : (isset($mapping['hubspotFormName']) ? $mapping['hubspotFormName'] : ''));
        $fields     = $is_new ? [] : (isset($mapping['mapping']) && is_array($mapping['mapping']) ? $mapping['mapping'] : []);
        $current_id = $is_new ? '' : (isset($mapping['id']) ? $mapping['id'] : $mapping_id);

        $list_url   = admin_url('admin.php?page=mgmit-hubspot-bridge');
        $page_title = $is_new ? 'Añadir Nuevo Mapeo' : 'Editar Mapeo';
        $btn_label  = $is_new ? 'Crear Mapeo' : 'Guardar Cambios';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-share-alt" style="font-size:28px;width:28px;height:28px;vertical-align:middle;margin-right:6px;"></span>
                <?php echo esc_html($page_title); ?>
            </h1>
            <a href="<?php echo esc_url($list_url); ?>" class="page-title-action">&#8592; Volver a la lista</a>
            <hr class="wp-header-end">

            <div id="mgmit-save-notice" style="display:none;margin-top:12px;"></div>

            <form id="mgmit-mapping-form" novalidate>
                <input type="hidden" id="mgmit-mapping-id" value="<?php echo esc_attr($current_id); ?>">

                <table class="form-table" role="presentation">
                    <tbody>

                        <tr>
                            <th scope="row">
                                <label for="mgmit-name">Nombre del Mapeo</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="mgmit-name"
                                    class="regular-text"
                                    value="<?php echo esc_attr($name); ?>"
                                    placeholder="Ej: Registro Profesional Nivel 13">
                                <p class="description">Nombre descriptivo para identificar este mapeo en el panel de administración.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="mgmit-form-selector">Selector del Formulario <span style="color:#b32d2e;">*</span></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="mgmit-form-selector"
                                    class="regular-text code"
                                    value="<?php echo esc_attr($form_sel); ?>"
                                    placeholder="Ej: #registro-profesional-13">
                                <p class="description">
                                    Selector CSS que identifica el formulario en la página.
                                    Acepta múltiples selectores separados por coma: <code>#form-a, #form-b</code>.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="mgmit-hs-name">Form GUID HubSpot <span style="color:#b32d2e;">*</span></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="mgmit-hs-name"
                                    class="regular-text code"
                                    value="<?php echo esc_attr($hs_name); ?>"
                                    placeholder="Ej: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                                <p class="description">
                                    GUID del formulario en HubSpot. Lo encuentras en: Marketing → Formularios → [formulario] → Acciones → Compartir.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Mapeo de Campos</th>
                            <td>
                                <p class="description" style="margin-bottom:10px;">
                                    Relaciona el atributo <code>name</code> de cada campo del formulario WordPress con su propiedad correspondiente en HubSpot.
                                </p>
                                <table class="widefat striped" id="mgmit-fields-table" style="max-width:640px;">
                                    <thead>
                                        <tr>
                                            <th style="width:46%;">
                                                Campo WordPress
                                                <small style="font-weight:400;display:block;color:#666;">(atributo <code>name</code> del input)</small>
                                            </th>
                                            <th style="width:46%;">
                                                Propiedad HubSpot
                                                <small style="font-weight:400;display:block;color:#666;">(nombre de la propiedad)</small>
                                            </th>
                                            <th style="width:8%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mgmit-fields-body">
                                        <?php if (!empty($fields)): ?>
                                            <?php foreach ($fields as $wp_field => $hs_prop): ?>
                                            <tr class="mgmit-field-row">
                                                <td><input type="text" class="mgmit-wp-field widefat code" value="<?php echo esc_attr($wp_field); ?>" placeholder="swpm-472"></td>
                                                <td><input type="text" class="mgmit-hs-prop widefat code" value="<?php echo esc_attr($hs_prop); ?>" placeholder="firstname"></td>
                                                <td style="text-align:center;">
                                                    <button type="button" class="button button-small mgmit-remove-row" title="Eliminar fila" style="color:#b32d2e;border-color:#b32d2e;">&#10005;</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="mgmit-field-row">
                                                <td><input type="text" class="mgmit-wp-field widefat code" value="" placeholder="swpm-472"></td>
                                                <td><input type="text" class="mgmit-hs-prop widefat code" value="" placeholder="firstname"></td>
                                                <td style="text-align:center;">
                                                    <button type="button" class="button button-small mgmit-remove-row" title="Eliminar fila" style="color:#b32d2e;border-color:#b32d2e;">&#10005;</button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" style="padding:10px 8px;">
                                                <button type="button" id="mgmit-add-row" class="button">&#43; Añadir Campo</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" id="mgmit-save-btn" class="button button-primary button-large">
                        <?php echo esc_html($btn_label); ?>
                    </button>
                    <a href="<?php echo esc_url($list_url); ?>" class="button button-large" style="margin-left:8px;">
                        Cancelar
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // AJAX: Guardar / actualizar un mapeo individual
    // -------------------------------------------------------------------------

    public function ajax_save_mapping() {
        check_ajax_referer('mgmit-save-mapping', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        $id           = isset($_POST['id'])              ? sanitize_text_field($_POST['id'])              : '';
        $name         = isset($_POST['name'])            ? sanitize_text_field($_POST['name'])            : '';
        $form_id      = isset($_POST['formId'])          ? sanitize_text_field($_POST['formId'])          : '';
        $form_guid  = isset($_POST['hubspotFormName']) ? sanitize_text_field($_POST['hubspotFormName']) : '';
        $fields_raw = isset($_POST['fields'])          ? (array) $_POST['fields']                       : [];

        if (empty($form_id) || empty($form_guid)) {
            wp_send_json_error('El selector del formulario y el Form GUID de HubSpot son obligatorios.');
        }

        $mapping = [];
        foreach ($fields_raw as $field) {
            $wp_field = isset($field['wp_field']) ? sanitize_text_field($field['wp_field']) : '';
            $hs_prop  = isset($field['hs_prop'])  ? sanitize_text_field($field['hs_prop'])  : '';
            if ($wp_field !== '' && $hs_prop !== '') {
                $mapping[$wp_field] = $hs_prop;
            }
        }

        $new_entry = array(
            'id'       => $id !== '' ? $id : $this->generate_id(),
            'name'     => $name,
            'formId'   => $form_id,
            'formGuid' => $form_guid,
            'mapping'  => $mapping,
        );

        $config = $this->get_config();
        $found  = false;

        // Intentar actualizar entrada existente por 'id'
        foreach ($config as &$m) {
            if (isset($m['id']) && $m['id'] === $new_entry['id']) {
                $m     = $new_entry;
                $found = true;
                break;
            }
        }
        unset($m);

        // Fallback: actualizar entrada legacy por índice numérico
        if (!$found && is_numeric($id)) {
            $idx = intval($id);
            if (isset($config[$idx])) {
                $config[$idx] = $new_entry;
                $found        = true;
            }
        }

        if (!$found) {
            $config[] = $new_entry;
        }

        update_option(MGMIT_HS_BRIDGE_OPTION, $config);

        wp_send_json_success([
            'redirect' => admin_url('admin.php?page=mgmit-hubspot-bridge&saved=1'),
        ]);
    }

    // -------------------------------------------------------------------------
    // AJAX: Eliminar un mapeo
    // -------------------------------------------------------------------------

    public function ajax_delete_mapping() {
        check_ajax_referer('mgmit-delete-mapping', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

        if ($id === '') {
            wp_send_json_error('ID no válido.');
        }

        $config     = $this->get_config();
        $new_config = [];

        foreach ($config as $index => $m) {
            $current_id = isset($m['id']) ? $m['id'] : strval($index);
            if ($current_id !== $id) {
                $new_config[] = $m;
            }
        }

        update_option(MGMIT_HS_BRIDGE_OPTION, $new_config);

        wp_send_json_success([
            'redirect' => admin_url('admin.php?page=mgmit-hubspot-bridge&deleted=1'),
        ]);
    }

    // -------------------------------------------------------------------------
    // AJAX legacy: Guardar config completa en JSON (Fase 3 - retrocompatibilidad)
    // -------------------------------------------------------------------------

    public function ajax_save_config() {
        check_ajax_referer('mgmit-save-config', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes para modificar esta configuración.');
        }

        $config_json  = isset($_POST['config']) ? stripslashes($_POST['config']) : '';
        $config_array = json_decode($config_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('El JSON enviado contiene errores de formato (Sintaxis inválida).');
        }

        update_option(MGMIT_HS_BRIDGE_OPTION, $config_array);
        wp_send_json_success('Configuración guardada y activa.');
    }

    // -------------------------------------------------------------------------
    // Sección: Credenciales API HubSpot
    // -------------------------------------------------------------------------

    private function render_credentials_section() {
        $creds         = get_option('mgmit_hubspot_credentials', array());
        $has_token     = !empty($creds['access_token']);
        $has_portal    = !empty($creds['portal_id']);
        $token_display = $has_token ? str_repeat('•', 32) : '';
        $portal_val    = $has_portal ? esc_attr($creds['portal_id']) : '';

        // Si las constantes vienen de wp-config.php, indicarlo y no mostrar el form.
        $from_config = defined('MGMIT_HS_ACCESS_TOKEN_SECRET');
        ?>
        <hr style="margin-top:32px;">
        <h2 style="margin-top:24px;">Credenciales API HubSpot</h2>

        <?php if ($from_config): ?>
            <div class="notice notice-info inline" style="margin:0 0 16px;">
                <p>Las credenciales están definidas en <code>wp-config.php</code> y tienen prioridad sobre los valores de este formulario.</p>
            </div>
        <?php endif; ?>

        <div id="mgmit-creds-notice" style="display:none;margin-bottom:12px;"></div>

        <form id="mgmit-credentials-form" novalidate>
            <?php wp_nonce_field('mgmit-save-credentials', 'mgmit_creds_nonce'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="mgmit-access-token">Access Token</label></th>
                        <td>
                            <?php if ($has_token): ?>
                                <span id="mgmit-token-mask" style="font-family:monospace;letter-spacing:2px;">
                                    <?php echo esc_html($token_display); ?>
                                </span>
                                <button type="button" id="mgmit-token-change" class="button button-small" style="margin-left:8px;">Cambiar</button>
                                <div id="mgmit-token-field" style="display:none;margin-top:6px;">
                                    <input type="password" id="mgmit-access-token" class="regular-text" autocomplete="new-password" placeholder="pat-eu1-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                                    <p class="description">Introduce el nuevo token para reemplazar el actual.</p>
                                </div>
                            <?php else: ?>
                                <input type="password" id="mgmit-access-token" class="regular-text" autocomplete="new-password" placeholder="pat-eu1-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                                <p class="description">Token de la App Privada de HubSpot. Se almacena cifrado y nunca se expone en el frontend.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mgmit-portal-id">Portal ID</label></th>
                        <td>
                            <input type="text" id="mgmit-portal-id" class="regular-text code" value="<?php echo $portal_val; ?>" placeholder="144893874">
                            <p class="description">ID numérico del portal HubSpot. Lo encuentras en Configuración → Cuenta → ID de cuenta.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" id="mgmit-save-creds-btn" class="button button-primary">Guardar Credenciales</button>
            </p>
        </form>

        <script>
        (function($){
            $('#mgmit-token-change').on('click', function(){
                $('#mgmit-token-mask').hide();
                $(this).hide();
                $('#mgmit-token-field').show();
            });

            $('#mgmit-credentials-form').on('submit', function(e){
                e.preventDefault();
                var $btn   = $('#mgmit-save-creds-btn');
                var token  = $('#mgmit-access-token').val().trim();
                var portal = $('#mgmit-portal-id').val().trim();

                $btn.prop('disabled', true).text('Guardando...');

                $.post(ajaxurl, {
                    action:           'mgmit_save_credentials',
                    security:         $('#mgmit_creds_nonce').val(),
                    access_token:     token,
                    portal_id:        portal,
                })
                .done(function(res){
                    var cls = res.success ? 'notice-success' : 'notice-error';
                    $('#mgmit-creds-notice')
                        .attr('class', 'notice ' + cls)
                        .html('<p>' + (res.data || 'Error inesperado.') + '</p>')
                        .show();
                    if (res.success) {
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                })
                .fail(function(){
                    $('#mgmit-creds-notice')
                        .attr('class', 'notice notice-error')
                        .html('<p>Error de conexión. Inténtalo de nuevo.</p>')
                        .show();
                })
                .always(function(){
                    $btn.prop('disabled', false).text('Guardar Credenciales');
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    // -------------------------------------------------------------------------
    // AJAX: Guardar credenciales API
    // -------------------------------------------------------------------------

    public function ajax_save_credentials() {
        check_ajax_referer('mgmit-save-credentials', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes.');
        }

        $creds = get_option('mgmit_hubspot_credentials', array());

        $new_token  = isset($_POST['access_token']) ? sanitize_text_field($_POST['access_token']) : '';
        $new_portal = isset($_POST['portal_id'])    ? sanitize_text_field($_POST['portal_id'])    : '';

        // Solo actualizar el token si se envió uno nuevo (campo no vacío)
        if ($new_token !== '') {
            $creds['access_token'] = $new_token;
        }

        if ($new_portal !== '') {
            $creds['portal_id'] = $new_portal;
        }

        update_option('mgmit_hubspot_credentials', $creds);

        wp_send_json_success('Credenciales guardadas correctamente.');
    }

    // -------------------------------------------------------------------------
    // Utilidades
    // -------------------------------------------------------------------------

    private function generate_id() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
