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
     * Parsea una clave de propiedad con prefijo de objeto ("contact-email")
     * y devuelve ['objectTypeId', 'name']. Sin prefijo reconocido → contact.
     *
     * Mapa de objetos soportados:
     *   contact → 0-1 | company → 0-2 | deal → 0-3 | ticket → 0-5
     *
     * @param string $key Clave en formato "{objeto}-{propiedad}" o solo "{propiedad}".
     * @return array ['objectTypeId' => string, 'name' => string]
     */
    public static function parse_prop_key($key) {
        static $object_map = array(
            'contact' => '0-1',
            'company' => '0-2',
            'deal'    => '0-3',
            'ticket'  => '0-5',
        );
        $dash = strpos($key, '-');
        if ($dash !== false && $dash > 0) {
            $prefix = substr($key, 0, $dash);
            if (isset($object_map[$prefix])) {
                return array(
                    'objectTypeId' => $object_map[$prefix],
                    'name'         => substr($key, $dash + 1),
                );
            }
        }
        return array(
            'objectTypeId' => '0-1',
            'name'         => $key,
        );
    }

    /**
     * Devuelve un array de nombres de propiedad sin prefijo de objeto.
     * Elimina duplicados tras el strip.
     *
     * @param array $field_names Lista de claves posiblemente prefijadas.
     * @return array
     */
    private static function strip_prop_names($field_names) {
        $result = array();
        foreach ($field_names as $name) {
            $parsed   = self::parse_prop_key($name);
            $result[] = $parsed['name'];
        }
        return array_values(array_unique($result));
    }

    /**
     * Punto de entrada principal desde los adaptadores.
     *
     * Garantiza que el formGuid existe (crea el form si no) y envía la submission.
     *
     * @param array  $mapping Mapeo completo de la config del plugin.
     * @param array  $props   [{objeto}-{propiedad} => valor] a enviar.
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

        $form_guid = self::ensure_form($mapping_id, $form_name, self::strip_prop_names(array_keys($props)));
        if ($form_guid === '') {
            self::log('No se pudo obtener formGuid para "' . $form_name . '".');
            return false;
        }

        $hutk     = isset($_COOKIE['hubspotutk']) ? sanitize_text_field($_COOKIE['hubspotutk']) : '';
        $page_uri = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';

        // Separar props en seguros (no-enum) y enum.
        // Para enum: corregir capitalización si hay coincidencia case-insensitive.
        // Enviar enum al final; si el submit falla, reintentar solo con los seguros.
        $token       = MGMIT_HS_Contacts::get_token();
        $safe_props  = array();
        $enum_props  = array();    // [prop => valor (corregido si posible)]
        $enum_unsafe = array();    // props enum sin match válido (potencialmente inválidas)

        if ($token !== '') {
            $prop_types = self::get_property_types($token);
            foreach ($props as $prop_name => $value) {
                $bare = self::parse_prop_key($prop_name);
                $bare_name = $bare['name'];
                if (isset($prop_types[$bare_name]) && in_array($prop_types[$bare_name], array('select', 'checkbox', 'radio'), true) && $value !== '') {
                    $corrected = self::resolve_enum_value($token, $bare_name, (string) $value);
                    if ($corrected !== false) {
                        $enum_props[$prop_name] = $corrected;
                    } else {
                        // Sin match: enviar valor original pero marcar como unsafe.
                        $enum_props[$prop_name]  = (string) $value;
                        $enum_unsafe[$prop_name] = true;
                        self::log('process: enum sin match válido "' . $prop_name . '" valor="' . $value . '"');
                    }
                } else {
                    $safe_props[$prop_name] = $value;
                }
            }
        } else {
            $safe_props = $props;
        }

        // Primer intento: safe_props primero, enum al final.
        $ordered_props = array_merge($safe_props, $enum_props);
        $result = self::submit($portal_id, $form_guid, $ordered_props, $hutk, $page_uri);

        // Si falló y hay enums potencialmente inválidos: reintentar solo con safe + enums válidos.
        if (!$result && !empty($enum_unsafe)) {
            $valid_enum_props = array_diff_key($enum_props, $enum_unsafe);
            $retry_props      = array_merge($safe_props, $valid_enum_props);
            self::log('process: reintentando submit sin ' . implode(',', array_keys($enum_unsafe)));
            $result = self::submit($portal_id, $form_guid, $retry_props, $hutk, $page_uri);
        }

        return $result;
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

        $prop_types = self::get_property_types($token);

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

        $url = $base . '/forms/v2/forms/' . rawurlencode($guid);

        // Fase 1: intentar añadir todos los campos en un solo POST (caso normal).
        $batch = $form;
        if (!isset($batch['formFieldGroups']) || !is_array($batch['formFieldGroups'])) {
            $batch['formFieldGroups'] = array();
        }
        foreach ($missing as $name) {
            $batch['formFieldGroups'][0]['fields'][] = array(
                'name'               => $name,
                'label'              => ucfirst(str_replace('_', ' ', $name)),
                'fieldType'          => isset($prop_types[$name]) ? $prop_types[$name] : 'text',
                'propertyObjectType' => 'CONTACT',
                'required'           => false,
            );
        }

        $batch_response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($batch),
                'timeout' => 15,
            )
        );

        $batch_code = is_wp_error($batch_response) ? 0 : (int) wp_remote_retrieve_response_code($batch_response);
        self::log('update_form_fields batch HTTP ' . $batch_code . ' campos: ' . implode(', ', $missing));

        if ($batch_code >= 200 && $batch_code < 300) {
            return; // Todos los campos añadidos en una sola llamada.
        }

        // Fase 2 (fallback): algún campo no existe en HubSpot; añadir uno a uno.
        // Re-GET para partir del estado real tras el intento fallido.
        $fresh_response = wp_remote_get(
            $url,
            array(
                'headers' => array('Authorization' => 'Bearer ' . $token),
                'timeout' => 15,
            )
        );
        if (is_wp_error($fresh_response) || (int) wp_remote_retrieve_response_code($fresh_response) !== 200) {
            return;
        }
        $form = json_decode(wp_remote_retrieve_body($fresh_response), true);
        if (!is_array($form)) {
            return;
        }
        if (!isset($form['formFieldGroups']) || !is_array($form['formFieldGroups'])) {
            $form['formFieldGroups'] = array();
        }

        foreach ($missing as $name) {
            $ft = isset($prop_types[$name]) ? $prop_types[$name] : 'text';
            $form['formFieldGroups'][0]['fields'][] = array(
                'name'               => $name,
                'label'              => ucfirst(str_replace('_', ' ', $name)),
                'fieldType'          => $ft,
                'propertyObjectType' => 'CONTACT',
                'required'           => false,
            );

            $res  = wp_remote_post(
                $url,
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode($form),
                    'timeout' => 15,
                )
            );
            $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
            self::log('update_form_fields fallback HTTP ' . $code . ' campo: ' . $name . ' fieldType=' . $ft . ($code >= 300 ? ' body=' . wp_remote_retrieve_body($res) : ''));

            if ($code < 200 || $code >= 300) {
                // Campo inexistente en HubSpot: descartar de $form y continuar con el siguiente.
                array_pop($form['formFieldGroups'][0]['fields']);
            }
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

        // Crear el formulario solo con el campo email (siempre existe en HubSpot).
        // Los demás campos se añaden después vía update_form_fields() para evitar que
        // propiedades inexistentes en HubSpot bloqueen la creación completa del formulario.
        $body = array(
            'name'            => $form_name,
            'formFieldGroups' => array(
                array(
                    'fields' => array(
                        array(
                            'name'               => 'email',
                            'label'              => 'Email',
                            'fieldType'          => 'text',
                            'propertyObjectType' => 'CONTACT',
                            'required'           => true,
                        ),
                    ),
                ),
            ),
        );

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
        $guid = '';
        if (isset($data['guid']) && $data['guid'] !== '') {
            $guid = (string) $data['guid'];
        } elseif (isset($data['id']) && is_string($data['id'])) {
            $guid = $data['id'];
        }

        // Añadir el resto de campos (excluir email, ya incluido en la creación).
        if ($guid !== '') {
            $extra = array_values(array_filter($field_names, function ($n) {
                return $n !== 'email';
            }));
            if (!empty($extra)) {
                self::update_form_fields($guid, $extra);
            }
        }

        return $guid;
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
            $parsed   = self::parse_prop_key((string) $name);
            $fields[] = array(
                'objectTypeId' => $parsed['objectTypeId'],
                'name'         => $parsed['name'],
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
     * Devuelve un mapa [nombre_propiedad => fieldType] de todas las propiedades
     * de contacto en HubSpot. Resultado cacheado en memoria para la request actual.
     *
     * Mapeo HubSpot type → Forms API fieldType:
     *   string      → text
     *   enumeration → select
     *   number      → number
     *   date        → date
     *   datetime    → date
     *   bool        → booleancheckbox
     *
     * @param string $token Access token de HubSpot.
     * @return array [prop_name => fieldType]
     */
    private static function get_property_types($token) {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        if ($token === '') {
            $cache = array();
            return $cache;
        }

        $response = wp_remote_get(
            self::get_api_base() . '/crm/v3/properties/contacts',
            array(
                'headers' => array('Authorization' => 'Bearer ' . $token),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            self::log('get_property_types: no se pudo obtener propiedades. Usando fieldType=text como fallback.');
            $cache = array();
            return $cache;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $map  = array();

        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $prop) {
                if (isset($prop['name'])) {
                    $map[$prop['name']] = !empty($prop['fieldType']) ? $prop['fieldType'] : 'text';
                }
            }
        }

        $cache = $map;
        return $cache;
    }

    /**
     * Busca $value entre las opciones válidas de una propiedad enumeration en HubSpot.
     * Primero comparación exacta; si no hay match, búsqueda case-insensitive.
     * Para valores múltiples ";" separados, corrige cada parte por separado.
     *
     * @param string $token     Access token de HubSpot.
     * @param string $prop_name Nombre de la propiedad HubSpot (enumeration).
     * @param string $value     Valor o valores ";" separados a resolver.
     * @return string|false Valor corregido listo para enviar, o false si ninguna parte tiene match.
     */
    private static function resolve_enum_value($token, $prop_name, $value) {
        static $options_cache = array();

        if ($token === '' || $prop_name === '' || $value === '') {
            return false;
        }

        // Obtener opciones (cacheadas por prop).
        if (!isset($options_cache[$prop_name])) {
            $url      = self::get_api_base() . '/crm/v3/properties/contacts/' . rawurlencode($prop_name);
            $response = wp_remote_get(
                $url,
                array(
                    'headers' => array('Authorization' => 'Bearer ' . $token),
                    'timeout' => 15,
                )
            );
            if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
                self::log('resolve_enum_value: no se pudo leer propiedad "' . $prop_name . '".');
                $options_cache[$prop_name] = array();
            } else {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                $opts = array();
                if (is_array($data) && isset($data['options']) && is_array($data['options'])) {
                    foreach ($data['options'] as $opt) {
                        if (isset($opt['value'])) {
                            $opts[] = $opt['value'];
                        }
                    }
                }
                $options_cache[$prop_name] = $opts;
            }
        }

        $existing = $options_cache[$prop_name];
        if (empty($existing)) {
            return false;
        }

        $parts   = array_filter(array_map('trim', explode(';', $value)));
        $resolved = array();
        $all_matched = true;

        foreach ($parts as $v) {
            // Exacto.
            if (in_array($v, $existing, true)) {
                $resolved[] = $v;
                continue;
            }
            // Case-insensitive.
            $match = false;
            foreach ($existing as $opt) {
                if (strcasecmp($v, $opt) === 0) {
                    $resolved[] = $opt;
                    $match = true;
                    self::log('resolve_enum_value: corregido "' . $v . '" → "' . $opt . '" en prop=' . $prop_name);
                    break;
                }
            }
            if (!$match) {
                $all_matched = false;
                self::log('resolve_enum_value: sin match para "' . $v . '" en prop=' . $prop_name);
            }
        }

        if (empty($resolved)) {
            return false;
        }

        // Si no todos tuvieron match, indicarlo pero devolver los que sí.
        // Retornamos false solo si NINGUNO tuvo match (ya cubierto arriba).
        // Si hay mezcla: devolver los válidos y marcar como unsafe en process().
        if (!$all_matched) {
            return false;
        }

        return implode(';', $resolved);
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
