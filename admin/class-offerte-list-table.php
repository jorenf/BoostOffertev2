<?php
/**
 * Offerte List Table.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte\Admin;

use BoostOfferte\Offerte_Post_Type;
use BoostOfferte\Offerte_Model;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Offerte_List_Table class - WP_List_Table for offerte overview.
 */
class Offerte_List_Table extends \WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => 'offerte',
            'plural'   => 'offertes',
            'ajax'     => false,
        ) );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'nummer'     => __( 'Offertenummer', 'boost-offerte' ),
            'klant'      => __( 'Klant', 'boost-offerte' ),
            'datum'      => __( 'Datum', 'boost-offerte' ),
            'geldig_tot' => __( 'Geldig tot', 'boost-offerte' ),
            'status'     => __( 'Status', 'boost-offerte' ),
            'bedrag'     => __( 'Bedrag', 'boost-offerte' ),
        );
    }

    /**
     * Get sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return array(
            'nummer'     => array( 'nummer', false ),
            'datum'      => array( 'date', true ),
            'geldig_tot' => array( 'geldig_tot', false ),
            'bedrag'     => array( 'bedrag', false ),
        );
    }

    /**
     * Get views (status filter tabs).
     *
     * @return array
     */
    protected function get_views() {
        $current = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
        $base    = admin_url( 'admin.php?page=bs-offertes' );

        $statuses = Offerte_Post_Type::get_statuses();
        $counts   = $this->get_status_counts();
        $total    = array_sum( $counts );

        $views = array();

        $class        = ( 'all' === $current ) ? 'current' : '';
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( $base ),
            $class,
            esc_html__( 'Alle', 'boost-offerte' ),
            $total
        );

        foreach ( $statuses as $status => $label ) {
            $count = isset( $counts[ $status ] ) ? $counts[ $status ] : 0;
            $class = ( $status === $current ) ? 'current' : '';
            $slug  = str_replace( 'offerte-', '', $status );

            $views[ $slug ] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url( add_query_arg( 'status', $status, $base ) ),
                $class,
                esc_html( $label ),
                $count
            );
        }

        return $views;
    }

    /**
     * Get bulk actions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return array(
            'cancel' => __( 'Annuleren', 'boost-offerte' ),
            'delete' => __( 'Verwijderen', 'boost-offerte' ),
        );
    }

    /**
     * Process bulk actions.
     */
    public function process_bulk_action() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $action = $this->current_action();
        if ( ! $action ) {
            return;
        }

        $ids = isset( $_GET['offerte'] ) ? array_map( 'absint', (array) $_GET['offerte'] ) : array();

        check_admin_referer( 'bulk-offertes' );

        foreach ( $ids as $id ) {
            $offerte = new Offerte_Model( $id );
            if ( ! $offerte->is_valid() ) {
                continue;
            }

            switch ( $action ) {
                case 'cancel':
                    $offerte->set_status( 'offerte-cancelled' );
                    break;
                case 'delete':
                    wp_delete_post( $id, true );
                    break;
            }
        }
    }

    /**
     * Prepare items for display.
     */
    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 20;
        $paged    = $this->get_pagenum();
        $orderby  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
        $order    = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC';

        $args = array(
            'post_type'      => Offerte_Post_Type::POST_TYPE,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date' === $orderby ? 'date' : 'meta_value',
            'order'          => strtoupper( $order ),
            'post_status'    => array(
                'offerte-draft', 'offerte-sent', 'offerte-viewed',
                'offerte-accepted', 'offerte-expired', 'offerte-cancelled',
            ),
        );

        // Status filter.
        $status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        if ( $status && 'all' !== $status ) {
            $args['post_status'] = array( $status );
        }

        // Sort by meta.
        if ( 'nummer' === $orderby ) {
            $args['meta_key'] = '_bs_quote_number';
            $args['orderby']  = 'meta_value';
        } elseif ( 'geldig_tot' === $orderby ) {
            $args['meta_key'] = '_bs_valid_until';
            $args['orderby']  = 'meta_value';
        } elseif ( 'bedrag' === $orderby ) {
            $args['meta_key']  = '_bs_total';
            $args['orderby']   = 'meta_value_num';
        }

        // Search.
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        if ( $search ) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_bs_quote_number',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => '_bs_customer_data',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ),
            );
        }

        $query = new \WP_Query( $args );

        $this->items = $query->posts;

        $this->set_pagination_args( array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => ceil( $query->found_posts / $per_page ),
        ) );

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    /**
     * Checkbox column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="offerte[]" value="%d" />', $item->ID );
    }

    /**
     * Quote number column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_nummer( $item ) {
        $offerte = new Offerte_Model( $item->ID );
        $number  = $offerte->get_quote_number();
        $url     = admin_url( 'admin.php?page=bs-offerte-edit&id=' . $item->ID );

        return sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url( $url ),
            esc_html( $number )
        );
    }

    /**
     * Customer column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_klant( $item ) {
        $offerte  = new Offerte_Model( $item->ID );
        $customer = $offerte->get_customer_data();

        $name    = esc_html( $customer['naam'] );
        $company = esc_html( $customer['bedrijf'] );

        if ( $company ) {
            return $company . '<br><small>' . $name . '</small>';
        }

        return $name;
    }

    /**
     * Date column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_datum( $item ) {
        return date_i18n( 'd-m-Y', strtotime( $item->post_date ) );
    }

    /**
     * Valid until column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_geldig_tot( $item ) {
        $offerte     = new Offerte_Model( $item->ID );
        $valid_until = $offerte->get_valid_until();

        if ( empty( $valid_until ) ) {
            return '—';
        }

        $formatted = date_i18n( 'd-m-Y', strtotime( $valid_until ) );

        if ( $offerte->is_expired() && ! in_array( $offerte->get_status(), array( 'offerte-accepted', 'offerte-expired', 'offerte-cancelled' ), true ) ) {
            return '<span style="color:#e53e3e;">' . $formatted . '</span>';
        }

        return $formatted;
    }

    /**
     * Status column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_status( $item ) {
        $status = $item->post_status;
        $label  = Offerte_Post_Type::get_status_label( $status );
        $color  = Offerte_Post_Type::get_status_color( $status );

        return sprintf(
            '<span class="bs-offerte-badge" style="background-color:%s;color:#fff;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;white-space:nowrap;">%s</span>',
            esc_attr( $color ),
            esc_html( $label )
        );
    }

    /**
     * Amount column.
     *
     * @param \WP_Post $item Post object.
     * @return string
     */
    protected function column_bedrag( $item ) {
        $offerte = new Offerte_Model( $item->ID );
        $total   = $offerte->get_total();

        return '€ ' . number_format( $total, 2, ',', '.' );
    }

    /**
     * Default column fallback.
     *
     * @param \WP_Post $item        Post object.
     * @param string   $column_name Column name.
     * @return string
     */
    protected function column_default( $item, $column_name ) {
        return '';
    }

    /**
     * Get status counts.
     *
     * @return array
     */
    private function get_status_counts() {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_status, COUNT(*) as count FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status",
            Offerte_Post_Type::POST_TYPE
        ), ARRAY_A );

        $counts = array();
        foreach ( $results as $row ) {
            $counts[ $row['post_status'] ] = (int) $row['count'];
        }

        return $counts;
    }
}
