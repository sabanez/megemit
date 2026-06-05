<?php
/**
 * Capa de formularios HubSpot — creación automática y envío server-side.
 *
 * Flujo:
 *  1. ensure_form() — si el mapeo no tiene formGuid, crea el formulario en HubSpot
 *     vía Forms API v3 y persiste el guid en la config del mapeo.
 *  2. submit() — envía los datos al endpoint de submissions v3 (sin autenticación).
 *
 * Esto popula Marketing → Formularios en HubSpot bajo el nombre configurado
 * en cada mapeo (hubspotFormName), sin necesidad de crear los formularios
 * manualmente ni de depender de JS/LeadIn.
 *
 * Compatible con PHP 7.4.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MGMIT_HS_Forms {

    const API_FORMS_PATH = '/marketing/v3/forms';
    const API_SUBMIT     = 'https://api.hsforms.com/submissions/v3/integration/submit';

    /**
     * Punto de entrada principal desde los adaptadores.
     *
     * Garantiza que el formGuid existe (crea el form si no) y envía la submission.
     *
     * @param array  $mapping Mapeo completo de la config del plugin.
     * @param array  $props   [hs_prop => valor] a enviar.
     * @return bool
     */
    public static function process($mapping, $props) {
        $portal_id = MGMIT_Ghost_Relay::get_portal_id();
        if ($portal_id === '') {
            self::log('Portal ID no configurado; se omite el envío.');
            return false;
        }

        $form_name = isset($mapping['hubspotFormName']) ? trim($mapping['hubspotFormName']) : '';
        if ($form_name === '') {
            $form_name = isset($mapping['name']) ? sanitize_title($mapping['name']) : 'mgmit-form';
        }

        $mapping_id = isset($mapping['id']) ? $mapping['id'] : '';

        $form_guid = self::ensure_form($mapping_id, $form_name, array_keys($props));
        if ($form_guid === '') {
            self::log('No se pudo obtener formGuid para "' . $form_name . '".');
            return false;
        }

        $hutk     = isset($_COOKIE['hubspotutk']) ? sanitize_text_field($_COOKIE['hubspotutk']) : '';
        $page_uri = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';

        return self::submit($portal_id, $form_guid, $props, $hutk, $page_uri);
    }

    /**
     * Devuelve el formGuid del mapeo. Si no existe lo crea en HubSpot y lo persiste.
     *
     * @param string $mapping_id  ID del mapeo en la config del plugin.
     * @param string $form_name   Nombre del formulario (hubspotFormName).
     * @param array  $field_names Lista de nombres de propiedades HubSpot.
     * @return string formGuid o cadena vacía en error.
     */
    public static function ensure_form($mapping_id, $form_name, $field_names) {
        if ($mapping_id === '') {
            return '';
        }

        // Leer guid persistido.
        $config = get_option(MGMIT_MAPPER_OPTION, array());
        foreach ($config as $idx => $m) {
            if (isset($m['id']) && $m['id'] === $mapping_id) {
                if (!empty($m['hs_form_guid'])) {
                    return (string) $m['hs_form_guid'];
                }
                break;
            }
        }

        // Buscar si ya existe en HubSpot por nombre antes de crear.
        $guid = self::find_form_by_name($form_name);
        if ($guid === '') {
            $guid = self::create_form($form_name, $field_names);
        } else {
            self::log('Formulario existente encontrado en HubSpot: "' . $form_name . '" guid=' . $guid);
            // Asegurar que todos los campos actuales (incluidos estáticos) están en el form.
            self::update_form_fields($guid, $field_names);
        }
        if ($guid === '') {
            return '';
        }

        // Persistir guid en la config del mapeo.
        foreach ($config as $idx => $m) {
            if (isset($m['id']) && $m['id'] === $mapping_id) {
                $config[$idx]['hs_form_guid'] = $guid;
                break;
            }
        }
        update_option(MGMIT_MAPPER_OPTION, $config);

        self::log('Formulario creado en HubSpot: "' . $form_name . '" guid=' . $guid);
        return $guid;
    }

    /**
     * Actualiza el formulario en HubSpot añadiendo los campos que falten.
     * Se llama solo cuando el guid se obtiene por nombre (tras guardar el mapeo),
     * no en envíos sucesivos donde el guid ya está cacheado.
     *
     * @param string $guid
     * @param array  $field_names
     * @return void
     */
    private static function update_form_fields($guid, $field_names) {
        $token = MGMIT_HS_Contacts::get_token();
        if ($token === '') {
            return;
        }

        $base = self::get_api_base();
        $response = wp_remote_get(
            $base . '/forms/v2/forms/' . rawurlencode($guid),
            array(
                'headers' => array('Authorization' => 'Bearer ' . $token),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return;
        }

        $form = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($form)) {
            return;
        }

        // Recopilar nombres de campos ya presentes en el formulario.
        $existing = array();
        if (isset($form['formFieldGroups']) && is_array($form['formFieldGroups'])) {
            foreach ($form['formFieldGroups'] as $group) {
                if (isset($group['fields']) && is_array($group['fields'])) {
                    foreach ($group['fields'] as $f) {
                        if (isset($f['name'])) {
                            $existing[] = $f['name'];
                        }
                    }
                }
            }
        }

        $missing = array_values(array_diff($field_names, $existing));
        if (empty($missing)) {
            return;
        }

        $new_fields = array();
        foreach ($missing as $name) {
            $new_fields[] = array(
                'name'               => $name,
                'label'              => ucfirst(str_replace('_', ' ', $name)),
                'fieldType'          => 'text',
                'propertyObjectType' => 'CONTACT',
                'required'           => false,
            );
        }

        if (!isset($form['formFieldGroups']) || !is_array($form['formFieldGroups'])) {
            $form['formFieldGroups'] = array();
        }
        $form['formFieldGroups'][] = array('fields' => $new_fields);

        $update_response = wp_remote_post(
            $base . '/forms/v2/forms/' . rawurlencode($guid),
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($form),
                'timeout' => 15,
            )
        );

        if (!is_wp_error($update_response)) {
            $code = (int) wp_remote_retrieve_response_code($update_response);
            self::log('update_form_fields HTTP ' . $code . ' para campos: ' . implode(', ', $missing));
        }
    }

    /**
     * Busca en HubSpot un formulario por nombre exacto vía Forms API v2.
     *
     * @param string $form_name
     * @return string formGuid o cadena vacía si no existe o hay error.
     */
    private static function find_form_by_name($form_name) {
        $token = MGMIT_HS_Contacts::get_token();
        if ($token === '') {
            return '';
        }

        $url = self::get_api_base() . '/forms/v2/forms';

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array('Authorization' => 'Bearer ' . $token),
                'timeout' => 20,
            )
        );

        if (is_wp_error($response)) {
            return '';
        }

        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        $forms = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($forms)) {
            return '';
        }

        foreach ($forms as $form) {
            if (isset($form['name']) && $form['name'] === $form_name && !empty($form['guid'])) {
                return (string) $form['guid'];
            }
        }

        return '';
    }

    /**
     * Crea un formulario en HubSpot vía Forms API v2.
     *
     * @param string $form_name   Nombre del formulario.
     * @param array  $field_names Propiedades HubSpot a incluir como campos.
     * @return string formGuid o cadena vacía en error.
     */
    private static function create_form($form_name, $field_names) {
        $token = MGMIT_HS_Contacts::get_token();
        if ($token === '') {
            self::log('Sin access token; no se puede crear el formulario.');
            return '';
        }

        // Asegurar que email esté siempre incluido.
        if (!in_array('email', $field_names, true)) {
            array_unshift($field_names, 'email');
        }

        // Forms API v2 — estructura más simple y robusta que v3.
        $fields = array();
        foreach ($field_names as $name) {
            $fields[] = array(
                'name'               => $name,
                'label'              => ucfirst($name),
                'fieldType'          => ($name === 'email') ? 'text' : 'text',
                'propertyObjectType' => 'CONTACT',
                'required'           => ($name === 'email'),
            );
        }

        $body = array(
            'name'            => $form_name,
            'formFieldGroups' => array(
                array('fields' => $fields),
            ),
        );

        // v2 endpoint usa api base sin hublet prefix en el path.
        $url = self::get_api_base() . '/forms/v2/forms';

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
                'timeout' => 20,
            )
        );

        if (is_wp_error($response)) {
            self::log('Error al crear form: ' . $response->get_error_message());
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            self::log('Create form HTTP ' . $code . ': ' . wp_remote_retrieve_body($response));
            return '';
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        // v2 devuelve 'guid'; v3 devuelve 'id'.
        if (isset($data['guid']) && $data['guid'] !== '') {
            return (string) $data['guid'];
        }
        return (isset($data['id']) && is_string($data['id'])) ? $data['id'] : '';
    }

    /**
     * Envía datos al endpoint de submissions v3 de HubSpot.
     * No requiere autenticación.
     *
     * @param string $portal_id
     * @param string $form_guid
     * @param array  $props     [hs_prop => valor]
     * @param string $hutk      Cookie hubspotutk (puede ser vacío).
     * @param string $page_uri  URL de la página de origen (puede ser vacío).
     * @return bool
     */
    public static function submit($portal_id, $form_guid, $props, $hutk = '', $page_uri = '') {
        $fields = array();
        foreach ($props as $name => $value) {
            $fields[] = array(
                'objectTypeId' => '0-1',
                'name'         => (string) $name,
                'value'        => (string) $value,
            );
        }

        $context = array(
            'pageUri'  => $page_uri,
            'pageName' => '',
        );
        if ($hutk !== '') {
            $context['hutk'] = $hutk;
        }

        $body = array(
            'fields'  => $fields,
            'context' => $context,
        );

        $url = self::API_SUBMIT . '/' . intval($portal_id) . '/' . rawurlencode($form_guid);

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array('Content-Type' => 'application/json'),
                'body'    => wp_json_encode($body),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response)) {
            self::log('Error en submission: ' . $response->get_error_message());
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            self::log('Submission HTTP ' . $code . ': ' . wp_remote_retrieve_body($response));
            return false;
        }

        return true;
    }

    /**
     * Devuelve la URL base de la API de HubSpot según el hublet del portal.
     * EU1: https://api-eu1.hubapi.com  |  NA/defecto: https://api.hubapi.com
     *
     * @return string
     */
    private static function get_api_base() {
        $hublet = get_option('leadin_hublet', '');
        if (!empty($hublet) && $hublet !== 'na1') {
            return 'https://api-' . $hublet . '.hubapi.com';
        }
        return 'https://api.hubapi.com';
    }

    private static function log($message) {
        error_log('[hubspot-mapper] ' . $message);
    }
}
