<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<!-- CABECERA -->
<?php
$header_img_path = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
if ( file_exists( $header_img_path ) ) :
?>
<div class="header-wrap">
	<img src="<?php echo esc_attr( $header_img_path ); ?>" class="header-img" alt="MeGeMIT" />
</div>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>
<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<!-- DIRECCIÓN + FECHA
     Nombre en negrita (pdf24_07), resto en regular (pdf24_10)
     Fecha alineada al fondo de la columna derecha, misma línea que ciudad -->
<table class="address-date-table">
	<tr>
		<td class="billing-col">
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<?php
			$billing_first   = $this->order->get_billing_first_name();
			$billing_last    = $this->order->get_billing_last_name();
			$billing_name    = trim( $billing_first . ' ' . $billing_last );
			$billing_company = $this->order->get_billing_company();

			$address_fields = array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => $this->order->get_billing_address_1(),
				'address_2'  => $this->order->get_billing_address_2(),
				'city'       => $this->order->get_billing_city(),
				'state'      => $this->order->get_billing_state(),
				'postcode'   => $this->order->get_billing_postcode(),
				'country'    => $this->order->get_billing_country(),
			);
			$formatted_address = WC()->countries->get_formatted_address( $address_fields );
			?>
			<?php if ( $billing_name ) : ?>
				<span class="billing-name"><?php echo esc_html( $billing_name ); ?></span>
			<?php endif; ?>
			<?php if ( $billing_company ) : ?>
				<span style="display:block;"><?php echo esc_html( $billing_company ); ?></span>
			<?php endif; ?>
			<?php echo wp_kses_post( $formatted_address ); ?>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div><?php $this->billing_phone(); ?></div>
			<?php endif; ?>
		</td>
		<td class="date-col">
			<?php
			$doc_date = $this->get_date();
			if ( $doc_date && is_a( $doc_date, 'WC_DateTime' ) ) {
				$date_str = date_i18n( 'Y-m-d', $doc_date->getTimestamp() );
			} else {
				$order_date = $this->order->get_date_created();
				$date_str   = $order_date ? date_i18n( 'Y-m-d', $order_date->getTimestamp() ) : date_i18n( 'Y-m-d' );
			}
			?>
			<span>Wörgl, am <?php echo esc_html( $date_str ); ?></span>
		</td>
	</tr>
</table>

<!-- NÚMERO DE FACTURA Y PEDIDO
     pdf24_07 label bold #000 | pdf24_11 valor bold #0070C0 -->
<div class="invoice-numbers">
	<p>
		<span class="inv-label"><?php $this->number_title(); ?></span>
		<span class="inv-value"><?php $this->number( $this->get_type() ); ?></span>
	</p>
	<p>
		<span class="inv-label"><?php $this->order_number_title(); ?></span>
		<span class="inv-value"><?php $this->order_number(); ?></span>
	</p>
</div>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<!-- TABLA DE LÍNEAS
     Columnas según posicionamiento del HTML:
     Position ~13% | Menge ~9% | Artikelbezeichnung ~50% | Einzelpreis ~15% | Gesamtpreis ~13%
     Cabecera pdf24_14: 9.1pt bold | Filas pdf24_15: 9.1pt regular | SKU pdf24_19: 4.8pt -->
<table class="order-details">
	<colgroup>
		<col style="width:11%" />
		<col style="width:10%" />
		<col style="width:47%" />
		<col style="width:16%" />
		<col style="width:16%" />
	</colgroup>
	<thead>
		<tr>
			<th style="text-align:center;">Position</th>
			<th style="text-align:center;">Menge</th>
			<th>Artikelbezeichnung</th>
			<th style="text-align:right;">Einzelpreis</th>
			<th style="text-align:right;">Gesamtpreis</th>
		</tr>
		<tr class="header-rule">
			<td colspan="5"></td>
		</tr>
	</thead>
	<tbody>
		<?php $pos = 1; ?>
		<?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
		<tr class="<?php echo esc_attr( $item['row_class'] ); ?>">
			<td class="td-center"><?php echo esc_html( $pos ); ?></td>
			<td class="td-center"><?php echo esc_html( $item['quantity'] ); ?></td>
			<td>
				<span class="item-name"><?php echo esc_html( $item['name'] ); ?></span>
				<?php do_action( 'wpo_wcpdf_before_item_meta', $this->get_type(), $item, $this->order ); ?>
				<?php if ( ! empty( $item['sku'] ) || ! empty( $item['meta'] ) ) : ?>
				<div class="item-meta">
					<?php if ( ! empty( $item['sku'] ) ) : ?>
						<span class="sku">Artikel-Nr.:<?php echo esc_html( $item['sku'] ); ?><?php if ( ! empty( $item['meta'] ) ) : ?> | <?php endif; ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['meta'] ) ) : ?>
						<?php echo wp_kses_post( $item['meta'] ); ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_item_meta', $this->get_type(), $item, $this->order ); ?>
			</td>
			<td class="td-right"><?php echo ! empty( $item['price'] ) ? esc_html( $item['price'] ) : esc_html( $item['order_price'] ); ?></td>
			<td class="td-right"><?php echo esc_html( $item['order_price'] ); ?></td>
		</tr>
		<?php $pos++; ?>
		<?php endforeach; ?>
	</tbody>
</table>

<!-- ── TOTALES ── -->
<table class="totals-outer">
	<colgroup>
		<col style="width:87%" />
		<col style="width:13%" />
	</colgroup>
	<tfoot>
		<?php foreach ( $this->get_woocommerce_totals() as $key => $total ) : ?>
		<tr class="<?php echo esc_attr( $key ); ?>">
			<th><?php echo esc_html( $total['label'] ); ?></th>
			<td><?php echo esc_html( $total['value'] ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tfoot>
</table>

<?php if ( $this->get_document_notes() || $this->get_shipping_notes() ) : ?>
<div class="order-notes">
	<?php if ( $this->get_document_notes() ) : ?>
		<?php do_action( 'wpo_wcpdf_before_document_notes', $this->get_type(), $this->order ); ?>
		<div class="document-notes"><h3><?php $this->notes_title(); ?></h3><?php $this->document_notes(); ?></div>
		<?php do_action( 'wpo_wcpdf_after_document_notes', $this->get_type(), $this->order ); ?>
	<?php endif; ?>
	<?php if ( $this->get_shipping_notes() ) : ?>
		<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
		<div class="customer-notes"><h3><?php $this->customer_notes_title(); ?></h3><?php $this->shipping_notes(); ?></div>
		<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<!-- INSTRUCCIÓN DE PAGO: pdf24_10 → 9.6pt regular -->
<?php
$totals_data   = $this->get_woocommerce_totals();
$total_display = ! empty( $totals_data['order_total']['value'] ) ? $totals_data['order_total']['value'] : '';
?>
<p class="payment-instruction">Bitte überweisen sie den Betrag von <?php echo esc_html( $total_display ); ?> unter Angabe der Rechnungsnummer auf eines der unten angegeben Konten.</p>

<!-- DATOS BANCARIOS: pdf24_07 → 9.6pt bold, dos columnas -->
<table class="bank-table">
	<tr>
		<td class="bank-col">
			<p>Bank Austria:</p>
			<p>IBAN:AT19 1200 0100 0563 5676</p>
			<p>BIC:BKAUATWW</p>
		</td>
		<td class="bank-col">
			<p>Raiffeisenbank Oberaudorf eG:</p>
			<p>IBAN:DE47 7116 2355 0000 0600 62</p>
			<p>BIC:GENODEF1OBD</p>
		</td>
	</tr>
</table>

<!-- AVISO LEGAL: pdf24_22 → 5.8pt bold, fondo #efefef -->
<div class="legal-box">
	<p>Beachten Sie bitte zum Widerrufsrecht und Stornobedingungen unseren angefügten Allgemeinen Geschäftsbedingungen.</p>
</div>

<!-- FOOTER: pdf24_24 → 7.5pt regular, #0A78B8 -->
<htmlpagefooter name="docFooter2">
	<div id="footer">
		Medizinische Gesellschaft für Mikroimmuntherapie (MeGeMIT)<br/>
		SPACES/Gertrude-Fröhlich-Sandner-Str. 2, Tower 9 &middot; 1100 Wien &middot; T. 0043-(0)-1- 9 30 27 30 40 &middot; Fax: 0043-(0)-1- 391 000 4<br/>
		UID: ATU68398067 &middot; Steuernummer (DE): 182/123/21261<br/>
		www.megemit.org &middot; www.mikroimmuntherapie.com &middot; info@megemit.org<br/>
		<?php
		$fb_icon = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit2/facebook_icon.png';
		if ( file_exists( $fb_icon ) ) :
		?><img src="<?php echo esc_attr( $fb_icon ); ?>" style="width:9pt;height:9pt;vertical-align:middle;" alt="Facebook" /><?php endif; ?> @mikroimmuntherapie
	</div>
</htmlpagefooter>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
