<?php
/**
 * Offerte to WooCommerce Order Creator.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Order_Creator class - Creates WooCommerce orders from accepted quotes.
 */
class Offerte_Order_Creator {

    /**
     * Offerte model.
     *
     * @var Offerte_Model
     */
    private $offerte;

    /**
     * Constructor.
     *
     * @param Offerte_Model $offerte Offerte instance.
     */
    public function __construct( Offerte_Model $offerte ) {
        $this->offerte = $offerte;
    }

    /**
     * Create a WooCommerce order from the offerte.
     *
     * @return \WC_Order|false
     */
    public function create_order() {
        if ( ! function_exists( 'wc_create_order' ) ) {
            return false;
        }

        $order = wc_create_order( array(
            'status' => 'pending',
        ) );

        if ( is_wp_error( $order ) ) {
            return false;
        }

        $this->set_addresses( $order );
        $this->add_line_items( $order );
        $this->add_shipping( $order );

        // Add order note referencing the offerte.
        $order->add_order_note(
            sprintf(
                /* translators: 1: quote number, 2: datetime */
                __( 'Aangemaakt vanuit offerte %1$s, digitaal ondertekend op %2$s', 'bossier-calculator' ),
                $this->offerte->get_quote_number(),
                $this->offerte->get_signed_at() ?: current_time( 'mysql' )
            )
        );

        // Store link between order and offerte.
        $order->update_meta_data( '_bs_offerte_id', $this->offerte->get_id() );
        $order->update_meta_data( '_bs_quote_number', $this->offerte->get_quote_number() );

        $order->calculate_totals();
        $order->save();

        // Link the order back to the offerte.
        $this->offerte->set_wc_order_id( $order->get_id() );

        return $order;
    }

    /**
     * Set billing and shipping addresses from customer data.
     *
     * @param \WC_Order $order WC Order instance.
     */
    private function set_addresses( \WC_Order $order ) {
        $customer = $this->offerte->get_customer_data();
        $adres    = isset( $customer['adres'] ) ? $customer['adres'] : array();

        // Parse name into first/last.
        $name_parts = explode( ' ', $customer['naam'], 2 );
        $first_name = $name_parts[0];
        $last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';

        // Parse street into address_1 (WC expects street + number).
        $address_1 = isset( $adres['straat'] ) ? $adres['straat'] : '';

        $billing = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'company'    => $customer['bedrijf'],
            'email'      => $customer['email'],
            'phone'      => $customer['tel'],
            'address_1'  => $address_1,
            'postcode'   => isset( $adres['postcode'] ) ? $adres['postcode'] : '',
            'city'       => isset( $adres['plaats'] ) ? $adres['plaats'] : '',
            'country'    => isset( $adres['land'] ) ? $adres['land'] : 'NL',
        );

        $order->set_address( $billing, 'billing' );
        $order->set_address( $billing, 'shipping' );
    }

    /**
     * Add line items to the order.
     *
     * Matches the meta format from Frontend\Order::save_order_item_meta()
     * to ensure compatibility with existing order display, PDFs, and emails.
     *
     * @param \WC_Order $order WC Order instance.
     */
    private function add_line_items( \WC_Order $order ) {
        $items = $this->offerte->get_items();

        foreach ( $items as $item_data ) {
            $product_id = ! empty( $item_data['product_id'] ) ? absint( $item_data['product_id'] ) : 0;
            $quantity   = ! empty( $item_data['quantity'] ) ? (int) $item_data['quantity'] : 1;
            $unit_price = ! empty( $item_data['unit_price'] ) ? (float) $item_data['unit_price'] : 0;

            if ( $product_id && ( $product = wc_get_product( $product_id ) ) ) {
                $item_id = $order->add_product( $product, $quantity, array(
                    'subtotal' => $unit_price * $quantity,
                    'total'    => $unit_price * $quantity,
                ) );
            } else {
                // Add as a fee-like line item for custom/non-WC products.
                $item = new \WC_Order_Item_Product();
                $item->set_name( ! empty( $item_data['title'] ) ? $item_data['title'] : __( 'Product', 'bossier-calculator' ) );
                $item->set_quantity( $quantity );
                $item->set_subtotal( $unit_price * $quantity );
                $item->set_total( $unit_price * $quantity );
                $item_id = $order->add_item( $item );
            }

            if ( ! $item_id ) {
                continue;
            }

            $item = $order->get_item( $item_id );
            if ( ! $item ) {
                continue;
            }

            // Add calculator meta (same format as Frontend\Order::save_order_item_meta).
            if ( ! empty( $item_data['calculator_id'] ) ) {
                $item->add_meta_data( '_bossier_calculator_id', absint( $item_data['calculator_id'] ), true );
            }

            if ( ! empty( $item_data['selections'] ) ) {
                $item->add_meta_data( '_bossier_selections', $item_data['selections'], true );
            }

            if ( ! empty( $item_data['display_data'] ) ) {
                $item->add_meta_data( '_bossier_display_data', $item_data['display_data'], true );

                // Also save visible meta for standard WC display.
                foreach ( $item_data['display_data'] as $field_id => $data ) {
                    if ( ! empty( $data['value'] ) ) {
                        $item->add_meta_data( '_bossier_field_' . $field_id, $data, true );
                        $item->add_meta_data( $data['label'], $data['value'], true );
                    }
                }
            }

            $item->add_meta_data( '_bossier_calculated_price', $unit_price, true );

            if ( ! empty( $item_data['weight_per_unit'] ) ) {
                $item->add_meta_data( '_bossier_calculated_weight', (float) $item_data['weight_per_unit'], true );
                $weight_unit = get_option( 'woocommerce_weight_unit', 'kg' );
                $item->add_meta_data(
                    __( 'Weight', 'bossier-calculator' ),
                    wc_format_localized_decimal( $item_data['weight_per_unit'] ) . ' ' . $weight_unit,
                    true
                );
            }

            if ( ! empty( $item_data['breakdown'] ) ) {
                $item->add_meta_data( '_bossier_breakdown', $item_data['breakdown'], true );
            }

            $item->save();
        }
    }

    /**
     * Add shipping line to the order.
     *
     * @param \WC_Order $order WC Order instance.
     */
    private function add_shipping( \WC_Order $order ) {
        $shipping_cost = $this->offerte->get_shipping_cost();

        if ( $shipping_cost > 0 ) {
            $shipping = new \WC_Order_Item_Shipping();
            $shipping->set_method_title( __( 'Verzending', 'bossier-calculator' ) );
            $shipping->set_method_id( 'flat_rate' );
            $shipping->set_total( $shipping_cost );
            $order->add_item( $shipping );
        }
    }
}
