<?php
/**
 * Public Offerte Page template.
 *
 * @package Bossier_Calculator_Builder
 * @var Bossier\Calculator\Offerte\Offerte_Model $bs_offerte (via $GLOBALS)
 */

defined( 'ABSPATH' ) || exit;

$offerte   = $GLOBALS['bs_offerte'];
$customer  = $offerte->get_customer_data();
$items     = $offerte->get_items();
$settings  = \Bossier\Calculator\Offerte\Offerte_Settings::get_settings();
$company   = $settings['offerte_company_name'] ?: get_bloginfo( 'name' );
$status    = $offerte->get_status();
$can_accept = $offerte->can_accept();

get_header();
?>
<div class="bs-offerte-page">
    <div class="bs-offerte-container">
        <!-- Header -->
        <div class="bs-offerte-header">
            <div class="bs-offerte-company">
                <?php
                $logo_id = $settings['offerte_company_logo'] ?: get_option( 'boost_pdf_logo', '' );
                if ( $logo_id ) :
                    $logo_url = wp_get_attachment_url( $logo_id );
                    if ( $logo_url ) :
                ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $company ); ?>" class="bs-offerte-logo">
                <?php endif; endif; ?>
                <h1><?php echo esc_html( $company ); ?></h1>
            </div>
            <div class="bs-offerte-meta">
                <h2><?php echo esc_html( sprintf( __( 'Offerte %s', 'bossier-calculator' ), $offerte->get_quote_number() ) ); ?></h2>
                <p><strong><?php esc_html_e( 'Datum:', 'bossier-calculator' ); ?></strong> <?php echo esc_html( date_i18n( 'd F Y', strtotime( $offerte->get_post()->post_date ) ) ); ?></p>
                <p><strong><?php esc_html_e( 'Geldig tot:', 'bossier-calculator' ); ?></strong> <?php echo esc_html( date_i18n( 'd F Y', strtotime( $offerte->get_valid_until() ) ) ); ?></p>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if ( 'offerte-expired' === $status ) : ?>
            <div class="bs-offerte-notice bs-notice-expired">
                <strong><?php esc_html_e( 'Deze offerte is verlopen.', 'bossier-calculator' ); ?></strong>
                <p><?php esc_html_e( 'Neem contact met ons op voor een nieuwe offerte.', 'bossier-calculator' ); ?></p>
            </div>
        <?php elseif ( 'offerte-cancelled' === $status ) : ?>
            <div class="bs-offerte-notice bs-notice-cancelled">
                <strong><?php esc_html_e( 'Deze offerte is geannuleerd.', 'bossier-calculator' ); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Customer Info -->
        <div class="bs-offerte-customer-info">
            <p><strong><?php echo esc_html( $customer['naam'] ); ?></strong></p>
            <?php if ( ! empty( $customer['bedrijf'] ) ) : ?>
                <p><?php echo esc_html( $customer['bedrijf'] ); ?></p>
            <?php endif; ?>
            <?php if ( ! empty( $customer['adres']['straat'] ) ) : ?>
                <p><?php echo esc_html( $customer['adres']['straat'] ); ?></p>
                <p><?php echo esc_html( $customer['adres']['postcode'] . ' ' . $customer['adres']['plaats'] ); ?></p>
            <?php endif; ?>
        </div>

        <!-- Products Table -->
        <div class="bs-offerte-products">
            <table class="bs-offerte-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Product', 'bossier-calculator' ); ?></th>
                        <th><?php esc_html_e( 'Specificaties', 'bossier-calculator' ); ?></th>
                        <th class="bs-text-center"><?php esc_html_e( 'Aantal', 'bossier-calculator' ); ?></th>
                        <th class="bs-text-right"><?php esc_html_e( 'Stukprijs', 'bossier-calculator' ); ?></th>
                        <th class="bs-text-right"><?php esc_html_e( 'Totaal', 'bossier-calculator' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
                                <?php if ( ! empty( $item['category'] ) ) : ?>
                                    <br><small><?php echo esc_html( \Bossier\Calculator\Offerte\Offerte_Settings::get_product_categories()[ $item['category'] ] ?? $item['category'] ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="bs-specs-cell">
                                <?php
                                $specs = $item['specs'] ?? array();
                                $spec_parts = array();
                                if ( ! empty( $specs['lengte'] ) ) $spec_parts[] = $specs['lengte'] . ' cm (L)';
                                if ( ! empty( $specs['breedte'] ) ) $spec_parts[] = $specs['breedte'] . ' cm (B)';
                                if ( ! empty( $specs['hoogte'] ) ) $spec_parts[] = $specs['hoogte'] . ' cm (H)';
                                if ( ! empty( $specs['kleur'] ) ) $spec_parts[] = $specs['kleur'];
                                if ( ! empty( $specs['afwerking'] ) ) $spec_parts[] = $specs['afwerking'];
                                echo esc_html( implode( ' | ', $spec_parts ) );
                                ?>
                            </td>
                            <td class="bs-text-center"><?php echo esc_html( $item['quantity'] ?? 1 ); ?></td>
                            <td class="bs-text-right">&euro; <?php echo esc_html( number_format( $item['unit_price'] ?? 0, 2, ',', '.' ) ); ?></td>
                            <td class="bs-text-right">&euro; <?php echo esc_html( number_format( $item['line_total'] ?? 0, 2, ',', '.' ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="bs-offerte-totals-section">
            <table class="bs-offerte-totals">
                <tr>
                    <td><?php esc_html_e( 'Subtotaal', 'bossier-calculator' ); ?></td>
                    <td class="bs-text-right">&euro; <?php echo esc_html( number_format( $offerte->get_subtotal(), 2, ',', '.' ) ); ?></td>
                </tr>
                <?php if ( $offerte->get_shipping_cost() > 0 ) : ?>
                    <tr>
                        <td><?php esc_html_e( 'Verzendkosten', 'bossier-calculator' ); ?></td>
                        <td class="bs-text-right">&euro; <?php echo esc_html( number_format( $offerte->get_shipping_cost(), 2, ',', '.' ) ); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td><?php esc_html_e( 'BTW (21%)', 'bossier-calculator' ); ?></td>
                    <td class="bs-text-right">&euro; <?php echo esc_html( number_format( $offerte->get_tax(), 2, ',', '.' ) ); ?></td>
                </tr>
                <tr class="bs-total-row">
                    <td><strong><?php esc_html_e( 'Totaal', 'bossier-calculator' ); ?></strong></td>
                    <td class="bs-text-right"><strong>&euro; <?php echo esc_html( number_format( $offerte->get_total(), 2, ',', '.' ) ); ?></strong></td>
                </tr>
            </table>
        </div>

        <!-- Customer Note -->
        <?php if ( ! empty( $offerte->get_customer_note() ) ) : ?>
            <div class="bs-offerte-note">
                <h3><?php esc_html_e( 'Opmerking', 'bossier-calculator' ); ?></h3>
                <p><?php echo nl2br( esc_html( $offerte->get_customer_note() ) ); ?></p>
            </div>
        <?php endif; ?>

        <!-- PDF Download -->
        <div class="bs-offerte-download">
            <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=bs_download_offerte_pdf&offerte_id=' . $offerte->get_id() . '&token=' . $offerte->get_access_token() ) ); ?>" class="bs-btn bs-btn-secondary">
                <?php esc_html_e( 'Download PDF', 'bossier-calculator' ); ?>
            </a>
        </div>

        <!-- Accept Section -->
        <?php if ( $can_accept ) : ?>
            <div class="bs-offerte-accept" id="bs-accept-section">
                <h2><?php esc_html_e( 'Offerte Accepteren', 'bossier-calculator' ); ?></h2>

                <div class="bs-accept-summary">
                    <p><?php echo esc_html( sprintf(
                        __( 'Door deze offerte te accepteren gaat u akkoord met een bestelling ter waarde van %s (incl. BTW).', 'bossier-calculator' ),
                        '€ ' . number_format( $offerte->get_total(), 2, ',', '.' )
                    ) ); ?></p>
                </div>

                <!-- Signature -->
                <div class="bs-signature-section">
                    <label><?php esc_html_e( 'Uw handtekening', 'bossier-calculator' ); ?></label>
                    <div class="bs-signature-wrapper">
                        <canvas id="bs-signature-pad" width="500" height="200"></canvas>
                        <button type="button" id="bs-clear-signature" class="bs-btn-link">
                            <?php esc_html_e( 'Handtekening wissen', 'bossier-calculator' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Terms -->
                <div class="bs-terms-section">
                    <label class="bs-checkbox-label">
                        <input type="checkbox" id="bs-accept-terms">
                        <?php
                        $terms_text = $settings['offerte_terms_text'] ?: __( 'Ik ga akkoord met de algemene voorwaarden', 'bossier-calculator' );
                        if ( ! empty( $settings['offerte_terms_url'] ) ) {
                            printf(
                                '%s (<a href="%s" target="_blank">%s</a>)',
                                esc_html( $terms_text ),
                                esc_url( $settings['offerte_terms_url'] ),
                                esc_html__( 'lezen', 'bossier-calculator' )
                            );
                        } else {
                            echo esc_html( $terms_text );
                        }
                        ?>
                    </label>
                </div>

                <!-- Submit -->
                <div class="bs-accept-submit">
                    <button type="button" id="bs-accept-offerte" class="bs-btn bs-btn-primary" disabled>
                        <?php esc_html_e( 'Offerte Accepteren', 'bossier-calculator' ); ?>
                    </button>
                    <div id="bs-accept-message" class="bs-accept-message" style="display:none;"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
