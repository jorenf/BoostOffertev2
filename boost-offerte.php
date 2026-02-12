<?php
/**
 * Plugin Name: Boost Offerte
 * Plugin URI: https://bossierbeton.nl
 * Description: Professional quote-to-order system for WooCommerce. Create, send, and manage offertes with digital signatures. Integrates with Boost Calculator for product pricing.
 * Version: 1.0.0
 * Author: ByteQ
 * Author URI: https://byteq.nl
 * Text Domain: boost-offerte
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'BOOST_OFFERTE_VERSION', '1.0.0' );
define( 'BOOST_OFFERTE_PLUGIN_FILE', __FILE__ );
define( 'BOOST_OFFERTE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOOST_OFFERTE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOOST_OFFERTE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * SPL Autoloader for BoostOfferte namespace.
 *
 * Maps:
 *   BoostOfferte\Offerte_Model       → includes/class-offerte-model.php
 *   BoostOfferte\Admin\Offerte_Admin → admin/class-offerte-admin.php
 */
spl_autoload_register( function ( $class ) {

	// Only handle our namespace.
	if ( 0 !== strpos( $class, 'BoostOfferte\\' ) ) {
		return;
	}

	$relative = substr( $class, strlen( 'BoostOfferte\\' ) );

	// Sub-namespace to directory mapping.
	$directories = array(
		'Admin' => 'admin',
	);

	$parts     = explode( '\\', $relative );
	$classname = array_pop( $parts );
	$subns     = implode( '\\', $parts );

	if ( isset( $directories[ $subns ] ) ) {
		$dir = $directories[ $subns ];
	} else {
		$dir = 'includes';
	}

	$filename = 'class-' . strtolower( str_replace( '_', '-', $classname ) ) . '.php';
	$filepath = BOOST_OFFERTE_PLUGIN_DIR . $dir . '/' . $filename;

	if ( file_exists( $filepath ) ) {
		require_once $filepath;
	}
} );

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Check if Boost Calculator is active.
 *
 * @return bool
 */
function is_boost_calculator_active() {
	return defined( 'BOSSIER_CALC_VERSION' ) && class_exists( 'Bossier\\Calculator\\Price_Calculator' );
}

/**
 * Get the Boost Calculator plugin directory path.
 *
 * @return string|false Directory path or false if not found.
 */
function get_boost_calculator_dir() {
	if ( defined( 'BOSSIER_CALC_PLUGIN_DIR' ) ) {
		return BOSSIER_CALC_PLUGIN_DIR;
	}
	return false;
}

/**
 * Display admin notices for missing dependencies.
 */
function admin_dependency_notices() {
	if ( ! is_woocommerce_active() ) {
		printf(
			'<div class="notice notice-error"><p><strong>Boost Offerte:</strong> %s</p></div>',
			esc_html__( 'WooCommerce is vereist. Installeer en activeer WooCommerce.', 'boost-offerte' )
		);
	}
}

/**
 * Initialize the plugin after all plugins are loaded.
 */
function init_plugin() {

	// WooCommerce is required.
	if ( ! is_woocommerce_active() ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\admin_dependency_notices' );
		return;
	}

	// Initialize the main module.
	Offerte_Module::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init_plugin', 20 );

/**
 * Activation hook — flush rewrite rules.
 */
function activate() {
	// Register CPT so rewrite rules are set.
	Offerte_Post_Type::register_post_type();
	Offerte_Post_Type::register_post_statuses();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Deactivation hook — clean up.
 */
function deactivate() {
	wp_clear_scheduled_hook( 'boost_offerte_daily_check' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );
