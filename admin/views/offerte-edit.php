<?php
/**
 * Offerte Edit Page view.
 *
 * @package Boost_Offerte
 * @var BoostOfferte\Offerte_Model|null $offerte
 * @var array  $settings
 * @var array  $categories
 * @var array  $payment_methods
 * @var string $next_number
 */

defined( 'ABSPATH' ) || exit;

$customer_data = $offerte ? $offerte->get_customer_data() : array(
    'bedrijf' => '', 'naam' => '', 'email' => '', 'tel' => '',
    'adres' => array( 'straat' => '', 'postcode' => '', 'plaats' => '', 'land' => 'NL' ),
);
$items          = $offerte ? $offerte->get_items() : array();
$shipping_cost  = $offerte ? $offerte->get_shipping_cost() : 0;
$valid_until    = $offerte ? $offerte->get_valid_until() : gmdate( 'Y-m-d', strtotime( '+' . $settings['offerte_default_validity'] . ' days' ) );
$payment_method = $offerte ? $offerte->get_payment_method() : 'ideal_invoice';
$reminder_days  = $offerte ? $offerte->get_reminder_days() : $settings['offerte_default_reminder'];
$customer_note  = $offerte ? $offerte->get_customer_note() : '';
$status         = $offerte ? $offerte->get_status() : 'offerte-draft';
$email_log      = $offerte ? $offerte->get_email_log() : array();
?>
<div class="wrap bs-offerte-wrap">
    <h1 class="wp-heading-inline">
        <?php if ( $offerte ) : ?>
            <?php echo esc_html( sprintf( __( 'Offerte: %s', 'boost-offerte' ), $offerte->get_quote_number() ) ); ?>
        <?php else : ?>
            <?php esc_html_e( 'Nieuwe Offerte', 'boost-offerte' ); ?>
        <?php endif; ?>
    </h1>

    <?php if ( $offerte ) : ?>
        <span class="bs-offerte-badge" style="background-color:<?php echo esc_attr( \BoostOfferte\Offerte_Post_Type::get_status_color( $status ) ); ?>;color:#fff;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:600;margin-left:10px;vertical-align:middle;">
            <?php echo esc_html( \BoostOfferte\Offerte_Post_Type::get_status_label( $status ) ); ?>
        </span>
    <?php endif; ?>

    <hr class="wp-header-end">

    <input type="hidden" id="bs-offerte-id" value="<?php echo $offerte ? esc_attr( $offerte->get_id() ) : ''; ?>">

    <div class="bs-offerte-grid">
        <!-- Left column: Customer + Items -->
        <div class="bs-offerte-main">
            <!-- Customer Section -->
            <div class="bs-offerte-section">
                <h2><?php esc_html_e( 'Klantgegevens', 'boost-offerte' ); ?></h2>
                <div class="bs-offerte-customer-search">
                    <input type="text" id="bs-customer-search" placeholder="<?php esc_attr_e( 'Zoek bestaande klant...', 'boost-offerte' ); ?>" class="regular-text" autocomplete="off">
                    <div id="bs-customer-results" class="bs-customer-results"></div>
                </div>
                <div class="bs-offerte-fields">
                    <div class="bs-field-row">
                        <div class="bs-field">
                            <label><?php esc_html_e( 'Bedrijf', 'boost-offerte' ); ?></label>
                            <input type="text" name="customer[bedrijf]" value="<?php echo esc_attr( $customer_data['bedrijf'] ); ?>" class="regular-text">
                        </div>
                        <div class="bs-field">
                            <label><?php esc_html_e( 'Naam', 'boost-offerte' ); ?> *</label>
                            <input type="text" name="customer[naam]" value="<?php echo esc_attr( $customer_data['naam'] ); ?>" class="regular-text" required>
                        </div>
                    </div>
                    <div class="bs-field-row">
                        <div class="bs-field">
                            <label><?php esc_html_e( 'E-mail', 'boost-offerte' ); ?> *</label>
                            <input type="email" name="customer[email]" value="<?php echo esc_attr( $customer_data['email'] ); ?>" class="regular-text" required>
                        </div>
                        <div class="bs-field">
                            <label><?php esc_html_e( 'Telefoon', 'boost-offerte' ); ?></label>
                            <input type="text" name="customer[tel]" value="<?php echo esc_attr( $customer_data['tel'] ); ?>" class="regular-text">
                        </div>
                    </div>
                    <div class="bs-field-row">
                        <div class="bs-field">
                            <label><?php esc_html_e( 'Straat + huisnummer', 'boost-offerte' ); ?></label>
                            <input type="text" name="customer[straat]" value="<?php echo esc_attr( $customer_data['adres']['straat'] ); ?>" class="regular-text">
                        </div>
                        <div class="bs-field bs-field-small">
                            <label><?php esc_html_e( 'Postcode', 'boost-offerte' ); ?></label>
                            <input type="text" name="customer[postcode]" value="<?php echo esc_attr( $customer_data['adres']['postcode'] ); ?>">
                        </div>
                    </div>
                    <div class="bs-field-row">
                        <div class="bs-field">
                            <label><?php esc_html_e( 'Plaats', 'boost-offerte' ); ?></label>
                            <input type="text" name="customer[plaats]" value="<?php echo esc_attr( $customer_data['adres']['plaats'] ); ?>" class="regular-text">
                        </div>
                        <div class="bs-field bs-field-small">
                            <label><?php esc_html_e( 'Land', 'boost-offerte' ); ?></label>
                            <select name="customer[land]">
                                <option value="NL" <?php selected( $customer_data['adres']['land'], 'NL' ); ?>>Nederland</option>
                                <option value="BE" <?php selected( $customer_data['adres']['land'], 'BE' ); ?>>België</option>
                                <option value="DE" <?php selected( $customer_data['adres']['land'], 'DE' ); ?>>Duitsland</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="bs-offerte-section">
                <h2><?php esc_html_e( 'Producten', 'boost-offerte' ); ?></h2>

                <table class="widefat bs-offerte-items-table" id="bs-items-table">
                    <thead>
                        <tr>
                            <th class="bs-col-product"><?php esc_html_e( 'Product', 'boost-offerte' ); ?></th>
                            <th class="bs-col-specs"><?php esc_html_e( 'Specificaties', 'boost-offerte' ); ?></th>
                            <th class="bs-col-qty"><?php esc_html_e( 'Aantal', 'boost-offerte' ); ?></th>
                            <th class="bs-col-price"><?php esc_html_e( 'Stukprijs', 'boost-offerte' ); ?></th>
                            <th class="bs-col-total"><?php esc_html_e( 'Totaal', 'boost-offerte' ); ?></th>
                            <th class="bs-col-actions"></th>
                        </tr>
                    </thead>
                    <tbody id="bs-items-body">
                        <?php if ( ! empty( $items ) ) : ?>
                            <?php foreach ( $items as $index => $item ) : ?>
                                <tr class="bs-item-row" data-index="<?php echo esc_attr( $index ); ?>">
                                    <td class="bs-col-product">
                                        <select class="bs-item-category" name="items[<?php echo esc_attr( $index ); ?>][category]">
                                            <option value=""><?php esc_html_e( 'Categorie...', 'boost-offerte' ); ?></option>
                                            <?php foreach ( $categories as $key => $label ) : ?>
                                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $item['category'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" class="bs-item-title" name="items[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Productnaam', 'boost-offerte' ); ?>">
                                        <input type="hidden" class="bs-item-product-id" name="items[<?php echo esc_attr( $index ); ?>][product_id]" value="<?php echo esc_attr( $item['product_id'] ?? 0 ); ?>">
                                        <input type="hidden" class="bs-item-calc-id" name="items[<?php echo esc_attr( $index ); ?>][calculator_id]" value="<?php echo esc_attr( $item['calculator_id'] ?? 0 ); ?>">
                                    </td>
                                    <td class="bs-col-specs">
                                        <div class="bs-specs-grid">
                                            <input type="text" class="bs-spec" name="items[<?php echo esc_attr( $index ); ?>][lengte]" value="<?php echo esc_attr( $item['specs']['lengte'] ?? '' ); ?>" placeholder="L (cm)" size="6">
                                            <input type="text" class="bs-spec" name="items[<?php echo esc_attr( $index ); ?>][breedte]" value="<?php echo esc_attr( $item['specs']['breedte'] ?? '' ); ?>" placeholder="B (cm)" size="6">
                                            <input type="text" class="bs-spec" name="items[<?php echo esc_attr( $index ); ?>][hoogte]" value="<?php echo esc_attr( $item['specs']['hoogte'] ?? '' ); ?>" placeholder="H (cm)" size="6">
                                            <input type="text" class="bs-spec" name="items[<?php echo esc_attr( $index ); ?>][kleur]" value="<?php echo esc_attr( $item['specs']['kleur'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Kleur', 'boost-offerte' ); ?>" size="10">
                                            <input type="text" class="bs-spec" name="items[<?php echo esc_attr( $index ); ?>][afwerking]" value="<?php echo esc_attr( $item['specs']['afwerking'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Afwerking', 'boost-offerte' ); ?>" size="10">
                                        </div>
                                    </td>
                                    <td class="bs-col-qty">
                                        <input type="number" class="bs-item-qty" name="items[<?php echo esc_attr( $index ); ?>][quantity]" value="<?php echo esc_attr( $item['quantity'] ?? 1 ); ?>" min="1" size="4">
                                    </td>
                                    <td class="bs-col-price">
                                        <input type="text" class="bs-item-unit-price" name="items[<?php echo esc_attr( $index ); ?>][unit_price]" value="<?php echo esc_attr( number_format( $item['unit_price'] ?? 0, 2, '.', '' ) ); ?>" size="8">
                                    </td>
                                    <td class="bs-col-total">
                                        <span class="bs-item-line-total"><?php echo esc_html( number_format( $item['line_total'] ?? 0, 2, ',', '.' ) ); ?></span>
                                        <input type="hidden" class="bs-item-line-total-input" name="items[<?php echo esc_attr( $index ); ?>][line_total]" value="<?php echo esc_attr( $item['line_total'] ?? 0 ); ?>">
                                    </td>
                                    <td class="bs-col-actions">
                                        <button type="button" class="button bs-remove-item" title="<?php esc_attr_e( 'Verwijderen', 'boost-offerte' ); ?>">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <button type="button" class="button" id="bs-add-item">+ <?php esc_html_e( 'Product Toevoegen', 'boost-offerte' ); ?></button>

                <!-- Totals -->
                <div class="bs-offerte-totals">
                    <table class="bs-totals-table">
                        <tr>
                            <td><?php esc_html_e( 'Subtotaal', 'boost-offerte' ); ?></td>
                            <td class="bs-total-value" id="bs-subtotal"><?php echo esc_html( number_format( $offerte ? $offerte->get_subtotal() : 0, 2, ',', '.' ) ); ?></td>
                        </tr>
                        <tr>
                            <td>
                                <?php esc_html_e( 'Verzendkosten', 'boost-offerte' ); ?>
                                <input type="text" id="bs-shipping-cost" name="shipping_cost" value="<?php echo esc_attr( number_format( $shipping_cost, 2, '.', '' ) ); ?>" size="8" style="margin-left:10px;">
                            </td>
                            <td class="bs-total-value" id="bs-shipping-display"><?php echo esc_html( number_format( $shipping_cost, 2, ',', '.' ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'BTW (21%)', 'boost-offerte' ); ?></td>
                            <td class="bs-total-value" id="bs-tax"><?php echo esc_html( number_format( $offerte ? $offerte->get_tax() : 0, 2, ',', '.' ) ); ?></td>
                        </tr>
                        <tr class="bs-total-row">
                            <td><strong><?php esc_html_e( 'Totaal', 'boost-offerte' ); ?></strong></td>
                            <td class="bs-total-value" id="bs-total"><strong><?php echo esc_html( number_format( $offerte ? $offerte->get_total() : 0, 2, ',', '.' ) ); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right column: Settings -->
        <div class="bs-offerte-sidebar">
            <div class="bs-offerte-section">
                <h2><?php esc_html_e( 'Offerte Details', 'boost-offerte' ); ?></h2>

                <div class="bs-field">
                    <label><?php esc_html_e( 'Geldig tot', 'boost-offerte' ); ?></label>
                    <input type="date" name="valid_until" value="<?php echo esc_attr( $valid_until ); ?>" class="regular-text">
                </div>

                <div class="bs-field">
                    <label><?php esc_html_e( 'Betaalmethode', 'boost-offerte' ); ?></label>
                    <select name="payment_method">
                        <?php foreach ( $payment_methods as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $payment_method, $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bs-field">
                    <label><?php esc_html_e( 'Herinnering na (dagen)', 'boost-offerte' ); ?></label>
                    <select name="reminder_days">
                        <option value="" <?php selected( $reminder_days, null ); ?>><?php esc_html_e( 'Geen herinnering', 'boost-offerte' ); ?></option>
                        <option value="7" <?php selected( $reminder_days, 7 ); ?>>7 <?php esc_html_e( 'dagen', 'boost-offerte' ); ?></option>
                        <option value="14" <?php selected( $reminder_days, 14 ); ?>>14 <?php esc_html_e( 'dagen', 'boost-offerte' ); ?></option>
                    </select>
                </div>

                <div class="bs-field">
                    <label><?php esc_html_e( 'Notitie voor klant', 'boost-offerte' ); ?></label>
                    <textarea name="customer_note" rows="4" class="large-text"><?php echo esc_textarea( $customer_note ); ?></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="bs-offerte-section bs-offerte-actions">
                <h2><?php esc_html_e( 'Acties', 'boost-offerte' ); ?></h2>
                <button type="button" class="button button-primary button-large bs-offerte-btn" id="bs-save-draft">
                    <?php esc_html_e( 'Opslaan als Concept', 'boost-offerte' ); ?>
                </button>

                <?php if ( ! $offerte || in_array( $status, array( 'offerte-draft', 'offerte-sent', 'offerte-viewed' ), true ) ) : ?>
                    <button type="button" class="button button-large bs-offerte-btn" id="bs-send-offerte">
                        <?php esc_html_e( 'Offerte Verzenden', 'boost-offerte' ); ?>
                    </button>
                <?php endif; ?>

                <?php if ( $offerte ) : ?>
                    <button type="button" class="button button-large bs-offerte-btn" id="bs-generate-pdf">
                        <?php esc_html_e( 'PDF Genereren', 'boost-offerte' ); ?>
                    </button>

                    <?php if ( $offerte->get_public_url() ) : ?>
                        <a href="<?php echo esc_url( $offerte->get_public_url() ); ?>" target="_blank" class="button button-large bs-offerte-btn">
                            <?php esc_html_e( 'Preview', 'boost-offerte' ); ?>
                        </a>
                    <?php endif; ?>

                    <hr>

                    <button type="button" class="button button-large bs-offerte-btn" id="bs-duplicate-offerte">
                        <?php esc_html_e( 'Dupliceren', 'boost-offerte' ); ?>
                    </button>

                    <?php if ( ! in_array( $status, array( 'offerte-accepted', 'offerte-cancelled' ), true ) ) : ?>
                        <button type="button" class="button button-large bs-offerte-btn bs-btn-danger" id="bs-cancel-offerte">
                            <?php esc_html_e( 'Annuleren', 'boost-offerte' ); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ( $offerte->get_wc_order_id() ) : ?>
                        <hr>
                        <p>
                            <strong><?php esc_html_e( 'WooCommerce Order:', 'boost-offerte' ); ?></strong>
                            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $offerte->get_wc_order_id() . '&action=edit' ) ); ?>" target="_blank">
                                #<?php echo esc_html( $offerte->get_wc_order_id() ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Email Log -->
            <?php if ( ! empty( $email_log ) ) : ?>
                <div class="bs-offerte-section">
                    <h2><?php esc_html_e( 'E-mail Log', 'boost-offerte' ); ?></h2>
                    <table class="widefat bs-email-log">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Datum', 'boost-offerte' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'boost-offerte' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'boost-offerte' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( array_reverse( $email_log ) as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( date_i18n( 'd-m-Y H:i', strtotime( $log['timestamp'] ) ) ); ?></td>
                                    <td><?php echo esc_html( $log['type'] ); ?></td>
                                    <td><?php echo $log['success'] ? '<span style="color:#38a169;">OK</span>' : '<span style="color:#e53e3e;">Mislukt</span>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Item row template -->
<script type="text/template" id="bs-item-row-template">
    <tr class="bs-item-row" data-index="{{index}}">
        <td class="bs-col-product">
            <select class="bs-item-category" name="items[{{index}}][category]">
                <option value=""><?php esc_html_e( 'Categorie...', 'boost-offerte' ); ?></option>
                <?php foreach ( $categories as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" class="bs-item-title" name="items[{{index}}][title]" value="" placeholder="<?php esc_attr_e( 'Productnaam', 'boost-offerte' ); ?>">
            <input type="hidden" class="bs-item-product-id" name="items[{{index}}][product_id]" value="0">
            <input type="hidden" class="bs-item-calc-id" name="items[{{index}}][calculator_id]" value="0">
        </td>
        <td class="bs-col-specs">
            <div class="bs-specs-grid">
                <input type="text" class="bs-spec" name="items[{{index}}][lengte]" placeholder="L (cm)" size="6">
                <input type="text" class="bs-spec" name="items[{{index}}][breedte]" placeholder="B (cm)" size="6">
                <input type="text" class="bs-spec" name="items[{{index}}][hoogte]" placeholder="H (cm)" size="6">
                <input type="text" class="bs-spec" name="items[{{index}}][kleur]" placeholder="<?php esc_attr_e( 'Kleur', 'boost-offerte' ); ?>" size="10">
                <input type="text" class="bs-spec" name="items[{{index}}][afwerking]" placeholder="<?php esc_attr_e( 'Afwerking', 'boost-offerte' ); ?>" size="10">
            </div>
        </td>
        <td class="bs-col-qty">
            <input type="number" class="bs-item-qty" name="items[{{index}}][quantity]" value="1" min="1" size="4">
        </td>
        <td class="bs-col-price">
            <input type="text" class="bs-item-unit-price" name="items[{{index}}][unit_price]" value="0.00" size="8">
        </td>
        <td class="bs-col-total">
            <span class="bs-item-line-total">0,00</span>
            <input type="hidden" class="bs-item-line-total-input" name="items[{{index}}][line_total]" value="0">
        </td>
        <td class="bs-col-actions">
            <button type="button" class="button bs-remove-item" title="<?php esc_attr_e( 'Verwijderen', 'boost-offerte' ); ?>">&times;</button>
        </td>
    </tr>
</script>
