<?php
/**
 * Notas de abono por factura (pedidos facturados por línea, ver invoice-split.php).
 *
 * - Reembolso con líneas seleccionadas: una nota de abono por cada línea reembolsada,
 *   con el importe reembolsado de esa línea.
 * - Cancelación del pedido: una nota de abono por cada línea con importe pendiente
 *   de abonar.
 * - Reembolsos sin líneas (solo importe) y pedidos de factura única: comportamiento
 *   de siempre (una nota de abono por pedido).
 * - Numeración: la primera nota del pedido usa el número nativo del documento
 *   "credit-note" del plugin PDF; las demás, números consecutivos del mismo contador.
 * - Cada nota se guarda en la meta MGMIT_CREDIT_SPLIT_META (clave "refund:línea" o
 *   "cancel:línea"), de modo que no se emite dos veces ni cambia al regenerar el PDF.
 * - Compatible con PHP 7.4.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Meta del pedido con las notas de abono por línea. */
if ( ! defined( 'MGMIT_CREDIT_SPLIT_META' ) ) {
    define( 'MGMIT_CREDIT_SPLIT_META', '_mgmit_split_credit_notes' );
}

/** Meta que marca que el número nativo del documento ya lo usó una nota por línea. */
if ( ! defined( 'MGMIT_CREDIT_NATIVE_META' ) ) {
    define( 'MGMIT_CREDIT_NATIVE_META', '_mgmit_split_credit_native_claimed' );
}

/**
 * Notas emitidas en esta petición (claves). El PDF/email de un reembolso o
 * cancelación incluye solo estas; sin lote, el PDF incluye todas las del pedido.
 *
 * @param array|null $set null = solo leer.
 * @return array
 */
function mgmit_credit_note_batch( $set = null ) {
    static $batch = array();
    if ( null !== $set ) {
        $batch = $set;
    }
    return $batch;
}

/**
 * Limita el PDF a las notas de abono de una línea (0 = todas).
 *
 * @param int|false $set false = solo leer.
 * @return int
 */
function mgmit_credit_note_only_item( $set = false ) {
    static $only = 0;
    if ( false !== $set ) {
        $only = (int) $set;
    }
    return $only;
}

/**
 * ¿Pedido facturado por línea y con facturas ya numeradas?
 *
 * @param mixed $order
 * @return bool
 */
function mgmit_order_is_split_invoiced( $order ) {
    if ( ! function_exists( 'mgmit_invoice_split_applies' ) || ! mgmit_invoice_split_applies( $order ) ) {
        return false;
    }
    $map = $order->get_meta( MGMIT_INVOICE_SPLIT_META, true );
    return is_array( $map ) && ! empty( $map );
}

/**
 * Notas de abono emitidas del pedido, ordenadas por número.
 *
 * @param WC_Order $order
 * @return array clave => array( item_id, refund_id, number, plain, qty, net, tax, taxes )
 */
function mgmit_get_split_credit_notes( $order ) {
    $entries = $order->get_meta( MGMIT_CREDIT_SPLIT_META, true );
    if ( ! is_array( $entries ) ) {
        return array();
    }
    uasort( $entries, function ( $a, $b ) {
        return (int) $a['plain'] - (int) $b['plain'];
    } );
    return $entries;
}

/**
 * Emite (si no existen) las notas de abono pedidas.
 *
 * @param WC_Order $order
 * @param array    $wanted clave => array( item_id, refund_id, qty, net, tax, taxes[rate_id => importe] ) (importes positivos)
 * @return array Claves emitidas ahora.
 */
function mgmit_issue_split_credit_notes( $order, array $wanted ) {
    if ( ! function_exists( 'wcpdf_get_document' )
        || ! class_exists( '\\WPO\\IPS\\Semaphore' )
        || ! class_exists( '\\WPO\\IPS\\Documents\\DocumentNumber' ) ) {
        return array();
    }

    $semaphore = new \WPO\IPS\Semaphore( 'mgmit_split_credit_numbers' );
    if ( ! $semaphore->lock() ) {
        return array();
    }

    $issued = array();

    try {
        $fresh   = wc_get_order( $order->get_id() );
        $entries = $fresh ? $fresh->get_meta( MGMIT_CREDIT_SPLIT_META, true ) : array();
        $entries = is_array( $entries ) ? $entries : array();

        $todo = array();
        foreach ( $wanted as $key => $data ) {
            if ( ! isset( $entries[ $key ] ) ) {
                $todo[ $key ] = $data;
            }
        }

        if ( $fresh && ! empty( $todo ) ) {
            $probe         = wcpdf_get_document( 'credit-note', $fresh, false );
            $native_exists = $probe && $probe->exists();
            $claimed       = (bool) $fresh->get_meta( MGMIT_CREDIT_NATIVE_META );

            // Crea el documento (y su número nativo) solo si aún no existe.
            $credit_note = wcpdf_get_document( 'credit-note', $fresh, true );

            if ( $credit_note ) {
                $store    = $credit_note->get_sequential_number_store();
                $date     = $credit_note->get_date();
                $date_str = $date ? $date->date_i18n( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
                $invoices = $fresh->get_meta( MGMIT_INVOICE_SPLIT_META, true );

                foreach ( $todo as $key => $data ) {
                    if ( ! $native_exists && ! $claimed ) {
                        $native = $credit_note->get_number();
                        if ( ! $native ) {
                            break;
                        }
                        $number  = (string) $native->get_formatted();
                        $plain   = (int) $native->get_plain();
                        $claimed = true;
                    } else {
                        $plain = (int) $store->increment( $fresh->get_id(), $date_str );
                        if ( $plain <= 0 ) {
                            break;
                        }
                        $doc_number = new \WPO\IPS\Documents\DocumentNumber( $plain, $credit_note->get_number_settings(), $credit_note, $fresh );
                        $number     = (string) $doc_number->get_formatted();
                    }

                    $entries[ $key ] = array(
                        'item_id'   => (int) $data['item_id'],
                        'refund_id' => (int) $data['refund_id'],
                        'number'    => $number,
                        'plain'     => $plain,
                        'qty'       => (float) $data['qty'],
                        'net'       => (float) $data['net'],
                        'tax'       => (float) $data['tax'],
                        'taxes'     => $data['taxes'],
                    );
                    $issued[] = $key;

                    $related = ( is_array( $invoices ) && isset( $invoices[ $data['item_id'] ]['number'] ) ) ? $invoices[ $data['item_id'] ]['number'] : '';
                    $fresh->add_order_note( sprintf( 'Nota de abono nº %s emitida%s.', $number, '' !== $related ? ' para la factura nº ' . $related : '' ) );
                }

                if ( ! empty( $issued ) ) {
                    $fresh->update_meta_data( MGMIT_CREDIT_SPLIT_META, $entries );
                    $fresh->update_meta_data( MGMIT_CREDIT_NATIVE_META, $claimed ? '1' : '' );
                    $fresh->save_meta_data();
                }
            }
        }
    } catch ( \Exception $e ) {
        $issued = array();
    } catch ( \Error $e ) {
        $issued = array();
    }

    $semaphore->release();

    if ( ! empty( $issued ) ) {
        mgmit_credit_note_batch( array_merge( mgmit_credit_note_batch(), $issued ) );
    }

    return $issued;
}

add_action( 'woocommerce_order_refunded', 'mgmit_split_credit_notes_on_refund', 5, 2 );

/**
 * Reembolso con líneas: una nota de abono por línea reembolsada.
 * Antes que el email nativo de reembolso, que adjunta el PDF.
 */
function mgmit_split_credit_notes_on_refund( $order_id, $refund_id ) {
    $order  = wc_get_order( $order_id );
    $refund = wc_get_order( $refund_id );

    if ( ! $order || ! $refund || ! mgmit_order_is_split_invoiced( $order ) ) {
        return;
    }

    mgmit_credit_note_batch( array() ); // cada evento empieza su propio lote
    $wanted = array();

    foreach ( $refund->get_items( 'line_item' ) as $refund_item ) {
        $orig = (int) $refund_item->get_meta( '_refunded_item_id' );
        $net  = abs( (float) $refund_item->get_total() );
        $tax  = abs( (float) $refund_item->get_total_tax() );

        if ( ! $orig || ( $net + $tax ) < 0.0001 ) {
            continue;
        }

        $taxes     = array();
        $item_tax  = $refund_item->get_taxes();
        if ( ! empty( $item_tax['total'] ) && is_array( $item_tax['total'] ) ) {
            foreach ( $item_tax['total'] as $rate_id => $amount ) {
                if ( '' !== $amount && abs( (float) $amount ) >= 0.0001 ) {
                    $taxes[ $rate_id ] = abs( (float) $amount );
                }
            }
        }

        $wanted[ $refund_id . ':' . $orig ] = array(
            'item_id'   => $orig,
            'refund_id' => (int) $refund_id,
            'qty'       => abs( (float) $refund_item->get_quantity() ),
            'net'       => $net,
            'tax'       => $tax,
            'taxes'     => $taxes,
        );
    }

    if ( ! empty( $wanted ) ) {
        mgmit_issue_split_credit_notes( $order, $wanted );
    }
}

add_action( 'woocommerce_order_status_cancelled', 'mgmit_split_credit_notes_on_cancel', 5 );

/**
 * Cancelación: una nota de abono por cada línea con importe pendiente de abonar.
 * Antes que el email propio de cancelación (credit-note-mailer.php).
 */
function mgmit_split_credit_notes_on_cancel( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order || ! mgmit_order_is_split_invoiced( $order ) ) {
        return;
    }

    mgmit_credit_note_batch( array() ); // cada evento empieza su propio lote
    $existing = mgmit_get_split_credit_notes( $order );
    $wanted   = array();

    foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
        $key = 'cancel:' . $item_id;
        if ( isset( $existing[ $key ] ) ) {
            continue;
        }

        $net   = (float) $item->get_total();
        $tax   = (float) $item->get_total_tax();
        $qty   = (float) $item->get_quantity();
        $taxes = array();

        $item_tax = $item->get_taxes();
        if ( ! empty( $item_tax['total'] ) && is_array( $item_tax['total'] ) ) {
            foreach ( $item_tax['total'] as $rate_id => $amount ) {
                if ( '' !== $amount ) {
                    $taxes[ $rate_id ] = (float) $amount;
                }
            }
        }

        // Descontar lo ya abonado por reembolsos de esa línea.
        foreach ( $existing as $entry ) {
            if ( (int) $entry['item_id'] !== (int) $item_id ) {
                continue;
            }
            $net -= (float) $entry['net'];
            $tax -= (float) $entry['tax'];
            $qty -= (float) $entry['qty'];
            foreach ( $entry['taxes'] as $rate_id => $amount ) {
                if ( isset( $taxes[ $rate_id ] ) ) {
                    $taxes[ $rate_id ] -= (float) $amount;
                }
            }
        }

        if ( ( $net + $tax ) < 0.0001 ) {
            continue;
        }

        $taxes = array_filter( $taxes, function ( $amount ) {
            return abs( $amount ) >= 0.0001;
        } );

        $wanted[ $key ] = array(
            'item_id'   => (int) $item_id,
            'refund_id' => 0,
            'qty'       => max( 0, $qty ),
            'net'       => $net,
            'tax'       => $tax,
            'taxes'     => $taxes,
        );
    }

    if ( ! empty( $wanted ) ) {
        mgmit_issue_split_credit_notes( $order, $wanted );
    }
}

/**
 * Vistas de nota de abono que pinta credit-note.php.
 * Cada vista: number, related (nº de factura), item (línea del plugin), qty, gross, net,
 * totals (filas label/value ya con signo negativo). null en number = nota nativa de siempre.
 *
 * @param object $doc Documento "credit-note" ($this de la plantilla).
 * @return array
 */
function mgmit_build_credit_note_views( $doc ) {
    $legacy = array( array( 'number' => null ) );
    $order  = $doc->order;

    if ( ! $order || ! mgmit_order_is_split_invoiced( $order ) ) {
        return $legacy;
    }

    $entries = mgmit_get_split_credit_notes( $order );
    if ( empty( $entries ) ) {
        return $legacy;
    }

    $batch = mgmit_credit_note_batch();
    $only  = mgmit_credit_note_only_item();

    $selected = array();
    foreach ( $entries as $key => $entry ) {
        if ( ! empty( $batch ) ) {
            if ( in_array( $key, $batch, true ) ) {
                $selected[ $key ] = $entry;
            }
        } elseif ( $only ) {
            if ( (int) $entry['item_id'] === $only ) {
                $selected[ $key ] = $entry;
            }
        } else {
            $selected[ $key ] = $entry;
        }
    }

    if ( empty( $selected ) ) {
        $selected = $entries;
    }

    $items    = $doc->get_order_items();
    $invoices = $order->get_meta( MGMIT_INVOICE_SPLIT_META, true );
    $currency = array( 'currency' => $order->get_currency() );
    $label    = 'Total';
    foreach ( $doc->get_woocommerce_totals() as $total_key => $total ) {
        if ( 'order_total' === $total_key ) {
            $label = $total['label'];
        }
    }

    $views = array();

    foreach ( $selected as $entry ) {
        $item_id = (int) $entry['item_id'];
        if ( ! isset( $items[ $item_id ] ) ) {
            continue;
        }

        $net   = (float) $entry['net'];
        $gross = $net + (float) $entry['tax'];

        $rows = array(
            array(
                'label' => $label . ' (netto ' . wp_strip_all_tags( wc_price( -1 * $net, $currency ) ) . ')',
                'value' => wc_price( -1 * $gross, $currency ),
            ),
        );
        foreach ( $entry['taxes'] as $rate_id => $amount ) {
            $rows[] = array(
                'label' => 'enthaltene Mehrwertsteuer zum Satz von ' . WC_Tax::get_rate_percent_value( $rate_id ) . ' %',
                'value' => wc_price( -1 * (float) $amount, $currency ),
                'class' => 'tax-rate-row',
            );
        }

        $views[] = array(
            'number'  => $entry['number'],
            'related' => ( is_array( $invoices ) && isset( $invoices[ $item_id ]['number'] ) ) ? $invoices[ $item_id ]['number'] : '',
            'item'    => $items[ $item_id ],
            'qty'     => (float) $entry['qty'] > 0 ? (float) $entry['qty'] : 1,
            'gross'   => $gross,
            'totals'  => $rows,
        );
    }

    return empty( $views ) ? $legacy : $views;
}
