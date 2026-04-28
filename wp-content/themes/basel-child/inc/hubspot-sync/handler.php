<?php
/**
 * HubSpot Form Data Sync Handler
 *
 * Sincroniza datos del formulario HubSpot (registrierungsdetails)
 * hacia las tablas wpgr_users y wpgr_swpm_members_tbl
 */

if (!defined('ABSPATH')) {
    exit;
}

class MGMIT_HubSpot_Sync {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_sync_endpoint']);
    }

    /**
     * Registra el endpoint REST para recibir datos de HubSpot
     */
    public function register_sync_endpoint() {
        register_rest_route('mgmit/v1', '/sync-hubspot-data', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_sync_request'],
            'permission_callback' => [$this, 'verify_request_authenticity'],
        ]);
    }

    /**
     * Verifica autenticidad básica de la solicitud
     * (puede extenderse con validación de firma HubSpot)
     */
    public function verify_request_authenticity($request) {
        // Validación básica: debe venir con email
        $email = $request->get_param('email');
        if (empty($email) || !is_email($email)) {
            return false;
        }
        return true;
    }

    /**
     * Handler principal: procesa los datos de HubSpot
     */
    public function handle_sync_request($request) {
        $email = sanitize_email($request->get_param('email'));
        $data  = $request->get_param('data');

        write_log('[MGMIT_HS_SYNC] Solicitud recibida para: ' . $email);

        // Buscar usuario por email
        $user = get_user_by('email', $email);

        if (!$user) {
            write_log('[MGMIT_HS_SYNC] ERROR: Usuario no encontrado con email: ' . $email);
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'email'   => $email,
                ],
                404
            );
        }

        // Sincronizar datos
        $sync_result = $this->sync_user_data($user->ID, $data);

        if (!$sync_result) {
            write_log('[MGMIT_HS_SYNC] ERROR: Falló la sincronización para user_id: ' . $user->ID);
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Error al sincronizar datos',
                    'user_id' => $user->ID,
                ],
                500
            );
        }

        write_log('[MGMIT_HS_SYNC] Sincronización exitosa para user_id: ' . $user->ID);

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'Datos sincronizados correctamente',
                'user_id' => $user->ID,
                'email'   => $email,
            ],
            200
        );
    }

    /**
     * Sincroniza datos en wpgr_usermeta (WooCommerce/WordPress),
     * wpgr_swpm_members_tbl y wpgr_swpm_members_meta_tbl
     *
     * @param int $user_id
     * @param array $data Array con los datos a sincronizar
     * @return bool
     */
    private function sync_user_data($user_id, $data) {
        global $wpdb;

        if (!is_array($data) || empty($data)) {
            return false;
        }

        $user_id = absint($user_id);

        // ========== SINCRONIZAR wpgr_usermeta (WooCommerce/WordPress metadata) ==========
        if (isset($data['first_name']) && !empty($data['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
            update_user_meta($user_id, 'billing_first_name', sanitize_text_field($data['first_name']));
        }

        if (isset($data['last_name']) && !empty($data['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
            update_user_meta($user_id, 'billing_last_name', sanitize_text_field($data['last_name']));
        }

        if (isset($data['address']) && !empty($data['address'])) {
            update_user_meta($user_id, 'billing_address_1', sanitize_text_field($data['address']));
        }

        if (isset($data['address2']) && !empty($data['address2'])) {
            update_user_meta($user_id, 'billing_address_2', sanitize_text_field($data['address2']));
        }

        if (isset($data['zip']) && !empty($data['zip'])) {
            update_user_meta($user_id, 'billing_postcode', sanitize_text_field($data['zip']));
        }

        if (isset($data['city']) && !empty($data['city'])) {
            update_user_meta($user_id, 'billing_city', sanitize_text_field($data['city']));
        }

        if (isset($data['country']) && !empty($data['country'])) {
            update_user_meta($user_id, 'select2-billing_country-container', sanitize_text_field($data['country']));
        }

        if (isset($data['phone']) && !empty($data['phone'])) {
            update_user_meta($user_id, 'billing_phone', sanitize_text_field($data['phone']));
        }

        if (isset($data['job_title']) && !empty($data['job_title'])) {
            update_user_meta($user_id, 'job_title_de', sanitize_text_field($data['job_title']));
        }

        write_log('[MGMIT_HS_SYNC] wpgr_usermeta actualizado para user_id: ' . $user_id);

        // ========== SINCRONIZAR wpgr_swpm_members_tbl ==========
        $swpm_prefix = $wpdb->prefix . 'swpm_members_tbl';

        $member = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$swpm_prefix} WHERE user_id = %d", $user_id),
            ARRAY_A
        );

        if (!$member) {
            write_log('[MGMIT_HS_SYNC] Advertencia: No se encontró registro SWPM para user_id: ' . $user_id);
            return true; // No es fatal, el usuario WP existe
        }

        $member_update = [];

        if (isset($data['first_name']) && !empty($data['first_name'])) {
            $member_update['first_name'] = sanitize_text_field($data['first_name']);
        }

        if (isset($data['last_name']) && !empty($data['last_name'])) {
            $member_update['last_name'] = sanitize_text_field($data['last_name']);
        }

        if (isset($data['phone']) && !empty($data['phone'])) {
            $member_update['phone'] = sanitize_text_field($data['phone']);
        }

        if (isset($data['address']) && !empty($data['address'])) {
            $member_update['address'] = sanitize_text_field($data['address']);
        }

        if (isset($data['address2']) && !empty($data['address2'])) {
            $member_update['address2'] = sanitize_text_field($data['address2']);
        }

        if (isset($data['zip']) && !empty($data['zip'])) {
            $member_update['zip'] = sanitize_text_field($data['zip']);
        }

        if (isset($data['city']) && !empty($data['city'])) {
            $member_update['city'] = sanitize_text_field($data['city']);
        }

        if (isset($data['country']) && !empty($data['country'])) {
            $member_update['country'] = sanitize_text_field($data['country']);
        }

        if (isset($data['billing_titel']) && !empty($data['billing_titel'])) {
            $member_update['billing_titel'] = sanitize_text_field($data['billing_titel']);
        }

        if (!empty($member_update)) {
            $result = $wpdb->update(
                $swpm_prefix,
                $member_update,
                ['user_id' => $user_id],
                null,
                ['%d']
            );

            if ($result === false) {
                write_log('[MGMIT_HS_SYNC] BD Error updating SWPM member for user_id: ' . $user_id . ' - ' . $wpdb->last_error);
                return false;
            }

            write_log('[MGMIT_HS_SYNC] wpgr_swpm_members_tbl actualizado para user_id: ' . $user_id);
        }

        return true;
    }

    /**
     * Sanitiza los datos de campo de forma recursiva
     */
    public function sanitize_field_data($data) {
        if (!is_array($data)) {
            return sanitize_text_field($data);
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[sanitize_key($key)] = is_array($value)
                ? $this->sanitize_field_data($value)
                : sanitize_text_field($value);
        }

        return $sanitized;
    }
}

// Inicializar
new MGMIT_HubSpot_Sync();
