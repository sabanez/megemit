<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resuelve el tratamiento formal alemán (Sehr geehrte(r) Herr/Frau [Titel] Nachname)
 * a partir de las propiedades de HubSpot courtesy_de y job_title_de.
 *
 * Nunca bloquea el envío de un email: si HubSpot no responde o el contacto no
 * tiene courtesy_de, degrada al fallback "Sehr geehrte(r) Vorname Nachname".
 */

define( 'MGMIT_SALUTATION_HS_PROPS', array( 'courtesy_de', 'job_title_de' ) );

/**
 * Nombre, apellidos y email son los del usuario en la base de datos (SWPM/WordPress).
 * De HubSpot solo salen el tratamiento (courtesy_de) y el título (job_title_de).
 *
 * @param string $email      Email del usuario, usado como clave para consultar HubSpot.
 * @param string $first_name Nombre del usuario (base de datos).
 * @param string $last_name  Apellido del usuario (base de datos).
 * @return array{greeting: string, courtesy_label: string, courtesy_de: string, job_title_de: string, first_name: string, last_name: string, source: string}
 *         courtesy_de: valor de courtesy_de tal cual lo devuelve HubSpot (lo que muestran los PDFs).
 *         courtesy_label: 'Herr' / 'Frau' / '' (solo para uso interno; vacío si no se pudo resolver).
 *         source: 'hubspot' si se resolvió con courtesy_de, 'fallback' si no.
 */
function mgmit_bridge_build_salutation( $email, $first_name, $last_name ) {
    if ( ! class_exists( 'MGMIT_HS_Contacts' ) || ! method_exists( 'MGMIT_HS_Contacts', 'get_properties' ) ) {
        if ( class_exists( 'MGMIT_Webhook_Sender' ) ) {
            MGMIT_Webhook_Sender::debug_log(
                'salutation: MGMIT_HS_Contacts::get_properties no disponible (plugin hubspot-mapper inactivo o desactualizado en este entorno)'
            );
        }
        $fallback               = mgmit_bridge_salutation_fallback( $first_name, $last_name );
        $fallback['first_name'] = $first_name;
        $fallback['last_name']  = $last_name;
        return $fallback;
    }

    $result = MGMIT_HS_Contacts::get_properties( $email, MGMIT_SALUTATION_HS_PROPS );

    $courtesy  = isset( $result['values']['courtesy_de'] )  ? trim( $result['values']['courtesy_de'] )  : '';
    $job_title = isset( $result['values']['job_title_de'] ) ? trim( $result['values']['job_title_de'] ) : '';

    if ( '' === $courtesy && class_exists( 'MGMIT_Webhook_Sender' ) ) {
        MGMIT_Webhook_Sender::debug_log(
            'salutation: HubSpot sin courtesy_de para ' . $email . ' (http=' . $result['http_code'] . ', error=' . substr( $result['error'], 0, 160 ) . ')'
        );
    }

    // Descarta job_title_de si por error de captura en HubSpot contiene
    // un tratamiento (Herr/Frau) en vez de un título académico.
    $job_title = mgmit_bridge_discard_if_courtesy_value( $job_title );

    if ( '' === $courtesy ) {
        $fallback                 = mgmit_bridge_salutation_fallback( $first_name, $last_name );
        $fallback['job_title_de'] = $job_title;
        $fallback['first_name']   = $first_name;
        $fallback['last_name']    = $last_name;
        return $fallback;
    }

    $name_with_title = trim( implode( ' ', array_filter( array( $job_title, $last_name ) ) ) );

    // courtesy_de en HubSpot: "Sehr geehrter Herr" / "Sehr geehrte Frau" (también se aceptan
    // "Herr" / "Frau" sueltos y cualquier combinación de mayúsculas o espacios).
    if ( preg_match( '/\bherr\b/i', $courtesy ) ) {
        $courtesy_label = 'Herr';
        $greeting       = 'Sehr geehrter Herr ' . $name_with_title;
    } elseif ( preg_match( '/\bfrau\b/i', $courtesy ) ) {
        $courtesy_label = 'Frau';
        $greeting       = 'Sehr geehrte Frau ' . $name_with_title;
    } else {
        if ( class_exists( 'MGMIT_Webhook_Sender' ) ) {
            MGMIT_Webhook_Sender::debug_log(
                'salutation: valor de courtesy_de no reconocido para ' . $email . ': "' . substr( $courtesy, 0, 80 ) . '"'
            );
        }
        return array(
            'greeting'       => 'Sehr geehrte(r) ' . $first_name . ' ' . $last_name,
            'courtesy_label' => '',
            'courtesy_de'    => $courtesy,
            'job_title_de'   => $job_title,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'source'         => 'fallback',
        );
    }

    return array(
        'greeting'       => $greeting,
        'courtesy_label' => $courtesy_label,
        'courtesy_de'    => $courtesy,
        'job_title_de'   => $job_title,
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'source'         => 'hubspot',
    );
}

/**
 * Descarta un valor si coincide (sin mayúsculas/minúsculas) con "Herr" o "Frau",
 * señal de que un campo de título/profesión se rellenó por error con el tratamiento.
 */
function mgmit_bridge_discard_if_courtesy_value( $value ) {
    $normalized = strtolower( trim( $value ) );
    if ( 'herr' === $normalized || 'frau' === $normalized ) {
        return '';
    }
    return $value;
}

/**
 * Fallback cuando HubSpot no responde o no tiene courtesy_de para el contacto.
 */
function mgmit_bridge_salutation_fallback( $first_name, $last_name ) {
    return array(
        'greeting'       => 'Sehr geehrte(r) ' . trim( $first_name . ' ' . $last_name ),
        'courtesy_label' => '',
        'courtesy_de'    => '',
        'job_title_de'   => '',
        'source'         => 'fallback',
    );
}

/**
 * Socio SWPM asociado a un pago (IPN): por ID de socio, por suscripción de Stripe
 * o, en último caso, por el email del pago. Devuelve null si no se encuentra.
 *
 * @param array $ipn_data Datos IPN de SWPM.
 * @return object|null Fila de swpm_members_tbl.
 */
function mgmit_bridge_get_swpm_member_from_ipn( $ipn_data ) {
    if ( ! class_exists( 'SwpmMemberUtils' ) ) {
        return null;
    }

    $member  = null;
    $swpm_id = isset( $ipn_data['swpm_id'] ) ? absint( $ipn_data['swpm_id'] ) : 0;
    if ( ! $swpm_id && isset( $ipn_data['member_id'] ) ) {
        $swpm_id = absint( $ipn_data['member_id'] );
    }
    if ( $swpm_id ) {
        $member = SwpmMemberUtils::get_user_by_id( $swpm_id );
    }

    if ( ! $member && ! empty( $ipn_data['subscr_id'] ) ) {
        $member = SwpmMemberUtils::get_user_by_subsriber_id( sanitize_text_field( $ipn_data['subscr_id'] ) );
    }

    if ( ! $member && ! empty( $ipn_data['payer_email'] ) ) {
        $member = SwpmMemberUtils::get_user_by_email( sanitize_email( $ipn_data['payer_email'] ) );
    }

    return $member ? $member : null;
}

/**
 * Saludo para un pago de Stripe. Se busca al socio en SWPM y se usa su email y su nombre
 * registrados; si no se encuentra, el email y el nombre del pago.
 *
 * @param array  $ipn_data   Datos IPN de SWPM.
 * @param string $first_name Nombre del pago (se sustituye por el del socio si existe).
 * @param string $last_name  Apellido del pago (idem).
 * @return array Igual que mgmit_bridge_build_salutation() más first_name, last_name y lookup_email.
 */
function mgmit_bridge_build_salutation_for_ipn( $ipn_data, $first_name, $last_name ) {
    $member = mgmit_bridge_get_swpm_member_from_ipn( $ipn_data );

    // El email del socio es el de su usuario de WordPress y, por tanto, el que está en HubSpot.
    // Solo si no se encuentra al socio se usa el email del pago.
    if ( $member && ! empty( $member->email ) ) {
        $email = $member->email;
        if ( ! empty( $member->first_name ) ) {
            $first_name = sanitize_text_field( $member->first_name );
        }
        if ( ! empty( $member->last_name ) ) {
            $last_name = sanitize_text_field( $member->last_name );
        }
    } else {
        $email = isset( $ipn_data['payer_email'] ) ? $ipn_data['payer_email'] : '';
    }

    return mgmit_bridge_build_salutation_logged( $email, $first_name, $last_name );
}

/**
 * Saludo para un socio de SWPM (bajas, avisos de renovación), con su email y nombre.
 *
 * @param object $member Fila de swpm_members_tbl.
 * @return array Igual que mgmit_bridge_build_salutation() más first_name, last_name y lookup_email.
 */
function mgmit_bridge_build_salutation_for_member( $member ) {
    $email      = ! empty( $member->email ) ? $member->email : '';
    $first_name = ! empty( $member->first_name ) ? sanitize_text_field( $member->first_name ) : '';
    $last_name  = ! empty( $member->last_name ) ? sanitize_text_field( $member->last_name ) : '';

    return mgmit_bridge_build_salutation_logged( $email, $first_name, $last_name );
}

/**
 * mgmit_bridge_build_salutation() más constancia en el log del bridge del email usado y
 * de la fuente (hubspot/fallback). Añade first_name, last_name y lookup_email al resultado.
 */
function mgmit_bridge_build_salutation_logged( $email, $first_name, $last_name ) {
    $email  = sanitize_email( $email );
    $result = mgmit_bridge_build_salutation( $email, $first_name, $last_name );

    if ( class_exists( 'MGMIT_Webhook_Sender' ) ) {
        MGMIT_Webhook_Sender::debug_log( 'salutation: email=' . $email . ' fuente=' . $result['source'] );
    }

    $result['first_name']   = $first_name;
    $result['last_name']    = $last_name;
    $result['lookup_email'] = $email;
    return $result;
}
