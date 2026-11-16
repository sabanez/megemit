<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas:
 * $greeting_line  — "Sehr geehrte Frau Herbst" / "Sehr geehrter Herr Müller" / "Sehr geehrte/r Muster"
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
ul {
    margin: 8px 0 12px 20px;
    padding: 0;
}
ul li {
    margin-bottom: 6px;
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

<p>Wir freuen uns sehr, Sie als Mitglied der MeGeMIT begr&uuml;&szlig;en zu d&uuml;rfen.</p>

<p>Genie&szlig;en Sie als Mitglied folgende exklusive Vorz&uuml;ge:</p>

<ul>
    <li>10% Erm&auml;&szlig;igung auf unsere Aus- und Weiterbildungsangebote</li>
    <li>Kostenlose telefonische Therapeutenberatung</li>
    <li>Aufnahme in die Therapeutenliste auf Wunsch &ndash; Voraussetzung: Sie sind in der
    Mikroimmuntherapie komplett ausgebildet und wenden diese aktiv in Ihrer Behandlungsstrategie an</li>
    <li>Verg&uuml;nstigtes Jahresabo des Fachmagazins f&uuml;r angewandte Komplement&auml;rmedizin AKOM</li>
    <li>Kostenlose Aufzeichnung unserer Wintersymposien &ndash; bitte fordern Sie diese an unter
    <a href="mailto:info@megemit.org">info@megemit.org</a></li>
</ul>

<p>&nbsp;</p>

<p>Diese Mitgliedschaft erneuert sich nach Ablauf von 12 Monaten automatisch. Sie erhalten kurz vor
Ablauf der 12 Monate eine E-Mail mit dem Hinweis, dass der Mitgliedsbeitrag in K&uuml;rze von Ihrem
Konto abgebucht wird. Falls Sie Ihre Mitgliedschaft nicht erneuern m&ouml;chten, sagen Sie uns einfach
vor Ablauf der aktuellen Mitgliedschaft schriftlich Bescheid. Eine r&uuml;ckwirkende Stornierung ist
nicht m&ouml;glich.</p>

<p>&nbsp;</p>

<p>Anliegend finden Sie Hinweise zur Nutzung der Therapeutenberatung, Ihre Rechnung als Vorlage f&uuml;r
Ihre Buchhaltung und einen Bestellschein f&uuml;r Informationsmaterialien. Sie k&ouml;nnen den
ausgef&uuml;llten Bestellschein per E-Mail an <a href="mailto:info@megemit.org">info@megemit.org</a>
schicken.</p>

<p>&nbsp;</p>

<p>Wir w&uuml;nschen Ihnen viele positive Behandlungserfolge bei der Anwendung der
Mikroimmuntherapie.</p>

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
