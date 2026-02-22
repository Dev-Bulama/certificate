<?php
/**
 * Certificate rendering engine.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Certificate_Engine {

    /**
     * Render a certificate from template with data.
     */
    public static function render( $certificate_id ) {
        global $wpdb;

        $cert = SSCV_Helpers::get_certificate( $certificate_id );
        if ( ! $cert ) {
            return false;
        }

        // Get template
        $template_id = $cert->template_id;
        if ( ! $template_id ) {
            // Fallback to course template, then default
            $course_template = $wpdb->get_var( $wpdb->prepare(
                "SELECT template_id FROM {$wpdb->prefix}sscv_courses WHERE id = %d",
                $cert->course_id
            ) );
            $template_id = $course_template ?: $wpdb->get_var(
                "SELECT id FROM {$wpdb->prefix}sscv_certificate_templates WHERE is_default = 1 LIMIT 1"
            );
        }

        $template = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sscv_certificate_templates WHERE id = %d",
            $template_id
        ) );

        if ( ! $template ) {
            return false;
        }

        $settings = SSCV_Helpers::get_settings();

        // Build placeholder data
        $placeholders = self::build_placeholders( $cert, $settings );

        // Replace placeholders in HTML
        $html = $template->html_content;
        $css  = $template->css_content;

        foreach ( $placeholders as $key => $value ) {
            $html = str_replace( '{{' . $key . '}}', $value, $html );
            $css  = str_replace( '{{' . $key . '}}', $value, $css );
        }

        return array(
            'html' => $html,
            'css'  => $css,
            'data' => $placeholders,
        );
    }

    /**
     * Build all placeholder values.
     */
    public static function build_placeholders( $cert, $settings ) {
        // Get QR code
        $qr_url = ! empty( $cert->qr_code_url ) ? $cert->qr_code_url : '';
        $verification_url = SSCV_Helpers::get_verification_url( $cert->certificate_id );

        // Build placeholders
        $placeholders = array(
            'student_name'      => esc_html( $cert->full_name ),
            'course_title'      => esc_html( $cert->course_title ?? '' ),
            'completion_date'   => ! empty( $cert->date_completed ) ? SSCV_Helpers::format_date( $cert->date_completed ) : '',
            'date_issued'       => ! empty( $cert->date_issued ) ? SSCV_Helpers::format_date( $cert->date_issued ) : SSCV_Helpers::format_date( current_time( 'mysql' ) ),
            'student_id'        => esc_html( self::get_student_id_number( $cert->student_id ) ),
            'grade'             => esc_html( $cert->grade ),
            'certificate_id'    => esc_html( $cert->certificate_id ),
            'institution_name'  => esc_html( $settings['institution_name'] ),
            'verification_url'  => esc_url( $verification_url ),
            'theme_color'       => esc_attr( $settings['theme_color'] ),
            'accent_color'      => esc_attr( $settings['accent_color'] ),
            'font_family'       => esc_attr( $settings['font_family'] ),
        );

        // Image placeholders
        if ( ! empty( $cert->passport_url ) ) {
            $placeholders['passport'] = '<img src="' . esc_url( $cert->passport_url ) . '" alt="Student Photo" class="passport-photo" />';
        } else {
            $placeholders['passport'] = '';
        }

        if ( ! empty( $qr_url ) ) {
            $placeholders['qr_code'] = '<img src="' . esc_url( $qr_url ) . '" alt="QR Verification Code" class="qr-code" />';
        } else {
            $placeholders['qr_code'] = '';
        }

        if ( ! empty( $settings['logo_url'] ) ) {
            $placeholders['institution_logo'] = '<img src="' . esc_url( $settings['logo_url'] ) . '" alt="' . esc_attr( $settings['institution_name'] ) . '" />';
        } else {
            $placeholders['institution_logo'] = '';
        }

        if ( ! empty( $settings['signature_url'] ) ) {
            $placeholders['signature'] = '<img src="' . esc_url( $settings['signature_url'] ) . '" alt="Signature" />';
        } else {
            $placeholders['signature'] = '';
        }

        if ( ! empty( $settings['stamp_url'] ) ) {
            $placeholders['institution_stamp'] = '<img src="' . esc_url( $settings['stamp_url'] ) . '" alt="Institution Stamp" />';
        } else {
            $placeholders['institution_stamp'] = '';
        }

        return $placeholders;
    }

    /**
     * Get student ID number from student table ID.
     */
    private static function get_student_id_number( $student_table_id ) {
        global $wpdb;
        $student_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT student_id FROM {$wpdb->prefix}sscv_students WHERE id = %d",
            $student_table_id
        ) );
        return $student_id ?: '';
    }

    /**
     * Generate the full certificate HTML page.
     */
    public static function generate_full_html( $certificate_id ) {
        $rendered = self::render( $certificate_id );
        if ( ! $rendered ) {
            return false;
        }

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate - ' . esc_attr( $rendered['data']['student_name'] ) . '</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
}
' . $rendered['css'] . '
</style>
</head>
<body>
' . $rendered['html'] . '
</body>
</html>';

        return $html;
    }

    /**
     * Save certificate HTML to file.
     */
    public static function save_certificate_file( $certificate_id ) {
        $html = self::generate_full_html( $certificate_id );
        if ( ! $html ) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $cert_dir   = $upload_dir['basedir'] . '/sscv-certificates/';

        if ( ! file_exists( $cert_dir ) ) {
            wp_mkdir_p( $cert_dir );
            // Add index.php for security
            file_put_contents( $cert_dir . 'index.php', '<?php // Silence is golden.' );
        }

        $filename = 'certificate-' . sanitize_file_name( $certificate_id ) . '.html';
        $filepath = $cert_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-certificates/' . $filename;

        file_put_contents( $filepath, $html );

        return array(
            'file' => $filepath,
            'url'  => $fileurl,
        );
    }
}
