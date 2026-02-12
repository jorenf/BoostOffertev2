<?php
/**
 * Offerte Module - Entry point.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Module class - Singleton entry point for the Offerte module.
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
        $this->load_classes();
        $this->init();
    }

    /**
     * Load required class files.
     */
    private function load_classes() {
        $dir = BOSSIER_CALC_PLUGIN_DIR . 'includes/offerte/';

        require_once $dir . 'class-offerte-post-type.php';
        require_once $dir . 'class-offerte-numbering.php';
        require_once $dir . 'class-offerte-model.php';
        require_once $dir . 'class-offerte-settings.php';
        require_once $dir . 'class-offerte-ajax.php';
        require_once $dir . 'class-offerte-pdf.php';
        require_once $dir . 'class-offerte-email.php';
        require_once $dir . 'class-offerte-signature.php';
        require_once $dir . 'class-offerte-order-creator.php';
        require_once $dir . 'class-offerte-frontend.php';
        require_once $dir . 'class-offerte-cron.php';

        if ( is_admin() ) {
            require_once BOSSIER_CALC_PLUGIN_DIR . 'admin/class-offerte-admin.php';
            require_once BOSSIER_CALC_PLUGIN_DIR . 'admin/class-offerte-list-table.php';
        }
    }

    /**
     * Initialize module components.
     */
    private function init() {
        new Offerte_Post_Type();
        new Offerte_Ajax();
        new Offerte_Frontend();
        new Offerte_Cron();

        if ( is_admin() ) {
            new \Bossier\Calculator\Admin\Offerte_Admin();
        }
    }
}
