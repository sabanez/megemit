<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
$header_img_path = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/header_invoice.jpg';
$fb_icon_path    = get_stylesheet_directory() . '/woocommerce/pdf/MeGeMit/facebook_icon.png';
?>

<!-- Fase 2 (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md): plantilla de la nota de abono.
     Calco de invoice.php, reutilizando el mismo style.css/header/footer. -->

<!-- HEADER: position:fixed en mPDF repite en todas las páginas -->
<?php if ( file_exists( $header_img_path ) ) : ?>
<div id="page-header">
	<img src="<?php echo esc_attr( $header_img_path ); ?>" alt="MeGeMIT" />
</div>
<?php endif; ?>

<!-- FOOTER: position:fixed en mPDF repite en todas las páginas -->
<div id="footer">
	Medizinische Gesellschaft für Mikroimmuntherapie (MeGeMIT)<br/>
	SPACES/Gertrude-Fröhlich-Sandner-Str. 2, Tower 9 &middot; 1100 Wien &middot; T. 0043-(0)-1- 9 30 27 30 40 &middot; Fax: 0043-(0)-1- 391 000 4<br/>
	UID: ATU68398067 &middot; Steuernummer (DE): 182/123/21261<br/>
	www.megemit.org &middot; www.mikroimmuntherapie.com &middot; info@megemit.org<br/>
	<?php if ( file_exists( $fb_icon_path ) ) : ?>
	<img src="<?php echo esc_attr( $fb_icon_path ); ?>" style="width:9pt;height:9pt;vertical-align:middle;" alt="Facebook" />
	<?php endif; ?> @mikroimmuntherapie
</div>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>
<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<!-- DIRECCIÓN + FECHA -->
<table class="address-date-table">
	<tr>
		<td class="billing-col">
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<?php
			$billing_first   = $this->order->get_billing_first_name();
			$billing_last    = $this->order->get_billing_last_name();
			$billing_anrede  = $this->order->get_meta( '_anrede' );
			$billing_name    = trim( ( $billing_anrede ? $billing_anrede . ' ' : '' ) . $billing_first . ' ' . $billing_last );
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

<!-- NÚMERO DE STORNO + REFERENCIA A LA FACTURA ORIGINAL + Nº DE PEDIDO
     Formato calcado del histórico de Solve: "Storno-Rechnungsnummer: S50148 zu Rechnung: M12853" -->
<?php $related_invoice = $this->get_related_invoice_number(); ?>
<div class="invoice-numbers">
	<p>
		<span class="inv-label">Storno-Rechnungsnummer:</span>
		<span class="inv-value"><?php $this->number( $this->get_type() ); ?></span>
		<?php if ( ! empty( $related_invoice ) ) : ?>
		<span class="inv-label"> zu Rechnung:</span>
		<span class="inv-value"><?php echo esc_html( $related_invoice ); ?></span>
		<?php endif; ?>
	</p>
	<p>
		<span class="inv-label"><?php $this->order_number_title(); ?></span>
		<span class="inv-value"><?php $this->order_number(); ?></span>
	</p>
</div>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<!-- TABLA DE LÍNEAS (idéntica a la factura, para que quede claro qué se está anulando) -->
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
			<?php
			// Importes en negativo (nota de abono): se antepone "-" a las cadenas ya
			// formateadas por WPO WCPDF/WooCommerce, sin recalcular el redondeo/IVA.
			$unit_price = ! empty( $item['price'] ) ? $item['price'] : $item['order_price'];
			$line_price = $item['order_price'];
			?>
			<td class="td-right">-<?php echo esc_html( $unit_price ); ?></td>
			<td class="td-right">-<?php echo esc_html( $line_price ); ?></td>
		</tr>
		<?php $pos++; ?>
		<?php endforeach; ?>
	</tbody>
</table>

<!-- TOTALES -->
<table class="totals-outer">
	<colgroup>
		<col style="width:87%" />
		<col style="width:13%" />
	</colgroup>
	<tfoot>
		<?php foreach ( $this->get_woocommerce_totals() as $key => $total ) : ?>
		<tr class="<?php echo esc_attr( $key ); ?>">
			<th><?php
			echo esc_html( $total['label'] );
			if ( 'order_total' === $key ) {
				// Importe negativo: nota de abono, calcado del formato del histórico de Solve.
				$net_total = -1 * ( $this->order->get_total() - $this->order->get_total_tax() );
				echo ' (netto ' . wp_kses_post( wc_price( $net_total, array( 'currency' => $this->order->get_currency() ) ) ) . ')';
			}
			?></th>
			<td><?php
			if ( 'order_total' === $key ) {
				echo wp_kses_post( wc_price( -1 * $this->order->get_total(), array( 'currency' => $this->order->get_currency() ) ) );
			} else {
				echo '-' . esc_html( $total['value'] );
			}
			?></td>
		</tr>
		<?php endforeach; ?>
		<?php foreach ( $this->order->get_tax_totals() as $tax ) : ?>
		<?php $tax_percent = WC_Tax::get_rate_percent_value( $tax->rate_id ); ?>
		<tr class="tax-rate-row">
			<th>enthaltene Mehrwertsteuer zum Satz von <?php echo esc_html( $tax_percent . ' %' ); ?></th>
			<td>-<?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tfoot>
</table>

<?php if ( $this->get_document_notes() ) : ?>
<div class="order-notes">
	<?php do_action( 'wpo_wcpdf_before_document_notes', $this->get_type(), $this->order ); ?>
	<div class="document-notes"><h3><?php $this->notes_title(); ?></h3><?php $this->document_notes(); ?></div>
	<?php do_action( 'wpo_wcpdf_after_document_notes', $this->get_type(), $this->order ); ?>
</div>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<?php // Sin aviso de Storno ni caja legal: el documento termina tras los totales, igual que el histórico de Solve. ?>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
