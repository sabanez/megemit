<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas (pasadas como extract() desde email-membership.php):
 * $first_name, $last_name,
 * $member_street, $member_postcode, $member_city, $member_country (dirección del perfil del socio en SWPM),
 * $amount, $year, $date_formatted, $header_img_path
 * $salutation_label — courtesy_de de HubSpot ("Sehr geehrte Frau"...; aquí se muestra sin "Sehr geehrte(r)"),
 * $job_title — job_title_de (puede venir vacío)
 */
$salutation_label = isset( $salutation_label ) ? $salutation_label : '';
$job_title        = isset( $job_title ) ? $job_title : '';
$member_name      = trim( implode( ' ', array_filter( array( $job_title, $first_name, $last_name ) ) ) );

// Tratamiento sin la fórmula de saludo: "Sehr geehrte Frau" -> "Frau".
$treatment = trim( preg_replace( '/^\s*sehr\s+geehrte[rs]?\s+/iu', '', $salutation_label ) );

// Dirección del perfil SWPM (cada parte solo si tiene dato).
$member_street  = isset( $member_street ) ? trim( $member_street ) : '';
$member_country = isset( $member_country ) ? trim( $member_country ) : '';
$member_place   = trim( ( isset( $member_postcode ) ? $member_postcode : '' ) . ' ' . ( isset( $member_city ) ? $member_city : '' ) );
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
.watermark-wrap {
    position: relative;
    min-height: 540pt;
}
.content {
    position: relative;
    z-index: 1;
    padding-left: 10pt;
}
.title-block {
    margin-top: 40pt;
    text-align: center;
    font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
    font-size: 14pt;
    font-weight: bold;
    line-height: 1.5;
    color: #195CA8;
}
.member-block {
    margin-top: 36pt;
    font-size: 10.5pt;
    line-height: 2;
}
.member-block .label {
    font-weight: normal;
}
.member-block .value {
    font-weight: bold;
}
.body-text {
    margin-top: 28pt;
    font-size: 10pt;
    line-height: 1.7;
}
.date-line {
    margin-top: 40pt;
    font-size: 10pt;
}
.signature-block {
    margin-top: 20pt;
    text-align: right;
}
.signature-block .sig-name {
    font-size: 9.5pt;
    margin-top: 4pt;
    text-align: right;
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

<div class="watermark-wrap">

    <div class="content">

        <div class="title-block">
            Best&auml;tigung der Mitgliedschaft <?php echo esc_html( $year ); ?> in der<br/>
            Medizinischen Gesellschaft f&uuml;r Mikroimmuntherapie<br/>
            MeGeMIT
        </div>

        <div class="member-block">
            <?php if ( $treatment ) : ?>
            <span class="label"><?php echo esc_html( $treatment ); ?>&nbsp;</span>
            <?php endif; ?>
            <span class="value"><?php echo esc_html( $member_name ); ?></span>
            <?php if ( '' !== $member_street ) : ?>
            <br/><span class="value"><?php echo esc_html( $member_street ); ?></span>
            <?php endif; ?>
            <?php if ( '' !== $member_place ) : ?>
            <br/><span class="value"><?php echo esc_html( $member_place ); ?></span>
            <?php endif; ?>
            <?php if ( '' !== $member_country ) : ?>
            <br/><span class="value"><?php echo esc_html( $member_country ); ?></span>
            <?php endif; ?>
        </div>

        <div class="body-text">
            Hiermit wird best&auml;tigt, dass die oben genannte Person Mitglied in der
            Medizinischen Gesellschaft f&uuml;r Mikroimmuntherapie MeGeMIT ist.
            Die <strong>Mitgliedschaftsgeb&uuml;hr von <?php echo esc_html( number_format( (float) $amount, 0, ',', '.' ) ); ?>,-</strong>
            wurde auf das Konto der MeGeMIT &uuml;berwiesen.
        </div>

        <div class="date-line">
            W&ouml;rgl, am <?php echo esc_html( $date_formatted ); ?>
        </div>

        <div class="signature-block">
            <div class="sig-name">Hagleitner Doris</div>
        </div>

    </div>
</div>

</body>
</html>
