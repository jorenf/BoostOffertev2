<?php
/**
 * Offerte Accepted (success) page template.
 *
 * @package Boost_Offerte
 * @var BoostOfferte\Offerte_Model $bs_offerte (via $GLOBALS)
 */

defined( 'ABSPATH' ) || exit;

$offerte  = $GLOBALS['bs_offerte'];
$customer = $offerte->get_customer_data();
$settings = \BoostOfferte\Offerte_Settings::get_settings();
$company  = $settings['offerte_company_name'] ?: get_bloginfo( 'name' );

get_header();
?>
<div class="bs-offerte-page">
    <div class="bs-offerte-container">
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
        </div>

        <div class="bs-offerte-accepted-message">
            <div class="bs-success-icon">&#10003;</div>
            <h2><?php esc_html_e( 'Offerte Geaccepteerd!', 'boost-offerte' ); ?></h2>
            <p><?php echo esc_html( sprintf(
                __( 'Bedankt %s! Uw offerte %s is succesvol geaccepteerd.', 'boost-offerte' ),
                $customer['naam'],
                $offerte->get_quote_number()
            ) ); ?></p>

            <?php if ( $offerte->get_wc_order_id() ) : ?>
                <p><?php echo esc_html( sprintf(
                    __( 'Uw bestelling #%d is aangemaakt. U ontvangt hierover een e-mail met verdere instructies.', 'boost-offerte' ),
                    $offerte->get_wc_order_id()
                ) ); ?></p>

                <?php
                $order = wc_get_order( $offerte->get_wc_order_id() );
                if ( $order && 'pending' === $order->get_status() ) :
                ?>
                    <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="bs-btn bs-btn-primary">
                        <?php esc_html_e( 'Ga naar betaling', 'boost-offerte' ); ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="bs-accepted-details">
                <p><strong><?php esc_html_e( 'Ondertekend op:', 'boost-offerte' ); ?></strong> <?php echo esc_html( date_i18n( 'd F Y \o\m H:i', strtotime( $offerte->get_signed_at() ) ) ); ?></p>
                <p><strong><?php esc_html_e( 'Totaalbedrag:', 'boost-offerte' ); ?></strong> &euro; <?php echo esc_html( number_format( $offerte->get_total(), 2, ',', '.' ) ); ?></p>
            </div>
        </div>
    </div>
</div>
<?php
get_footer();
