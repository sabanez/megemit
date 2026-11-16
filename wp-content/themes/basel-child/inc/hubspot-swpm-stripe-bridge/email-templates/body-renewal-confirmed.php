<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas:
 * $greeting_line  — "Sehr geehrte Frau ..." / "Sehr geehrter Herr ..." / fallback
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #3a3a3a;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    background: #ffffff;
}
.email-wrap {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}
.header-img {
    display: block;
    width: 100%;
    max-width: 480px;
    margin: 0 auto 24px auto;
}
p {
    margin: 0 0 12px 0;
}
.signature-block {
    margin-top: 24px;
}
.signature-block img {
    display: block;
    max-width: 200px;
    margin-top: 8px;
}
.footer-info {
    margin-top: 24px;
    font-size: 8.5pt;
    color: #0A78B8;
    line-height: 1.8;
    border-top: 1px solid #cccccc;
    padding-top: 12px;
}
</style>
</head>
<body>
<div class="email-wrap">

<?php if ( ! empty( $header_img_url ) ) : ?>
<img class="header-img" src="<?php echo esc_url( $header_img_url ); ?>" alt="MeGeMIT" />
<?php endif; ?>

<p><?php echo esc_html( $greeting_line ); ?>,</p>

<p>Wir freuen uns sehr, dass Sie weiterhin Mitglied der MeGeMIT sind und bedanken uns f&uuml;r die
Fortsetzung Ihrer Mitgliedschaft.</p>

<p>Anliegend finden Sie die Rechnung f&uuml;r Ihre Buchhaltung.</p>

<p>&nbsp;</p>

<p>Herzliche Gr&uuml;&szlig;e</p>
<p>Ihr MeGeMIT-Team</p>

<div class="signature-block">
    <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/inc/hubspot-swpm-stripe-bridge/email-templates/img/doris_signature.png' ); ?>" alt="MeGeMIT" />
</div>

<div class="footer-info">
    Postadresse:<br/>
    Unterer Aubachweg 8<br/>
    A-6300 W&ouml;rgl<br/>
    <br/>
    Adresse:<br/>
    SPACES/Gertrude-Fr&ouml;hlich-Sandner-Str. 2, Tower 9<br/>
    A-1100 Wien<br/>
    <br/>
    FOLGEN SIE UNS:<br/>
    Instagram: <a href="https://www.instagram.com/mikro.immuntherapie">www.instagram.com/mikro.immuntherapie</a><br/>
    Facebook: <a href="https://www.facebook.com/Mikroimmuntherapie">www.facebook.com/Mikroimmuntherapie</a><br/>
    LinkedIn: <a href="https://www.linkedin.com/company/megemit-medizinische-gesellschaft-f">www.linkedin.com/company/megemit-medizinische-gesellschaft-f</a>
</div>

</div>
</body>
</html>
