<?php
/**
 * Factura por línea de pedido (una factura independiente por producto).
 *
 * - Aplica solo a pedidos creados desde MGMIT_INVOICE_SPLIT_SINCE (config.php),
 *   con 2 o más líneas de producto y sin envío ni tasas con importe (en esos
 *   casos se mantiene la factura única de siempre).
 * - La primera línea conserva el número de factura nativo del plugin PDF Invoices
 *   & Packing Slips; las demás consumen números consecutivos del mismo contador.
 * - La asignación línea -> número se guarda en la meta del pedido
 *   MGMIT_INVOICE_SPLIT_META, de modo que regenerar el PDF da siempre lo mismo.
 * - Compatible con PHP 7.4.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ¿Hay que facturar este pedido por líneas?
 *
 * @param mixed $order
 * @return bool
 */
function mgmit_invoice_split_applies( $order ) {
    if ( ! ( $order instanceof WC_Order ) ) {
        return false;
    }

    try {
        $since = new DateTime( MGMIT_INVOICE_SPLIT_SINCE, wp_timezone() );
    } catch ( Exception $e ) {
        return false;
    }

    $created = $order->get_date_created();
    if ( ! $created || $created->getTimestamp() < $since->getTimestamp() ) {
        return false;
    }

    if ( count( $order->get_items( 'line_item' ) ) < 2 ) {
        return false;
    }

    // Envío o tasas con importe: no se pueden repartir entre facturas -> factura única.
    if ( abs( (float) $order->get_shipping_total() ) > 0.0001 ) {
        return false;
    }
    foreach ( $order->get_fees() as $fee ) {
        if ( abs( (float) $fee->get_total() ) > 0.0001 ) {
            return false;
        }
    }

    return true;
}

/**
 * Devuelve (y asigna la primera vez) el número de factura de cada línea.
 *
 * @param object $invoice Documento "invoice" del plugin (con número ya iniciado).
 * @return array item_id => array( 'number' => string formateado, 'plain' => int ). Vacío si no se puede.
 */
function mgmit_get_split_invoice_numbers( $invoice ) {
    $order = $invoice->order;
    if ( ! $order ) {
        return array();
    }

    $stored = $order->get_meta( MGMIT_INVOICE_SPLIT_META, true );
    if ( is_array( $stored ) && ! empty( $stored ) ) {
        return $stored;
    }

    $native = $invoice->get_number();
    if ( ! $native || ! class_exists( '\\WPO\\IPS\\Semaphore' ) || ! class_exists( '\\WPO\\IPS\\Documents\\DocumentNumber' ) ) {
        return array();
    }

    $semaphore = new \WPO\IPS\Semaphore( 'mgmit_split_invoice_numbers' );
    if ( ! $semaphore->lock() ) {
        return array();
    }

    $map = array();

    try {
        // Releer dentro del bloqueo: otra petición pudo asignarlos entre medias.
        $fresh  = wc_get_order( $order->get_id() );
        $stored = $fresh ? $fresh->get_meta( MGMIT_INVOICE_SPLIT_META, true ) : array();

        if ( is_array( $stored ) && ! empty( $stored ) ) {
            $map = $stored;
        } else {
            $date     = $invoice->get_date();
            $date_str = $date ? $date->date_i18n( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
            $store    = $invoice->get_sequential_number_store();
            $first    = true;

            foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
                if ( $first ) {
                    $map[ $item_id ] = array(
                        'number' => (string) $native->get_formatted(),
                        'plain'  => (int) $native->get_plain(),
                    );
                    $first = false;
                    continue;
                }

                $plain = (int) $store->increment( $order->get_id(), $date_str );
                if ( $plain <= 0 ) {
                    $map = array();
                    break;
                }

                $doc_number      = new \WPO\IPS\Documents\DocumentNumber( $plain, $invoice->get_number_settings(), $invoice, $order );
                $map[ $item_id ] = array(
                    'number' => (string) $doc_number->get_formatted(),
                    'plain'  => $plain,
                );
            }

            if ( ! empty( $map ) ) {
                $order->update_meta_data( MGMIT_INVOICE_SPLIT_META, $map );
                $order->save_meta_data();
            }
        }
    } catch ( \Exception $e ) {
        $map = array();
    } catch ( \Error $e ) {
        $map = array();
    }

    $semaphore->release();

    return $map;
}

/**
 * Limita el PDF a la factura de una sola línea (0 = todas). Lo usa el endpoint
 * invoice-endpoint.php cuando recibe item_id.
 *
 * @param int|false $set Línea a mostrar, 0 para todas, false solo para leer.
 * @return int
 */
function mgmit_invoice_only_item( $set = false ) {
    static $only = 0;
    if ( false !== $set ) {
        $only = (int) $set;
    }
    return $only;
}

/**
 * Prepara las "vistas" de factura que pinta la plantilla invoice.php.
 *
 * Cada vista: number (string|null: null = número nativo), items (líneas del plugin),
 * totals (null = totales del pedido de siempre; array de filas label/value en HTML),
 * gross (float|null: importe de la factura para la instrucción de pago).
 *
 * @param object $doc Documento "invoice" (el propio $this de la plantilla).
 * @return array
 */
function mgmit_build_invoice_views( $doc ) {
    $items  = $doc->get_order_items();
    $legacy = array(
        array(
            'number' => null,
            'items'  => $items,
            'totals' => null,
            'gross'  => null,
        ),
    );

    $order = $doc->order;
    if ( ! mgmit_invoice_split_applies( $order ) ) {
        return $legacy;
    }

    $numbers = mgmit_get_split_invoice_numbers( $doc );
    if ( empty( $numbers ) ) {
        return $legacy;
    }

    // Si alguna línea actual no tiene número (p. ej. añadida después), factura única.
    foreach ( $items as $item_id => $item ) {
        if ( ! isset( $numbers[ $item_id ] ) ) {
            return $legacy;
        }
    }

    $currency = array( 'currency' => $order->get_currency() );
    $labels   = array();
    foreach ( $doc->get_woocommerce_totals() as $key => $total ) {
        $labels[ $key ] = $total['label'];
    }

    $views = array();

    foreach ( $items as $item_id => $item ) {
        $wc_item = $order->get_item( $item_id );
        if ( ! $wc_item ) {
            continue;
        }

        $subtotal_gross = (float) $wc_item->get_subtotal() + (float) $wc_item->get_subtotal_tax();
        $net            = (float) $wc_item->get_total();
        $tax            = (float) $wc_item->get_total_tax();
        $gross          = $net + $tax;
        $discount       = $subtotal_gross - $gross;

        $rows = array();

        if ( $discount > 0.0001 ) {
            $rows[] = array(
                'label' => isset( $labels['cart_subtotal'] ) ? $labels['cart_subtotal'] : 'Zwischensumme',
                'value' => wc_price( $subtotal_gross, $currency ),
            );
            $rows[] = array(
                'label' => isset( $labels['discount'] ) ? $labels['discount'] : 'Rabatt',
                'value' => '-' . wc_price( $discount, $currency ),
            );
        }

        $rows[] = array(
            'label' => ( isset( $labels['order_total'] ) ? $labels['order_total'] : 'Gesamtsumme' ) . ' (netto ' . wp_strip_all_tags( wc_price( $net, $currency ) ) . ')',
            'value' => wc_price( $gross, $currency ),
        );

        $taxes = $wc_item->get_taxes();
        if ( ! empty( $taxes['total'] ) && is_array( $taxes['total'] ) ) {
            foreach ( $taxes['total'] as $rate_id => $amount ) {
                if ( '' === $amount || abs( (float) $amount ) < 0.0001 ) {
                    continue;
                }
                $rows[] = array(
                    'label' => 'enthaltene Mehrwertsteuer zum Satz von ' . WC_Tax::get_rate_percent_value( $rate_id ) . ' %',
                    'value' => wc_price( (float) $amount, $currency ),
                    'class' => 'tax-rate-row',
                );
            }
        }

        $views[] = array(
            'item_id' => $item_id,
            'number' => $numbers[ $item_id ]['number'],
            'items'  => array( $item_id => $item ),
            'totals' => $rows,
            'gross'  => $gross,
        );
    }

    if ( empty( $views ) ) {
        return $legacy;
    }

    $only = mgmit_invoice_only_item();
    if ( $only ) {
        $filtered = array();
        foreach ( $views as $view ) {
            if ( (int) $view['item_id'] === $only ) {
                $filtered[] = $view;
            }
        }
        if ( ! empty( $filtered ) ) {
            return $filtered;
        }
    }

    return $views;
}
