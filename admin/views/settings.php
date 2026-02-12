<?php
/**
 * Boost Offerte Settings page view.
 *
 * @package Boost_Offerte
 * @var array $settings Current settings.
 */

defined( 'ABSPATH' ) || exit;

$has_calculator = \BoostOfferte\is_boost_calculator_active();
?>

<div class="wrap boost-offerte-settings">
    <h1><?php esc_html_e( 'Boost Offerte Instellingen', 'boost-offerte' ); ?></h1>

    <?php if ( ! $has_calculator ) : ?>
        <div class="notice notice-warning" style="margin-bottom: 20px;">
            <p>
                <strong><?php esc_html_e( 'Boost Calculator is niet actief.', 'boost-offerte' ); ?></strong>
                <?php esc_html_e( 'Calculator integratie (automatische prijsberekening) is niet beschikbaar. U kunt nog steeds handmatig offertes aanmaken met vrije prijsinvoer.', 'boost-offerte' ); ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'boost_offerte_settings_group' ); ?>

        <!-- Bedrijfsgegevens -->
        <div class="boost-settings-section" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e( 'Bedrijfsgegevens', 'boost-offerte' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Deze gegevens worden gebruikt op offertes en in e-mails.', 'boost-offerte' ); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Bedrijfsnaam', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_name]"
                               value="<?php echo esc_attr( $settings['offerte_company_name'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Adres', 'boost-offerte' ); ?></th>
                    <td>
                        <textarea name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_address]"
                                  rows="3"
                                  class="large-text"><?php echo esc_textarea( $settings['offerte_company_address'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Telefoon', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_phone]"
                               value="<?php echo esc_attr( $settings['offerte_company_phone'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'KVK-nummer', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_kvk]"
                               value="<?php echo esc_attr( $settings['offerte_company_kvk'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'BTW-nummer', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_btw]"
                               value="<?php echo esc_attr( $settings['offerte_company_btw'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'IBAN', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_iban]"
                               value="<?php echo esc_attr( $settings['offerte_company_iban'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Logo', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_company_logo]"
                               value="<?php echo esc_attr( $settings['offerte_company_logo'] ); ?>"
                               class="small-text"
                               placeholder="<?php esc_attr_e( 'Attachment ID', 'boost-offerte' ); ?>">
                        <p class="description"><?php esc_html_e( 'WordPress Media Library attachment ID van het bedrijfslogo.', 'boost-offerte' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Offerte Instellingen -->
        <div class="boost-settings-section" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e( 'Offerte Instellingen', 'boost-offerte' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Offerte Prefix', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_prefix]"
                               value="<?php echo esc_attr( $settings['offerte_prefix'] ); ?>"
                               class="small-text">
                        <p class="description"><?php esc_html_e( 'Prefix voor offertenummers, bijv. BS-2026-0001.', 'boost-offerte' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standaard Geldigheid', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="number"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_default_validity]"
                               value="<?php echo esc_attr( $settings['offerte_default_validity'] ); ?>"
                               class="small-text"
                               min="1"
                               step="1">
                        <span class="description"><?php esc_html_e( 'dagen', 'boost-offerte' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standaard Herinnering', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="number"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_default_reminder]"
                               value="<?php echo esc_attr( $settings['offerte_default_reminder'] ); ?>"
                               class="small-text"
                               min="0"
                               step="1">
                        <span class="description"><?php esc_html_e( 'dagen na verzending (0 = geen herinnering)', 'boost-offerte' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Betaalmethoden', 'boost-offerte' ); ?></th>
                    <td>
                        <?php
                        $payment_options  = \BoostOfferte\Offerte_Settings::get_payment_method_options();
                        $selected_methods = $settings['offerte_payment_methods'] ?? array( 'ideal_invoice' );
                        foreach ( $payment_options as $key => $label ) :
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_payment_methods][]"
                                       value="<?php echo esc_attr( $key ); ?>"
                                       <?php checked( in_array( $key, $selected_methods, true ) ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Algemene Voorwaarden URL', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="url"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_terms_url]"
                               value="<?php echo esc_attr( $settings['offerte_terms_url'] ); ?>"
                               class="large-text"
                               placeholder="https://">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Voorwaarden Tekst', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_terms_text]"
                               value="<?php echo esc_attr( $settings['offerte_terms_text'] ); ?>"
                               class="large-text">
                        <p class="description"><?php esc_html_e( 'Tekst bij de voorwaarden checkbox op de publieke offertepagina.', 'boost-offerte' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- E-mail Instellingen -->
        <div class="boost-settings-section" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e( 'E-mail Instellingen', 'boost-offerte' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Afzendernaam', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_sender_name]"
                               value="<?php echo esc_attr( $settings['offerte_sender_name'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Afzender E-mail', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="email"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_sender_email]"
                               value="<?php echo esc_attr( $settings['offerte_sender_email'] ); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Offerte Verzonden E-mail', 'boost-offerte' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Onderwerp', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_email_sent_subject]"
                               value="<?php echo esc_attr( $settings['offerte_email_sent_subject'] ); ?>"
                               class="large-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Inhoud', 'boost-offerte' ); ?></th>
                    <td>
                        <textarea name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_email_sent_body]"
                                  rows="8"
                                  class="large-text"><?php echo esc_textarea( $settings['offerte_email_sent_body'] ); ?></textarea>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Herinnering E-mail', 'boost-offerte' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Onderwerp', 'boost-offerte' ); ?></th>
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_email_reminder_subject]"
                               value="<?php echo esc_attr( $settings['offerte_email_reminder_subject'] ); ?>"
                               class="large-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Inhoud', 'boost-offerte' ); ?></th>
                    <td>
                        <textarea name="<?php echo esc_attr( \BoostOfferte\Offerte_Settings::OPTION_NAME ); ?>[offerte_email_reminder_body]"
                                  rows="8"
                                  class="large-text"><?php echo esc_textarea( $settings['offerte_email_reminder_body'] ); ?></textarea>
                    </td>
                </tr>
            </table>

            <div style="background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 4px; padding: 15px; margin-top: 15px;">
                <h4 style="margin-top: 0;"><?php esc_html_e( 'Beschikbare Placeholders', 'boost-offerte' ); ?></h4>
                <p><code>{customer_name}</code> — <?php esc_html_e( 'Klantnaam', 'boost-offerte' ); ?></p>
                <p><code>{company_name}</code> — <?php esc_html_e( 'Uw bedrijfsnaam', 'boost-offerte' ); ?></p>
                <p><code>{quote_number}</code> — <?php esc_html_e( 'Offertenummer', 'boost-offerte' ); ?></p>
                <p><code>{quote_url}</code> — <?php esc_html_e( 'Link naar offertepagina', 'boost-offerte' ); ?></p>
                <p><code>{valid_until}</code> — <?php esc_html_e( 'Geldig tot datum', 'boost-offerte' ); ?></p>
                <p><code>{total}</code> — <?php esc_html_e( 'Totaalbedrag', 'boost-offerte' ); ?></p>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="boost-settings-section" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e( 'Integratie Status', 'boost-offerte' ); ?></h2>
            <table class="widefat" style="margin-top: 10px;">
                <tbody>
                    <tr>
                        <td><strong>WooCommerce</strong></td>
                        <td>
                            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                <span style="color: #059669;">&#10003; <?php esc_html_e( 'Actief', 'boost-offerte' ); ?></span>
                            <?php else : ?>
                                <span style="color: #dc2626;">&#10007; <?php esc_html_e( 'Niet gevonden', 'boost-offerte' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Boost Calculator</strong></td>
                        <td>
                            <?php if ( $has_calculator ) : ?>
                                <span style="color: #059669;">&#10003; <?php esc_html_e( 'Actief — calculator prijsberekening beschikbaar', 'boost-offerte' ); ?></span>
                            <?php else : ?>
                                <span style="color: #d97706;">&#9888; <?php esc_html_e( 'Niet actief — handmatige prijsinvoer', 'boost-offerte' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>DOMPDF (PDF generatie)</strong></td>
                        <td>
                            <?php
                            $dompdf_available = class_exists( 'Dompdf\\Dompdf' );
                            if ( ! $dompdf_available ) {
                                $bc_dir = \BoostOfferte\get_boost_calculator_dir();
                                $dompdf_available = $bc_dir && file_exists( $bc_dir . 'includes/pdf/autoload.php' );
                            }
                            ?>
                            <?php if ( $dompdf_available ) : ?>
                                <span style="color: #059669;">&#10003; <?php esc_html_e( 'Beschikbaar', 'boost-offerte' ); ?></span>
                            <?php else : ?>
                                <span style="color: #dc2626;">&#10007; <?php esc_html_e( 'Niet gevonden — installeer Boost Calculator of DOMPDF via Composer', 'boost-offerte' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php submit_button( __( 'Instellingen Opslaan', 'boost-offerte' ) ); ?>
    </form>
</div>
