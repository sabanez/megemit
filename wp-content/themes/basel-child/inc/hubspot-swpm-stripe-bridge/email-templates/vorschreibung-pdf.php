<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas (pasadas como extract() desde email-membership.php):
 * $salutation_full  — "Frau" / "Herr" / ""
 * $first_name, $last_name
 * $billing_address_1, $billing_city, $billing_postcode, $billing_state, $billing_country
 * $amount, $year, $date_formatted, $greeting_line
 * $header_img_path
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
@page {
    margin-top: 3.2cm;
    margin-bottom: 3.2cm;
    margin-left: 2cm;
    margin-right: 2cm;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #000;
    margin: 0;
    padding: 0;
    background: #fff;
}
#page-header {
    position: fixed;
    top: -3.2cm;
    left: 0;
    right: 0;
    height: 3cm;
    text-align: center;
}
#page-header img {
    width: 100%;
    max-width: 100%;
    display: block;
}
#footer {
    position: fixed;
    bottom: -3.2cm;
    left: 0;
    right: 0;
    height: 3cm;
    text-align: center;
    border-top: 0.5pt solid #cccccc;
    padding-top: 2mm;
    font-size: 7.5pt;
    font-weight: normal;
    color: #0A78B8;
    line-height: 1.7;
}
.address-block {
    margin-top: 10pt;
    font-size: 9.6pt;
    line-height: 1.6;
}
.date-right {
    text-align: right;
    font-size: 9.6pt;
    margin-top: -40pt;
    margin-bottom: 30pt;
}
.betreff {
    margin-top: 20pt;
    margin-bottom: 14pt;
    font-size: 10.5pt;
    font-weight: bold;
    color: #E05A00;
}
.betreff span {
    text-decoration: underline;
}
.greeting {
    margin-top: 6pt;
    margin-bottom: 14pt;
    font-size: 10pt;
}
.body-text {
    font-size: 10pt;
    line-height: 1.6;
    margin-bottom: 16pt;
}
table.totals {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14pt;
    margin-bottom: 14pt;
    font-size: 10pt;
}
table.totals td {
    padding: 3pt 4pt;
    border: 0;
}
table.totals .label-col {
    width: 60%;
}
table.totals .currency-col {
    width: 15%;
    text-align: right;
}
table.totals .amount-col {
    width: 25%;
    text-align: right;
}
table.totals tr.total-row td {
    font-weight: bold;
    border-top: 1pt solid #333;
}
.payment-note {
    font-size: 9.6pt;
    line-height: 1.6;
    margin-bottom: 14pt;
}
table.bank-table {
    width: 100%;
    border: 0;
    margin-bottom: 16pt;
}
table.bank-table td.bank-col {
    width: 50%;
    vertical-align: top;
    padding-right: 6pt;
    font-size: 9.6pt;
    font-weight: bold;
    line-height: 1.7;
}
.legal-box {
    background-color: #efefef;
    padding: 4pt 6pt;
    font-size: 7pt;
    font-weight: bold;
    color: #000;
    margin-top: 10pt;
}
</style>
</head>
<body>

<div id="page-header">
<?php if ( $header_img_path && file_exists( $header_img_path ) ) : ?>
    <img src="<?php echo esc_attr( $header_img_path ); ?>" alt="MeGeMIT" />
<?php endif; ?>
</div>

<div id="footer">
    Medizinische Gesellschaft f&uuml;r Mikroimmuntherapie (MeGeMIT)<br/>
    SPACES/Gertrude-Fr&ouml;hlich-Sandner-Str. 2, Tower 9 &middot; 1100 Wien &middot; T. 0043-(0)-1-930 27 30 40 &middot; Fax: 0043-(0)-1-391 000 4<br/>
    UID: ATU68398067 &middot; Steuernummer (DE): 182/123/21261<br/>
    www.megemit.org &middot; www.mikroimmuntherapie.com &middot; info@megemit.org
</div>

<!-- Bloque de dirección -->
<div class="address-block">
    <?php if ( $salutation_full ) : ?>
    <?php echo esc_html( $salutation_full ); ?><br/>
    <?php endif; ?>
    <?php echo esc_html( $first_name . ' ' . $last_name ); ?><br/>
    <?php if ( $billing_address_1 ) : ?>
    <?php echo esc_html( $billing_address_1 ); ?><br/>
    <?php endif; ?>
    <?php if ( $billing_postcode || $billing_city ) : ?>
    <?php echo esc_html( trim( $billing_postcode . ' ' . $billing_city ) ); ?><br/>
    <?php endif; ?>
    <?php if ( $billing_state ) : ?>
    <?php echo esc_html( $billing_state ); ?><br/>
    <?php endif; ?>
    <?php if ( $billing_country ) : ?>
    <?php echo esc_html( $billing_country ); ?>
    <?php endif; ?>
</div>

<div class="date-right">
    W&ouml;rgl, am <?php echo esc_html( $date_formatted ); ?>
</div>

<div class="betreff">
    Betreff: <span>Vorschreibung zur Mitgliedschaft MeGeMIT <?php echo esc_html( $year ); ?></span>
</div>

<div class="greeting">
    <?php echo esc_html( $greeting_line ); ?>,
</div>

<div class="body-text">
    wir erlauben uns, Ihnen f&uuml;r die Anmeldung zur Mitgliedschaft MeGeMIT <?php echo esc_html( $year ); ?>
    folgende Vorschreibung zu stellen.
</div>

<table class="totals">
    <tr>
        <td class="label-col">Mitgliedsbeitrag <?php echo esc_html( $year ); ?></td>
        <td class="currency-col">EUR</td>
        <td class="amount-col"><?php echo esc_html( number_format( (float) $amount, 2, ',', '.' ) ); ?></td>
    </tr>
    <tr class="total-row">
        <td class="label-col">Gesamtbetrag EUR</td>
        <td class="currency-col">&nbsp;</td>
        <td class="amount-col"><strong><?php echo esc_html( number_format( (float) $amount, 2, ',', '.' ) ); ?></strong></td>
    </tr>
</table>

<div class="payment-note">
    Bitte zahlen Sie unter Angabe &bdquo;MG <?php echo esc_html( $year ); ?>&ldquo; auf eines der beiden Konten oder per PayPal an
    info@megemit.org
</div>

<table class="bank-table">
    <tr>
        <td class="bank-col">
            <strong>Bank Austria:</strong><br/>
            IBAN: AT19 1200 0100 0563 5676<br/>
            BIC: BKAUATWW
        </td>
        <td class="bank-col">
            <strong>Raiffeisenbank Oberaudorf eG:</strong><br/>
            IBAN: DE47 7118 2355 0000 0600 62<br/>
            BIC: GENODEF1OBD
        </td>
    </tr>
</table>

<div class="legal-box">
    &bdquo;Diese Vorschreibung (=&ldquo;Rechnung&rdquo; f&uuml;r Mitgliedsbeitrag) gilt gemeinsam mit einer
    Zahlungsbest&auml;tigung als Nachweis f&uuml;r die Betriebsausgabe in Ihrer Buchhaltung&ldquo;
</div>

</body>
</html>
