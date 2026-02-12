<?php
/**
 * Offerte Settings helper.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Settings class - Manages all offerte settings via its own wp_option.
 */
class Offerte_Settings {

	/**
	 * Option name in wp_options table.
	 */
	const OPTION_NAME = 'boost_offerte_settings';

	/**
	 * Get all offerte settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_settings();
		if ( null !== $default ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
		}
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Get default values for offerte settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'offerte_prefix'                 => 'BS',
			'offerte_default_validity'       => 30,
			'offerte_default_reminder'       => 7,
			'offerte_sender_name'            => get_bloginfo( 'name' ),
			'offerte_sender_email'           => get_option( 'admin_email' ),
			'offerte_company_name'           => '',
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
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize( $input ) {
		$clean = array();

		// Text fields.
		$text_fields = array(
			'offerte_prefix', 'offerte_sender_name', 'offerte_sender_email',
			'offerte_company_name', 'offerte_company_phone',
			'offerte_company_kvk', 'offerte_company_btw', 'offerte_company_iban',
			'offerte_email_sent_subject', 'offerte_email_reminder_subject',
			'offerte_terms_text',
		);
		foreach ( $text_fields as $field ) {
			$clean[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
		}

		// Textarea fields.
		$textarea_fields = array(
			'offerte_company_address', 'offerte_email_sent_body',
			'offerte_email_reminder_body',
		);
		foreach ( $textarea_fields as $field ) {
			$clean[ $field ] = isset( $input[ $field ] ) ? sanitize_textarea_field( $input[ $field ] ) : '';
		}

		// URL fields.
		$clean['offerte_terms_url'] = isset( $input['offerte_terms_url'] ) ? esc_url_raw( $input['offerte_terms_url'] ) : '';

		// Numeric fields.
		$clean['offerte_default_validity'] = isset( $input['offerte_default_validity'] ) ? absint( $input['offerte_default_validity'] ) : 30;
		$clean['offerte_default_reminder'] = isset( $input['offerte_default_reminder'] ) ? absint( $input['offerte_default_reminder'] ) : 7;
		$clean['offerte_company_logo']     = isset( $input['offerte_company_logo'] ) ? absint( $input['offerte_company_logo'] ) : 0;

		// Email.
		if ( ! empty( $clean['offerte_sender_email'] ) && ! is_email( $clean['offerte_sender_email'] ) ) {
			$clean['offerte_sender_email'] = get_option( 'admin_email' );
		}

		// Payment methods (array of strings).
		$clean['offerte_payment_methods'] = array();
		if ( ! empty( $input['offerte_payment_methods'] ) && is_array( $input['offerte_payment_methods'] ) ) {
			$valid = array_keys( self::get_payment_method_options() );
			foreach ( $input['offerte_payment_methods'] as $method ) {
				if ( in_array( sanitize_key( $method ), $valid, true ) ) {
					$clean['offerte_payment_methods'][] = sanitize_key( $method );
				}
			}
		}

		return $clean;
	}

	/**
	 * Get available payment method options.
	 *
	 * @return array
	 */
	public static function get_payment_method_options() {
		return array(
			'ideal_invoice' => __( 'iDEAL + Factuur', 'boost-offerte' ),
			'ideal'         => __( 'Alleen iDEAL', 'boost-offerte' ),
			'invoice'       => __( 'Alleen Factuur', 'boost-offerte' ),
		);
	}

	/**
	 * Get available product categories.
	 *
	 * @return array
	 */
	public static function get_product_categories() {
		return apply_filters( 'boost_offerte_product_categories', array(
			'raamdorpels'   => __( 'Raamdorpels', 'boost-offerte' ),
			'muurafdekkers' => __( 'Muurafdekkers', 'boost-offerte' ),
			'vensterbanken' => __( 'Vensterbanken', 'boost-offerte' ),
			'paalmutsen'    => __( 'Paalmutsen', 'boost-offerte' ),
			'betonpoeren'   => __( 'Betonpoeren', 'boost-offerte' ),
			'maatwerk'      => __( 'Maatwerk', 'boost-offerte' ),
		) );
	}

	/**
	 * Check if Boost Calculator integration is available.
	 *
	 * @return bool
	 */
	public static function has_calculator_integration() {
		return \BoostOfferte\is_boost_calculator_active();
	}
}
