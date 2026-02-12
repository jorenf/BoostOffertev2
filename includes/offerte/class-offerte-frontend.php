<?php
/**
 * Offerte Frontend handler.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Frontend class - Handles public-facing offerte pages.
 */
class Offerte_Frontend {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_filter( 'template_include', array( $this, 'handle_template' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    /**
     * Add rewrite rules for offerte public page.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^offerte/([A-Za-z0-9-]+)/?$',
            'index.php?bs_offerte_number=$matches[1]',
            'top'
        );
    }

    /**
     * Register custom query variables.
     *
     * @param array $vars Existing query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'bs_offerte_number';
        return $vars;
    }

    /**
     * Handle the template for offerte pages.
     *
     * @param string $template Current template path.
     * @return string
     */
    public function handle_template( $template ) {
        $quote_number = get_query_var( 'bs_offerte_number' );

        if ( empty( $quote_number ) ) {
            return $template;
        }

        // Look up offerte by quote number.
        $posts = get_posts( array(
            'post_type'   => Offerte_Post_Type::POST_TYPE,
            'meta_key'    => '_bs_quote_number',
            'meta_value'  => sanitize_text_field( $quote_number ),
            'post_status' => array(
                'offerte-draft', 'offerte-sent', 'offerte-viewed',
                'offerte-accepted', 'offerte-expired', 'offerte-cancelled',
            ),
            'numberposts' => 1,
        ) );

        if ( empty( $posts ) ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return $template;
        }

        $offerte = new Offerte_Model( $posts[0]->ID );

        // Validate access token.
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        if ( empty( $token ) || ! hash_equals( $offerte->get_access_token(), $token ) ) {
            wp_die(
                esc_html__( 'Ongeldige toegangslink. Controleer de link in uw e-mail.', 'bossier-calculator' ),
                esc_html__( 'Toegang geweigerd', 'bossier-calculator' ),
                array( 'response' => 403 )
            );
        }

        // Check for auto-expiration.
        if ( $offerte->is_expired() && ! in_array( $offerte->get_status(), array( 'offerte-accepted', 'offerte-expired', 'offerte-cancelled' ), true ) ) {
            $offerte->set_status( 'offerte-expired' );
        }

        // Mark as viewed (first time only).
        if ( empty( $offerte->get_viewed_at() ) && 'offerte-sent' === $offerte->get_status() ) {
            $offerte->mark_viewed();
            $offerte->set_status( 'offerte-viewed' );
        }

        // Make offerte available to template.
        $GLOBALS['bs_offerte'] = $offerte;

        // Load the appropriate template.
        if ( 'offerte-accepted' === $offerte->get_status() ) {
            return BOSSIER_CALC_PLUGIN_DIR . 'frontend/views/offerte-accepted.php';
        }

        return BOSSIER_CALC_PLUGIN_DIR . 'frontend/views/offerte-public.php';
    }

    /**
     * Enqueue frontend assets on offerte pages.
     */
    public function enqueue_frontend_assets() {
        if ( empty( get_query_var( 'bs_offerte_number' ) ) ) {
            return;
        }

        wp_enqueue_style(
            'bs-offerte-frontend',
            BOSSIER_CALC_PLUGIN_URL . 'assets/css/offerte-frontend.css',
            array(),
            BOSSIER_CALC_VERSION
        );

        wp_enqueue_script(
            'signature-pad',
            BOSSIER_CALC_PLUGIN_URL . 'assets/js/vendor/signature_pad.umd.min.js',
            array(),
            '4.1.7',
            true
        );

        wp_enqueue_script(
            'bs-offerte-frontend',
            BOSSIER_CALC_PLUGIN_URL . 'assets/js/offerte-frontend.js',
            array( 'jquery', 'signature-pad' ),
            BOSSIER_CALC_VERSION,
            true
        );

        $offerte = isset( $GLOBALS['bs_offerte'] ) ? $GLOBALS['bs_offerte'] : null;

        wp_localize_script( 'bs-offerte-frontend', 'bsOfferte', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => $offerte ? wp_create_nonce( 'bs_offerte_accept_' . $offerte->get_id() ) : '',
            'offerteId' => $offerte ? $offerte->get_id() : 0,
            'token'    => $offerte ? $offerte->get_access_token() : '',
            'i18n'     => array(
                'signatureRequired' => __( 'Plaats uw handtekening a.u.b.', 'bossier-calculator' ),
                'termsRequired'     => __( 'U dient akkoord te gaan met de voorwaarden.', 'bossier-calculator' ),
                'processing'        => __( 'Bezig met verwerken...', 'bossier-calculator' ),
                'error'             => __( 'Er is een fout opgetreden. Probeer het opnieuw.', 'bossier-calculator' ),
            ),
        ) );
    }
}
