<?php
/**
 * Offerte PDF template.
 *
 * @package Bossier_Calculator_Builder
 * @var Bossier\Calculator\Offerte\Offerte_PDF   $offerte_pdf
 * @var Bossier\Calculator\Offerte\Offerte_Model  $offerte
 * @var array  $company
 * @var array  $settings
 */

defined( 'ABSPATH' ) || exit;

$customer = $offerte->get_customer_data();
$items    = $offerte->get_items();
$adres    = $customer['adres'] ?? array();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Offerte <?php echo esc_html( $offerte->get_quote_number() ); ?></title>
    <style>
        <?php include __DIR__ . '/offerte-style.css'; ?>
    </style>
</head>
<body>
    <div class="document-wrapper">
        <!-- Header -->
        <table class="header-table" width="100%">
            <tr>
                <td class="logo-cell" width="50%">
                    <?php echo $offerte_pdf->get_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </td>
                <td class="company-cell" width="50%" style="text-align:right;">
                    <strong><?php echo esc_html( $company['name'] ); ?></strong><br>
                    <?php if ( ! empty( $company['address'] ) ) : ?>
                        <?php echo nl2br( esc_html( $company['address'] ) ); ?><br>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['phone'] ) ) : ?>
                        T: <?php echo esc_html( $company['phone'] ); ?><br>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h1 class="document-title">OFFERTE</h1>

        <!-- Quote Info + Customer Address -->
        <table class="info-table" width="100%">
            <tr>
                <td class="customer-address" width="50%">
                    <strong><?php echo esc_html( $customer['naam'] ); ?></strong><br>
                    <?php if ( ! empty( $customer['bedrijf'] ) ) : ?>
                        <?php echo esc_html( $customer['bedrijf'] ); ?><br>
                    <?php endif; ?>
                    <?php if ( ! empty( $adres['straat'] ) ) : ?>
                        <?php echo esc_html( $adres['straat'] ); ?><br>
                        <?php echo esc_html( ( $adres['postcode'] ?? '' ) . ' ' . ( $adres['plaats'] ?? '' ) ); ?><br>
                    <?php endif; ?>
                    <?php if ( ! empty( $customer['email'] ) ) : ?>
                        <?php echo esc_html( $customer['email'] ); ?><br>
                    <?php endif; ?>
                    <?php if ( ! empty( $customer['tel'] ) ) : ?>
                        T: <?php echo esc_html( $customer['tel'] ); ?>
                    <?php endif; ?>
                </td>
                <td class="quote-details" width="50%" style="text-align:right;">
                    <table class="details-table" style="margin-left:auto;">
                        <tr>
                            <td class="detail-label">Offertenummer:</td>
                            <td class="detail-value"><?php echo esc_html( $offerte->get_quote_number() ); ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Datum:</td>
                            <td class="detail-value"><?php echo esc_html( $offerte_pdf->format_date( $offerte->get_post()->post_date ) ); ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Geldig tot:</td>
                            <td class="detail-value"><?php echo esc_html( $offerte_pdf->format_date( $offerte->get_valid_until() ) ); ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Betaalmethode:</td>
                            <td class="detail-value">
                                <?php
                                $pm = \Bossier\Calculator\Offerte\Offerte_Settings::get_payment_method_options();
                                echo esc_html( $pm[ $offerte->get_payment_method() ] ?? $offerte->get_payment_method() );
                                ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Products -->
        <table class="products-table" width="100%">
            <thead>
                <tr>
                    <th class="col-product">Product</th>
                    <th class="col-specs">Specificaties</th>
                    <th class="col-qty">Aantal</th>
                    <th class="col-price">Stukprijs</th>
                    <th class="col-total">Totaal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td class="col-product">
                            <?php echo esc_html( $item['title'] ?? '' ); ?>
                            <?php
                            $cats = \Bossier\Calculator\Offerte\Offerte_Settings::get_product_categories();
                            if ( ! empty( $item['category'] ) && isset( $cats[ $item['category'] ] ) ) :
                            ?>
                                <br><small><?php echo esc_html( $cats[ $item['category'] ] ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="col-specs">
                            <?php
                            $specs = $item['specs'] ?? array();
                            $parts = array();
                            if ( ! empty( $specs['lengte'] ) ) $parts[] = $specs['lengte'] . 'cm (L)';
                            if ( ! empty( $specs['breedte'] ) ) $parts[] = $specs['breedte'] . 'cm (B)';
                            if ( ! empty( $specs['hoogte'] ) ) $parts[] = $specs['hoogte'] . 'cm (H)';
                            if ( ! empty( $specs['kleur'] ) ) $parts[] = $specs['kleur'];
                            if ( ! empty( $specs['afwerking'] ) ) $parts[] = $specs['afwerking'];
                            echo esc_html( implode( ' | ', $parts ) );
                            ?>
                        </td>
                        <td class="col-qty"><?php echo esc_html( $item['quantity'] ?? 1 ); ?></td>
                        <td class="col-price"><?php echo esc_html( $offerte_pdf->format_price( $item['unit_price'] ?? 0 ) ); ?></td>
                        <td class="col-total"><?php echo esc_html( $offerte_pdf->format_price( $item['line_total'] ?? 0 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table" width="100%">
            <tr>
                <td class="totals-label">Subtotaal</td>
                <td class="totals-value"><?php echo esc_html( $offerte_pdf->format_price( $offerte->get_subtotal() ) ); ?></td>
            </tr>
            <?php if ( $offerte->get_shipping_cost() > 0 ) : ?>
                <tr>
                    <td class="totals-label">Verzendkosten</td>
                    <td class="totals-value"><?php echo esc_html( $offerte_pdf->format_price( $offerte->get_shipping_cost() ) ); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="totals-label">BTW (21%)</td>
                <td class="totals-value"><?php echo esc_html( $offerte_pdf->format_price( $offerte->get_tax() ) ); ?></td>
            </tr>
            <tr class="totals-grand">
                <td class="totals-label"><strong>Totaal</strong></td>
                <td class="totals-value"><strong><?php echo esc_html( $offerte_pdf->format_price( $offerte->get_total() ) ); ?></strong></td>
            </tr>
        </table>

        <!-- Customer Note -->
        <?php if ( ! empty( $offerte->get_customer_note() ) ) : ?>
            <div class="customer-note">
                <h3>Opmerking</h3>
                <p><?php echo nl2br( esc_html( $offerte->get_customer_note() ) ); ?></p>
            </div>
        <?php endif; ?>

        <!-- Terms -->
        <?php if ( ! empty( $settings['offerte_terms_text'] ) ) : ?>
            <div class="terms-section">
                <p class="terms-text"><?php echo esc_html( $settings['offerte_terms_text'] ); ?></p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="document-footer">
            <table width="100%">
                <tr>
                    <td><?php echo esc_html( $company['name'] ); ?></td>
                    <?php if ( ! empty( $company['kvk'] ) ) : ?>
                        <td>KVK: <?php echo esc_html( $company['kvk'] ); ?></td>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['btw'] ) ) : ?>
                        <td>BTW: <?php echo esc_html( $company['btw'] ); ?></td>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['iban'] ) ) : ?>
                        <td>IBAN: <?php echo esc_html( $company['iban'] ); ?></td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
