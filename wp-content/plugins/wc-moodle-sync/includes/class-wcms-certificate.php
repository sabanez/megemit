<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera el certificado PDF dinámico superponiendo nombre y fecha
 * sobre el template del plugin usando GD + pdftoppm.
 */
class WCMS_Certificate {

	private static $instance = null;

	/** @var WC_Logger_Interface */
	private $logger;

	private static $log_context = array( 'source' => 'wc-moodle-sync' );

	const FONT_SCRIPT    = WCMS_PATH . 'assets/fonts/BrushScript.ttf';
	const FONT_ITALIC    = WCMS_PATH . 'assets/fonts/TimesItalic.ttf';
	const TEMPLATE_PNG   = WCMS_PATH . 'assets/certificate-template.png';

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
	 * Genera el PDF del certificado y devuelve la ruta al archivo.
	 *
	 * @param WP_User $wp_user
	 * @return string|WP_Error  Ruta absoluta al PDF o error.
	 */
	public function generate( $wp_user ) {
		$upload_dir = wp_upload_dir();
		$cert_dir   = trailingslashit( $upload_dir['basedir'] ) . 'wcms-certificates';

		if ( ! file_exists( $cert_dir ) ) {
			wp_mkdir_p( $cert_dir );
		}

		// Nombre completo
		$first = trim( $wp_user->first_name );
		$last  = trim( $wp_user->last_name );
		$name  = trim( $first . ' ' . $last );
		if ( $name === '' ) {
			$name = $wp_user->user_login;
		}

		// Fecha en alemán
		$date = $this->format_date_de( time() );

		// Cargar template PNG directamente
		$png_path = self::TEMPLATE_PNG;
		$img      = imagecreatefrompng( $png_path );
		if ( ! $img ) {
			return new WP_Error( 'wcms_cert_gd', 'Error cargando template PNG con GD.' );
		}

		$w    = imagesx( $img );
		$h    = imagesy( $img );
		$dark = imagecolorallocate( $img, 45, 45, 75 );  // color del nombre
		$blue = imagecolorallocate( $img, 29, 95, 165 ); // color de la fecha

		// Nombre en cursiva, centrado — 46% del alto
		$this->draw_centered_text( $img, 46, self::FONT_SCRIPT, $name, $dark, $w, (int) ( $h * 0.46 ) );

		// Fecha en itálica azul, centrada — 72% del alto
		$this->draw_centered_text( $img, 26, self::FONT_ITALIC, $date, $blue, $w, (int) ( $h * 0.72 ) );

		// Guardar PNG modificado en temporal
		$png_path = sys_get_temp_dir() . '/wcms_cert_' . $wp_user->ID . '_' . time() . '.png';
		imagepng( $img, $png_path );
		imagedestroy( $img );

		// Empaquetar PNG en PDF
		$pdf_path = $cert_dir . '/certificate-' . $wp_user->ID . '-' . time() . '.pdf';
		$result   = $this->png_to_pdf( $png_path, $pdf_path, $w, $h );
		@unlink( $png_path );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $pdf_path;
	}

	/**
	 * Dibuja texto centrado horizontalmente en la imagen.
	 */
	private function draw_centered_text( $img, $size, $font, $text, $color, $canvas_w, $y ) {
		$bbox = imagettfbbox( $size, 0, $font, $text );
		$tw   = $bbox[2] - $bbox[0];
		$x    = (int) ( ( $canvas_w - $tw ) / 2 );
		imagettftext( $img, $size, 0, $x, $y, $color, $font, $text );
	}

	/**
	 * Genera un PDF mínimo con el PNG embebido (sin dependencias externas).
	 * Formato PDF 1.4, imagen JPEG embebida para reducir tamaño.
	 *
	 * @param string $png_path
	 * @param string $pdf_path
	 * @param int    $img_w  Píxeles
	 * @param int    $img_h  Píxeles
	 * @return true|WP_Error
	 */
	private function png_to_pdf( $png_path, $pdf_path, $img_w, $img_h ) {
		// Convertir PNG a JPEG temporal para embebido más ligero
		$img      = imagecreatefrompng( $png_path );
		$jpg_path = $png_path . '.jpg';
		imagejpeg( $img, $jpg_path, 92 );
		imagedestroy( $img );

		$jpg_data = file_get_contents( $jpg_path );
		@unlink( $jpg_path );

		if ( ! $jpg_data ) {
			return new WP_Error( 'wcms_cert_jpg', 'Error generando JPEG para PDF.' );
		}

		// Tamaño en puntos PDF (1 punto = 1/72 pulgadas; 150 DPI → 1px = 72/150 pt)
		$pt_w = round( $img_w * 72 / 150, 2 );
		$pt_h = round( $img_h * 72 / 150, 2 );

		$jpg_len = strlen( $jpg_data );

		// Construir PDF mínimo
		$objects = array();

		// Objeto 1: catálogo
		$objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

		// Objeto 2: páginas
		$objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

		// Objeto 3: página
		$objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R"
			. " /MediaBox [0 0 {$pt_w} {$pt_h}]"
			. " /Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>\nendobj\n";

		// Objeto 4: stream de contenido
		$content_stream = "q {$pt_w} 0 0 {$pt_h} 0 0 cm /Im0 Do Q";
		$content_len    = strlen( $content_stream );
		$objects[4]     = "4 0 obj\n<< /Length {$content_len} >>\nstream\n{$content_stream}\nendstream\nendobj\n";

		// Objeto 5: imagen JPEG
		$objects[5] = "5 0 obj\n<< /Type /XObject /Subtype /Image"
			. " /Width {$img_w} /Height {$img_h}"
			. " /ColorSpace /DeviceRGB /BitsPerComponent 8"
			. " /Filter /DCTDecode /Length {$jpg_len} >>\nstream\n";

		// Ensamblar PDF
		$pdf  = "%PDF-1.4\n";
		$offsets = array();

		foreach ( $objects as $num => $obj ) {
			$offsets[ $num ] = strlen( $pdf );
			$pdf .= $obj;
			if ( $num === 5 ) {
				$pdf .= $jpg_data . "\nendstream\nendobj\n";
			}
		}

		// Cross-reference table
		$xref_offset = strlen( $pdf );
		$pdf .= "xref\n0 6\n0000000000 65535 f \n";
		foreach ( $offsets as $offset ) {
			$pdf .= str_pad( $offset, 10, '0', STR_PAD_LEFT ) . " 00000 n \n";
		}

		$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref_offset}\n%%EOF\n";

		$written = file_put_contents( $pdf_path, $pdf );
		if ( ! $written ) {
			return new WP_Error( 'wcms_cert_write', 'No se pudo escribir el PDF.' );
		}

		return true;
	}

	/**
	 * Formatea un timestamp como fecha en alemán: "6. Juni 2026"
	 */
	private function format_date_de( $timestamp ) {
		$months = array(
			1  => 'Januar',  2  => 'Februar', 3  => 'März',
			4  => 'April',   5  => 'Mai',      6  => 'Juni',
			7  => 'Juli',    8  => 'August',   9  => 'September',
			10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
		);
		$d = (int) date( 'j', $timestamp );
		$m = (int) date( 'n', $timestamp );
		$y = date( 'Y', $timestamp );
		return $d . '. ' . $months[ $m ] . ' ' . $y;
	}

}
