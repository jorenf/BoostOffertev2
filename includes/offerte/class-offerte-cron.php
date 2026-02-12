<?php
/**
 * Offerte Cron jobs.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Cron class - Handles scheduled tasks for reminders and expiration.
 */
class Offerte_Cron {

    /**
     * Cron hook name.
     */
    const CRON_HOOK = 'bs_offerte_daily_check';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( self::CRON_HOOK, array( $this, 'process_reminders' ) );
        add_action( self::CRON_HOOK, array( $this, 'process_expirations' ) );
        add_action( 'init', array( $this, 'schedule_events' ) );
    }

    /**
     * Schedule daily cron event if not already scheduled.
     */
    public function schedule_events() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Unschedule cron events.
     */
    public static function unschedule_events() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    /**
     * Process pending reminders.
     */
    public function process_reminders() {
        $posts = get_posts( array(
            'post_type'   => Offerte_Post_Type::POST_TYPE,
            'post_status' => array( 'offerte-sent', 'offerte-viewed' ),
            'numberposts' => 50,
            'meta_query'  => array(
                array(
                    'key'     => '_bs_reminder_days',
                    'value'   => '',
                    'compare' => '!=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_bs_reminder_sent',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_bs_reminder_sent',
                        'value'   => '1',
                        'compare' => '!=',
                    ),
                ),
            ),
        ) );

        $email = new Offerte_Email();

        foreach ( $posts as $post ) {
            $offerte = new Offerte_Model( $post->ID );
            $sent_at = $offerte->get_sent_at();

            if ( empty( $sent_at ) ) {
                continue;
            }

            $reminder_days = $offerte->get_reminder_days();
            if ( null === $reminder_days || $reminder_days <= 0 ) {
                continue;
            }

            $reminder_date = strtotime( $sent_at . ' +' . $reminder_days . ' days' );
            if ( $reminder_date <= current_time( 'timestamp' ) ) {
                $email->send_reminder( $offerte );
                $offerte->mark_reminder_sent();
            }
        }
    }

    /**
     * Process expired offertes.
     */
    public function process_expirations() {
        $posts = get_posts( array(
            'post_type'   => Offerte_Post_Type::POST_TYPE,
            'post_status' => array( 'offerte-sent', 'offerte-viewed' ),
            'numberposts' => 100,
            'meta_query'  => array(
                array(
                    'key'     => '_bs_valid_until',
                    'value'   => current_time( 'Y-m-d' ),
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
        ) );

        foreach ( $posts as $post ) {
            $offerte = new Offerte_Model( $post->ID );
            $offerte->set_status( 'offerte-expired' );
        }
    }
}
