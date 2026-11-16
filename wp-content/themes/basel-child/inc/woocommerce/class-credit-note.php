<?php
namespace WPO\IPS\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Credit Note / Storno-Rechnung Document
 *
 * Calco reducido de WPO\IPS\Documents\Invoice, adaptado a las necesidades
 * de la nota de abono automática (docs/CREDIT_NOTE_STORNO_PLUGIN_PLAN.md).
 * Se apoya en toda la infraestructura genérica de OrderDocument/OrderDocumentMethods
 * (numeración con lock, plantillas PDF, settings API) — solo se define lo
 * específico de este tipo de documento.
 */
if ( ! class_exists( '\\WPO\\IPS\\Documents\\CreditNote' ) ) :

class CreditNote extends OrderDocumentMethods {

	/**
	 * @param int|object|\WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		$this->type  = 'credit-note';
		$this->title = __( 'Credit Note', 'megemit' );
		$this->icon  = WPO_WCPDF()->plugin_url() . '/assets/images/invoice.svg';

		parent::__construct( $order );

		$this->output_formats = apply_filters(
			'wpo_wcpdf_document_output_formats',
			$this->output_formats,
			$this
		);
	}

	public function use_historical_settings(): bool {
		return apply_filters(
			'wpo_wcpdf_document_use_historical_settings',
			wpo_wcpdf_is_document_using_historical_settings( $this->get_type() ),
			$this
		);
	}

	public function storing_settings_enabled(): bool {
		return apply_filters( 'wpo_wcpdf_document_store_settings', true, $this );
	}

	public function get_title() {
		$title = __( 'Credit Note', 'megemit' );
		return apply_filters( 'wpo_wcpdf_document_title', $title, $this );
	}

	public function get_number_title() {
		$title = __( 'Credit Note Number:', 'megemit' );
		return apply_filters( 'wpo_wcpdf_document_number_title', $title, $this );
	}

	public function get_date_title() {
		$title = __( 'Credit Note Date:', 'megemit' );
		return apply_filters( 'wpo_wcpdf_document_date_title', $title, $this );
	}

	public function get_shipping_address_title(): string {
		return apply_filters( 'wpo_wcpdf_document_shipping_address_title', __( 'Ship To:', 'megemit' ), $this );
	}

	public function init() {
		$this->save_settings();
		$this->initiate_date();
		$this->initiate_number();

		do_action( 'wpo_wcpdf_init_document', $this );
	}

	public function exists() {
		return ! empty( $this->data['number'] );
	}

	/**
	 * Referencia a la factura original del pedido, para mostrarla en la
	 * plantilla PDF de la nota de abono ("Storno de factura Nº X").
	 *
	 * @return string
	 */
	public function get_related_invoice_number() {
		if ( empty( $this->order ) || ! function_exists( 'wcpdf_get_document' ) ) {
			return '';
		}

		$invoice = wcpdf_get_document( 'invoice', $this->order );

		if ( ! $invoice || ! $invoice->exists() ) {
			return '';
		}

		return (string) $invoice->get_number();
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_count = isset( $args['order_ids'] ) ? count( $args['order_ids'] ) : 1;
		$name        = _n( 'credit-note', 'credit-notes', $order_count, 'megemit' );

		if ( 1 === $order_count ) {
			$suffix = ( isset( $this->settings['display_number'] ) && 'invoice_number' === $this->settings['display_number'] )
				? (string) $this->get_number()
				: '';

			if ( empty( $suffix ) ) {
				if ( ! empty( $this->order_id ) ) {
					$suffix = $this->order_id;
				} elseif ( ! empty( $args['order_ids'][0] ) ) {
					$suffix = $args['order_ids'][0];
				} else {
					$suffix = uniqid();
				}
			}
		} else {
			$suffix = date_i18n( 'Y-m-d' );
		}

		$output_format = ! empty( $args['output'] ) ? esc_attr( $args['output'] ) : 'pdf';
		$filename      = $name . '-' . $suffix . wcpdf_get_document_output_format_extension( $output_format );

		$order_ids = isset( $args['order_ids'] ) ? $args['order_ids'] : array( $this->order_id );
		$filename  = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context, $args );

		return sanitize_file_name( $filename );
	}

	/**
	 * Settings mínimos necesarios para numeración/activación/envío.
	 * Recortado respecto a Invoice::get_pdf_settings_fields() — se puede
	 * ampliar en una fase posterior (shipping address, notas, etc.) si hace falta.
	 */
	public function init_settings() {
		do_action( "wpo_wcpdf_before_{$this->type}_init_settings", $this );

		foreach ( $this->output_formats as $output_format ) {
			if ( 'pdf' !== $output_format ) {
				continue;
			}

			$page = $option_group = $option_name = "wpo_wcpdf_documents_settings_{$this->get_type()}";

			$settings_fields = apply_filters(
				"wpo_wcpdf_settings_fields_documents_{$this->get_type()}",
				$this->get_pdf_settings_fields( $option_name ),
				$page,
				$option_group,
				$option_name
			);

			if ( ! empty( $settings_fields ) ) {
				WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
			}
		}

		do_action( "wpo_wcpdf_after_{$this->type}_init_settings", $this );
	}

	public function get_pdf_settings_fields( $option_name ) {
		$settings_fields = array(
			array(
				'type'     => 'section',
				'id'       => $this->type,
				'title'    => '',
				'callback' => 'section',
			),
			array(
				'type'     => 'setting',
				'id'       => 'enabled',
				'title'    => __( 'Enable', 'megemit' ),
				'callback' => 'checkbox',
				'section'  => $this->type,
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'enabled',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'attach_to_email_ids',
				'title'    => __( 'Attach to:', 'megemit' ),
				'callback' => 'multiple_checkboxes',
				'section'  => $this->type,
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'attach_to_email_ids',
					'fields_callback' => array( $this, 'get_wc_emails' ),
					'description'     => __( 'Nota: en el flujo MeGeMIT el envío no se hace por email adjunto, sino por el enlace firmado incluido en el webhook a HubSpot. Este ajuste queda disponible por si se necesita en el futuro.', 'megemit' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'disable_for_statuses',
				'title'    => __( 'Disable for:', 'megemit' ),
				'callback' => 'select',
				'section'  => $this->type,
				'args'     => array(
					'option_name'      => $option_name,
					'id'               => 'disable_for_statuses',
					'options_callback' => 'wc_get_order_statuses',
					'multiple'         => true,
					'enhanced_select'  => true,
					'placeholder'      => __( 'Select one or more statuses', 'megemit' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_number',
				'title'    => __( 'Display credit note number', 'megemit' ),
				'callback' => 'select',
				'section'  => $this->type,
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_number',
					'options'     => array(
						''               => __( 'No', 'megemit' ),
						'invoice_number' => __( 'Credit Note Number', 'megemit' ),
						'order_number'   => __( 'Order Number', 'megemit' ),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'next_number',
				'title'    => __( 'Next credit note number (without prefix/suffix etc.)', 'megemit' ),
				'callback' => 'next_number_edit',
				'section'  => $this->type,
				'args'     => array(
					'store_callback' => array( $this, 'get_sequential_number_store' ),
					'size'           => '10',
					'description'    => __( 'Número que se usará para la próxima nota de abono. Si se necesita continuidad con el histórico de Solve (llegó hasta S50148), fijar aquí el valor de arranque antes de generar la primera nota de abono real.', 'megemit' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'number_format',
				'title'    => __( 'Number format', 'megemit' ),
				'callback' => 'multiple_text_input',
				'section'  => $this->type,
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'number_format',
					'fields'      => array(
						'prefix'  => array(
							'label' => __( 'Prefix', 'megemit' ),
							'size'  => 20,
						),
						'suffix'  => array(
							'label' => __( 'Suffix', 'megemit' ),
							'size'  => 20,
						),
						'padding' => array(
							'label' => __( 'Padding', 'megemit' ),
							'size'  => 20,
							'type'  => 'number',
						),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'reset_number_yearly',
				'title'    => __( 'Reset credit note number yearly', 'megemit' ),
				'callback' => 'checkbox',
				'section'  => $this->type,
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'reset_number_yearly',
				),
			),
		);

		return apply_filters( "wpo_wcpdf_{$this->type}_pdf_settings_fields", $settings_fields, $option_name, $this );
	}

	public function get_settings_categories( string $output_format ): array {
		if ( ! in_array( $output_format, $this->output_formats, true ) ) {
			return array();
		}

		$settings_categories = array(
			'pdf' => array(
				'general'          => array(
					'title'   => __( 'General', 'megemit' ),
					'members' => array(
						'enabled',
						'attach_to_email_ids',
						'disable_for_statuses',
					),
				),
				'document_details' => array(
					'title'   => __( 'Document details', 'megemit' ),
					'members' => array(
						'display_number',
						'next_number',
						'number_format',
					),
				),
				'advanced'         => array(
					'title'   => __( 'Advanced', 'megemit' ),
					'members' => array(
						'reset_number_yearly',
					),
				),
			),
		);

		return apply_filters( 'wpo_wcpdf_document_settings_categories', $settings_categories[ $output_format ] ?? array(), $output_format, $this );
	}
}

endif;
