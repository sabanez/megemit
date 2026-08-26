<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCMS_Moodle_Api {

	private static $instance = null;

	/** @var WC_Logger_Interface */
	private $logger;

	private static $log_context = array( 'source' => 'wc-moodle-sync' );

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger = wc_get_logger();
	}

	/**
	 * Busca un usuario en Moodle por username.
	 *
	 * @param string $username
	 * @return int|false  ID de usuario Moodle o false si no existe.
	 */
	public function find_user( $username, $email = '' ) {
		$this->logger->debug( "Buscando usuario en Moodle por username: {$username}", self::$log_context );

		$res = $this->request( 'core_user_get_users_by_field', array(
			'field'  => 'username',
			'values' => array( $username ),
		) );

		if ( is_array( $res ) && ! isset( $res['exception'] ) && ! empty( $res ) ) {
			$this->logger->info( "find_user: encontrado por username '{$username}' con ID {$res[0]['id']}.", self::$log_context );
			return (int) $res[0]['id'];
		}

		// Fallback: buscar por email si se proporcionó.
		if ( ! empty( $email ) ) {
			$this->logger->debug( "find_user: username no encontrado, buscando por email: {$email}", self::$log_context );

			$res_email = $this->request( 'core_user_get_users_by_field', array(
				'field'  => 'email',
				'values' => array( $email ),
			) );

			if ( is_array( $res_email ) && ! isset( $res_email['exception'] ) && ! empty( $res_email ) ) {
				$this->logger->info( "find_user: encontrado por email '{$email}' con ID {$res_email[0]['id']} (username en Moodle: {$res_email[0]['username']}).", self::$log_context );
				return (int) $res_email[0]['id'];
			}
		}

		$this->logger->debug( "find_user({$username}): usuario no existe en Moodle.", self::$log_context );
		return false;
	}

	/**
	 * Crea un usuario en Moodle.
	 *
	 * @param WP_User $wp_user
	 * @param string  $username
	 * @param string  $password
	 * @return int|false  ID de usuario Moodle o false si falla.
	 */
	public function create_user( $wp_user, $username, $password ) {
		$firstname = ! empty( $wp_user->first_name ) ? $wp_user->first_name : 'Estudiante';
		$lastname  = ! empty( $wp_user->last_name )  ? $wp_user->last_name  : $username;

		$this->logger->info( "Creando usuario en Moodle: {$username} / {$wp_user->user_email}", self::$log_context );

		$res = $this->request( 'core_user_create_users', array(
			'users' => array(
				array(
					'username'  => $username,
					'password'  => $password,
					'firstname' => $firstname,
					'lastname'  => $lastname,
					'email'     => $wp_user->user_email,
					'auth'      => 'manual',
				),
			),
		) );

		if ( ! is_array( $res ) ) {
			$this->logger->error( "create_user({$username}): respuesta inválida de la API.", self::$log_context );
			return false;
		}

		if ( isset( $res['exception'] ) ) {
			$debuginfo = isset( $res['debuginfo'] ) ? $res['debuginfo'] : 'sin debuginfo';
			$this->logger->error( "create_user({$username}): excepción Moodle — {$res['message']} (errorcode: {$res['errorcode']}) | debuginfo: {$debuginfo}", self::$log_context );

			// Moodle rechaza la creación porque el username o el email ya existen (cuenta creada
			// previamente fuera de este plugin). find_user() no lo había localizado, pero Moodle
			// acaba de confirmar que sí existe: recuperar su ID en lugar de bloquear el checkout.
			if ( false !== stripos( $debuginfo, 'already' ) || false !== stripos( $res['message'], 'already' ) ) {
				$this->logger->warning( "create_user({$username}): usuario ya existente según Moodle, reintentando búsqueda.", self::$log_context );
				return $this->find_user( $username, $wp_user->user_email );
			}

			return false;
		}

		if ( empty( $res ) ) {
			$this->logger->error( "create_user({$username}): Moodle devolvió array vacío.", self::$log_context );
			return false;
		}

		$this->logger->info( "create_user({$username}): creado con ID {$res[0]['id']}.", self::$log_context );
		return (int) $res[0]['id'];
	}

	/**
	 * Añade a un usuario a uno o varios cohorts de Moodle.
	 *
	 * La matriculación real a los cursos/módulos la resuelve Moodle mediante el método
	 * de matriculación "Sincronización de cohortes" configurado en cada curso — este
	 * método no matricula directamente a ningún curso, solo gestiona la pertenencia al cohort.
	 * Añadir a un usuario a un cohort del que ya es miembro no da error en Moodle (operación idempotente).
	 *
	 * @param int   $moodle_user_id
	 * @param array $cohort_ids  Array de IDs (enteros) de cohorts de Moodle.
	 * @return bool
	 */
	public function add_to_cohort( $moodle_user_id, $cohort_ids ) {
		$this->logger->info( "Añadiendo usuario Moodle #{$moodle_user_id} a cohorts: " . implode( ', ', $cohort_ids ), self::$log_context );

		$members = array();
		foreach ( $cohort_ids as $cohort_id ) {
			$members[] = array(
				'cohorttype' => array(
					'type'  => 'id',
					'value' => (string) (int) $cohort_id,
				),
				'usertype' => array(
					'type'  => 'id',
					'value' => (string) (int) $moodle_user_id,
				),
			);
		}

		$res = $this->request( 'core_cohort_add_cohort_members', array(
			'members' => $members,
		) );

		if ( is_array( $res ) && isset( $res['exception'] ) ) {
			$this->logger->error( "add_to_cohort(#{$moodle_user_id}): excepción Moodle — {$res['message']} (errorcode: {$res['errorcode']})", self::$log_context );
			return false;
		}

		$this->logger->info( "add_to_cohort(#{$moodle_user_id}): añadido a cohort(s) correctamente.", self::$log_context );
		return true;
	}

	/**
	 * Realiza una petición POST a la API REST de Moodle.
	 *
	 * @param string $function
	 * @param array  $params
	 * @return array|false
	 */
	private function request( $function, $params ) {
		if ( empty( WCMS_MOODLE_API_URL ) || empty( WCMS_MOODLE_TOKEN ) ) {
			$this->logger->critical( 'WCMS_MOODLE_API_URL o WCMS_MOODLE_TOKEN no están definidos en wp-config.php.', self::$log_context );
			return false;
		}

		$body = array_merge(
			array(
				'wstoken'            => WCMS_MOODLE_TOKEN,
				'wsfunction'         => $function,
				'moodlewsrestformat' => 'json',
			),
			$params
		);

		$body_log = $body;
		$body_log['wstoken'] = '***';
		$this->logger->debug( "API request → {$function} | URL: " . WCMS_MOODLE_API_URL . " | Params: " . http_build_query( $body_log ), self::$log_context );

		$response = wp_remote_post( WCMS_MOODLE_API_URL, array(
			'body'    => http_build_query( $body ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( "HTTP error en {$function}: " . $response->get_error_message(), self::$log_context );
			return false;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );

		$this->logger->debug( "API response ← {$function} | HTTP {$http_code} | Body: {$raw_body}", self::$log_context );

		$decoded = json_decode( $raw_body, true );

		if ( ! is_array( $decoded ) ) {
			$this->logger->error( "Respuesta no JSON de Moodle en {$function}. HTTP {$http_code}. Body: {$raw_body}", self::$log_context );
			return false;
		}

		return $decoded;
	}
}
