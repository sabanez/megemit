<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Variables esperadas (pasadas como extract() desde email-membership.php):
 * $first_name, $last_name, $billing_postcode, $billing_city,
 * $amount, $year, $date_formatted, $header_img_path, $logo_watermark_path
 */
$salutation_label = isset( $salutation_label ) ? $salutation_label : 'Herr/Frau';
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
.watermark {
    position: absolute;
    left: -10pt;
    top: 20pt;
    width: 160pt;
    opacity: 0.12;
    z-index: 0;
}
.content {
    position: relative;
    z-index: 1;
    padding-left: 10pt;
}
.title-block {
    margin-top: 40pt;
    text-align: center;
    font-size: 14pt;
    font-weight: bold;
    line-height: 1.5;
    color: #000;
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
.signature-block img {
    width: 90pt;
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

    <?php if ( $logo_watermark_path && file_exists( $logo_watermark_path ) ) : ?>
    <img class="watermark" src="<?php echo esc_attr( $logo_watermark_path ); ?>" alt="" />
    <?php endif; ?>

    <div class="content">

        <div class="title-block">
            Best&auml;tigung der Mitgliedschaft <?php echo esc_html( $year ); ?> in der<br/>
            Medizinischen Gesellschaft f&uuml;r Mikroimmuntherapie<br/>
            MeGeMIT
        </div>

        <div class="member-block">
            <span class="label"><?php echo esc_html( $salutation_label ); ?>:&nbsp;</span>
            <span class="value"><?php echo esc_html( $first_name . ' ' . $last_name ); ?></span><br/>
            <span class="label">Ort:&nbsp;</span>
            <span class="value"><?php echo esc_html( trim( $billing_postcode . ' ' . $billing_city ) ); ?></span>
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
            <?php if ( $header_img_path && file_exists( $header_img_path ) ) : ?>
            <img src="<?php echo esc_attr( $header_img_path ); ?>" alt="MeGeMIT" />
            <?php endif; ?>
            <div class="sig-name">Hagleitner Doris</div>
        </div>

    </div>
</div>

</body>
</html>
