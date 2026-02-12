<?php
/**
 * Offerte Admin interface.
 *
 * @package Boost_Offerte
 */

namespace BoostOfferte\Admin;

use BoostOfferte\Offerte_Model;
use BoostOfferte\Offerte_Settings;
use BoostOfferte\Offerte_Numbering;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Admin class - Admin pages, settings, and asset management.
 */
class Offerte_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu pages under WooCommerce.
	 */
	public function add_menu_pages() {
		add_submenu_page(
			'woocommerce',
			__( 'Offertes', 'boost-offerte' ),
			__( 'Offertes', 'boost-offerte' ),
			'manage_woocommerce',
			'bs-offertes',
			array( $this, 'render_list_page' )
		);

		// Hidden page for editing (no menu entry).
		add_submenu_page(
			null,
			__( 'Offerte Bewerken', 'boost-offerte' ),
			__( 'Offerte Bewerken', 'boost-offerte' ),
			'manage_woocommerce',
			'bs-offerte-edit',
			array( $this, 'render_edit_page' )
		);

		// Settings page under WooCommerce.
		add_submenu_page(
			'woocommerce',
			__( 'Offerte Instellingen', 'boost-offerte' ),
			__( 'Offerte Instellingen', 'boost-offerte' ),
			'manage_woocommerce',
			'bs-offerte-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'boost_offerte_settings_group',
			Offerte_Settings::OPTION_NAME,
			array(
				'sanitize_callback' => array( Offerte_Settings::class, 'sanitize' ),
			)
		);
	}

	/**
	 * Render the offerte list page.
	 */
	public function render_list_page() {
		$list_table = new Offerte_List_Table();
		$list_table->prepare_items();

		include BOOST_OFFERTE_PLUGIN_DIR . 'admin/views/offerte-list.php';
	}

	/**
	 * Render the offerte edit page.
	 */
	public function render_edit_page() {
		$offerte_id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$offerte    = null;

		if ( $offerte_id ) {
			$offerte = new Offerte_Model( $offerte_id );
			if ( ! $offerte->is_valid() ) {
				wp_die( esc_html__( 'Offerte niet gevonden.', 'boost-offerte' ) );
			}
		}

		$settings        = Offerte_Settings::get_settings();
		$categories      = Offerte_Settings::get_product_categories();
		$payment_methods = Offerte_Settings::get_payment_method_options();
		$next_number     = $offerte ? $offerte->get_quote_number() : Offerte_Numbering::get_next_preview();
		$has_calculator  = Offerte_Settings::has_calculator_integration();

		include BOOST_OFFERTE_PLUGIN_DIR . 'admin/views/offerte-edit.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		$settings = Offerte_Settings::get_settings();

		include BOOST_OFFERTE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Enqueue admin assets on offerte pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our pages.
		if ( ! in_array( $hook, array( 'woocommerce_page_bs-offertes', 'admin_page_bs-offerte-edit', 'woocommerce_page_bs-offerte-settings' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_style(
			'bs-offerte-admin',
			BOOST_OFFERTE_PLUGIN_URL . 'assets/css/offerte-admin.css',
			array( 'wp-jquery-ui-dialog' ),
			BOOST_OFFERTE_VERSION
		);

		wp_enqueue_script(
			'bs-offerte-admin',
			BOOST_OFFERTE_PLUGIN_URL . 'assets/js/offerte-admin.js',
			array( 'jquery', 'jquery-ui-datepicker', 'wp-util' ),
			BOOST_OFFERTE_VERSION,
			true
		);

		wp_localize_script( 'bs-offerte-admin', 'bsOfferteAdmin', array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'bs_offerte_admin_nonce' ),
			'editUrl'           => admin_url( 'admin.php?page=bs-offerte-edit&id=' ),
			'listUrl'           => admin_url( 'admin.php?page=bs-offertes' ),
			'categories'        => Offerte_Settings::get_product_categories(),
			'paymentMethods'    => Offerte_Settings::get_payment_method_options(),
			'hasCalculator'     => Offerte_Settings::has_calculator_integration(),
			'i18n'              => array(
				'saving'           => __( 'Opslaan...', 'boost-offerte' ),
				'saved'            => __( 'Opgeslagen', 'boost-offerte' ),
				'sending'          => __( 'Verzenden...', 'boost-offerte' ),
				'sent'             => __( 'Verzonden', 'boost-offerte' ),
				'confirmSend'      => __( 'Offerte verzenden naar de klant?', 'boost-offerte' ),
				'confirmCancel'    => __( 'Offerte annuleren?', 'boost-offerte' ),
				'confirmDuplicate' => __( 'Offerte dupliceren?', 'boost-offerte' ),
				'error'            => __( 'Er is een fout opgetreden.', 'boost-offerte' ),
				'noCustomerEmail'  => __( 'Vul een e-mailadres in bij klantgegevens.', 'boost-offerte' ),
				'addItem'          => __( 'Product toevoegen', 'boost-offerte' ),
				'removeItem'       => __( 'Verwijderen', 'boost-offerte' ),
				'searchCustomer'   => __( 'Zoek klant...', 'boost-offerte' ),
				'searchProduct'    => __( 'Zoek product...', 'boost-offerte' ),
			),
		) );
	}
}
