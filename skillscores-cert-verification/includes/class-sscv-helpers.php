<?php
/**
 * Helper utility functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Helpers {

    /**
     * Generate a unique certificate ID.
     */
    public static function generate_certificate_id() {
        $prefix = strtoupper( substr( get_option( 'sscv_institution_name', 'CERT' ), 0, 3 ) );
        $unique  = strtoupper( wp_generate_password( 8, false, false ) );
        $cert_id = $prefix . '-' . date( 'Y' ) . '-' . $unique;

        // Ensure uniqueness
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates WHERE certificate_id = %s",
            $cert_id
        ) );

        if ( $exists ) {
            return self::generate_certificate_id();
        }

        return $cert_id;
    }

    /**
     * Get institution settings.
     */
    public static function get_settings() {
        return array(
            'institution_name' => get_option( 'sscv_institution_name', 'Your Institution' ),
            'logo_url'         => get_option( 'sscv_logo_url', '' ),
            'signature_url'    => get_option( 'sscv_signature_url', '' ),
            'stamp_url'        => get_option( 'sscv_stamp_url', '' ),
            'theme_color'      => get_option( 'sscv_theme_color', '#1a365d' ),
            'accent_color'     => get_option( 'sscv_accent_color', '#c8a951' ),
            'font_family'      => get_option( 'sscv_font_family', 'Georgia, serif' ),
            'qr_size'          => get_option( 'sscv_qr_size', 150 ),
            'email_subject'    => get_option( 'sscv_email_subject', '' ),
            'email_body'       => get_option( 'sscv_email_body', '' ),
            'recaptcha_site_key'   => get_option( 'sscv_recaptcha_site_key', '' ),
            'recaptcha_secret_key' => get_option( 'sscv_recaptcha_secret_key', '' ),
            'grade_field_enabled'       => get_option( 'sscv_grade_field_enabled', '1' ),
            'student_id_field_enabled'  => get_option( 'sscv_student_id_field_enabled', '1' ),
            'passport_field_enabled'    => get_option( 'sscv_passport_field_enabled', '1' ),
            'certificate_template_mode' => get_option( 'sscv_certificate_template_mode', 'html' ),
            'certificate_expiry_enabled' => get_option( 'sscv_certificate_expiry_enabled', '0' ),
            'certificate_expiry_months'  => get_option( 'sscv_certificate_expiry_months', '24' ),
        );
    }

    /**
     * Handle file upload for the plugin.
     */
    public static function handle_upload( $file, $allowed_types = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' ) ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Validate file type
        $file_info = wp_check_filetype( $file['name'] );
        if ( ! in_array( strtolower( $file_info['ext'] ), $allowed_types, true ) ) {
            return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Allowed types: ', 'skillscores-cert' ) . implode( ', ', $allowed_types ) );
        }

        // Validate file size (max 5MB)
        if ( $file['size'] > 5 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', __( 'File size must be less than 5MB.', 'skillscores-cert' ) );
        }

        $upload_overrides = array( 'test_form' => false );
        $uploaded = wp_handle_upload( $file, $upload_overrides );

        if ( isset( $uploaded['error'] ) ) {
            return new WP_Error( 'upload_error', $uploaded['error'] );
        }

        // Add to media library
        $attachment = array(
            'guid'           => $uploaded['url'],
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name( $file['name'] ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $uploaded['file'] );

        if ( ! is_wp_error( $attach_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
        }

        return array(
            'url'           => $uploaded['url'],
            'file'          => $uploaded['file'],
            'attachment_id' => $attach_id,
        );
    }

    /**
     * Format date for display.
     */
    public static function format_date( $date, $format = '' ) {
        if ( empty( $format ) ) {
            $format = get_option( 'date_format', 'F j, Y' );
        }
        return date_i18n( $format, strtotime( $date ) );
    }

    /**
     * Get certificate status badge HTML.
     */
    public static function status_badge( $status ) {
        $badges = array(
            'pending'  => '<span class="sscv-badge sscv-badge-pending">' . esc_html__( 'Pending', 'skillscores-cert' ) . '</span>',
            'approved' => '<span class="sscv-badge sscv-badge-approved">' . esc_html__( 'Approved', 'skillscores-cert' ) . '</span>',
            'rejected' => '<span class="sscv-badge sscv-badge-rejected">' . esc_html__( 'Rejected', 'skillscores-cert' ) . '</span>',
            'revoked'  => '<span class="sscv-badge sscv-badge-revoked">' . esc_html__( 'Revoked', 'skillscores-cert' ) . '</span>',
        );

        return isset( $badges[ $status ] ) ? $badges[ $status ] : $badges['pending'];
    }

    /**
     * Get student by student_id_number.
     */
    public static function get_student_by_id( $student_id_number ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sscv_students WHERE student_id = %s",
            $student_id_number
        ) );
    }

    /**
     * Get certificate by certificate_id.
     */
    public static function get_certificate( $certificate_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT c.*, co.course_title, co.course_code
             FROM {$wpdb->prefix}sscv_certificates c
             LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
             WHERE c.certificate_id = %s",
            $certificate_id
        ) );
    }

    /**
     * Check for duplicate certificate application.
     */
    public static function has_duplicate_certificate( $student_id, $course_id ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates
             WHERE student_id = %d AND course_id = %d AND status IN ('pending','approved')",
            $student_id,
            $course_id
        ) );
    }

    /**
     * Sanitize and validate email.
     */
    public static function validate_email( $email ) {
        $email = sanitize_email( $email );
        return is_email( $email ) ? $email : false;
    }

    /**
     * Get verification URL for a certificate.
     */
    public static function get_verification_url( $certificate_id ) {
        return add_query_arg( 'cert_id', urlencode( $certificate_id ), home_url( '/verify/' ) );
    }

    /**
     * Generate a unique student ID.
     */
    public static function generate_student_id() {
        $prefix = 'STU';
        $unique = strtoupper( wp_generate_password( 6, false, false ) );
        $student_id = $prefix . '-' . date( 'Y' ) . '-' . $unique;

        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_students WHERE student_id = %s",
            $student_id
        ) );

        if ( $exists ) {
            return self::generate_student_id();
        }

        return $student_id;
    }

    /**
     * Check if a template is image-based.
     */
    public static function is_image_template( $template_id ) {
        return get_option( 'sscv_template_type_' . $template_id ) === 'image';
    }

    /**
     * Get all courses for dropdown.
     */
    public static function get_courses_dropdown() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, course_title, course_code FROM {$wpdb->prefix}sscv_courses WHERE status = 'active' ORDER BY course_title ASC"
        );
    }

    /**
     * Get all templates for dropdown.
     */
    public static function get_templates_dropdown() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, template_name FROM {$wpdb->prefix}sscv_certificate_templates ORDER BY template_name ASC"
        );
    }

    /**
     * Get project categories.
     */
    public static function get_project_categories() {
        $categories = get_option( 'sscv_project_categories', "Web Development\nMobile App\nData Science\nCybersecurity\nAI/ML\nDesign\nOther" );
        return array_filter( array_map( 'trim', explode( "\n", $categories ) ) );
    }
}
