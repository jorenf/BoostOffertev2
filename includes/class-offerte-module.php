<?php
/**
 * Offerte Module - Entry point.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Module class - Singleton entry point for the Boost Offerte plugin.
 */
class Offerte_Module {

	/**
	 * Single instance.
	 *
	 * @var Offerte_Module|null
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return Offerte_Module
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init() {
		new Offerte_Post_Type();
		new Offerte_Ajax();
		new Offerte_Frontend();
		new Offerte_Cron();

		if ( is_admin() ) {
			new Admin\Offerte_Admin();
		}
	}
}
