<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Envuelve el contenido editable del admin con el header/footer de marca fijo.
 * $body_content ya debe llevar los placeholders sustituidos.
 */
function swpm_er_render_email_html( $body_content ) {
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
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:auto;max-width:600px;min-width:320px;border-collapse:collapse;border-spacing:0px;">

  <!-- Header: banner corporativo MeGeMIT (mismo asset que las facturas PDF) -->
  <tr>
    <td>
      <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg' ); ?>"
           alt="MeGeMIT" width="600" style="display:block;width:100%;max-width:600px;border:0;" />
    </td>
  </tr>

  <!-- Cuerpo editable desde el admin -->
  <tr>
    <td style="padding:24px 35px;color:#000000;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;">
      <?php echo $body_content; ?>
    </td>
  </tr>

  <!-- Footer fijo: mismos datos legales y color corporativo que las facturas PDF -->
  <tr>
    <td style="padding:18px 35px 30px;border-top:1px solid #cccccc;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#0070C0;line-height:1.6;">
      Medizinische Gesellschaft für Mikroimmuntherapie (MeGeMIT)<br/>
      SPACES/Gertrude-Fröhlich-Sandner-Str. 2, Tower 9 &middot; 1100 Wien &middot; T. 0043-(0)-1- 9 30 27 30 40<br/>
      <a href="https://www.megemit.org" style="color:#0070C0;text-decoration:underline;">www.megemit.org</a> &middot;
      <a href="mailto:info@megemit.org" style="color:#0070C0;text-decoration:underline;">info@megemit.org</a>
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
