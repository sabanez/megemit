<?php
/**
 * Plugin Name: WooCommerce → HubSpot Email Logger
 * Description: Registra en el timeline del contacto de HubSpot los correos que WooCommerce envía al CLIENTE (confirmación de pedido, factura, notas, reembolsos...). Estos correos salen directamente desde el servidor y HubSpot nunca los ve, salvo que lleguen a un buzón conectado a Conversations.
 * Author: Megemit
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * ============================================================
 * CONFIGURACIÓN REQUERIDA
 * ============================================================
 * El token de HubSpot se resuelve vía wc_hs_email_logger_get_token(),
 * en cascada: constante MGMIT_HS_ACCESS_TOKEN_SECRET (wp-config.php) ->
 * opción propia del plugin hubspot-mapper -> opción del bridge. Si
 * ninguno de esos plugins/constante está presente en el sitio donde se
 * reutilice este snippet, define la constante en wp-config.php:
 *
 *   define( 'MGMIT_HS_ACCESS_TOKEN_SECRET', 'pat-xx-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' );
 *
 * Ese token debe ser de una Private App de HubSpot con, como mínimo,
 * estos scopes:
 *   - crm.objects.contacts.read
 *   - crm.objects.emails.write
 *
 * (Ajusta los nombres de scope si tu cuenta usa la nomenclatura
 * crm.objects.emails.read.write en vez de crm.objects.emails.write —
 * lo ves al crear la Private App en Configuración > Integraciones).
 * ============================================================
 */

/**
 * IDs de los emails de WooCommerce dirigidos al CLIENTE que queremos
 * loguear en HubSpot. Los que van al ADMIN (new_order, cancelled_order,
 * failed_order, backorder...) se dejan fuera a propósito: esos suelen
 * llegar ya a un buzón conectado a HubSpot Conversations.
 */
function wc_hs_email_logger_customer_email_ids() {
	return apply_filters(
		'wc_hs_email_logger_customer_email_ids',
		array(
			'customer_processing_order',
			'customer_completed_order',
			'customer_invoice',
			'customer_note',
			'customer_refunded_order',
			'customer_partially_refunded_order',
		)
	);
}

/**
 * ============================================================
 * 1) Enganchar el envío real del email en WooCommerce
 * ============================================================
 * WooCommerce dispara 'woocommerce_email_sent' justo después de
 * intentar enviar cada email, con ($return, $email_id, $email).
 * Disponible desde WooCommerce 3.9+.
 */
add_action( 'woocommerce_email_sent', 'wc_hs_email_logger_capture_customer_email', 10, 3 );

function wc_hs_email_logger_capture_customer_email( $return, $email_id, $email ) {

	// Solo correos al cliente, y solo si WooCommerce confirma que se envió.
	if ( ! $return || ! in_array( $email_id, wc_hs_email_logger_customer_email_ids(), true ) ) {
		return;
	}

	if ( ! ( $email instanceof WC_Email ) ) {
		return;
	}

	$recipient = $email->get_recipient();
	if ( empty( $recipient ) || ! is_email( $recipient ) ) {
		return;
	}

	$subject = $email->get_subject();
	$html    = $email->get_content(); // cuerpo ya renderizado, con el layout HTML de WooCommerce
	$text    = wp_strip_all_tags( $html );

	$order_id = 0;
	if ( is_callable( array( $email, 'get_order' ) ) && $email->get_order() ) {
		$order_id = $email->get_order()->get_id();
	} elseif ( isset( $email->object ) && is_a( $email->object, 'WC_Order' ) ) {
		$order_id = $email->object->get_id();
	}

	// Los adjuntos (factura PDF, nota de abono...) hay que subirlos a HubSpot
	// AHORA, de forma síncrona: son archivos temporales que WooCommerce puede
	// borrar en cuanto termina el request, así que para cuando corra la tarea
	// diferida de Action Scheduler ya podrían no existir en disco.
	$attachment_ids = array();
	$token          = wc_hs_email_logger_get_token();
	if ( ! empty( $token ) && is_callable( array( $email, 'get_attachments' ) ) ) {
		foreach ( $email->get_attachments() as $attachment_path ) {
			if ( ! is_string( $attachment_path ) || ! file_exists( $attachment_path ) ) {
				continue;
			}
			$file_id = wc_hs_email_logger_upload_attachment( $token, $attachment_path );
			if ( $file_id ) {
				$attachment_ids[] = $file_id;
			}
		}
	}

	$payload = array(
		'recipient'       => $recipient,
		'subject'         => $subject,
		'html'            => $html,
		'text'            => $text,
		'timestamp'       => (int) round( microtime( true ) * 1000 ),
		'order_id'        => $order_id,
		'email_id'        => $email_id,
		'attachment_ids'  => $attachment_ids,
	);

	// Encolamos el envío a HubSpot como tarea diferida (Action Scheduler,
	// ya viene incluido con WooCommerce) para no retrasar el checkout ni
	// el envío del correo real si HubSpot tarda o falla.
	//
	// El payload (con el HTML completo del email) se guarda en un transient
	// y solo se pasa su clave a la acción: Action Scheduler rechaza acciones
	// cuyos argumentos, codificados en JSON, superen los 8000 caracteres —
	// el HTML renderizado de WooCommerce lo supera con frecuencia.
	if ( function_exists( 'as_enqueue_async_action' ) ) {
		$transient_key = 'wc_hs_el_' . wp_generate_password( 12, false );
		set_transient( $transient_key, $payload, HOUR_IN_SECONDS );
		as_enqueue_async_action( 'wc_hs_email_logger_log_email', array( $transient_key ), 'wc-hs-email-logger' );
	} else {
		wc_hs_email_logger_log_email_to_timeline( $payload );
	}
}

add_action( 'wc_hs_email_logger_log_email', 'wc_hs_email_logger_log_email_from_transient' );

/**
 * Recupera el payload guardado en el transient y lo procesa. Wrapper entre
 * la acción programada (que solo recibe la clave) y la función que hace el
 * trabajo real, para poder reutilizar esta última también en el envío
 * síncrono (cuando Action Scheduler no está disponible).
 *
 * @param string $transient_key
 */
function wc_hs_email_logger_log_email_from_transient( $transient_key ) {
	$payload = get_transient( $transient_key );
	delete_transient( $transient_key );

	if ( false === $payload ) {
		error_log( 'HubSpot email log: transient de payload no encontrado o expirado (' . $transient_key . ').' );
		return;
	}

	wc_hs_email_logger_log_email_to_timeline( $payload );
}

/**
 * Resuelve el Access Token de HubSpot. Si el plugin hubspot-mapper está
 * activo, reutiliza su cascada (MGMIT_HS_Contacts::get_token(): constante
 * MGMIT_HS_ACCESS_TOKEN_SECRET -> opción propia del mapper -> opción del
 * bridge). Si no está disponible, replica el mismo orden aquí para poder
 * funcionar de forma independiente en otros sitios.
 *
 * @return string Token o cadena vacía si no está configurado por ninguna vía.
 */
function wc_hs_email_logger_get_token() {
	if ( class_exists( 'MGMIT_HS_Contacts' ) ) {
		return MGMIT_HS_Contacts::get_token();
	}

	if ( defined( 'MGMIT_HS_ACCESS_TOKEN_SECRET' ) && MGMIT_HS_ACCESS_TOKEN_SECRET !== '' ) {
		return MGMIT_HS_ACCESS_TOKEN_SECRET;
	}

	$own = get_option( 'mgmit_mapper_credentials', array() );
	if ( is_array( $own ) && ! empty( $own['access_token'] ) ) {
		return trim( $own['access_token'] );
	}

	$bridge = get_option( 'mgmit_hubspot_credentials', array() );
	if ( is_array( $bridge ) && ! empty( $bridge['access_token'] ) ) {
		return trim( $bridge['access_token'] );
	}

	return '';
}

/**
 * Sube un archivo a HubSpot (Files API) y devuelve su file id, para poder
 * asociarlo como adjunto de un email engagement vía hs_attachment_ids.
 * Requiere el scope `files` en la Private App de HubSpot.
 *
 * @param string $token
 * @param string $file_path Ruta absoluta del archivo en disco.
 * @return string|null
 */
function wc_hs_email_logger_upload_attachment( $token, $file_path ) {

	$file_contents = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $file_contents ) {
		return null;
	}

	$filename  = basename( $file_path );
	$filetype  = wp_check_filetype( $filename );
	$mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream';

	$boundary = wp_generate_password( 24, false );
	$options  = wp_json_encode( array( 'access' => 'PRIVATE' ) );

	$body  = "--{$boundary}\r\n";
	$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
	$body .= "Content-Type: {$mime_type}\r\n\r\n";
	$body .= $file_contents . "\r\n";
	$body .= "--{$boundary}\r\n";
	$body .= "Content-Disposition: form-data; name=\"options\"\r\n\r\n";
	$body .= $options . "\r\n";
	$body .= "--{$boundary}\r\n";
	$body .= "Content-Disposition: form-data; name=\"folderPath\"\r\n\r\n";
	$body .= "/wc-hs-email-logger\r\n";
	$body .= "--{$boundary}--";

	$response = wp_remote_post(
		'https://api.hubapi.com/files/v3/files',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'HubSpot attachment upload: error de conexión - ' . $response->get_error_message() );
		return null;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log( sprintf( 'HubSpot attachment upload: respuesta %d - %s', $code, wp_remote_retrieve_body( $response ) ) );
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	return isset( $data['id'] ) ? $data['id'] : null;
}

/**
 * ============================================================
 * 2) Loguear el correo como "email engagement" en HubSpot,
 *    asociado al contacto correspondiente
 * ============================================================
 */
function wc_hs_email_logger_log_email_to_timeline( $payload ) {

	$token = wc_hs_email_logger_get_token();

	if ( empty( $token ) ) {
		error_log( 'HubSpot email log: no hay token de HubSpot configurado (ni constante, ni opción del mapper, ni opción del bridge).' );
		return;
	}

	$contact_id = wc_hs_email_logger_get_contact_id( $payload['recipient'] );

	if ( ! $contact_id ) {
		error_log(
			sprintf(
				'HubSpot email log: no se encontró contacto en HubSpot para %s (pedido #%d, email %s). No se logueó el correo.',
				$payload['recipient'],
				$payload['order_id'],
				$payload['email_id']
			)
		);
		return;
	}

	$properties = array(
		'hs_timestamp'       => (string) $payload['timestamp'],
		'hs_email_direction' => 'EMAIL', // saliente, enviado desde "nosotros" al contacto
		'hs_email_status'    => 'SENT',
		'hs_email_subject'   => $payload['subject'],
		'hs_email_html'      => $payload['html'],
		'hs_email_text'      => $payload['text'],
	);

	if ( ! empty( $payload['attachment_ids'] ) ) {
		// La API espera los file id separados por ";".
		$properties['hs_attachment_ids'] = implode( ';', $payload['attachment_ids'] );
	}

	$body = array(
		'properties'   => $properties,
		'associations' => array(
			array(
				'to'    => array( 'id' => $contact_id ),
				'types' => array(
					array(
						'associationCategory' => 'HUBSPOT_DEFINED',
						// 198 = Email -> Contact (asociación por defecto de HubSpot).
						// Si tu portal devuelve error de asociación, verifica el ID
						// vigente en GET /crm/v4/associations/emails/contacts/labels
						'associationTypeId'   => 198,
					),
				),
			),
		),
	);

	$response = wp_remote_post(
		'https://api.hubspot.com/crm/v3/objects/emails',
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'HubSpot email log: error de conexión - ' . $response->get_error_message() );
		return;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log(
			sprintf(
				'HubSpot email log: respuesta %d al loguear correo del pedido #%d (%s) — %s',
				$code,
				$payload['order_id'],
				$payload['email_id'],
				wp_remote_retrieve_body( $response )
			)
		);
	}
}

/**
 * ============================================================
 * 3) Buscar el contactId de HubSpot a partir del email del cliente
 * ============================================================
 */
function wc_hs_email_logger_get_contact_id( $email ) {

	$token = wc_hs_email_logger_get_token();
	if ( empty( $token ) ) {
		error_log( 'HubSpot contact lookup: no hay token de HubSpot configurado.' );
		return null;
	}

	$url = add_query_arg(
		array( 'idProperty' => 'email' ),
		'https://api.hubspot.com/crm/v3/objects/contacts/' . rawurlencode( $email )
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'HubSpot contact lookup: error de conexión - ' . $response->get_error_message() );
		return null;
	}

	$code = wp_remote_retrieve_response_code( $response );

	if ( 404 === $code ) {
		return null; // el contacto aún no existe en HubSpot
	}

	if ( $code < 200 || $code >= 300 ) {
		error_log( sprintf( 'HubSpot contact lookup: respuesta %d - %s', $code, wp_remote_retrieve_body( $response ) ) );
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	return isset( $data['id'] ) ? $data['id'] : null;
}
