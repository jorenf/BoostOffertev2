<?php
/**
 * Offerte AJAX handlers.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Ajax class - All AJAX handlers for admin and frontend.
 */
class Offerte_Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        // Admin AJAX.
        add_action( 'wp_ajax_bs_save_offerte', array( $this, 'ajax_save_offerte' ) );
        add_action( 'wp_ajax_bs_send_offerte', array( $this, 'ajax_send_offerte' ) );
        add_action( 'wp_ajax_bs_calc_line_item', array( $this, 'ajax_calc_line_item' ) );
        add_action( 'wp_ajax_bs_search_customers', array( $this, 'ajax_search_customers' ) );
        add_action( 'wp_ajax_bs_search_products', array( $this, 'ajax_search_products' ) );
        add_action( 'wp_ajax_bs_get_calc_fields', array( $this, 'ajax_get_calc_fields' ) );
        add_action( 'wp_ajax_bs_generate_offerte_pdf', array( $this, 'ajax_generate_pdf' ) );
        add_action( 'wp_ajax_bs_cancel_offerte', array( $this, 'ajax_cancel_offerte' ) );
        add_action( 'wp_ajax_bs_duplicate_offerte', array( $this, 'ajax_duplicate_offerte' ) );

        // Frontend AJAX.
        add_action( 'wp_ajax_bs_accept_offerte', array( $this, 'ajax_accept_offerte' ) );
        add_action( 'wp_ajax_nopriv_bs_accept_offerte', array( $this, 'ajax_accept_offerte' ) );
        add_action( 'wp_ajax_bs_download_offerte_pdf', array( $this, 'ajax_download_pdf' ) );
        add_action( 'wp_ajax_nopriv_bs_download_offerte_pdf', array( $this, 'ajax_download_pdf' ) );
    }

    /**
     * Save or create an offerte.
     */
    public function ajax_save_offerte() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $data       = $this->sanitize_offerte_data( $_POST );
        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;

        if ( $offerte_id ) {
            $offerte = new Offerte_Model( $offerte_id );
            if ( ! $offerte->is_valid() ) {
                wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
            }
        } else {
            $offerte = Offerte_Model::create( $data );
            if ( ! $offerte ) {
                wp_send_json_error( array( 'message' => __( 'Offerte kon niet worden aangemaakt.', 'bossier-calculator' ) ) );
            }
        }

        // Update fields.
        if ( isset( $data['customer_data'] ) ) {
            $offerte->set_customer_data( $data['customer_data'] );
        }
        if ( isset( $data['items'] ) ) {
            $offerte->set_items( $data['items'] );
        }
        if ( isset( $data['shipping_cost'] ) ) {
            $offerte->set_shipping_cost( $data['shipping_cost'] );
        }
        if ( isset( $data['valid_until'] ) ) {
            $offerte->set_valid_until( $data['valid_until'] );
        }
        if ( isset( $data['payment_method'] ) ) {
            $offerte->set_payment_method( $data['payment_method'] );
        }
        if ( isset( $data['reminder_days'] ) ) {
            $offerte->set_reminder_days( $data['reminder_days'] );
        }
        if ( isset( $data['customer_note'] ) ) {
            $offerte->set_customer_note( $data['customer_note'] );
        }

        $offerte->recalculate_totals();

        // Update title to match quote number.
        wp_update_post( array(
            'ID'         => $offerte->get_id(),
            'post_title' => $offerte->get_quote_number(),
        ) );

        wp_send_json_success( array(
            'message'    => __( 'Offerte opgeslagen.', 'bossier-calculator' ),
            'offerte_id' => $offerte->get_id(),
            'number'     => $offerte->get_quote_number(),
            'total'      => $offerte->get_total(),
        ) );
    }

    /**
     * Send an offerte to the customer.
     */
    public function ajax_send_offerte() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;
        $offerte    = new Offerte_Model( $offerte_id );

        if ( ! $offerte->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
        }

        // Generate PDF.
        $pdf     = new Offerte_PDF( $offerte );
        $pdf_path = $pdf->generate_and_save();

        if ( ! $pdf_path ) {
            wp_send_json_error( array( 'message' => __( 'PDF kon niet worden gegenereerd.', 'bossier-calculator' ) ) );
        }

        // Send email.
        $email   = new Offerte_Email();
        $success = $email->send_quote( $offerte );

        if ( ! $success ) {
            wp_send_json_error( array( 'message' => __( 'E-mail kon niet worden verzonden.', 'bossier-calculator' ) ) );
        }

        // Update status.
        $offerte->set_status( 'offerte-sent' );
        $offerte->mark_sent();

        wp_send_json_success( array(
            'message' => __( 'Offerte is verzonden.', 'bossier-calculator' ),
            'url'     => $offerte->get_public_url(),
        ) );
    }

    /**
     * Calculate a line item price using the calculator engine.
     */
    public function ajax_calc_line_item() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $calculator_id = ! empty( $_POST['calculator_id'] ) ? absint( $_POST['calculator_id'] ) : 0;
        $product_id    = ! empty( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $selections    = ! empty( $_POST['selections'] ) && is_array( $_POST['selections'] ) ? $_POST['selections'] : array();

        if ( ! $calculator_id ) {
            wp_send_json_error( array( 'message' => __( 'Geen calculator opgegeven.', 'bossier-calculator' ) ) );
        }

        $result = \Bossier\Calculator\Price_Calculator::calculate_from_request( $calculator_id, $selections, $product_id );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Berekening mislukt.', 'bossier-calculator' ) ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Search WooCommerce customers.
     */
    public function ajax_search_customers() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $term = ! empty( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
        if ( strlen( $term ) < 2 ) {
            wp_send_json_success( array() );
        }

        $results = array();

        // Search WP users with customer role.
        $users = get_users( array(
            'search'  => '*' . $term . '*',
            'number'  => 20,
            'role__in' => array( 'customer', 'subscriber', 'administrator', 'shop_manager' ),
        ) );

        foreach ( $users as $user ) {
            $results[] = array(
                'id'      => $user->ID,
                'label'   => $user->display_name . ' (' . $user->user_email . ')',
                'bedrijf' => get_user_meta( $user->ID, 'billing_company', true ),
                'naam'    => get_user_meta( $user->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $user->ID, 'billing_last_name', true ),
                'email'   => $user->user_email,
                'tel'     => get_user_meta( $user->ID, 'billing_phone', true ),
                'adres'   => array(
                    'straat'   => get_user_meta( $user->ID, 'billing_address_1', true ),
                    'postcode' => get_user_meta( $user->ID, 'billing_postcode', true ),
                    'plaats'   => get_user_meta( $user->ID, 'billing_city', true ),
                    'land'     => get_user_meta( $user->ID, 'billing_country', true ) ?: 'NL',
                ),
            );
        }

        wp_send_json_success( $results );
    }

    /**
     * Search WooCommerce products that have a calculator linked.
     */
    public function ajax_search_products() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $term = ! empty( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            's'              => $term,
        );

        $products = get_posts( $args );
        $results  = array();

        foreach ( $products as $product_post ) {
            $product = wc_get_product( $product_post->ID );
            if ( ! $product ) {
                continue;
            }

            $calculator_id = get_post_meta( $product_post->ID, '_bossier_calculator_id', true );

            $results[] = array(
                'id'            => $product_post->ID,
                'title'         => $product->get_name(),
                'price'         => $product->get_price(),
                'calculator_id' => $calculator_id ? absint( $calculator_id ) : 0,
            );
        }

        wp_send_json_success( $results );
    }

    /**
     * Get calculator field configuration for a given calculator ID.
     */
    public function ajax_get_calc_fields() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $calculator_id = ! empty( $_GET['calculator_id'] ) ? absint( $_GET['calculator_id'] ) : 0;
        if ( ! $calculator_id ) {
            wp_send_json_error( array( 'message' => __( 'Geen calculator ID.', 'bossier-calculator' ) ) );
        }

        $calculator = new \Bossier\Calculator\Calculator( $calculator_id );
        if ( ! $calculator->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Calculator niet gevonden.', 'bossier-calculator' ) ) );
        }

        $config = $calculator->get_config();

        wp_send_json_success( array(
            'fields'   => $config['fields'] ?? array(),
            'settings' => $config['settings'] ?? array(),
        ) );
    }

    /**
     * Generate offerte PDF.
     */
    public function ajax_generate_pdf() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;
        $offerte    = new Offerte_Model( $offerte_id );

        if ( ! $offerte->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
        }

        $pdf      = new Offerte_PDF( $offerte );
        $pdf_path = $pdf->generate_and_save();

        if ( ! $pdf_path ) {
            wp_send_json_error( array( 'message' => __( 'PDF kon niet worden gegenereerd.', 'bossier-calculator' ) ) );
        }

        wp_send_json_success( array(
            'message'  => __( 'PDF gegenereerd.', 'bossier-calculator' ),
            'filename' => $pdf->get_filename(),
        ) );
    }

    /**
     * Cancel an offerte.
     */
    public function ajax_cancel_offerte() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;
        $offerte    = new Offerte_Model( $offerte_id );

        if ( ! $offerte->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
        }

        $offerte->set_status( 'offerte-cancelled' );

        wp_send_json_success( array(
            'message' => __( 'Offerte is geannuleerd.', 'bossier-calculator' ),
        ) );
    }

    /**
     * Duplicate an offerte.
     */
    public function ajax_duplicate_offerte() {
        check_ajax_referer( 'bs_offerte_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'bossier-calculator' ) ) );
        }

        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;
        $original   = new Offerte_Model( $offerte_id );

        if ( ! $original->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
        }

        $new_offerte = Offerte_Model::create( array(
            'customer_data'  => $original->get_customer_data(),
            'items'          => $original->get_items(),
            'shipping_cost'  => $original->get_shipping_cost(),
            'payment_method' => $original->get_payment_method(),
            'customer_note'  => $original->get_customer_note(),
        ) );

        if ( ! $new_offerte ) {
            wp_send_json_error( array( 'message' => __( 'Dupliceren mislukt.', 'bossier-calculator' ) ) );
        }

        wp_send_json_success( array(
            'message'    => __( 'Offerte gedupliceerd.', 'bossier-calculator' ),
            'offerte_id' => $new_offerte->get_id(),
            'number'     => $new_offerte->get_quote_number(),
            'edit_url'   => admin_url( 'admin.php?page=bs-offerte-edit&id=' . $new_offerte->get_id() ),
        ) );
    }

    /**
     * Accept offerte (frontend, no login required).
     */
    public function ajax_accept_offerte() {
        $offerte_id = ! empty( $_POST['offerte_id'] ) ? absint( $_POST['offerte_id'] ) : 0;
        $token      = ! empty( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

        $offerte = new Offerte_Model( $offerte_id );

        if ( ! $offerte->is_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Offerte niet gevonden.', 'bossier-calculator' ) ) );
        }

        // Validate token.
        if ( ! hash_equals( $offerte->get_access_token(), $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Ongeldige toegang.', 'bossier-calculator' ) ) );
        }

        // Verify nonce.
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'bs_offerte_accept_' . $offerte_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Beveiligingscontrole mislukt.', 'bossier-calculator' ) ) );
        }

        // Check if offerte can be accepted.
        if ( ! $offerte->can_accept() ) {
            wp_send_json_error( array( 'message' => __( 'Deze offerte kan niet meer worden geaccepteerd.', 'bossier-calculator' ) ) );
        }

        // Save signature.
        $signature_data = ! empty( $_POST['signature'] ) ? $_POST['signature'] : '';
        if ( empty( $signature_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Handtekening is vereist.', 'bossier-calculator' ) ) );
        }

        $signature_path = Offerte_Signature::save_signature( $signature_data, $offerte->get_quote_number() );
        if ( ! $signature_path ) {
            wp_send_json_error( array( 'message' => __( 'Handtekening kon niet worden opgeslagen.', 'bossier-calculator' ) ) );
        }

        // Record signature with IP.
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $offerte->set_signature( $signature_path, $ip );

        // Update status.
        $offerte->set_status( 'offerte-accepted' );

        // Create WooCommerce order.
        $order_creator = new Offerte_Order_Creator( $offerte );
        $order         = $order_creator->create_order();

        // Send notification to admin.
        $email = new Offerte_Email();
        $email->send_accepted_notification( $offerte );

        $response = array(
            'message' => __( 'Offerte geaccepteerd! Uw bestelling wordt verwerkt.', 'bossier-calculator' ),
        );

        if ( $order ) {
            $response['order_id']      = $order->get_id();
            $response['checkout_url']  = $order->get_checkout_payment_url();
            $response['redirect_url']  = $order->get_checkout_payment_url();
        }

        wp_send_json_success( $response );
    }

    /**
     * Download offerte PDF (frontend, token-based access).
     */
    public function ajax_download_pdf() {
        $offerte_id = ! empty( $_GET['offerte_id'] ) ? absint( $_GET['offerte_id'] ) : 0;
        $token      = ! empty( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

        $offerte = new Offerte_Model( $offerte_id );

        if ( ! $offerte->is_valid() ) {
            wp_die( esc_html__( 'Offerte niet gevonden.', 'bossier-calculator' ), '', array( 'response' => 404 ) );
        }

        if ( ! hash_equals( $offerte->get_access_token(), $token ) ) {
            wp_die( esc_html__( 'Ongeldige toegang.', 'bossier-calculator' ), '', array( 'response' => 403 ) );
        }

        // Generate or serve existing PDF.
        $pdf_path = $offerte->get_pdf_path();
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            $content  = file_get_contents( $pdf_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $filename = 'Offerte-' . $offerte->get_quote_number() . '.pdf';

            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Content-Length: ' . strlen( $content ) );
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        // Generate new PDF.
        $pdf = new Offerte_PDF( $offerte );
        $pdf->generate_and_save();
        $pdf->stream();
    }

    /**
     * Sanitize offerte form data.
     *
     * @param array $raw Raw POST data.
     * @return array Sanitized data.
     */
    private function sanitize_offerte_data( $raw ) {
        $data = array();

        // Customer data.
        if ( ! empty( $raw['customer'] ) && is_array( $raw['customer'] ) ) {
            $c = $raw['customer'];
            $data['customer_data'] = array(
                'bedrijf' => isset( $c['bedrijf'] ) ? sanitize_text_field( $c['bedrijf'] ) : '',
                'naam'    => isset( $c['naam'] ) ? sanitize_text_field( $c['naam'] ) : '',
                'email'   => isset( $c['email'] ) ? sanitize_email( $c['email'] ) : '',
                'tel'     => isset( $c['tel'] ) ? sanitize_text_field( $c['tel'] ) : '',
                'adres'   => array(
                    'straat'   => isset( $c['straat'] ) ? sanitize_text_field( $c['straat'] ) : '',
                    'postcode' => isset( $c['postcode'] ) ? sanitize_text_field( $c['postcode'] ) : '',
                    'plaats'   => isset( $c['plaats'] ) ? sanitize_text_field( $c['plaats'] ) : '',
                    'land'     => isset( $c['land'] ) ? sanitize_key( $c['land'] ) : 'NL',
                ),
            );
        }

        // Line items.
        if ( ! empty( $raw['items'] ) && is_array( $raw['items'] ) ) {
            $data['items'] = array();
            foreach ( $raw['items'] as $item ) {
                $data['items'][] = array(
                    'product_id'    => ! empty( $item['product_id'] ) ? absint( $item['product_id'] ) : 0,
                    'calculator_id' => ! empty( $item['calculator_id'] ) ? absint( $item['calculator_id'] ) : 0,
                    'title'         => ! empty( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
                    'category'      => ! empty( $item['category'] ) ? sanitize_key( $item['category'] ) : '',
                    'selections'    => ! empty( $item['selections'] ) && is_array( $item['selections'] ) ? $item['selections'] : array(),
                    'display_data'  => ! empty( $item['display_data'] ) && is_array( $item['display_data'] ) ? $item['display_data'] : array(),
                    'breakdown'     => ! empty( $item['breakdown'] ) && is_array( $item['breakdown'] ) ? $item['breakdown'] : array(),
                    'specs'         => array(
                        'lengte'   => ! empty( $item['lengte'] ) ? sanitize_text_field( $item['lengte'] ) : '',
                        'breedte'  => ! empty( $item['breedte'] ) ? sanitize_text_field( $item['breedte'] ) : '',
                        'hoogte'   => ! empty( $item['hoogte'] ) ? sanitize_text_field( $item['hoogte'] ) : '',
                        'kleur'    => ! empty( $item['kleur'] ) ? sanitize_text_field( $item['kleur'] ) : '',
                        'afwerking' => ! empty( $item['afwerking'] ) ? sanitize_text_field( $item['afwerking'] ) : '',
                    ),
                    'quantity'       => ! empty( $item['quantity'] ) ? max( 1, absint( $item['quantity'] ) ) : 1,
                    'unit_price'     => ! empty( $item['unit_price'] ) ? (float) $item['unit_price'] : 0,
                    'line_total'     => ! empty( $item['line_total'] ) ? (float) $item['line_total'] : 0,
                    'weight_per_unit' => ! empty( $item['weight_per_unit'] ) ? (float) $item['weight_per_unit'] : 0,
                    'line_weight'    => ! empty( $item['line_weight'] ) ? (float) $item['line_weight'] : 0,
                );
            }
        }

        // Simple fields.
        if ( isset( $raw['shipping_cost'] ) ) {
            $data['shipping_cost'] = (float) $raw['shipping_cost'];
        }
        if ( isset( $raw['valid_until'] ) ) {
            $data['valid_until'] = sanitize_text_field( $raw['valid_until'] );
        }
        if ( isset( $raw['payment_method'] ) ) {
            $data['payment_method'] = sanitize_key( $raw['payment_method'] );
        }
        if ( isset( $raw['reminder_days'] ) ) {
            $data['reminder_days'] = '' !== $raw['reminder_days'] ? absint( $raw['reminder_days'] ) : null;
        }
        if ( isset( $raw['customer_note'] ) ) {
            $data['customer_note'] = sanitize_textarea_field( $raw['customer_note'] );
        }

        return $data;
    }
}
