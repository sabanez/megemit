<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook receptor: Moodle notifica la finalización de un curso.
 * Genera un cupón WooCommerce único y envía email de felicitación.
 *
 * Endpoint: POST /wp-json/wc-moodle-sync/v1/course-complete
 * Header:   Authorization: Bearer <WCMS_COMPLETION_SECRET>
 * Body:     { "moodle_user_id": 123, "moodle_course_id": 456 }
 *
 * En wp-config.php:
 *   define( 'WCMS_COMPLETION_SECRET', 'tu_token_secreto' );
 *   define( 'WCMS_COUPON_SHOP_URL',   'https://megemit.org/produktkategorie/mdlc/' );
 */
class WCMS_Completion_Handler {

	private static $instance = null;

	/** @var WC_Logger_Interface */
	private $logger;

	private static $log_context = array( 'source' => 'wc-moodle-sync' );

	/**
	 * Mapeo estático moodle_course_id => valor a guardar en la meta
	 * 'exams_and_certificates' del usuario WP al completar ese curso.
	 */
	/**
	 * Mapeo: moodle_section_id (sección dentro del curso "Examen", ID 5)
	 * => valor a guardar en 'exams_and_certificates'.
	 * Se recibe como 'moodle_course_id' en el payload del webhook.
	 *
	 * Sección 6 = Grundkurs | Sección 7 = Aufbaukurs
	 */
	private static $course_exam_map = array(
		6 => 'Grundkurs (Basic course)',
		7 => 'Aufbaukurs (Advanced course)',
	);

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger = wc_get_logger();
		add_action( 'rest_api_init', array( $this, 'register_endpoint' ) );
	}

	public function register_endpoint() {
		register_rest_route(
			'wc-moodle-sync/v1',
			'/course-complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'authenticate' ),
			)
		);
	}

	/**
	 * Valida el token Bearer en el header Authorization.
	 */
	public function authenticate( WP_REST_Request $request ) {
		$secret = defined( 'WCMS_COMPLETION_SECRET' ) ? WCMS_COMPLETION_SECRET : '';
		if ( empty( $secret ) ) {
			$this->logger->error( 'WCMS_COMPLETION_SECRET no definido en wp-config.php.', self::$log_context );
			return new WP_Error( 'wcms_no_secret', 'Endpoint no configurado.', array( 'status' => 500 ) );
		}

		$auth   = $request->get_header( 'authorization' );
		$token  = '';
		if ( $auth && strpos( $auth, 'Bearer ' ) === 0 ) {
			$token = substr( $auth, 7 );
		}

		if ( ! hash_equals( $secret, $token ) ) {
			$this->logger->warning( 'course-complete: token inválido.', self::$log_context );
			return new WP_Error( 'wcms_unauthorized', 'Token inválido.', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Procesa la notificación de curso completado.
	 */
	public function handle( WP_REST_Request $request ) {
		$params         = $request->get_json_params();
		$moodle_user_id   = isset( $params['moodle_user_id'] )   ? (int) $params['moodle_user_id']   : 0;
		$moodle_course_id = isset( $params['moodle_course_id'] ) ? (int) $params['moodle_course_id'] : 0;
		$event_type       = isset( $params['event_type'] )       ? sanitize_key( $params['event_type'] ) : 'course_completed';

		if ( ! $moodle_user_id || ! $moodle_course_id ) {
			return new WP_Error( 'wcms_bad_request', 'moodle_user_id y moodle_course_id son obligatorios.', array( 'status' => 400 ) );
		}

		// Localizar usuario WP por su Moodle ID guardado en user_meta.
		$users = get_users( array(
			'meta_key'   => '_wcms_moodle_user_id',
			'meta_value' => $moodle_user_id,
			'number'     => 1,
			'fields'     => 'all',
		) );

		if ( empty( $users ) ) {
			$this->logger->error( "course-complete: no se encontró usuario WP con _wcms_moodle_user_id={$moodle_user_id}.", self::$log_context );
			return new WP_Error( 'wcms_user_not_found', 'Usuario no encontrado.', array( 'status' => 404 ) );
		}

		$wp_user = $users[0];

		if ( $event_type === 'exam_passed' ) {
			return $this->handle_exam_passed( $wp_user, $moodle_course_id );
		}

		if ( $event_type === 'grade_updated' ) {
			return $this->handle_grade_updated( $wp_user, $moodle_course_id );
		}

		$this->update_exams_and_certificates_meta( $wp_user, $moodle_course_id );

		// Flujo course_completed: cupón descuento + email de felicitación.
		if ( ! WCMS_SEND_COUPON ) {
			$this->logger->info( "course-complete: WCMS_SEND_COUPON desactivado. Ignorado para user #{$wp_user->ID}.", self::$log_context );
			return rest_ensure_response( array( 'status' => 'disabled' ) );
		}

		$meta_key = '_wcms_coupon_course_' . $moodle_course_id;

		$existing = get_user_meta( $wp_user->ID, $meta_key, true );
		if ( $existing ) {
			$this->logger->info( "course-complete: cupón ya emitido para user #{$wp_user->ID} curso #{$moodle_course_id}. Ignorado.", self::$log_context );
			return rest_ensure_response( array( 'status' => 'already_issued', 'coupon' => $existing ) );
		}

		$coupon_code = $this->create_coupon( $wp_user );
		if ( is_wp_error( $coupon_code ) ) {
			$this->logger->error( "course-complete: error creando cupón para user #{$wp_user->ID}: " . $coupon_code->get_error_message(), self::$log_context );
			return $coupon_code;
		}

		update_user_meta( $wp_user->ID, $meta_key, $coupon_code );
		WCMS_Mailer::get_instance()->send_course_completion( $wp_user, $coupon_code );
		$this->logger->info( "course-complete: cupón {$coupon_code} emitido y email enviado a {$wp_user->user_email}.", self::$log_context );

		return rest_ensure_response( array( 'status' => 'ok', 'coupon' => $coupon_code ) );
	}

	/**
	 * Añade el valor mapeado del curso a la meta 'exams_and_certificates'
	 * del usuario, sin duplicados. No-op si el curso no está en el mapeo.
	 *
	 * @param WP_User $wp_user
	 * @param int     $moodle_course_id
	 * @return void
	 */
	private function update_exams_and_certificates_meta( $wp_user, $moodle_course_id ) {
		if ( ! isset( self::$course_exam_map[ $moodle_course_id ] ) ) {
			$this->logger->info( "exams_and_certificates: curso #{$moodle_course_id} no mapeado. Ignorado para user #{$wp_user->ID}.", self::$log_context );
			return;
		}

		$value = self::$course_exam_map[ $moodle_course_id ];

		$current = get_user_meta( $wp_user->ID, 'exams_and_certificates', true );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		if ( in_array( $value, $current, true ) ) {
			$this->logger->info( "exams_and_certificates: '{$value}' ya presente para user #{$wp_user->ID}. Ignorado.", self::$log_context );
			return;
		}

		$current[] = $value;
		update_user_meta( $wp_user->ID, 'exams_and_certificates', $current );
		$this->logger->info( "exams_and_certificates: '{$value}' añadido para user #{$wp_user->ID}.", self::$log_context );
	}

	/**
	 * Flujo exam_passed: genera certificado PDF y envía email con adjunto.
	 *
	 * @param WP_User $wp_user
	 * @param int     $moodle_course_id
	 * @return WP_REST_Response|WP_Error
	 */
	private function handle_exam_passed( $wp_user, $moodle_course_id ) {
		if ( ! WCMS_SEND_CERTIFICATE ) {
			$this->logger->info( "exam_passed: WCMS_SEND_CERTIFICATE desactivado. Ignorado para user #{$wp_user->ID}.", self::$log_context );
			return rest_ensure_response( array( 'status' => 'disabled' ) );
		}

		$meta_key = '_wcms_cert_course_' . $moodle_course_id;

		$existing = get_user_meta( $wp_user->ID, $meta_key, true );
		if ( $existing ) {
			$this->logger->info( "exam_passed: certificado ya emitido para user #{$wp_user->ID} curso #{$moodle_course_id}. Ignorado.", self::$log_context );
			return rest_ensure_response( array( 'status' => 'already_issued' ) );
		}

		$pdf_path = WCMS_Certificate::get_instance()->generate( $wp_user );
		if ( is_wp_error( $pdf_path ) ) {
			$this->logger->error( "exam_passed: error generando certificado para user #{$wp_user->ID}: " . $pdf_path->get_error_message(), self::$log_context );
			return $pdf_path;
		}

		update_user_meta( $wp_user->ID, $meta_key, basename( $pdf_path ) );
		WCMS_Mailer::get_instance()->send_certificate( $wp_user, $pdf_path );
		$this->logger->info( "exam_passed: certificado generado y email enviado a {$wp_user->user_email}.", self::$log_context );

		return rest_ensure_response( array( 'status' => 'ok', 'certificate' => basename( $pdf_path ) ) );
	}

	/**
	 * Umbrales de % de nota combinado que otorgan cupón de bonificación,
	 * en orden ascendente (importante para no saltarse el 40% si el update
	 * llega ya con el alumno por encima del 80%).
	 */
	private static $bonus_thresholds = array( 40, 80 );

	/**
	 * Máximo de cupones activos (sin usar) que un cliente puede tener a la vez,
	 * contando todos los cupones emitidos por este plugin (finalización + bonificación).
	 */
	private static $max_active_coupons = 2;

	/**
	 * Flujo grade_updated: recalcula el % de nota global combinado del alumno
	 * (suma de graderaw / suma de grademax de todos los módulos de todos los
	 * cursos matriculados) y, si cruza el umbral del 40% o 80% por primera vez
	 * y no ha alcanzado el límite de cupones activos, emite un cupón de bonificación.
	 *
	 * @param WP_User $wp_user
	 * @param int     $moodle_course_id
	 * @return WP_REST_Response|WP_Error
	 */
	private function handle_grade_updated( $wp_user, $moodle_course_id ) {
		if ( ! WCMS_SEND_COUPON ) {
			$this->logger->info( "grade_updated: WCMS_SEND_COUPON desactivado. Ignorado para user #{$wp_user->ID}.", self::$log_context );
			return rest_ensure_response( array( 'status' => 'disabled' ) );
		}

		$percent = $this->calc_combined_grade_percent( $wp_user );
		if ( false === $percent ) {
			$this->logger->info( "grade_updated: no se pudo calcular el % combinado para user #{$wp_user->ID} (sin cursos matriculados o sin items calificados aún).", self::$log_context );
			return rest_ensure_response( array( 'status' => 'no_data' ) );
		}

		$this->logger->info( "grade_updated: user #{$wp_user->ID} % combinado = " . round( $percent, 2 ) . '%.', self::$log_context );

		$issued = array();

		foreach ( self::$bonus_thresholds as $threshold ) {
			if ( $percent < $threshold ) {
				continue;
			}

			$meta_key = "_wcms_bonus_{$threshold}_coupon";
			if ( get_user_meta( $wp_user->ID, $meta_key, true ) ) {
				continue; // Ya emitido para este umbral.
			}

			if ( $this->count_active_coupons( $wp_user->user_email ) >= self::$max_active_coupons ) {
				$this->logger->info( "grade_updated: límite de " . self::$max_active_coupons . " cupones activos alcanzado para user #{$wp_user->ID}. Umbral {$threshold}% no otorgado.", self::$log_context );
				continue;
			}

			$coupon_code = $this->create_coupon( $wp_user );
			if ( is_wp_error( $coupon_code ) ) {
				$this->logger->error( "grade_updated: error creando cupón de bonificación {$threshold}% para user #{$wp_user->ID}: " . $coupon_code->get_error_message(), self::$log_context );
				continue;
			}

			update_user_meta( $wp_user->ID, $meta_key, $coupon_code );
			WCMS_Mailer::get_instance()->send_bonus_coupon( $wp_user, $coupon_code, $threshold );
			$this->logger->info( "grade_updated: cupón de bonificación {$coupon_code} ({$threshold}%) emitido y email enviado a {$wp_user->user_email}.", self::$log_context );
			$issued[ $threshold ] = $coupon_code;
		}

		return rest_ensure_response( array(
			'status'         => 'ok',
			'percent'        => round( $percent, 2 ),
			'coupons_issued' => $issued,
		) );
	}

	/**
	 * Calcula el % de nota combinado del alumno: suma de las notas obtenidas
	 * entre suma de las notas máximas, de todos los módulos (itemtype 'mod')
	 * de todos los cursos en los que está matriculado en Moodle. Se excluye
	 * el item de tipo 'course' (total agregado) para no depender del método
	 * de agregación configurado en cada curso.
	 *
	 * @param WP_User $wp_user
	 * @return float|false  Porcentaje (0-100+) o false si no se puede calcular.
	 */
	private function calc_combined_grade_percent( $wp_user ) {
		$moodle_user_id = (int) get_user_meta( $wp_user->ID, '_wcms_moodle_user_id', true );
		if ( ! $moodle_user_id ) {
			$this->logger->warning( "calc_combined_grade_percent: user #{$wp_user->ID} sin _wcms_moodle_user_id.", self::$log_context );
			return false;
		}

		$api     = WCMS_Moodle_Api::get_instance();
		$courses = $api->get_user_courses( $moodle_user_id );
		if ( empty( $courses ) ) {
			return false;
		}

		$sum_obtained = 0.0;
		$sum_max      = 0.0;

		foreach ( $courses as $course ) {
			$course_id = isset( $course['id'] ) ? (int) $course['id'] : 0;
			if ( ! $course_id ) {
				continue;
			}

			$items = $api->get_course_grade_items( $moodle_user_id, $course_id );
			foreach ( $items as $item ) {
				if ( ! isset( $item['itemtype'] ) || 'mod' !== $item['itemtype'] ) {
					continue; // Excluye el total del curso y otros items no calificables por módulo.
				}
				if ( ! isset( $item['graderaw'] ) || null === $item['graderaw'] ) {
					continue; // Módulo aún sin calificar.
				}
				if ( empty( $item['grademax'] ) ) {
					continue;
				}

				$sum_obtained += (float) $item['graderaw'];
				$sum_max      += (float) $item['grademax'];
			}
		}

		if ( $sum_max <= 0 ) {
			return false;
		}

		return ( $sum_obtained / $sum_max ) * 100;
	}

	/**
	 * Cuenta los cupones activos (sin usar) emitidos por este plugin para un email.
	 *
	 * @param string $email
	 * @return int
	 */
	private function count_active_coupons( $email ) {
		$coupons = wc_get_coupons( array(
			'limit'           => -1,
			'customer_email'  => $email,
		) );

		$count = 0;
		foreach ( $coupons as $coupon ) {
			if ( 0 !== stripos( $coupon->get_code(), 'megemit-' ) ) {
				continue; // Solo cupones emitidos por este plugin.
			}
			if ( $coupon->get_usage_count() < 1 ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Crea un cupón WooCommerce único de un solo uso al 100%.
	 *
	 * @param WP_User $wp_user
	 * @return string|WP_Error  Código del cupón o error.
	 */
	private function create_coupon( $wp_user ) {
		$code = 'MeGeMIT-' . strtoupper( wp_generate_password( 8, false, false ) );

		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 100 );
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit( 1 );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->set_email_restrictions( array( $wp_user->user_email ) );
		$first = trim( $wp_user->first_name );
		$last  = trim( $wp_user->last_name );
		$name  = trim( $first . ' ' . $last );
		$label = $name !== '' ? $name : $wp_user->user_login;
		$coupon->set_description( 'Akademie-Coupon f' . "\xc3\xbc" . 'r ' . $label );

		$cat_ids = $this->get_category_ids( array( 'MDL-Coupon' ) );
		if ( ! empty( $cat_ids ) ) {
			$coupon->set_product_categories( $cat_ids );
		} else {
			$this->logger->warning( 'create_coupon: categoría "MDL-Coupon" no encontrada en WooCommerce. El cupón se creará sin restricción de categoría.', self::$log_context );
		}

		$result = $coupon->save();
		if ( ! $result ) {
			return new WP_Error( 'wcms_coupon_save', 'No se pudo guardar el cupón.' );
		}

		return $code;
	}

	/**
	 * Devuelve los term_ids de las categorías de producto por slug o nombre.
	 *
	 * @param string[] $names  Nombres o slugs de categorías.
	 * @return int[]
	 */
	private function get_category_ids( $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$term = get_term_by( 'name', $name, 'product_cat' );
			if ( ! $term ) {
				$term = get_term_by( 'slug', sanitize_title( $name ), 'product_cat' );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}
		return $ids;
	}
}
