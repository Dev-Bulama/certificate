<?php
/**
 * Email automation system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Email {

    /**
     * Send certificate email to student.
     */
    public static function send_certificate( $certificate_id ) {
        $cert = SSCV_Helpers::get_certificate( $certificate_id );
        if ( ! $cert ) {
            return false;
        }

        $settings = SSCV_Helpers::get_settings();

        // Build email data
        $placeholders = array(
            '{{student_name}}'    => $cert->full_name,
            '{{course_title}}'    => $cert->course_title ?? '',
            '{{certificate_id}}'  => $cert->certificate_id,
            '{{date_issued}}'     => SSCV_Helpers::format_date( $cert->date_issued ?: current_time( 'mysql' ) ),
            '{{grade}}'           => $cert->grade,
            '{{institution_name}}' => $settings['institution_name'],
            '{{verification_url}}' => SSCV_Helpers::get_verification_url( $cert->certificate_id ),
        );

        $subject = str_replace(
            array_keys( $placeholders ),
            array_values( $placeholders ),
            $settings['email_subject'] ?: 'Your Certificate is Ready'
        );

        $body = str_replace(
            array_keys( $placeholders ),
            array_values( $placeholders ),
            $settings['email_body'] ?: self::get_default_body()
        );

        // Build HTML email with the certificate preview embedded
        $certificate_preview_html = '';
        $rendered = SSCV_Certificate_Engine::render( $certificate_id );
        if ( $rendered ) {
            $certificate_preview_html = '<div style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;max-width:100%;">'
                . '<style>' . $rendered['css'] . '</style>'
                . '<div style="transform:scale(0.5);transform-origin:top left;width:200%;overflow:hidden;">' . $rendered['html'] . '</div>'
                . '</div>';
        }

        $html_body = self::build_html_email( $body, $cert, $settings, $certificate_preview_html );

        // Attachments - use already-generated files from approval process
        $attachments = array();
        $upload_dir = wp_upload_dir();

        // Attach the PDF that was generated during approval
        if ( ! empty( $cert->pdf_url ) ) {
            $pdf_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $cert->pdf_url );
            if ( file_exists( $pdf_path ) ) {
                $attachments[] = $pdf_path;
            }
        }

        // Fallback: generate PDF if not already available
        if ( empty( $attachments ) ) {
            $pdf_result = SSCV_PDF_Generator::generate( $certificate_id );
            if ( $pdf_result && ! empty( $pdf_result['file'] ) && file_exists( $pdf_result['file'] ) ) {
                $attachments[] = $pdf_result['file'];
            }
        }

        // Headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings['institution_name'] . ' <' . get_option( 'admin_email' ) . '>',
        );

        $sent = wp_mail( $cert->email, $subject, $html_body, $headers, $attachments );

        // Log email status
        if ( $sent ) {
            self::log_email( $cert->id, $cert->email, $subject, 'sent' );
        } else {
            self::log_email( $cert->id, $cert->email, $subject, 'failed' );
        }

        return $sent;
    }

    /**
     * Send notification email to admin on new application.
     */
    public static function notify_admin_new_application( $cert_data ) {
        $admin_email = get_option( 'admin_email' );
        $settings    = SSCV_Helpers::get_settings();

        $subject = sprintf(
            __( '[%s] New Certificate Application - %s', 'skillscores-cert' ),
            $settings['institution_name'],
            $cert_data['full_name']
        );

        $body = sprintf(
            __( "A new certificate application has been submitted.\n\nStudent: %s\nEmail: %s\nCourse: %s\nStudent ID: %s\n\nPlease review this application in the admin panel.", 'skillscores-cert' ),
            $cert_data['full_name'],
            $cert_data['email'],
            $cert_data['course_title'] ?? '',
            $cert_data['student_id_number'] ?? ''
        );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings['institution_name'] . ' <' . get_option( 'admin_email' ) . '>',
        );

        wp_mail( $admin_email, $subject, nl2br( esc_html( $body ) ), $headers );
    }

    /**
     * Build HTML email template.
     */
    private static function build_html_email( $body_text, $cert, $settings, $certificate_preview = '' ) {
        $theme_color = $settings['theme_color'];
        $accent_color = $settings['accent_color'];
        $institution = esc_html( $settings['institution_name'] );
        $verification_url = SSCV_Helpers::get_verification_url( $cert->certificate_id );

        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">

<!-- Header -->
<tr><td style="background:' . esc_attr( $theme_color ) . ';padding:30px;text-align:center;">
' . ( ! empty( $settings['logo_url'] ) ? '<img src="' . esc_url( $settings['logo_url'] ) . '" alt="' . $institution . '" style="max-height:60px;margin-bottom:10px;" /><br>' : '' ) . '
<h1 style="color:#fff;margin:0;font-size:22px;">' . $institution . '</h1>
</td></tr>

<!-- Body -->
<tr><td style="padding:30px;">
' . nl2br( esc_html( $body_text ) ) . '

<div style="margin:25px 0;padding:20px;background:#f8f9fa;border-radius:6px;border-left:4px solid ' . esc_attr( $accent_color ) . ';">
<p style="margin:0 0 5px;"><strong>' . esc_html__( 'Certificate ID:', 'skillscores-cert' ) . '</strong> ' . esc_html( $cert->certificate_id ) . '</p>
<p style="margin:0 0 5px;"><strong>' . esc_html__( 'Course:', 'skillscores-cert' ) . '</strong> ' . esc_html( $cert->course_title ?? '' ) . '</p>
<p style="margin:0;"><strong>' . esc_html__( 'Grade:', 'skillscores-cert' ) . '</strong> ' . esc_html( $cert->grade ) . '</p>
</div>

<p style="text-align:center;margin:25px 0;">
<a href="' . esc_url( $verification_url ) . '" style="display:inline-block;padding:12px 30px;background:' . esc_attr( $theme_color ) . ';color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'Verify Certificate', 'skillscores-cert' ) . '</a>
</p>
' . $certificate_preview . '
</td></tr>

<!-- Footer -->
<tr><td style="background:#f8f9fa;padding:20px;text-align:center;font-size:12px;color:#888;">
<p>' . $institution . '</p>
<p style="margin-top:5px;">' . esc_html__( 'This is an automated message. Please do not reply.', 'skillscores-cert' ) . '</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>';
    }

    /**
     * Default email body.
     */
    private static function get_default_body() {
        return "Dear {{student_name}},\n\nCongratulations! Your certificate for {{course_title}} has been approved.\n\nCertificate ID: {{certificate_id}}\nDate Issued: {{date_issued}}\n\nYou can verify your certificate at: {{verification_url}}\n\nBest Regards,\n{{institution_name}}";
    }

    /**
     * Log email sending.
     */
    private static function log_email( $cert_db_id, $email, $subject, $status ) {
        global $wpdb;
        // Update certificate record with email status
        $wpdb->update(
            $wpdb->prefix . 'sscv_certificates',
            array( 'admin_notes' => $wpdb->get_var( $wpdb->prepare(
                "SELECT admin_notes FROM {$wpdb->prefix}sscv_certificates WHERE id = %d",
                $cert_db_id
            ) ) . "\n[Email " . $status . " to " . $email . " at " . current_time( 'mysql' ) . "]" ),
            array( 'id' => $cert_db_id ),
            array( '%s' ),
            array( '%d' )
        );
    }
}
