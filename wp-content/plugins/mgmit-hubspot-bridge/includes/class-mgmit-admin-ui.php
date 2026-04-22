<?php
/**
 * Clase para la interfaz de administración (Fase 3)
 */

if (!defined('ABSPATH')) {
    exit;
}

class MGMIT_Admin_UI {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_mgmit_save_hubspot_config', [$this, 'ajax_save_config']);
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

    public function render_admin_page() {
        // Cargar configuración actual
        $config = get_option(MGMIT_HS_BRIDGE_OPTION, []);
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-share-alt" style="font-size: 32px; width: 32px; height: 32px;"></span> MeGeMIT: HubSpot Bridge & Onboarding</h1>
            
            <p>Configura los puente de conexión entre los formularios de WordPress y HubSpot sin necesidad de tocar el código (Edición en tiempo real).</p>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #f39910; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 0;">Mapeador Visual (JSON View)</h2>
                <p>En esta versión 1.2 del plugin, la configuración se expone como un objeto JSON estructurado validado en servidor, que refleja la relación estricta <code>Formulario -> Campos</code>.</p>
                <textarea id="mgmit-config-json" style="width: 100%; height: 400px; font-family: monospace; font-size: 14px; background: #f9f9f9; padding: 15px; border-radius: 5px;"><?php echo esc_textarea(json_encode($config, JSON_PRETTY_PRINT)); ?></textarea>
                
                <br><br>
                <button id="save-mgmit-config" class="button button-primary button-hero">Guardar Configuración en Base de Datos</button>
                <span id="save-status" style="margin-left: 15px; color: green; display: none; font-weight: bold;">✅ Configuración sincronizada y guardada exitosamente.</span>
            </div>
            
            <div style="margin-top:20px; background: #e5f5fa; padding: 15px; border-left: 4px solid #00a0d2;">
                <strong>Nota Técnica:</strong> Al guardar, esta información se transfiere automáticamente al Frontend a la variable <code>HS_CONFIG</code> asegurando retrocompatibilidad total con el actual <code>hubspot_map.js</code>.
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#save-mgmit-config').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true).text('Guardando en Base de datos...');
                
                var newConfig = $('#mgmit-config-json').val();
                
                try {
                    JSON.parse(newConfig); // Validación cliente
                } catch(e) {
                    alert('El JSON es inválido. Por favor, corrige los errores de sintaxis antes de guardar. Error: ' + e.message);
                    btn.prop('disabled', false).text('Guardar Configuración en Base de Datos');
                    return;
                }

                $.post(ajaxurl, {
                    action: 'mgmit_save_hubspot_config',
                    security: '<?php echo wp_create_nonce("mgmit-save-config"); ?>',
                    config: newConfig
                }, function(response) {
                    if(response.success) {
                        $('#save-status').fadeIn().delay(3000).fadeOut();
                    } else {
                        alert('Error al guardar: ' + response.data);
                    }
                    btn.prop('disabled', false).text('Guardar Configuración en Base de Datos');
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_save_config() {
        check_ajax_referer('mgmit-save-config', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes para modificar esta configuración.');
        }

        $config_json = isset($_POST['config']) ? stripslashes($_POST['config']) : '';
        $config_array = json_decode($config_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('El JSON enviado contiene errores de formato (Sintaxis inválida).');
        }

        // Actualizamos la DB. A partir de ahora el Frontend leerá esta opción actualizada.
        update_option(MGMIT_HS_BRIDGE_OPTION, $config_array);

        wp_send_json_success('Configuración guardada y activa.');
    }
}
