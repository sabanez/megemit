<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCMS_Mailer {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Envía email con certificado PDF adjunto al aprobar un examen.
	 *
	 * @param WP_User $wp_user
	 * @param string  $pdf_path  Ruta absoluta al PDF generado.
	 */
	public function send_certificate( $wp_user, $pdf_path ) {
		$subject = 'Ihr MeGeMIT-Zertifikat';
		$body    = $this->build_certificate_html();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: MeGeMIT Online-Akademie <akademie@megemit.org>',
		);

		$attachments = array( $pdf_path );

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		wp_mail( $wp_user->user_email, $subject, $body, $headers, $attachments );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	private function build_certificate_html() {
		ob_start();
		?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,sans-serif;">
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:auto;">
  <tr>
    <td style="padding:30px 35px;color:#1d1c1d;font-size:14px;line-height:1.6;">

      <p style="margin:0 0 16px 0;">
        <strong><em>Gro&szlig;artig!</em></strong>&nbsp;Sie haben erfolgreich beide Examen
        in der Online &ndash; Akademie der MeGeMIT&nbsp;absolviert. Sie erhalten nun hier
        mit gro&szlig;em Trommelwirbel Ihre Urkunde, welches Sie als&nbsp;zertifizierten
        Anwender der Mikroimmuntherapie&nbsp;auszeichnet.&nbsp;<br>
        Lassen Sie Konfetti regnen und die Korken knallen, tanzen Sie auf den Tischen
        und jubeln Sie vor Freude &ndash; wir freuen uns mit Ihnen!
      </p>

      <p style="margin:0 0 16px 0;">&nbsp;</p>

      <p style="margin:0 0 16px 0;"><strong><em>Und hier ist noch lange nicht Schluss!</em></strong></p>

      <p style="margin:0 0 16px 0;">
        Begegnen wir uns doch pers&ouml;nlich &ndash; unsere Veranstaltungen finden Sie
        hier:&nbsp;<a href="https://megemit.org/megemit-akademie/" style="color:#1155cc;">https://megemit.org/megemit-akademie/</a>
      </p>

      <p style="margin:0 0 16px 0;">
        Sie wissen, bei Fragen, W&uuml;nschen, Anregungen &ndash; was immer es auch sei,
        wir sind gern f&uuml;r Sie da!&nbsp;
        <a href="mailto:akademie@megemit.org" style="color:#1155cc;">akademie@megemit.org</a>
      </p>

      <p style="margin:0 0 4px 0;">Ihr Online-Akademie Team</p>
      <p style="margin:0 0 4px 0;">E-mail: <a href="mailto:akademie@megemit.org" style="color:#1155cc;">akademie@megemit.org</a></p>
      <p style="margin:0 0 24px 0;">Tel: +43 664 164 6894</p>

      <p style="margin:0;font-size:11px;color:#888888;line-height:1.4;">
        The information contained in this e-mail is confidential and may be legally
        privileged. It is intended only for the addressee(s). Any person other than
        the addressee(s) is prohibited to reproduce, use, disclose or print this content.
        If you have received this email in error, please notify immediately the sender
        and delete the original message (including any attached files). Thank you.
      </p>

    </td>
  </tr>
</table>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Envía email de bienvenida al alumno tras la matriculación.
	 *
	 * @param WP_User $wp_user
	 * @param string  $username
	 * @param string  $password    Vacío si el usuario ya existía en Moodle.
	 * @param bool    $is_new      true = cuenta Moodle recién creada.
	 * @param array   $course_names  Nombres de los cursos matriculados.
	 */
	public function send_welcome( $wp_user, $username, $password, $is_new, $course_names = array() ) {
		$login_url   = WCMS_MOODLE_LOGIN_URL;
		$courses_str = ! empty( $course_names ) ? implode( ', ', $course_names ) : '';

		$subject = $is_new
			? 'Ihre Zugangsdaten für die MeGeMIT Online-Akademie'
			: 'Ihr neuer Kurs in der MeGeMIT Online-Akademie';

		$body = $this->build_html( $wp_user, $username, $password, $is_new, $courses_str, $login_url );

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		wp_mail(
			$wp_user->user_email,
			$subject,
			$body,
			array(
				'Content-Type: text/html; charset=UTF-8',
				'From: MeGeMIT Online-Akademie <akademie@megemit.org>',
			)
		);

		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Envía email de felicitación con cupón descuento tras completar un curso.
	 *
	 * @param WP_User $wp_user
	 * @param string  $coupon_code
	 */
	public function send_course_completion( $wp_user, $coupon_code ) {
		$subject = 'Ihr Prämien-Gutschein von der MeGeMIT Online-Akademie';
		$body    = $this->build_completion_html( $coupon_code );

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		wp_mail(
			$wp_user->user_email,
			$subject,
			$body,
			array(
				'Content-Type: text/html; charset=UTF-8',
				'From: MeGeMIT Online-Akademie <akademie@megemit.org>',
			)
		);

		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	private function build_completion_html( $coupon_code ) {
		$shop_url    = defined( 'WCMS_COUPON_SHOP_URL' ) ? WCMS_COUPON_SHOP_URL : 'https://megemit.org/produktkategorie/mdlc/';
		$logo_url    = 'https://megemit.org/wp-content/uploads/2025/06/CNCL_Logo.png';
		$mascot_url  = 'https://megemit.org/wp-content/uploads/2025/07/Oh-Meggy.jpg';

		ob_start();
		?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,sans-serif;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;border-spacing:0px;background-color:#ffffff;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:auto;max-width:800px;min-width:320px;border-collapse:collapse;border-spacing:0px;">

  <!-- Logo -->
  <tr>
    <td align="center" style="padding:18px 12px;">
      <img src="<?php echo esc_url( $logo_url ); ?>" alt="MeGeMIT Akademie"
           width="154" height="58" style="height:auto;display:block;border:0px;" />
    </td>
  </tr>

  <!-- Título -->
  <tr>
    <td align="center" style="padding:14px 12px 0;">
      <h1 style="margin:0;font-size:34pt;font-family:'Arial Black',Arial,sans-serif;font-weight:700;color:#006ab4;text-align:center;line-height:1.2;">
        HERZLICHEN GL&Uuml;CKWUNSCH
      </h1>
      <p style="margin:12pt 0 0;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;text-align:center;line-height:1.5;">
        SIE HABEN GEN&Uuml;GEND PUNKTE IN DER ONLINE-AKADEMIE ERREICHT, UM SICH
        <strong>EINE</strong> PR&Auml;MIE AUSZUSUCHEN!
      </p>
    </td>
  </tr>

  <!-- Imagen mascota -->
  <tr>
    <td style="padding:18px 0 0;">
      <img src="<?php echo esc_url( $mascot_url ); ?>" alt="Oh Meggy"
           width="498" style="height:auto;display:block;width:100%;border:0px;" />
    </td>
  </tr>

  <!-- Bloque gris — código -->
  <tr>
    <td align="center" style="background-color:#dadada;padding:18px 12px;">
      <p style="margin:0;font-family:Arial,sans-serif;font-size:12pt;font-weight:700;color:#006ab4;text-align:center;line-height:1.5;">
        IHR GUTSCHEIN-CODE: <?php echo esc_html( $coupon_code ); ?>
      </p>
    </td>
  </tr>

  <!-- Bloque naranja — botón -->
  <tr>
    <td align="center" style="background-color:#ef7d00;padding:18px 12px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr>
          <td align="center" style="background-color:#f9f9f9;border:1px solid #006ab4;border-radius:4px;padding:0 12px;height:45px;line-height:45px;">
            <a href="<?php echo esc_url( $shop_url ); ?>"
               style="color:#006ab4;font-family:Arial,sans-serif;font-size:12pt;font-weight:400;text-decoration:none;white-space:nowrap;display:block;padding:0 12px;">
              ZUM PR&Auml;MIENSHOP
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Contacto -->
  <tr>
    <td style="padding:24px 12px 12px;">
      <p style="margin:0 0 12pt;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;line-height:1.5;">
        Bei Fragen, k&ouml;nnen Sie uns gerne wie folgt kontaktieren:&nbsp;
      </p>
      <p style="margin:0 0 12pt;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;line-height:1.5;">
        E-Mail: <a href="mailto:akademie@megemit.org" style="color:#006ab4;text-decoration:underline;">akademie@megemit.org</a>
      </p>
      <p style="margin:0 0 12pt;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;line-height:1.5;">
        Telefon: +43 664 164 689 4
      </p>
      <p style="margin:12pt 0 0;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;line-height:1.5;">
        Herzliche Gr&uuml;&szlig;e,&nbsp;
      </p>
      <p style="margin:12pt 0 24px;font-family:Arial,sans-serif;font-size:12pt;color:#006ab4;line-height:1.5;">
        Ihr Online-Akademie Team
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	public function set_html_content_type() {
		return 'text/html';
	}

	private function build_html( $wp_user, $username, $password, $is_new, $courses_str, $login_url ) {
		$firstname = ! empty( $wp_user->first_name ) ? $wp_user->first_name : '';

		ob_start();
		?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;">
<tr><td align="center" style="padding:20px 0;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;max-width:600px;">

  <!-- Header image -->
  <tr>
    <td>
      <img src="https://megemit.org/wp-content/uploads/2025/01/Geballtes_Wissen.jpg"
           width="600" style="display:block;width:100%;max-width:600px;" alt="MeGeMIT Akademie" />
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="padding:30px 35px;color:#1d1c1d;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;">

		<?php if ( $is_new ) : ?>
      <p style="margin:0 0 6px 0;">Se ha creado un acceso para ti a la academia en línea MeGeMIT.</p>
		<?php else : ?>
      <p style="margin:0 0 6px 0;">Se ha matriculado tu usuario en la academia en línea MeGeMIT.</p>
		<?php endif; ?>

		<?php if ( ! empty( $courses_str ) ) : ?>
      <p style="margin:0 0 6px 0;">Se le ha concedido acceso al siguiente curso:</p>
      <p style="margin:0 0 20px 0;"><?php echo esc_html( $courses_str ); ?></p>
		<?php endif; ?>

		<?php if ( $is_new ) : ?>
      <p style="margin:0 0 6px 0;">Estos son sus datos de inicio de sesión preliminares:</p>
      <p style="margin:0 0 4px 0;"><strong>Nombre de usuario:</strong> <?php echo esc_html( $username ); ?></p>
      <p style="margin:0 0 25px 0;"><strong>Contraseña:</strong> <?php echo esc_html( $password ); ?></p>
		<?php endif; ?>

      <!-- TUS PRIMEROS PASOS -->
      <p style="margin:0 0 15px 0;">
        <strong style="color:#0b5394;">TUS PRIMEROS PASOS:</strong>
      </p>

      <!-- ACCESO -->
      <p style="margin:0 0 6px 0;">
        <strong style="background-color:#ff9900;padding:1px 4px;">ACCESO:</strong>&nbsp;
        Inicie sesión con sus datos de acceso indicados anteriormente a través del siguiente enlace:
      </p>
		<?php if ( ! empty( $login_url ) ) : ?>
      <p style="margin:0 0 6px 0;">
        <a href="<?php echo esc_url( $login_url ); ?>" style="color:#1155cc;">
          <?php echo esc_html( $login_url ); ?>
        </a>
      </p>
		<?php endif; ?>
		<?php if ( $is_new ) : ?>
      <p style="margin:0 0 25px 0;">
        Deberás cambiar tu contraseña al iniciar sesión por primera vez, por lo que solo será válida para ese primer inicio de sesión.
        Te recomendamos ver este
        <a href="https://vimeo.com/1101819608/aa7456e267?share=copy" style="color:#1155cc;"><strong>video tutorial</strong></a>
        para comenzar.
      </p>
		<?php else : ?>
      <p style="margin:0 0 25px 0;">Accede con tus credenciales habituales.</p>
		<?php endif; ?>

      <!-- APOYO -->
      <p style="margin:0 0 6px 0;">
        <strong style="background-color:#ff9900;padding:1px 4px;">APOYO:</strong>&nbsp;
        Si necesita ayuda o asistencia inmediata, no dude en ponerse en contacto con la academia en línea
        por teléfono o correo electrónico (le responderemos lo antes posible durante el horario laboral):
      </p>
      <p style="margin:0 0 4px 0;">Correo electrónico: <a href="mailto:akademie@megemit.org" style="color:#1155cc;">akademie@megemit.org</a></p>
      <p style="margin:0 0 25px 0;">Teléfono: <a href="tel:+436641646894" style="color:#1155cc;">+43 664 1646894</a></p>

      <p style="margin:0 0 20px 0;"><strong><em>¡Les deseamos mucha diversión y valiosas perspectivas!</em></strong></p>

      <p style="margin:0 0 20px 0;">El equipo de tu academia online</p>

      <!-- Footer links -->
      <p style="margin:0 0 4px 0;font-size:13px;color:#1d1c1d;">
        <em>Iniciar sesión:
          <?php if ( ! empty( $login_url ) ) : ?>
          <a href="<?php echo esc_url( $login_url ); ?>" style="color:#1155cc;">www.onlineakademie.megemit.org</a>
          <?php else : ?>
          www.onlineakademie.megemit.org
          <?php endif; ?>
        </em>
      </p>
      <p style="margin:0 0 4px 0;font-size:13px;color:#1d1c1d;">
        <em>Correo electrónico: <a href="mailto:akademie@megemit.org" style="color:#1155cc;">akademie@megemit.org</a></em>
      </p>
      <p style="margin:0;font-size:13px;color:#1d1c1d;">
        <em>Teléfono: +43 664 164 689 4</em>
      </p>

    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
		<?php
		return ob_get_clean();
	}
}
