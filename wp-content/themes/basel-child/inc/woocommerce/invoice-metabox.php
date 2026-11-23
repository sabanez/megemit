<?php
/**
 * Cuadro "Rechnungen" en la ficha del pedido de WooCommerce.
 *
 * Solo aparece en pedidos facturados por línea (ver invoice-split.php). Lista cada
 * factura (número, producto, importe) con su descarga individual, y la descarga
 * del PDF con todas. Las descargas usan las URLs firmadas de invoice-endpoint.php.
 * Compatible con PHP 7.4.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'mgmit_register_invoices_metabox', 30 );

function mgmit_register_invoices_metabox() {
    $screens = array( 'shop_order' );
    if ( function_exists( 'wc_get_page_screen_id' ) ) {
        $hpos_screen = wc_get_page_screen_id( 'shop-order' );
        if ( $hpos_screen && ! in_array( $hpos_screen, $screens, true ) ) {
            $screens[] = $hpos_screen;
        }
    }

    foreach ( $screens as $screen ) {
        add_meta_box(
            'mgmit-invoices',
            'Rechnungen (eine pro Produkt)',
            'mgmit_render_invoices_metabox',
            $screen,
            'side',
            'default'
        );
    }
}

function mgmit_render_invoices_metabox( $post_or_order ) {
    $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );

    if ( ! $order || ! function_exists( 'mgmit_invoice_split_applies' ) || ! mgmit_invoice_split_applies( $order ) ) {
        echo '<p>' . esc_html( 'Pedido facturado con una única factura.' ) . '</p>';
        return;
    }

    $split        = $order->get_meta( MGMIT_INVOICE_SPLIT_META, true );
    $all          = mgmit_get_invoice_pdf_url( $order->get_id() );
    $credit_notes = function_exists( 'mgmit_get_split_credit_notes' ) ? mgmit_get_split_credit_notes( $order ) : array();

    if ( ! is_array( $split ) || empty( $split ) ) {
        echo '<p>' . esc_html( 'Las facturas se numeran al generarse el PDF por primera vez.' ) . '</p>';
        if ( $all ) {
            echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $all ) . '">' . esc_html( 'Generar y descargar PDF' ) . '</a></p>';
        }
        return;
    }

    echo '<table class="widefat striped" style="margin-bottom:10px;"><tbody>';

    foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
        if ( ! isset( $split[ $item_id ] ) ) {
            continue;
        }

        $gross = (float) $item->get_total() + (float) $item->get_total_tax();
        $url   = mgmit_get_invoice_pdf_url( $order->get_id(), $item_id );

        echo '<tr><td>';
        echo '<strong>' . esc_html( '№ ' . $split[ $item_id ]['number'] ) . '</strong> &middot; ';
        echo wp_kses_post( wc_price( $gross, array( 'currency' => $order->get_currency() ) ) ) . '<br/>';
        echo '<span style="color:#666;">' . esc_html( $item->get_name() ) . '</span><br/>';
        if ( $url ) {
            echo '<a target="_blank" rel="noopener" href="' . esc_url( $url ) . '">' . esc_html( 'Descargar PDF' ) . '</a>';
        }

        // Notas de abono emitidas contra esta factura.
        foreach ( $credit_notes as $entry ) {
            if ( (int) $entry['item_id'] !== (int) $item_id ) {
                continue;
            }
            $cn_url = function_exists( 'mgmit_get_storno_pdf_url' ) ? mgmit_get_storno_pdf_url( $order->get_id(), $item_id ) : '';
            echo '<br/><span style="color:#b32d2e;">' . esc_html( 'Storno № ' . $entry['number'] ) . ' &middot; '
                . wp_kses_post( wc_price( -1 * ( (float) $entry['net'] + (float) $entry['tax'] ), array( 'currency' => $order->get_currency() ) ) ) . '</span>';
            if ( $cn_url ) {
                echo ' <a target="_blank" rel="noopener" href="' . esc_url( $cn_url ) . '">' . esc_html( 'PDF' ) . '</a>';
            }
        }
        echo '</td></tr>';
    }

    echo '</tbody></table>';

    if ( $all ) {
        echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url( $all ) . '">' . esc_html( 'Descargar todas (PDF)' ) . '</a>';
    }

    if ( ! empty( $credit_notes ) && function_exists( 'mgmit_get_storno_pdf_url' ) ) {
        echo ' <a class="button" target="_blank" rel="noopener" href="' . esc_url( mgmit_get_storno_pdf_url( $order->get_id() ) ) . '">' . esc_html( 'Todas las notas de abono (PDF)' ) . '</a>';
    }
}
