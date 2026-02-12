<?php
/**
 * Offerte Numbering system.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Numbering class - Auto-generates quote numbers.
 *
 * Format: PREFIX-YEAR-SEQUENCE (e.g. BS-2026-0042)
 */
class Offerte_Numbering {

    /**
     * Generate a new unique quote number.
     *
     * @return string Quote number (e.g. BS-2026-0042).
     */
    public static function generate() {
        $settings = Offerte_Settings::get_settings();
        $prefix   = $settings['offerte_prefix'];
        $year     = gmdate( 'Y' );

        $option_key = 'bs_offerte_last_number_' . $year;
        $last       = (int) get_option( $option_key, 0 );
        $next       = $last + 1;

        update_option( $option_key, $next, false );

        return sprintf( '%s-%s-%04d', $prefix, $year, $next );
    }

    /**
     * Preview the next quote number without incrementing.
     *
     * @return string Preview of next number.
     */
    public static function get_next_preview() {
        $settings = Offerte_Settings::get_settings();
        $prefix   = $settings['offerte_prefix'];
        $year     = gmdate( 'Y' );

        $option_key = 'bs_offerte_last_number_' . $year;
        $last       = (int) get_option( $option_key, 0 );
        $next       = $last + 1;

        return sprintf( '%s-%s-%04d', $prefix, $year, $next );
    }
}
