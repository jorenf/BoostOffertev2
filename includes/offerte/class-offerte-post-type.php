<?php
/**
 * Offerte Post Type registration.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Post_Type class - Registers CPT and custom statuses.
 */
class Offerte_Post_Type {

    /**
     * Post type slug.
     */
    const POST_TYPE = 'bs_offerte';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_custom_statuses' ) );
    }

    /**
     * Register the bs_offerte post type.
     */
    public function register_post_type() {
        register_post_type( self::POST_TYPE, array(
            'labels'              => array(
                'name'          => __( 'Offertes', 'bossier-calculator' ),
                'singular_name' => __( 'Offerte', 'bossier-calculator' ),
            ),
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => false,
            'show_in_rest'        => false,
            'supports'            => array( 'title' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'exclude_from_search' => true,
        ) );
    }

    /**
     * Register custom post statuses for the offerte lifecycle.
     */
    public function register_custom_statuses() {
        $statuses = self::get_statuses();

        foreach ( $statuses as $status => $label ) {
            register_post_status( $status, array(
                'label'                     => $label,
                'public'                    => false,
                'internal'                  => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                // translators: %s: count of posts with this status.
                'label_count'               => _n_noop(
                    $label . ' <span class="count">(%s)</span>',
                    $label . ' <span class="count">(%s)</span>',
                    'bossier-calculator'
                ),
            ) );
        }
    }

    /**
     * Get all custom statuses with labels.
     *
     * @return array
     */
    public static function get_statuses() {
        return array(
            'offerte-draft'     => __( 'Concept', 'bossier-calculator' ),
            'offerte-sent'      => __( 'Verzonden', 'bossier-calculator' ),
            'offerte-viewed'    => __( 'Bekeken', 'bossier-calculator' ),
            'offerte-accepted'  => __( 'Geaccepteerd', 'bossier-calculator' ),
            'offerte-expired'   => __( 'Verlopen', 'bossier-calculator' ),
            'offerte-cancelled' => __( 'Geannuleerd', 'bossier-calculator' ),
        );
    }

    /**
     * Get status color for badges.
     *
     * @param string $status Post status.
     * @return string Hex color.
     */
    public static function get_status_color( $status ) {
        $colors = array(
            'offerte-draft'     => '#718096',
            'offerte-sent'      => '#3182ce',
            'offerte-viewed'    => '#d69e2e',
            'offerte-accepted'  => '#38a169',
            'offerte-expired'   => '#e53e3e',
            'offerte-cancelled' => '#a0aec0',
        );

        return isset( $colors[ $status ] ) ? $colors[ $status ] : '#718096';
    }

    /**
     * Get status label.
     *
     * @param string $status Post status.
     * @return string Label.
     */
    public static function get_status_label( $status ) {
        $statuses = self::get_statuses();
        return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
    }
}
