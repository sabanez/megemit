<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Factura (Rechnung) de alta/renovación de membresía. Maqueta según docs/Entwurf-Rechnung.pdf.
 *
 * Variables esperadas (pasadas como extract() desde email-membership.php / email-renewal-confirmed.php):
 * $salutation_full  — courtesy_de de HubSpot ("Sehr geehrte Frau"...); en la factura se muestra sin "Sehr geehrte(r)"
 * $job_title — título (job_title_de de HubSpot), puede venir vacío
 * $first_name, $last_name
 * $stripe_address — dirección introducida en Stripe al pagar (prioritaria; vacía si no hay)
 * $hubspot_address — dirección del contacto en HubSpot (segunda opción)
 * $billing_address, $billing_city, $billing_postcode, $billing_country — dirección de WordPress (respaldo)
 * $amount, $date_formatted
 * $invoice_number — nº de factura de Stripe (vacío si no disponible)
 * $order_number   — ID de socio SWPM (vacío si no disponible)
 * $payment_label  — método de pago real (vacío si no disponible)
 * $period_label   — "dd-mm-yyyy bis dd-mm-yyyy"
 * $header_img_path
 */
$job_title = isset( $job_title ) ? $job_title : '';
$name_line = trim( implode( ' ', array_filter( array( $job_title, $first_name, $last_name ) ) ) );

// Dirección de la factura, por orden: 1) la que el cliente introdujo en Stripe al pagar;
// 2) la del contacto en HubSpot; 3) la de WordPress ($billing_*, que incluye la ficha SWPM).
$invoice_address = null;
foreach ( array( 'stripe_address', 'hubspot_address' ) as $source ) {
    if ( ! empty( $$source ) && is_array( $$source ) && ! empty( $$source['address'] ) ) {
        $invoice_address = $$source;
        break;
    }
}
if ( $invoice_address ) {
    $billing_address  = $invoice_address['address'];
    $billing_city     = $invoice_address['city'];
    $billing_postcode = $invoice_address['postcode'];
    $billing_state    = $invoice_address['state'];
    $billing_country  = $invoice_address['country'];
}

// En la factura el tratamiento va sin la fórmula de saludo: "Sehr geehrte Frau" -> "Frau".
$treatment = trim( preg_replace( '/^\s*sehr\s+geehrte[rs]?\s+/iu', '', isset( $salutation_full ) ? $salutation_full : '' ) );

$city_line    = trim( $billing_postcode . ' ' . $billing_city );
$country_line = '';
if ( $billing_country && 2 === strlen( $billing_country ) ) {
    // Código de país (AT, DE...): prefijo del código postal, como en la muestra "AT-6300 Wörgl".
    if ( $city_line ) {
        $city_line = strtoupper( $billing_country ) . '-' . $city_line;
    }
} elseif ( $billing_country ) {
    // País escrito en texto (Österreich...): línea propia debajo de la localidad.
    $country_line = $billing_country;
}

$amount_fmt = number_format( (float) $amount, 2, ',', '.' ) . ' €';
$icon_path  = __DIR__ . '/img/icon_social.png';
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
    font-size: 9.5pt;
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
    padding-top: 2mm;
    font-size: 7.5pt;
    font-weight: normal;
    color: #0A78B8;
    line-height: 1.7;
}
#footer img {
    width: 9pt;
    height: 9pt;
    vertical-align: middle;
}
.address-block {
    margin-top: 10pt;
    font-size: 9.6pt;
    line-height: 1.5;
}
.date-right {
    text-align: right;
    font-size: 9.6pt;
    margin-top: -14pt;
    margin-bottom: 30pt;
}
.ref {
    font-size: 9.6pt;
    margin-bottom: 8pt;
}
.ref span {
    color: #0A78B8;
}
table.items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14pt;
    font-size: 9pt;
}
table.items th {
    font-weight: normal;
    text-align: center;
    padding: 6pt 4pt;
    border-top: 0.75pt solid #333;
    border-bottom: 0.75pt solid #333;
}
table.items td {
    padding: 6pt 4pt;
    vertical-align: top;
    text-align: center;
    height: 38pt;
}
table.items td.desc {
    text-align: left;
}
table.items td.num {
    text-align: right;
}
table.sums {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6pt;
    font-size: 9pt;
}
table.sums td {
    padding: 6pt 4pt;
    text-align: right;
    border-top: 0.75pt solid #999;
}
table.sums td.lbl {
    width: 78%;
}
table.sums tr.last td {
    border-bottom: 0.75pt solid #333;
    font-size: 10pt;
}
.payment {
    margin-top: 22pt;
    font-size: 9pt;
}
.grey-box {
    background-color: #efefef;
    height: 14pt;
    margin-top: 60pt;
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
    SPACES/Gertrude-Fr&ouml;hlich-Sandner-Str. 2, Tower 9 &middot; 1100 Wien &middot; T. 0043-(0)-1- 9 30 27 30 40 &middot; Fax: 0043-(0)-1- 391 000 4<br/>
    UID: ATU68398067 &middot; Steuernummer (DE): 182/123/21261<br/>
    www.megemit.org &middot; www.mikroimmuntherapie.com &middot; info@megemit.org<br/>
<?php if ( file_exists( $icon_path ) ) : ?>
    <img src="<?php echo esc_attr( $icon_path ); ?>" alt="" /> @mikroimmuntherapie
<?php else : ?>
    @mikroimmuntherapie
<?php endif; ?>
</div>

<div class="address-block">
    <?php if ( $treatment ) : ?>
    <?php echo esc_html( $treatment ); ?><br/>
    <?php endif; ?>
    <?php echo esc_html( $name_line ); ?><br/>
    <?php if ( $billing_address ) : ?>
    <?php echo esc_html( $billing_address ); ?><br/>
    <?php endif; ?>
    <?php if ( $city_line ) : ?>
    <?php echo esc_html( $city_line ); ?><br/>
    <?php endif; ?>
    <?php if ( $country_line ) : ?>
    <?php echo esc_html( $country_line ); ?>
    <?php endif; ?>
</div>

<div class="date-right">
    W&ouml;rgl, am <?php echo esc_html( $date_formatted ); ?>
</div>

<?php if ( $invoice_number ) : ?>
<div class="ref">Rechnungsnummer: <span><?php echo esc_html( $invoice_number ); ?></span></div>
<?php endif; ?>
<?php if ( $order_number ) : ?>
<div class="ref">Bestellnummer: <span><?php echo esc_html( $order_number ); ?></span></div>
<?php endif; ?>

<table class="items">
    <tr>
        <th style="width:12%">Position</th>
        <th style="width:12%">Menge</th>
        <th style="width:36%">Artikelbezeichnung</th>
        <th style="width:20%">Einzelpreis</th>
        <th style="width:20%">Gesamtpreis</th>
    </tr>
    <tr>
        <td>1</td>
        <td>1</td>
        <td class="desc">Mitgliedschaft MeGeMIT<br/><?php echo esc_html( $period_label ); ?></td>
        <td class="num"><?php echo esc_html( $amount_fmt ); ?></td>
        <td class="num"><?php echo esc_html( $amount_fmt ); ?></td>
    </tr>
</table>

<table class="sums">
    <tr>
        <td class="lbl">Zwischensumme</td>
        <td><?php echo esc_html( $amount_fmt ); ?></td>
    </tr>
    <tr class="last">
        <td class="lbl">Gesamt</td>
        <td><?php echo esc_html( $amount_fmt ); ?></td>
    </tr>
</table>

<div class="payment">
<?php if ( $payment_label ) : ?>
    Die Bezahlung ist bereits per <?php echo esc_html( $payment_label ); ?> erfolgt.
<?php else : ?>
    Die Bezahlung ist bereits erfolgt.
<?php endif; ?>
</div>

<div class="grey-box"></div>

</body>
</html>
