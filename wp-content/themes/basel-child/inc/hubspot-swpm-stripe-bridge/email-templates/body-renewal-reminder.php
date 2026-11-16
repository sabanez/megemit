<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas:
 * $greeting_line — "Sehr geehrte Frau ..." / "Sehr geehrter Herr ..." / fallback
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

<p>wir m&ouml;chten uns ganz herzlich bei Ihnen bedanken, dass Sie mit Ihrem Mitgliedsbeitrag die
Arbeit der MeGeMIT unterst&uuml;tzen. Wir haben in den vergangenen Monaten viele medizinische
Fachkr&auml;fte in der Mikroimmuntherapie ausgebildet und ihnen &uuml;ber unsere
Therapeutenberatung, monatlichen Live-Workshops, die Online-Akademie und eine Vielzahl an
Informationsbroschüren eine intensive Unterst&uuml;tzung ihrer Arbeit zur Verf&uuml;gung
gestellt.</p>

<p>Wir hoffen, dass auch Sie von diesem Angebot profitieren konnten!</p>

<p>In K&uuml;rze wird die Erneuerung der Mitgliedschaft f&auml;llig und automatisch von Ihrem Konto
abgebucht.</p>

<p>Falls Sie die Mitgliedschaft nicht verl&auml;ngern wollen, schreiben Sie dies bitte an
<a href="mailto:info@megemit.org">info@megemit.org</a>.</p>

<p>Wir w&uuml;rden uns sehr freuen, wenn Sie uns als Mitglied die Treue halten!</p>

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
