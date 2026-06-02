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

<?php if ( $header_img_path && file_exists( $header_img_path ) ) : ?>
<img class="header-img" src="<?php echo esc_attr( $header_img_path ); ?>" alt="MeGeMIT" />
<?php endif; ?>

<p><?php echo esc_html( $greeting_line ); ?>,</p>

<p>hiermit m&ouml;chten wir Ihnen die Best&auml;tigung Ihrer Mitgliedschaft bei der Medizinischen
Gesellschaft f&uuml;r Mikroimmuntherapie zukommen lassen und uns gleichzeitig f&uuml;r Ihre
Unterst&uuml;tzung bedanken.</p>

<p>Wir freuen uns, dass Sie weiterhin von den Leistungen der MeGeMIT profitieren k&ouml;nnen.<br/>
Wie Sie wissen, steht Ihnen die Therapeuten-Beratung als Mitglied kostenlos zur Verf&uuml;gung.</p>

<p>Genie&szlig;en Sie als Mitglied weiterhin unseren Service der MeGeMIT:</p>

<ul>
    <li>10% Erm&auml;&szlig;igung bei allen Pr&auml;senzseminaren, Workshops und Webinaren, sowie
    Online-Aufzeichnungen, welche zum Kauf zur Verf&uuml;gung stehen</li>
    <li>Kostenlose Nutzung der telefonischen Therapeutenberatung (siehe Anhang)</li>
    <li>Aufnahme in die Therapeutenliste auf Wunsch &ndash; Voraussetzung: Sie sind in der
    Mikroimmuntherapie komplett ausgebildet und wenden diese aktiv in Ihrer Behandlungsstrategie an</li>
    <li>Verg&uuml;nstigtes Jahresabo des Fachmagazins f&uuml;r angewandte Komplement&auml;rmedizin AKOM</li>
    <li>Exklusiv als Mitglied erhalten Sie auf Wunsch die Aufzeichnung zum Wintersymposium Ihrer
    Wahl* zugesendet (kontaktieren Sie uns hierf&uuml;r unter:
    <a href="mailto:info@megemit.org">info@megemit.org</a>)</li>
</ul>

<p>*Hierbei handelt es sich um mind. 4 Stunden Videomaterial mit Top-Referenten zu einem spannenden,
stets aktuellen Thema.</p>

<p>&nbsp;</p>

<p>Im Anhang dieser Nachricht finden Sie die Best&auml;tigung Ihrer Mitgliedschaft sowie den
Zahlungsnachweis. Diese Unterlagen sind f&uuml;r Ihre Buchhaltung bestimmt.</p>

<p>&nbsp;</p>

<p>Mit den besten Gr&uuml;&szlig;en</p>
<p>Doris Hagleitner</p>

<div class="signature-block">
    <img src="https://megemit.org/wp-content/uploads/2021/12/sig_megemit.jpg" alt="MeGeMIT" />
</div>

<div class="footer-info">
    Abt. Finanzen und Administration<br/>
    Vertretung Koordination Therapeuten-Beratung<br/>
    ATU 68 39 80 67<br/>
    Unterer Aubachweg 8 &middot; 6300 W&ouml;rgl &middot; AUSTRIA<br/>
    Tel.: 0043 (0)1 930 27 30 40 &middot; Fax: 0043 (0)1 391 00 04<br/>
    <a href="mailto:doris.hagleitner@megemit.org">doris.hagleitner@megemit.org</a><br/>
    <a href="http://www.megemit.org/">www.megemit.org</a> &middot;
    <a href="http://www.mikroimmuntherapie.com">www.mikroimmuntherapie.com</a>
</div>

</div>
</body>
</html>
