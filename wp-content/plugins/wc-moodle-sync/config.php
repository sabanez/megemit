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