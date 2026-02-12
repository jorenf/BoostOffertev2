<?php
/**
 * Offerte Email system.
 *
 * @package Bossier_Calculator_Builder
 */

namespace Bossier\Calculator\Offerte;

defined( 'ABSPATH' ) || exit;

/**
 * Offerte_Email class - Handles sending and logging offerte-related emails.
 */
class Offerte_Email {

    const TYPE_SENT     = 'quote_sent';
    const TYPE_REMINDER = 'quote_reminder';
    const TYPE_ACCEPTED = 'quote_accepted';

    /**
     * Send the initial quote email to the customer.
     *
     * @param Offerte_Model $offerte Offerte instance.
     * @return bool
     */
    public function send_quote( Offerte_Model $offerte ) {
        $settings  = Offerte_Settings::get_settings();
        $customer  = $offerte->get_customer_data();
        $recipient = $customer['email'];

        if ( empty( $recipient ) ) {
            return false;
        }

        $subject = $this->replace_placeholders( $settings['offerte_email_sent_subject'], $offerte );
        $body    = $this->replace_placeholders( $settings['offerte_email_sent_body'], $offerte );

        $headers     = $this->get_email_headers( $settings );
        $attachments = array();

        // Attach PDF if available.
        $pdf_path = $offerte->get_pdf_path();
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            $attachments[] = $pdf_path;
        }

        $html_body = $this->wrap_html( $body, $subject );
        $success   = wp_mail( $recipient, $subject, $html_body, $headers, $attachments );

        $this->log_email( $offerte, self::TYPE_SENT, $recipient, $success, $subject );

        return $success;
    }

    /**
     * Send a reminder email to the customer.
     *
     * @param Offerte_Model $offerte Offerte instance.
     * @return bool
     */
    public function send_reminder( Offerte_Model $offerte ) {
        $settings  = Offerte_Settings::get_settings();
        $customer  = $offerte->get_customer_data();
        $recipient = $customer['email'];

        if ( empty( $recipient ) ) {
            return false;
        }

        $subject = $this->replace_placeholders( $settings['offerte_email_reminder_subject'], $offerte );
        $body    = $this->replace_placeholders( $settings['offerte_email_reminder_body'], $offerte );

        $headers     = $this->get_email_headers( $settings );
        $attachments = array();

        $pdf_path = $offerte->get_pdf_path();
        if ( $pdf_path && file_exists( $pdf_path ) ) {
            $attachments[] = $pdf_path;
        }

        $html_body = $this->wrap_html( $body, $subject );
        $success   = wp_mail( $recipient, $subject, $html_body, $headers, $attachments );

        $this->log_email( $offerte, self::TYPE_REMINDER, $recipient, $success, $subject );

        return $success;
    }

    /**
     * Send acceptance notification to admin.
     *
     * @param Offerte_Model $offerte Offerte instance.
     * @return bool
     */
    public function send_accepted_notification( Offerte_Model $offerte ) {
        $settings  = Offerte_Settings::get_settings();
        $recipient = $settings['offerte_sender_email'] ?: get_option( 'admin_email' );
        $customer  = $offerte->get_customer_data();

        $subject = sprintf(
            __( 'Offerte %s geaccepteerd door %s', 'bossier-calculator' ),
            $offerte->get_quote_number(),
            $customer['naam']
        );

        $body = sprintf(
            __( "Offerte %s is zojuist geaccepteerd.\n\nKlant: %s (%s)\nBedrijf: %s\nBedrag: € %s\nOndertekend op: %s\nIP: %s", 'bossier-calculator' ),
            $offerte->get_quote_number(),
            $customer['naam'],
            $customer['email'],
            $customer['bedrijf'],
            number_format( $offerte->get_total(), 2, ',', '.' ),
            $offerte->get_signed_at(),
            $offerte->get_signed_ip()
        );

        $order_id = $offerte->get_wc_order_id();
        if ( $order_id ) {
            $body .= "\n\n" . sprintf(
                __( 'WooCommerce order #%d is aangemaakt.', 'bossier-calculator' ),
                $order_id
            );
        }

        $headers   = $this->get_email_headers( $settings );
        $html_body = $this->wrap_html( $body, $subject );
        $success   = wp_mail( $recipient, $subject, $html_body, $headers );

        $this->log_email( $offerte, self::TYPE_ACCEPTED, $recipient, $success, $subject );

        return $success;
    }

    /**
     * Replace template placeholders.
     *
     * @param string        $template Template with placeholders.
     * @param Offerte_Model $offerte  Offerte instance.
     * @return string
     */
    private function replace_placeholders( $template, Offerte_Model $offerte ) {
        $settings = Offerte_Settings::get_settings();
        $customer = $offerte->get_customer_data();

        $replacements = array(
            '{customer_name}' => $customer['naam'],
            '{company_name}'  => $settings['offerte_company_name'] ?: get_bloginfo( 'name' ),
            '{quote_number}'  => $offerte->get_quote_number(),
            '{quote_url}'     => $offerte->get_public_url(),
            '{valid_until}'   => date_i18n( 'd F Y', strtotime( $offerte->get_valid_until() ) ),
            '{total}'         => '€ ' . number_format( $offerte->get_total(), 2, ',', '.' ),
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /**
     * Get email headers.
     *
     * @param array $settings Plugin settings.
     * @return array
     */
    private function get_email_headers( $settings ) {
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $from_name  = $settings['offerte_sender_name'] ?: get_bloginfo( 'name' );
        $from_email = $settings['offerte_sender_email'] ?: get_option( 'admin_email' );

        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

        return $headers;
    }

    /**
     * Wrap plain text body in a styled HTML email template.
     *
     * @param string $body    Email body text.
     * @param string $subject Subject line.
     * @return string HTML email.
     */
    private function wrap_html( $body, $subject ) {
        $settings = Offerte_Settings::get_settings();
        $company  = $settings['offerte_company_name'] ?: get_bloginfo( 'name' );

        $body_html = nl2br( esc_html( $body ) );

        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . esc_html( $subject ) . '</title></head>
<body style="margin:0;padding:0;background-color:#f7f7f7;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7f7;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;">
<tr><td style="background-color:#2c3e50;padding:25px 30px;color:#fff;font-size:20px;font-weight:bold;">
' . esc_html( $company ) . '
</td></tr>
<tr><td style="padding:30px;font-size:14px;line-height:1.6;color:#333;">
' . $body_html . '
</td></tr>
<tr><td style="padding:20px 30px;background-color:#f9f9f9;font-size:12px;color:#999;text-align:center;">
' . esc_html( $company ) . '
</td></tr>
</table>
</td></tr>
</table>
</body></html>';
    }

    /**
     * Log an email event.
     *
     * @param Offerte_Model $offerte   Offerte instance.
     * @param string        $type      Email type.
     * @param string        $recipient Recipient email.
     * @param bool          $success   Whether send was successful.
     * @param string        $subject   Email subject.
     */
    private function log_email( Offerte_Model $offerte, $type, $recipient, $success, $subject ) {
        $offerte->add_email_log_entry( array(
            'timestamp' => current_time( 'mysql' ),
            'type'      => $type,
            'recipient' => $recipient,
            'success'   => $success,
            'subject'   => $subject,
        ) );
    }
}
