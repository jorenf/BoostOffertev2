<?php
/**
 * Offerte Settings helper.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Settings class - Helper for accessing offerte-specific settings.
 */
class Offerte_Settings {

    /**
     * Get all offerte settings with defaults.
     *
     * @return array
     */
    public static function get_settings() {
        $settings = \Bossier\Calculator\Modules_Settings::get_settings();

        // Merge with offerte-specific defaults.
        $defaults = self::get_defaults();

        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Get default values for offerte settings.
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'offerte_module_enabled'         => false,
            'offerte_prefix'                 => 'BS',
            'offerte_default_validity'       => 30,
            'offerte_default_reminder'       => 7,
            'offerte_sender_name'            => get_bloginfo( 'name' ),
            'offerte_sender_email'           => get_option( 'admin_email' ),
            'offerte_company_name'           => 'Bos Sierbeton',
            'offerte_company_address'        => '',
            'offerte_company_phone'          => '',
            'offerte_company_kvk'            => '',
            'offerte_company_btw'            => '',
            'offerte_company_iban'           => '',
            'offerte_company_logo'           => '',
            'offerte_email_sent_subject'     => 'Uw offerte {quote_number} van {company_name}',
            'offerte_email_sent_body'        => "Beste {customer_name},\n\nBijgaand ontvangt u onze offerte {quote_number}.\n\nU kunt de offerte bekijken en accepteren via onderstaande link:\n{quote_url}\n\nDeze offerte is geldig tot {valid_until}.\n\nMet vriendelijke groet,\n{company_name}",
            'offerte_email_reminder_subject' => 'Herinnering: offerte {quote_number}',
            'offerte_email_reminder_body'    => "Beste {customer_name},\n\nWij willen u graag herinneren aan onze offerte {quote_number} ter waarde van {total}.\n\nDeze offerte is geldig tot {valid_until}.\n\nBekijk en accepteer de offerte via:\n{quote_url}\n\nMet vriendelijke groet,\n{company_name}",
            'offerte_payment_methods'        => array( 'ideal_invoice' ),
            'offerte_terms_url'              => '',
            'offerte_terms_text'             => 'Ik ga akkoord met de algemene voorwaarden',
        );
    }

    /**
     * Get available payment method options.
     *
     * @return array
     */
    public static function get_payment_method_options() {
        return array(
            'ideal_invoice' => __( 'iDEAL + Factuur', 'bossier-calculator' ),
            'ideal'         => __( 'Alleen iDEAL', 'bossier-calculator' ),
            'invoice'       => __( 'Alleen Factuur', 'bossier-calculator' ),
        );
    }

    /**
     * Get available product categories.
     *
     * @return array
     */
    public static function get_product_categories() {
        return array(
            'raamdorpels'  => __( 'Raamdorpels', 'bossier-calculator' ),
            'muurafdekkers' => __( 'Muurafdekkers', 'bossier-calculator' ),
            'vensterbanken' => __( 'Vensterbanken', 'bossier-calculator' ),
            'paalmutsen'    => __( 'Paalmutsen', 'bossier-calculator' ),
            'betonpoeren'  => __( 'Betonpoeren', 'bossier-calculator' ),
            'maatwerk'     => __( 'Maatwerk', 'bossier-calculator' ),
        );
    }
}
