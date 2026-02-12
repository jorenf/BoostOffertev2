<?php
/**
 * Offerte PDF Generator.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Offerte_PDF class - Generates offerte PDFs using DOMPDF.
 */
class Offerte_PDF {

    /**
     * DOMPDF instance.
     *
     * @var Dompdf
     */
    private $dompdf;

    /**
     * Offerte model.
     *
     * @var Offerte_Model
     */
    private $offerte;

    /**
     * Constructor.
     *
     * @param Offerte_Model $offerte Offerte instance.
     */
    public function __construct( Offerte_Model $offerte ) {
        $this->offerte = $offerte;
        $this->init_dompdf();
    }

    /**
     * Initialize DOMPDF with options.
     */
    private function init_dompdf() {
        // Load DOMPDF autoloader from existing plugin infrastructure.
        if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
            require_once BOSSIER_CALC_PLUGIN_DIR . 'includes/pdf/autoload.php';
        }

        $options = new Options();
        $options->set( 'isRemoteEnabled', true );
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isFontSubsettingEnabled', true );
        $options->set( 'defaultFont', 'DejaVu Sans' );
        $options->set( 'tempDir', $this->get_temp_dir() );
        $options->set( 'fontDir', $this->get_font_dir() );
        $options->set( 'fontCache', $this->get_font_dir() );
        $options->set( 'chroot', ABSPATH );

        $this->dompdf = new Dompdf( $options );
        $this->dompdf->setPaper( 'A4', 'portrait' );
    }

    /**
     * Generate PDF and return content.
     *
     * @return string PDF binary content.
     */
    public function generate() {
        $html = $this->render_template();
        $this->dompdf->loadHtml( $html );
        $this->dompdf->render();
        return $this->dompdf->output();
    }

    /**
     * Generate PDF and save to file.
     *
     * @return string|false File path on success, false on failure.
     */
    public function generate_and_save() {
        $content  = $this->generate();
        $filepath = $this->get_storage_dir() . '/' . $this->get_filename();

        $result = file_put_contents( $filepath, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        if ( false !== $result ) {
            $this->offerte->set_pdf_path( $filepath );
            return $filepath;
        }

        return false;
    }

    /**
     * Stream PDF to browser for download.
     */
    public function stream() {
        $content = $this->generate();
        $filename = $this->get_filename();

        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $content ) );
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Get PDF filename.
     *
     * @return string
     */
    public function get_filename() {
        return 'Offerte-' . $this->offerte->get_quote_number() . '.pdf';
    }

    /**
     * Get the offerte model.
     *
     * @return Offerte_Model
     */
    public function get_offerte() {
        return $this->offerte;
    }

    /**
     * Render the PDF template.
     *
     * @return string HTML content.
     */
    private function render_template() {
        $offerte     = $this->offerte;
        $offerte_pdf = $this;
        $company     = $this->get_company_data();
        $settings    = Offerte_Settings::get_settings();

        ob_start();
        include BOSSIER_CALC_PLUGIN_DIR . 'templates/pdf/offerte.php';
        return ob_get_clean();
    }

    /**
     * Get company data from settings.
     *
     * @return array
     */
    public function get_company_data() {
        $settings = Offerte_Settings::get_settings();

        return array(
            'name'       => $settings['offerte_company_name'] ?: get_bloginfo( 'name' ),
            'address'    => $settings['offerte_company_address'],
            'phone'      => $settings['offerte_company_phone'],
            'kvk'        => $settings['offerte_company_kvk'],
            'btw'        => $settings['offerte_company_btw'],
            'iban'       => $settings['offerte_company_iban'],
            'logo'       => $settings['offerte_company_logo'],
        );
    }

    /**
     * Get logo HTML.
     *
     * @return string
     */
    public function get_logo_html() {
        $company = $this->get_company_data();

        if ( empty( $company['logo'] ) ) {
            // Fall back to PDF settings logo.
            $logo_id = get_option( 'boost_pdf_logo', '' );
        } else {
            $logo_id = $company['logo'];
        }

        if ( empty( $logo_id ) ) {
            return '';
        }

        $logo_path = get_attached_file( $logo_id );
        if ( ! $logo_path || ! file_exists( $logo_path ) ) {
            return '';
        }

        $logo_url = wp_get_attachment_url( $logo_id );

        return sprintf(
            '<img src="%s" alt="%s" style="max-width: 200px; max-height: 80px;">',
            esc_url( $logo_url ),
            esc_attr( $company['name'] )
        );
    }

    /**
     * Format price for display.
     *
     * @param float $price Price value.
     * @return string
     */
    public function format_price( $price ) {
        return '€ ' . number_format( (float) $price, 2, ',', '.' );
    }

    /**
     * Format date for display.
     *
     * @param string $date Date string.
     * @return string
     */
    public function format_date( $date ) {
        if ( empty( $date ) ) {
            return '';
        }
        return date_i18n( 'd F Y', strtotime( $date ) );
    }

    /**
     * Get storage directory for offerte PDFs.
     *
     * @return string
     */
    private function get_storage_dir() {
        $upload_dir  = wp_upload_dir();
        $storage_dir = $upload_dir['basedir'] . '/boost-offertes';
        $year_dir    = $storage_dir . '/' . gmdate( 'Y' );

        if ( ! file_exists( $year_dir ) ) {
            wp_mkdir_p( $year_dir );

            file_put_contents( $storage_dir . '/index.php', '<?php // Silence is golden' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $year_dir . '/index.php', '<?php // Silence is golden' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $storage_dir . '/.htaccess', 'deny from all' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }

        return $year_dir;
    }

    /**
     * Get temp directory for DOMPDF.
     *
     * @return string
     */
    private function get_temp_dir() {
        $upload_dir = wp_upload_dir();
        $temp_dir   = $upload_dir['basedir'] . '/boost-pdf-temp';

        if ( ! file_exists( $temp_dir ) ) {
            wp_mkdir_p( $temp_dir );
        }

        return $temp_dir;
    }

    /**
     * Get font directory for DOMPDF.
     *
     * @return string
     */
    private function get_font_dir() {
        $upload_dir = wp_upload_dir();
        $font_dir   = $upload_dir['basedir'] . '/boost-pdf-fonts';

        if ( ! file_exists( $font_dir ) ) {
            wp_mkdir_p( $font_dir );
        }

        return $font_dir;
    }
}
