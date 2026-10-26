<?php
/**
 * Configuración local del plugin wc-moodle-sync.
 * Editar valores directamente en este archivo (no se sobreescribe en updates de WP core/tema).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conexión a la API REST de Moodle.
 */
if ( ! defined( 'WCMS_MOODLE_API_URL' ) ) {
	define( 'WCMS_MOODLE_API_URL', 'https://onlineakademie.megemit.org/webservice/rest/server.php' );
}
if ( ! defined( 'WCMS_MOODLE_TOKEN' ) ) {
	define( 'WCMS_MOODLE_TOKEN', '4ec6738ce885483d45b64a26d2ee58ff' );
}

/**
 * URL de login de Moodle (puede diferir del subdominio de la API).
 */
if ( ! defined( 'WCMS_MOODLE_LOGIN_URL' ) ) {
	define( 'WCMS_MOODLE_LOGIN_URL', 'https://onlineakademie.megemit.org/login/?lang=de' );
}

/**
 * Token secreto para el webhook de finalización de curso
 * (POST /wp-json/wc-moodle-sync/v1/course-complete).
 */
if ( ! defined( 'WCMS_COMPLETION_SECRET' ) ) {
	define( 'WCMS_COMPLETION_SECRET', 'Qxg898layDlEYEdNaWnrh1NZEjb7bM30i3bFPR5Zq4krUPGiuvHcE0eN0LO9HpCF' );
}

/**
 * URL del Prämienshop para el botón en el email de felicitación.
 */
if ( ! defined( 'WCMS_COUPON_SHOP_URL' ) ) {
	define( 'WCMS_COUPON_SHOP_URL', 'https://megemit.org/produktkategorie/mdlc/' );
}

/**
 * Direcciones en copia (CC) para los emails enviados al alumno
 * (matriculación/acceso, certificado y cupón de finalización).
 * Lista separada por comas. Dejar vacío para no enviar copia.
 */
if ( ! defined( 'WCMS_WELCOME_EMAIL_CC' ) ) {
	define( 'WCMS_WELCOME_EMAIL_CC', 'akademie@megemit.org,santi.bartolome.martinez@labolife.com' );
}

/**
 * Activar/desactivar la generación y envío del certificado PDF
 * cuando el alumno aprueba el examen (exam_passed).
 */
if ( ! defined( 'WCMS_SEND_CERTIFICATE' ) ) {
	define( 'WCMS_SEND_CERTIFICATE', false );
}

/**
 * Activar/desactivar la creación y envío del cupón descuento
 * cuando el alumno completa el curso (course_completed).
 */
if ( ! defined( 'WCMS_SEND_COUPON' ) ) {
	define( 'WCMS_SEND_COUPON', false );
}

/**
 * Activar/desactivar la entrega de titulaciones (certificado por nota mínima
 * del examen, ver WCMS_DEGREES). Independiente de WCMS_SEND_CERTIFICATE y WCMS_SEND_COUPON.
 */
if ( ! defined( 'WCMS_SEND_DEGREE' ) ) {
	define( 'WCMS_SEND_DEGREE', false );
}

/**
 * Titulación: un único certificado que se entrega cuando el alumno cumple TODOS
 * los requisitos de nota mínima de la lista (evento grade_updated).
 *
 *  - template     : plantilla PNG del certificado, relativa a assets/ ('' = plantilla por defecto).
 *  - center_x     : centro horizontal del nombre y la fecha, como fracción del ancho (0.5 = centro del lienzo).
 *  - requirements : exámenes que deben superarse, cada uno con:
 *      name        : etiqueta (solo para logs).
 *      course_id   : curso Moodle que contiene el examen (el del payload del webhook).
 *      cmid        : ID del módulo (course module) del quiz de examen.
 *      min_percent : % mínimo de la nota máxima del examen (80 = 80 %).
 *      max         : puntos totales esperados del examen (solo se usa para avisar si cambian).
 *
 * Cohort 3 (Grundkurse)  -> "GRUNDKURS-EXAMEN" (curso 10, sobre 46)
 * Cohort 2 (Aufbaukurse) -> "AUFBAUKURS-EXAMEN" (curso 9, sobre 42)
 */
if ( ! defined( 'WCMS_DEGREES' ) ) {
	define( 'WCMS_DEGREES', array(
		'template'     => 'certificate-template-degree.png',
		'center_x'     => 0.531,
		'requirements' => array(
			array(
				'name'        => 'Grundkurs',
				'course_id'   => 10,
				'cmid'        => 246,
				'min_percent' => 80,
				'max'         => 46,
			),
			array(
				'name'        => 'Aufbaukurs',
				'course_id'   => 9,
				'cmid'        => 252,
				'min_percent' => 80,
				'max'         => 42,
			),
		),
	) );
}
