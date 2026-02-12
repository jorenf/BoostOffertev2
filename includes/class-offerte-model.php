<?php
/**
 * Offerte Data Model.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Model class - CRUD wrapper for a single offerte.
 */
class Offerte_Model {

    /**
     * Post ID.
     *
     * @var int
     */
    private $id;

    /**
     * WP_Post object.
     *
     * @var \WP_Post|null
     */
    private $post;

    /**
     * Constructor.
     *
     * @param int $offerte_id Post ID.
     */
    public function __construct( $offerte_id ) {
        $this->id   = absint( $offerte_id );
        $this->post = get_post( $this->id );
    }

    /**
     * Check if model is valid.
     *
     * @return bool
     */
    public function is_valid() {
        return $this->post && Offerte_Post_Type::POST_TYPE === $this->post->post_type;
    }

    /**
     * Get post ID.
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get WP_Post object.
     *
     * @return \WP_Post|null
     */
    public function get_post() {
        return $this->post;
    }

    // ─── Getters ────────────────────────────────────────────

    /**
     * Get quote number.
     *
     * @return string
     */
    public function get_quote_number() {
        return (string) get_post_meta( $this->id, '_bs_quote_number', true );
    }

    /**
     * Get current status.
     *
     * @return string
     */
    public function get_status() {
        return $this->post ? $this->post->post_status : '';
    }

    /**
     * Get status label.
     *
     * @return string
     */
    public function get_status_label() {
        return Offerte_Post_Type::get_status_label( $this->get_status() );
    }

    /**
     * Get customer data.
     *
     * @return array
     */
    public function get_customer_data() {
        $data = get_post_meta( $this->id, '_bs_customer_data', true );
        return is_array( $data ) ? $data : array(
            'bedrijf' => '',
            'naam'    => '',
            'email'   => '',
            'tel'     => '',
            'adres'   => array(
                'straat'   => '',
                'postcode' => '',
                'plaats'   => '',
                'land'     => 'NL',
            ),
        );
    }

    /**
     * Get quote line items.
     *
     * @return array
     */
    public function get_items() {
        $items = get_post_meta( $this->id, '_bs_quote_items', true );
        return is_array( $items ) ? $items : array();
    }

    /**
     * Get shipping cost.
     *
     * @return float
     */
    public function get_shipping_cost() {
        return (float) get_post_meta( $this->id, '_bs_shipping_cost', true );
    }

    /**
     * Get subtotal.
     *
     * @return float
     */
    public function get_subtotal() {
        return (float) get_post_meta( $this->id, '_bs_subtotal', true );
    }

    /**
     * Get tax amount.
     *
     * @return float
     */
    public function get_tax() {
        return (float) get_post_meta( $this->id, '_bs_tax', true );
    }

    /**
     * Get total.
     *
     * @return float
     */
    public function get_total() {
        return (float) get_post_meta( $this->id, '_bs_total', true );
    }

    /**
     * Get valid until date.
     *
     * @return string Y-m-d format.
     */
    public function get_valid_until() {
        return (string) get_post_meta( $this->id, '_bs_valid_until', true );
    }

    /**
     * Get payment method.
     *
     * @return string
     */
    public function get_payment_method() {
        return (string) get_post_meta( $this->id, '_bs_payment_method', true );
    }

    /**
     * Get reminder days.
     *
     * @return int|null
     */
    public function get_reminder_days() {
        $days = get_post_meta( $this->id, '_bs_reminder_days', true );
        return '' !== $days ? (int) $days : null;
    }

    /**
     * Get customer note.
     *
     * @return string
     */
    public function get_customer_note() {
        return (string) get_post_meta( $this->id, '_bs_customer_note', true );
    }

    /**
     * Get access token.
     *
     * @return string
     */
    public function get_access_token() {
        return (string) get_post_meta( $this->id, '_bs_access_token', true );
    }

    /**
     * Get signature image path.
     *
     * @return string
     */
    public function get_signature_image() {
        return (string) get_post_meta( $this->id, '_bs_signature_image', true );
    }

    /**
     * Get signed at timestamp.
     *
     * @return string|null
     */
    public function get_signed_at() {
        $val = get_post_meta( $this->id, '_bs_signed_at', true );
        return $val ? (string) $val : null;
    }

    /**
     * Get signed IP address.
     *
     * @return string
     */
    public function get_signed_ip() {
        return (string) get_post_meta( $this->id, '_bs_signed_ip', true );
    }

    /**
     * Get linked WooCommerce order ID.
     *
     * @return int|null
     */
    public function get_wc_order_id() {
        $val = get_post_meta( $this->id, '_bs_wc_order_id', true );
        return $val ? (int) $val : null;
    }

    /**
     * Get first viewed at timestamp.
     *
     * @return string|null
     */
    public function get_viewed_at() {
        $val = get_post_meta( $this->id, '_bs_viewed_at', true );
        return $val ? (string) $val : null;
    }

    /**
     * Get sent at timestamp.
     *
     * @return string|null
     */
    public function get_sent_at() {
        $val = get_post_meta( $this->id, '_bs_sent_at', true );
        return $val ? (string) $val : null;
    }

    /**
     * Get email log.
     *
     * @return array
     */
    public function get_email_log() {
        $log = get_post_meta( $this->id, '_bs_email_log', true );
        return is_array( $log ) ? $log : array();
    }

    /**
     * Get PDF file path.
     *
     * @return string
     */
    public function get_pdf_path() {
        return (string) get_post_meta( $this->id, '_bs_pdf_path', true );
    }

    /**
     * Check if reminder was already sent.
     *
     * @return bool
     */
    public function is_reminder_sent() {
        return (bool) get_post_meta( $this->id, '_bs_reminder_sent', true );
    }

    /**
     * Get the public URL for this offerte.
     *
     * @return string
     */
    public function get_public_url() {
        $number = $this->get_quote_number();
        $token  = $this->get_access_token();

        return add_query_arg( 'token', $token, home_url( '/offerte/' . $number . '/' ) );
    }

    /**
     * Check if offerte is expired.
     *
     * @return bool
     */
    public function is_expired() {
        $valid_until = $this->get_valid_until();
        if ( empty( $valid_until ) ) {
            return false;
        }
        return strtotime( $valid_until ) < strtotime( current_time( 'Y-m-d' ) );
    }

    /**
     * Check if offerte can be accepted (sent or viewed, not expired).
     *
     * @return bool
     */
    public function can_accept() {
        $status = $this->get_status();
        if ( ! in_array( $status, array( 'offerte-sent', 'offerte-viewed' ), true ) ) {
            return false;
        }
        return ! $this->is_expired();
    }

    // ─── Setters ────────────────────────────────────────────

    /**
     * Set post status.
     *
     * @param string $status New status.
     * @return bool
     */
    public function set_status( $status ) {
        $result = wp_update_post( array(
            'ID'          => $this->id,
            'post_status' => $status,
        ) );

        if ( $result && ! is_wp_error( $result ) ) {
            $this->post = get_post( $this->id );
            return true;
        }
        return false;
    }

    /**
     * Set customer data.
     *
     * @param array $data Customer data.
     */
    public function set_customer_data( $data ) {
        update_post_meta( $this->id, '_bs_customer_data', $data );
    }

    /**
     * Set quote items.
     *
     * @param array $items Line items.
     */
    public function set_items( $items ) {
        update_post_meta( $this->id, '_bs_quote_items', $items );
    }

    /**
     * Set shipping cost.
     *
     * @param float $cost Shipping cost.
     */
    public function set_shipping_cost( $cost ) {
        update_post_meta( $this->id, '_bs_shipping_cost', (float) $cost );
    }

    /**
     * Set totals.
     *
     * @param float $subtotal Subtotal.
     * @param float $tax      Tax amount.
     * @param float $total    Total.
     */
    public function set_totals( $subtotal, $tax, $total ) {
        update_post_meta( $this->id, '_bs_subtotal', (float) $subtotal );
        update_post_meta( $this->id, '_bs_tax', (float) $tax );
        update_post_meta( $this->id, '_bs_total', (float) $total );
    }

    /**
     * Set valid until date.
     *
     * @param string $date Y-m-d format.
     */
    public function set_valid_until( $date ) {
        update_post_meta( $this->id, '_bs_valid_until', sanitize_text_field( $date ) );
    }

    /**
     * Set payment method.
     *
     * @param string $method Payment method slug.
     */
    public function set_payment_method( $method ) {
        update_post_meta( $this->id, '_bs_payment_method', sanitize_key( $method ) );
    }

    /**
     * Set reminder days.
     *
     * @param int|null $days Days until reminder.
     */
    public function set_reminder_days( $days ) {
        update_post_meta( $this->id, '_bs_reminder_days', null !== $days ? (int) $days : '' );
    }

    /**
     * Set customer note.
     *
     * @param string $note Note text.
     */
    public function set_customer_note( $note ) {
        update_post_meta( $this->id, '_bs_customer_note', sanitize_textarea_field( $note ) );
    }

    /**
     * Set signature data on acceptance.
     *
     * @param string $image_path Path to signature PNG.
     * @param string $ip         Client IP address.
     */
    public function set_signature( $image_path, $ip ) {
        update_post_meta( $this->id, '_bs_signature_image', sanitize_text_field( $image_path ) );
        update_post_meta( $this->id, '_bs_signed_at', current_time( 'mysql' ) );
        update_post_meta( $this->id, '_bs_signed_ip', sanitize_text_field( $ip ) );
    }

    /**
     * Set linked WooCommerce order ID.
     *
     * @param int $order_id WC order ID.
     */
    public function set_wc_order_id( $order_id ) {
        update_post_meta( $this->id, '_bs_wc_order_id', absint( $order_id ) );
    }

    /**
     * Mark as viewed (first time only).
     */
    public function mark_viewed() {
        if ( empty( $this->get_viewed_at() ) ) {
            update_post_meta( $this->id, '_bs_viewed_at', current_time( 'mysql' ) );
        }
    }

    /**
     * Mark as sent.
     */
    public function mark_sent() {
        update_post_meta( $this->id, '_bs_sent_at', current_time( 'mysql' ) );
    }

    /**
     * Mark reminder as sent.
     */
    public function mark_reminder_sent() {
        update_post_meta( $this->id, '_bs_reminder_sent', true );
    }

    /**
     * Set PDF file path.
     *
     * @param string $path File path.
     */
    public function set_pdf_path( $path ) {
        update_post_meta( $this->id, '_bs_pdf_path', sanitize_text_field( $path ) );
    }

    /**
     * Add an email log entry.
     *
     * @param array $entry Log entry with timestamp, type, recipient, success, subject.
     */
    public function add_email_log_entry( $entry ) {
        $log   = $this->get_email_log();
        $log[] = $entry;
        update_post_meta( $this->id, '_bs_email_log', $log );
    }

    /**
     * Recalculate totals from items and shipping.
     */
    public function recalculate_totals() {
        $items    = $this->get_items();
        $subtotal = 0;

        foreach ( $items as $item ) {
            $subtotal += isset( $item['line_total'] ) ? (float) $item['line_total'] : 0;
        }

        $shipping = $this->get_shipping_cost();
        $taxable  = $subtotal + $shipping;
        $tax      = round( $taxable * 0.21, 2 );
        $total    = round( $taxable + $tax, 2 );

        $this->set_totals( $subtotal, $tax, $total );
    }

    // ─── Factory ────────────────────────────────────────────

    /**
     * Create a new offerte.
     *
     * @param array $data Initial data.
     * @return self|false
     */
    public static function create( $data = array() ) {
        $quote_number = Offerte_Numbering::generate();
        $token        = bin2hex( random_bytes( 16 ) );

        $post_id = wp_insert_post( array(
            'post_type'   => Offerte_Post_Type::POST_TYPE,
            'post_title'  => $quote_number,
            'post_status' => 'offerte-draft',
        ) );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return false;
        }

        update_post_meta( $post_id, '_bs_quote_number', $quote_number );
        update_post_meta( $post_id, '_bs_access_token', $token );

        $model = new self( $post_id );

        // Apply defaults from settings.
        $settings = Offerte_Settings::get_settings();

        if ( empty( $data['valid_until'] ) ) {
            $validity_days = $settings['offerte_default_validity'];
            $model->set_valid_until( gmdate( 'Y-m-d', strtotime( '+' . $validity_days . ' days' ) ) );
        } else {
            $model->set_valid_until( $data['valid_until'] );
        }

        if ( ! isset( $data['reminder_days'] ) ) {
            $model->set_reminder_days( $settings['offerte_default_reminder'] );
        } else {
            $model->set_reminder_days( $data['reminder_days'] );
        }

        if ( isset( $data['customer_data'] ) ) {
            $model->set_customer_data( $data['customer_data'] );
        }

        if ( isset( $data['items'] ) ) {
            $model->set_items( $data['items'] );
        }

        if ( isset( $data['shipping_cost'] ) ) {
            $model->set_shipping_cost( $data['shipping_cost'] );
        }

        if ( isset( $data['payment_method'] ) ) {
            $model->set_payment_method( $data['payment_method'] );
        }

        if ( isset( $data['customer_note'] ) ) {
            $model->set_customer_note( $data['customer_note'] );
        }

        $model->recalculate_totals();

        return $model;
    }
}
