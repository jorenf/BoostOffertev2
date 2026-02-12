<?php
/**
 * Offerte Signature handling.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Signature class - Processes and stores digital signatures.
 */
class Offerte_Signature {

    /**
     * Save a base64-encoded signature PNG to disk.
     *
     * @param string $base64_data Base64-encoded PNG data (with or without data URI prefix).
     * @param string $quote_number Quote number for filename.
     * @return string|false File path on success, false on failure.
     */
    public static function save_signature( $base64_data, $quote_number ) {
        // Strip data URI prefix if present.
        $base64_data = preg_replace( '/^data:image\/png;base64,/', '', $base64_data );
        $decoded     = base64_decode( $base64_data, true );

        if ( false === $decoded ) {
            return false;
        }

        // Validate it's actually a PNG.
        $finfo = new \finfo( FILEINFO_MIME_TYPE );
        $mime  = $finfo->buffer( $decoded );
        if ( 'image/png' !== $mime ) {
            return false;
        }

        $dir = self::get_storage_dir();
        if ( ! $dir ) {
            return false;
        }

        $filename = sanitize_file_name( $quote_number ) . '.png';
        $filepath = $dir . '/' . $filename;

        $result = file_put_contents( $filepath, $decoded ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        return false !== $result ? $filepath : false;
    }

    /**
     * Get the storage directory for signatures.
     *
     * @return string|false Directory path or false on failure.
     */
    public static function get_storage_dir() {
        $upload_dir = wp_upload_dir();
        $dir        = $upload_dir['basedir'] . '/boost-offertes/signatures';

        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );

            // Security: prevent direct access.
            $parent = dirname( $dir );
            file_put_contents( $parent . '/index.php', '<?php // Silence is golden' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $parent . '/.htaccess', 'deny from all' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $dir . '/index.php', '<?php // Silence is golden' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }

        return $dir;
    }
}
