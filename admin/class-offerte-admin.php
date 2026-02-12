<?php
/**
 * Offerte Admin interface.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Admin;

use Bossier\Calculator\Offerte\Offerte_Model;
use Bossier\Calculator\Offerte\Offerte_Settings;
use Bossier\Calculator\Offerte\Offerte_Numbering;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Admin class - Admin pages and asset management.
 */
class Offerte_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Register admin menu pages under WooCommerce.
     */
    public function add_menu_pages() {
        add_submenu_page(
            'woocommerce',
            __( 'Offertes', 'bossier-calculator' ),
            __( 'Offertes', 'bossier-calculator' ),
            'manage_woocommerce',
            'bs-offertes',
            array( $this, 'render_list_page' )
        );

        // Hidden page for editing (no menu entry).
        add_submenu_page(
            null,
            __( 'Offerte Bewerken', 'bossier-calculator' ),
            __( 'Offerte Bewerken', 'bossier-calculator' ),
            'manage_woocommerce',
            'bs-offerte-edit',
            array( $this, 'render_edit_page' )
        );
    }

    /**
     * Render the offerte list page.
     */
    public function render_list_page() {
        $list_table = new Offerte_List_Table();
        $list_table->prepare_items();

        include BOSSIER_CALC_PLUGIN_DIR . 'admin/views/offerte-list.php';
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
                wp_die( esc_html__( 'Offerte niet gevonden.', 'bossier-calculator' ) );
            }
        }

        $settings       = Offerte_Settings::get_settings();
        $categories     = Offerte_Settings::get_product_categories();
        $payment_methods = Offerte_Settings::get_payment_method_options();
        $next_number    = $offerte ? $offerte->get_quote_number() : Offerte_Numbering::get_next_preview();

        include BOSSIER_CALC_PLUGIN_DIR . 'admin/views/offerte-edit.php';
    }

    /**
     * Enqueue admin assets on offerte pages.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our pages.
        if ( ! in_array( $hook, array( 'woocommerce_page_bs-offertes', 'admin_page_bs-offerte-edit' ), true ) ) {
            return;
        }

        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-datepicker' );

        wp_enqueue_style(
            'bs-offerte-admin',
            BOSSIER_CALC_PLUGIN_URL . 'assets/css/offerte-admin.css',
            array( 'wp-jquery-ui-dialog' ),
            BOSSIER_CALC_VERSION
        );

        wp_enqueue_script(
            'bs-offerte-admin',
            BOSSIER_CALC_PLUGIN_URL . 'assets/js/offerte-admin.js',
            array( 'jquery', 'jquery-ui-datepicker', 'wp-util' ),
            BOSSIER_CALC_VERSION,
            true
        );

        wp_localize_script( 'bs-offerte-admin', 'bsOfferteAdmin', array(
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'bs_offerte_admin_nonce' ),
            'editUrl'          => admin_url( 'admin.php?page=bs-offerte-edit&id=' ),
            'listUrl'          => admin_url( 'admin.php?page=bs-offertes' ),
            'categories'       => Offerte_Settings::get_product_categories(),
            'paymentMethods'   => Offerte_Settings::get_payment_method_options(),
            'i18n'             => array(
                'saving'          => __( 'Opslaan...', 'bossier-calculator' ),
                'saved'           => __( 'Opgeslagen', 'bossier-calculator' ),
                'sending'         => __( 'Verzenden...', 'bossier-calculator' ),
                'sent'            => __( 'Verzonden', 'bossier-calculator' ),
                'confirmSend'     => __( 'Offerte verzenden naar de klant?', 'bossier-calculator' ),
                'confirmCancel'   => __( 'Offerte annuleren?', 'bossier-calculator' ),
                'confirmDuplicate' => __( 'Offerte dupliceren?', 'bossier-calculator' ),
                'error'           => __( 'Er is een fout opgetreden.', 'bossier-calculator' ),
                'noCustomerEmail' => __( 'Vul een e-mailadres in bij klantgegevens.', 'bossier-calculator' ),
                'addItem'         => __( 'Product toevoegen', 'bossier-calculator' ),
                'removeItem'      => __( 'Verwijderen', 'bossier-calculator' ),
                'searchCustomer'  => __( 'Zoek klant...', 'bossier-calculator' ),
                'searchProduct'   => __( 'Zoek product...', 'bossier-calculator' ),
            ),
        ) );
    }
}
