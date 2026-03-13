<?php
/**
 * AJAX request handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Ajax_Handler {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Public AJAX (logged-in and non-logged-in users)
        $public_actions = array(
            'sscv_submit_application',
            'sscv_verify_certificate',
            'sscv_submit_project',
            'sscv_load_projects',
            'sscv_search_projects',
            'sscv_student_dashboard_data',
        );

        foreach ( $public_actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( $this, $action ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
        }

        // Admin-only AJAX
        $admin_actions = array(
            'sscv_approve_certificate',
            'sscv_reject_certificate',
            'sscv_revoke_certificate',
            'sscv_approve_project',
            'sscv_reject_project',
            'sscv_save_course',
            'sscv_delete_course',
            'sscv_save_template',
            'sscv_delete_template',
            'sscv_save_settings',
            'sscv_preview_template',
            'sscv_seed_demo_data',
            'sscv_reset_demo_data',
            'sscv_bulk_export_pdf',
            'sscv_preview_certificate',
            'sscv_update_certificate_name',
            'sscv_export_students',
            'sscv_import_students',
            'sscv_save_image_template',
        );

        foreach ( $admin_actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( $this, $action ) );
        }
    }

    /**
     * Submit certificate application.
     */
    public function sscv_submit_application() {
        // Verify nonce
        if ( ! SSCV_Security::verify_nonce() ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'skillscores-cert' ) ) );
        }

        // Rate limiting — allow up to 30 applications per IP per hour to support shared networks
        if ( ! SSCV_Security::check_rate_limit( 'certificate_application', 30, 3600 ) ) {
            wp_send_json_error( array( 'message' => __( 'Too many applications. Please try again later.', 'skillscores-cert' ) ) );
        }

        // Verify reCAPTCHA
        $recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : '';
        if ( ! SSCV_Security::verify_recaptcha( $recaptcha_token ) ) {
            wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed.', 'skillscores-cert' ) ) );
        }

        // Sanitize input
        $fields = array(
            'full_name'     => 'text',
            'email'         => 'email',
            'course_id'     => 'int',
            'date_completed' => 'text',
            'student_id_number' => 'text',
            'grade'         => 'text',
        );

        $data = SSCV_Security::sanitize_input( $_POST, $fields );

        // Validate required fields
        $student_id_required = get_option( 'sscv_student_id_field_enabled', '1' ) === '1';
        if ( empty( $data['full_name'] ) || empty( $data['email'] ) || empty( $data['course_id'] ) || ( $student_id_required && empty( $data['student_id_number'] ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'skillscores-cert' ) ) );
        }

        // Auto-generate student ID if field is disabled
        if ( ! $student_id_required && empty( $data['student_id_number'] ) ) {
            $data['student_id_number'] = 'AUTO-' . strtoupper( wp_generate_password( 8, false, false ) );
        }

        if ( ! SSCV_Helpers::validate_email( $data['email'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        // Handle passport upload
        $passport_url = '';
        if ( ! empty( $_FILES['passport'] ) && $_FILES['passport']['error'] === UPLOAD_ERR_OK ) {
            $file_errors = SSCV_Security::validate_file( $_FILES['passport'], 'passport' );
            if ( ! empty( $file_errors ) ) {
                wp_send_json_error( array( 'message' => implode( ' ', $file_errors ) ) );
            }

            $uploaded = SSCV_Helpers::handle_upload( $_FILES['passport'], array( 'jpg', 'jpeg', 'png' ) );
            if ( is_wp_error( $uploaded ) ) {
                wp_send_json_error( array( 'message' => $uploaded->get_error_message() ) );
            }
            $passport_url = $uploaded['url'];
        }

        // Create or find student record
        $student = SSCV_Helpers::get_student_by_id( $data['student_id_number'] );

        if ( ! $student ) {
            $wpdb->insert(
                $wpdb->prefix . 'sscv_students',
                array(
                    'student_id'   => $data['student_id_number'],
                    'full_name'    => $data['full_name'],
                    'email'        => $data['email'],
                    'passport_url' => $passport_url,
                ),
                array( '%s', '%s', '%s', '%s' )
            );
            $student_db_id = $wpdb->insert_id;
        } else {
            $student_db_id = $student->id;
            // Update passport if new one uploaded
            if ( ! empty( $passport_url ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'sscv_students',
                    array( 'passport_url' => $passport_url ),
                    array( 'id' => $student_db_id ),
                    array( '%s' ),
                    array( '%d' )
                );
            } else {
                $passport_url = $student->passport_url;
            }
        }

        // Check for duplicate application
        if ( SSCV_Helpers::has_duplicate_certificate( $student_db_id, $data['course_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'You already have a pending or approved certificate for this course.', 'skillscores-cert' ) ) );
        }

        // Get course info
        $course = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sscv_courses WHERE id = %d AND status = 'active'",
            $data['course_id']
        ) );

        if ( ! $course ) {
            wp_send_json_error( array( 'message' => __( 'Selected course not found.', 'skillscores-cert' ) ) );
        }

        // Generate certificate ID
        $certificate_id = SSCV_Helpers::generate_certificate_id();

        // Insert certificate application
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'sscv_certificates',
            array(
                'certificate_id' => $certificate_id,
                'student_id'     => $student_db_id,
                'course_id'      => $data['course_id'],
                'template_id'    => $course->template_id,
                'full_name'      => $data['full_name'],
                'email'          => $data['email'],
                'grade'          => $data['grade'],
                'date_completed' => ! empty( $data['date_completed'] ) ? $data['date_completed'] : null,
                'passport_url'   => $passport_url,
                'status'         => 'pending',
            ),
            array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( array( 'message' => __( 'Failed to submit application. Please try again.', 'skillscores-cert' ) ) );
        }

        // Notify admin
        SSCV_Email::notify_admin_new_application( array_merge( $data, array(
            'course_title' => $course->course_title,
        ) ) );

        wp_send_json_success( array(
            'message'        => __( 'Application submitted successfully! You will receive an email once approved.', 'skillscores-cert' ),
            'certificate_id' => $certificate_id,
        ) );
    }

    /**
     * Verify certificate via AJAX.
     */
    public function sscv_verify_certificate() {
        if ( ! SSCV_Security::verify_nonce() ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'skillscores-cert' ) ) );
        }

        $search_type  = sanitize_text_field( $_POST['search_type'] ?? 'certificate_id' );
        $search_value = sanitize_text_field( $_POST['search_value'] ?? '' );

        if ( empty( $search_value ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a search value.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $results = array();

        switch ( $search_type ) {
            case 'certificate_id':
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.*, co.course_title, co.course_code, s.student_id as student_id_number
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
                     WHERE c.certificate_id = %s AND c.status = 'approved'",
                    $search_value
                ) );
                break;

            case 'student_id':
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.*, co.course_title, co.course_code, s.student_id as student_id_number
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
                     WHERE s.student_id = %s AND c.status = 'approved'",
                    $search_value
                ) );
                break;

            case 'student_name':
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.*, co.course_title, co.course_code, s.student_id as student_id_number
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
                     WHERE c.full_name LIKE %s AND c.status = 'approved'",
                    '%' . $wpdb->esc_like( $search_value ) . '%'
                ) );
                break;
        }

        if ( empty( $results ) ) {
            wp_send_json_error( array( 'message' => __( 'No verified certificates found matching your search.', 'skillscores-cert' ) ) );
        }

        $certificates = array();
        $settings = SSCV_Helpers::get_settings();

        foreach ( $results as $cert ) {
            // Check certificate expiry
            $expiry_status = 'valid';
            $expiry_date = '';
            if ( $settings['certificate_expiry_enabled'] === '1' && ! empty( $cert->date_issued ) ) {
                $months = intval( $settings['certificate_expiry_months'] );
                $expiry_timestamp = strtotime( $cert->date_issued . ' + ' . $months . ' months' );
                $expiry_date = SSCV_Helpers::format_date( date( 'Y-m-d', $expiry_timestamp ) );
                if ( time() > $expiry_timestamp ) {
                    $expiry_status = 'expired';
                }
            }

            $certificates[] = array(
                'certificate_id'   => $cert->certificate_id,
                'student_name'     => $cert->full_name,
                'student_id'       => $cert->student_id_number,
                'course_title'     => $cert->course_title,
                'course_code'      => $cert->course_code,
                'grade'            => $cert->grade,
                'date_completed'   => $cert->date_completed ? SSCV_Helpers::format_date( $cert->date_completed ) : '',
                'date_issued'      => $cert->date_issued ? SSCV_Helpers::format_date( $cert->date_issued ) : '',
                'expiry_date'      => $expiry_date,
                'expiry_status'    => $expiry_status,
                'passport_url'     => $cert->passport_url,
                'certificate_url'  => $cert->certificate_url,
                'pdf_url'          => $cert->pdf_url,
                'qr_code_url'      => $cert->qr_code_url,
                'status'           => $expiry_status === 'expired' ? 'expired' : 'verified',
                'institution_name' => $settings['institution_name'],
                'stamp_url'        => $settings['stamp_url'],
            );
        }

        wp_send_json_success( array(
            'certificates' => $certificates,
            'count'        => count( $certificates ),
        ) );
    }

    /**
     * Submit student project.
     */
    public function sscv_submit_project() {
        if ( ! SSCV_Security::verify_nonce() ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'skillscores-cert' ) ) );
        }

        if ( ! SSCV_Security::check_rate_limit( 'project_submission', 5, 3600 ) ) {
            wp_send_json_error( array( 'message' => __( 'Too many submissions. Please try again later.', 'skillscores-cert' ) ) );
        }

        // Verify reCAPTCHA
        $recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : '';
        if ( ! SSCV_Security::verify_recaptcha( $recaptcha_token ) ) {
            wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed.', 'skillscores-cert' ) ) );
        }

        $fields = array(
            'student_id_number' => 'text',
            'student_name'      => 'text',
            'project_title'     => 'text',
            'description'       => 'textarea',
            'website_url'       => 'url',
            'github_url'        => 'url',
            'social_url'        => 'url',
            'category'          => 'text',
        );

        $data = SSCV_Security::sanitize_input( $_POST, $fields );

        if ( empty( $data['student_id_number'] ) || empty( $data['student_name'] ) || empty( $data['project_title'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        // Verify student exists
        $student = SSCV_Helpers::get_student_by_id( $data['student_id_number'] );
        if ( ! $student ) {
            wp_send_json_error( array( 'message' => __( 'Student ID not found. Please apply for a certificate first.', 'skillscores-cert' ) ) );
        }

        // Handle image upload
        $image_url = '';
        if ( ! empty( $_FILES['project_image'] ) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK ) {
            $file_errors = SSCV_Security::validate_file( $_FILES['project_image'], 'image' );
            if ( ! empty( $file_errors ) ) {
                wp_send_json_error( array( 'message' => implode( ' ', $file_errors ) ) );
            }
            $uploaded = SSCV_Helpers::handle_upload( $_FILES['project_image'], array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) );
            if ( ! is_wp_error( $uploaded ) ) {
                $image_url = $uploaded['url'];
            }
        }

        // Handle CV upload
        $cv_url = '';
        if ( ! empty( $_FILES['cv_file'] ) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK ) {
            $file_errors = SSCV_Security::validate_file( $_FILES['cv_file'], 'cv' );
            if ( ! empty( $file_errors ) ) {
                wp_send_json_error( array( 'message' => implode( ' ', $file_errors ) ) );
            }
            $uploaded = SSCV_Helpers::handle_upload( $_FILES['cv_file'], array( 'pdf', 'doc', 'docx' ) );
            if ( ! is_wp_error( $uploaded ) ) {
                $cv_url = $uploaded['url'];
            }
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'sscv_projects',
            array(
                'student_id'        => $student->id,
                'student_name'      => $data['student_name'],
                'student_id_number' => $data['student_id_number'],
                'project_title'     => $data['project_title'],
                'description'       => $data['description'],
                'project_image_url' => $image_url,
                'website_url'       => $data['website_url'],
                'github_url'        => $data['github_url'],
                'social_url'        => $data['social_url'],
                'cv_url'            => $cv_url,
                'category'          => $data['category'],
                'status'            => 'pending',
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( array( 'message' => __( 'Failed to submit project. Please try again.', 'skillscores-cert' ) ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Project submitted successfully! It will be publicly visible after admin approval.', 'skillscores-cert' ),
        ) );
    }

    /**
     * Load projects with AJAX pagination.
     */
    public function sscv_load_projects() {
        global $wpdb;

        $page     = max( 1, intval( $_POST['page'] ?? 1 ) );
        $per_page = max( 1, min( 50, intval( $_POST['per_page'] ?? 10 ) ) );
        $search   = sanitize_text_field( $_POST['search'] ?? '' );
        $category = sanitize_text_field( $_POST['category'] ?? '' );
        $sort_by  = sanitize_text_field( $_POST['sort_by'] ?? 'created_at' );
        $sort_dir = strtoupper( sanitize_text_field( $_POST['sort_dir'] ?? 'DESC' ) );

        $allowed_sort = array( 'student_name', 'project_title', 'category', 'created_at' );
        if ( ! in_array( $sort_by, $allowed_sort, true ) ) {
            $sort_by = 'created_at';
        }
        $sort_dir = in_array( $sort_dir, array( 'ASC', 'DESC' ), true ) ? $sort_dir : 'DESC';

        $where = "WHERE status = 'approved'";
        $params = array();

        if ( ! empty( $search ) ) {
            $where .= " AND (student_name LIKE %s OR project_title LIKE %s OR student_id_number LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty( $category ) ) {
            $where .= " AND category = %s";
            $params[] = $category;
        }

        // Count total
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects {$where}";
        $total = $wpdb->get_var( empty( $params ) ? $count_sql : $wpdb->prepare( $count_sql, $params ) );

        // Get results
        $offset = ( $page - 1 ) * $per_page;
        $sql = "SELECT * FROM {$wpdb->prefix}sscv_projects {$where} ORDER BY {$sort_by} {$sort_dir} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $projects = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        $output = array();
        foreach ( $projects as $project ) {
            $output[] = array(
                'id'                => $project->id,
                'student_id'        => $project->student_id_number,
                'student_name'      => $project->student_name,
                'project_title'     => $project->project_title,
                'description'       => wp_trim_words( $project->description, 20 ),
                'project_image_url' => $project->project_image_url,
                'website_url'       => $project->website_url,
                'github_url'        => $project->github_url,
                'social_url'        => $project->social_url,
                'cv_url'            => $project->cv_url,
                'category'          => $project->category,
            );
        }

        wp_send_json_success( array(
            'projects'    => $output,
            'total'       => (int) $total,
            'pages'       => ceil( $total / $per_page ),
            'current_page' => $page,
        ) );
    }

    /**
     * Search projects (alias for load with search).
     */
    public function sscv_search_projects() {
        $this->sscv_load_projects();
    }

    /**
     * Student dashboard data.
     */
    public function sscv_student_dashboard_data() {
        if ( ! SSCV_Security::verify_nonce() ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'skillscores-cert' ) ) );
        }

        $student_id = sanitize_text_field( $_POST['student_id'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );

        if ( empty( $student_id ) || empty( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide Student ID and Email.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sscv_students WHERE student_id = %s AND email = %s",
            $student_id,
            $email
        ) );

        if ( ! $student ) {
            wp_send_json_error( array( 'message' => __( 'No student record found with these credentials.', 'skillscores-cert' ) ) );
        }

        // Get certificates
        $certificates = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.*, co.course_title
             FROM {$wpdb->prefix}sscv_certificates c
             LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
             WHERE c.student_id = %d
             ORDER BY c.created_at DESC",
            $student->id
        ) );

        // Get projects
        $projects = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sscv_projects WHERE student_id = %d ORDER BY created_at DESC",
            $student->id
        ) );

        $cert_data = array();
        foreach ( $certificates as $cert ) {
            $cert_data[] = array(
                'certificate_id' => $cert->certificate_id,
                'course_title'   => $cert->course_title,
                'grade'          => $cert->grade,
                'status'         => $cert->status,
                'date_issued'    => $cert->date_issued ? SSCV_Helpers::format_date( $cert->date_issued ) : '',
                'certificate_url' => $cert->certificate_url,
                'pdf_url'        => $cert->pdf_url,
            );
        }

        $proj_data = array();
        foreach ( $projects as $proj ) {
            $proj_data[] = array(
                'project_title' => $proj->project_title,
                'category'      => $proj->category,
                'status'        => $proj->status,
                'website_url'   => $proj->website_url,
            );
        }

        wp_send_json_success( array(
            'student' => array(
                'full_name'    => $student->full_name,
                'student_id'   => $student->student_id,
                'email'        => $student->email,
                'passport_url' => $student->passport_url,
            ),
            'certificates' => $cert_data,
            'projects'     => $proj_data,
        ) );
    }

    // ========================
    // ADMIN AJAX HANDLERS
    // ========================

    /**
     * Approve certificate.
     */
    public function sscv_approve_certificate() {
        // Wrap ENTIRE handler in try/catch to prevent any 500 errors
        try {
            if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
                wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
                return;
            }

            $cert_db_id = intval( $_POST['cert_id'] ?? 0 );
            if ( ! $cert_db_id ) {
                wp_send_json_error( array( 'message' => __( 'Invalid certificate.', 'skillscores-cert' ) ) );
                return;
            }

            global $wpdb;

            $cert = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sscv_certificates WHERE id = %d",
                $cert_db_id
            ) );

            if ( ! $cert ) {
                wp_send_json_error( array( 'message' => __( 'Certificate not found.', 'skillscores-cert' ) ) );
                return;
            }

            $qr_url = '';
            $cert_url = '';
            $pdf_url = '';
            $warnings = array();

            // Generate QR Code
            try {
                $qr_url = SSCV_QR_Generator::generate( $cert->certificate_id );
            } catch ( \Throwable $e ) {
                $warnings[] = 'QR failed: ' . $e->getMessage();
                error_log( 'SSCV Approve - QR error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            }

            // Update QR code URL in certificate
            if ( $qr_url ) {
                $wpdb->update(
                    $wpdb->prefix . 'sscv_certificates',
                    array( 'qr_code_url' => $qr_url ),
                    array( 'id' => $cert_db_id ),
                    array( '%s' ),
                    array( '%d' )
                );
            }

            // Save certificate HTML file
            try {
                $cert_file = SSCV_Certificate_Engine::save_certificate_file( $cert->certificate_id );
                $cert_url  = $cert_file ? $cert_file['url'] : '';
            } catch ( \Throwable $e ) {
                $warnings[] = 'HTML failed: ' . $e->getMessage();
                error_log( 'SSCV Approve - HTML error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            }

            // Generate PDF
            try {
                $pdf_result = SSCV_PDF_Generator::generate( $cert->certificate_id );
                $pdf_url    = $pdf_result ? $pdf_result['url'] : '';
            } catch ( \Throwable $e ) {
                $warnings[] = 'PDF failed: ' . $e->getMessage();
                error_log( 'SSCV Approve - PDF error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            }

            // Update certificate status and URLs
            $wpdb->update(
                $wpdb->prefix . 'sscv_certificates',
                array(
                    'status'          => 'approved',
                    'date_issued'     => current_time( 'Y-m-d' ),
                    'certificate_url' => $cert_url,
                    'pdf_url'         => $pdf_url,
                    'qr_code_url'     => $qr_url,
                ),
                array( 'id' => $cert_db_id ),
                array( '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );

            // Send email to student only if admin opted in
            $send_email = isset( $_POST['send_email'] ) ? intval( $_POST['send_email'] ) : 1;
            $email_msg = '';
            if ( $send_email ) {
                try {
                    SSCV_Email::send_certificate( $cert->certificate_id );
                    $email_msg = ' ' . __( 'Email sent to student.', 'skillscores-cert' );
                } catch ( \Throwable $e ) {
                    $email_msg = ' ' . __( 'Email sending failed.', 'skillscores-cert' );
                    error_log( 'SSCV Approve - Email error: ' . $e->getMessage() );
                }
            }

            $message = __( 'Certificate approved!', 'skillscores-cert' ) . $email_msg;
            if ( ! empty( $warnings ) ) {
                $message .= ' (Warnings: ' . implode( '; ', $warnings ) . ')';
            }

            wp_send_json_success( array( 'message' => $message ) );

        } catch ( \Throwable $e ) {
            error_log( 'SSCV Approve - Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() );
            wp_send_json_error( array( 'message' => 'Approval error: ' . $e->getMessage() ) );
        }
    }

    /**
     * Reject certificate.
     */
    public function sscv_reject_certificate() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $cert_db_id = intval( $_POST['cert_id'] ?? 0 );
        $notes      = sanitize_textarea_field( $_POST['notes'] ?? '' );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'sscv_certificates',
            array( 'status' => 'rejected', 'admin_notes' => $notes ),
            array( 'id' => $cert_db_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Certificate application rejected.', 'skillscores-cert' ) ) );
    }

    /**
     * Revoke certificate.
     */
    public function sscv_revoke_certificate() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $cert_db_id = intval( $_POST['cert_id'] ?? 0 );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'sscv_certificates',
            array( 'status' => 'revoked' ),
            array( 'id' => $cert_db_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Certificate revoked.', 'skillscores-cert' ) ) );
    }

    /**
     * Approve project.
     */
    public function sscv_approve_project() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $project_id = intval( $_POST['project_id'] ?? 0 );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'sscv_projects',
            array( 'status' => 'approved' ),
            array( 'id' => $project_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Project approved.', 'skillscores-cert' ) ) );
    }

    /**
     * Reject project.
     */
    public function sscv_reject_project() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $project_id = intval( $_POST['project_id'] ?? 0 );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'sscv_projects',
            array( 'status' => 'rejected' ),
            array( 'id' => $project_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Project rejected.', 'skillscores-cert' ) ) );
    }

    /**
     * Save course.
     */
    public function sscv_save_course() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $fields = array(
            'course_title' => 'text',
            'course_code'  => 'text',
            'description'  => 'textarea',
            'template_id'  => 'int',
            'status'       => 'text',
        );

        $data = SSCV_Security::sanitize_input( $_POST, $fields );
        $course_id = intval( $_POST['course_id'] ?? 0 );

        if ( empty( $data['course_title'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Course title is required.', 'skillscores-cert' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sscv_courses';

        $save_data = array(
            'course_title' => $data['course_title'],
            'course_code'  => $data['course_code'],
            'description'  => $data['description'],
            'template_id'  => $data['template_id'] ?: null,
            'status'       => in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active',
        );

        if ( $course_id ) {
            $wpdb->update( $table, $save_data, array( 'id' => $course_id ) );
            $message = __( 'Course updated successfully.', 'skillscores-cert' );
        } else {
            $wpdb->insert( $table, $save_data );
            $course_id = $wpdb->insert_id;
            $message = __( 'Course created successfully.', 'skillscores-cert' );
        }

        wp_send_json_success( array( 'message' => $message, 'course_id' => $course_id ) );
    }

    /**
     * Delete course.
     */
    public function sscv_delete_course() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $course_id = intval( $_POST['course_id'] ?? 0 );

        global $wpdb;

        // Check if course has certificates
        $has_certs = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates WHERE course_id = %d",
            $course_id
        ) );

        if ( $has_certs ) {
            wp_send_json_error( array( 'message' => __( 'Cannot delete course with existing certificates.', 'skillscores-cert' ) ) );
        }

        $wpdb->delete( $wpdb->prefix . 'sscv_courses', array( 'id' => $course_id ), array( '%d' ) );

        wp_send_json_success( array( 'message' => __( 'Course deleted.', 'skillscores-cert' ) ) );
    }

    /**
     * Save certificate template.
     */
    public function sscv_save_template() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $template_id   = intval( $_POST['template_id'] ?? 0 );
        $template_name = sanitize_text_field( $_POST['template_name'] ?? '' );
        // Store raw HTML for templates — admin-only with capability check above.
        // wp_kses_post strips style attributes, classes, and tags needed for custom certificate designs.
        $html_content  = wp_unslash( $_POST['html_content'] ?? '' );
        $css_content   = wp_unslash( $_POST['css_content'] ?? '' );
        $is_default    = intval( $_POST['is_default'] ?? 0 );

        if ( empty( $template_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Template name is required.', 'skillscores-cert' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sscv_certificate_templates';

        // If setting as default, unset others
        if ( $is_default ) {
            $wpdb->update( $table, array( 'is_default' => 0 ), array( 'is_default' => 1 ) );
        }

        $save_data = array(
            'template_name' => $template_name,
            'html_content'  => $html_content,
            'css_content'   => $css_content,
            'is_default'    => $is_default,
        );

        if ( $template_id ) {
            $wpdb->update( $table, $save_data, array( 'id' => $template_id ) );
            $message = __( 'Template updated.', 'skillscores-cert' );
        } else {
            $wpdb->insert( $table, $save_data );
            $template_id = $wpdb->insert_id;
            $message = __( 'Template created.', 'skillscores-cert' );
        }

        wp_send_json_success( array( 'message' => $message, 'template_id' => $template_id ) );
    }

    /**
     * Delete template.
     */
    public function sscv_delete_template() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $template_id = intval( $_POST['template_id'] ?? 0 );

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'sscv_certificate_templates', array( 'id' => $template_id ), array( '%d' ) );

        wp_send_json_success( array( 'message' => __( 'Template deleted.', 'skillscores-cert' ) ) );
    }

    /**
     * Save plugin settings.
     */
    public function sscv_save_settings() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $settings_map = array(
            'institution_name'     => 'sscv_institution_name',
            'logo_url'             => 'sscv_logo_url',
            'signature_url'        => 'sscv_signature_url',
            'stamp_url'            => 'sscv_stamp_url',
            'theme_color'          => 'sscv_theme_color',
            'accent_color'         => 'sscv_accent_color',
            'font_family'          => 'sscv_font_family',
            'qr_size'              => 'sscv_qr_size',
            'email_subject'        => 'sscv_email_subject',
            'email_body'           => 'sscv_email_body',
            'recaptcha_site_key'        => 'sscv_recaptcha_site_key',
            'recaptcha_secret_key'      => 'sscv_recaptcha_secret_key',
            'project_categories'        => 'sscv_project_categories',
            'certificate_template_mode' => 'sscv_certificate_template_mode',
            'certificate_expiry_months' => 'sscv_certificate_expiry_months',
        );

        foreach ( $settings_map as $field => $option ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = $field === 'email_body' || $field === 'project_categories'
                    ? sanitize_textarea_field( $_POST[ $field ] )
                    : sanitize_text_field( $_POST[ $field ] );
                update_option( $option, $value );
            }
        }

        // Handle checkbox fields (unchecked = not sent in POST)
        update_option( 'sscv_student_id_field_enabled', isset( $_POST['enable_student_id_field'] ) ? '1' : '0' );
        update_option( 'sscv_grade_field_enabled', isset( $_POST['enable_grade_field'] ) ? '1' : '0' );
        update_option( 'sscv_passport_field_enabled', isset( $_POST['enable_passport_field'] ) ? '1' : '0' );
        update_option( 'sscv_certificate_expiry_enabled', isset( $_POST['enable_certificate_expiry'] ) ? '1' : '0' );

        // Flush rewrite rules in case verification page settings changed
        flush_rewrite_rules();

        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'skillscores-cert' ) ) );
    }

    /**
     * Preview certificate template.
     */
    public function sscv_preview_template() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $html = wp_unslash( $_POST['html_content'] ?? '' );
        $css  = wp_unslash( $_POST['css_content'] ?? '' );

        $settings = SSCV_Helpers::get_settings();

        // Sample data
        $placeholders = array(
            '{{student_name}}'      => 'John Doe',
            '{{course_title}}'      => 'Advanced Web Development',
            '{{completion_date}}'   => SSCV_Helpers::format_date( current_time( 'mysql' ) ),
            '{{date_issued}}'       => SSCV_Helpers::format_date( current_time( 'mysql' ) ),
            '{{student_id}}'        => 'STU-2024-001',
            '{{grade}}'             => 'A (Distinction)',
            '{{certificate_id}}'    => 'CERT-2024-SAMPLE',
            '{{institution_name}}'  => $settings['institution_name'],
            '{{qr_code}}'           => '<img src="' . SSCV_PLUGIN_URL . 'assets/images/sample-qr.png" alt="QR Code" style="width:100px;height:100px;" />',
            '{{passport}}'          => '<img src="' . SSCV_PLUGIN_URL . 'assets/images/sample-passport.png" alt="Photo" style="width:100px;height:120px;" />',
            '{{institution_logo}}'  => ! empty( $settings['logo_url'] ) ? '<img src="' . esc_url( $settings['logo_url'] ) . '" alt="Logo" />' : '',
            '{{signature}}'         => ! empty( $settings['signature_url'] ) ? '<img src="' . esc_url( $settings['signature_url'] ) . '" alt="Signature" />' : '',
            '{{institution_stamp}}' => ! empty( $settings['stamp_url'] ) ? '<img src="' . esc_url( $settings['stamp_url'] ) . '" alt="Stamp" />' : '',
        );

        foreach ( $placeholders as $key => $value ) {
            $html = str_replace( $key, $value, $html );
        }

        wp_send_json_success( array(
            'html' => $html,
            'css'  => $css,
        ) );
    }

    /**
     * Seed demo data.
     */
    public function sscv_seed_demo_data() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $result = SSCV_Demo_Data::seed();
        $counts = SSCV_Demo_Data::get_counts();

        $summary = sprintf(
            __( 'Added %d students, %d courses, %d certificates, and %d projects.', 'skillscores-cert' ),
            $result['students'],
            $result['courses'],
            $result['certificates'],
            $result['projects']
        );

        wp_send_json_success( array(
            'message' => __( 'Demo data loaded successfully.', 'skillscores-cert' ),
            'summary' => $summary,
            'seeded'  => $result,
            'counts'  => $counts,
        ) );
    }

    /**
     * Reset all plugin data.
     */
    public function sscv_reset_demo_data() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $reset = SSCV_Demo_Data::reset();

        if ( $reset ) {
            wp_send_json_success( array( 'message' => __( 'All data has been reset successfully.', 'skillscores-cert' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Reset failed.', 'skillscores-cert' ) ) );
        }
    }

    /**
     * Preview certificate before approval.
     */
    public function sscv_preview_certificate() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $cert_db_id = intval( $_POST['cert_id'] ?? 0 );
        if ( ! $cert_db_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid certificate.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $cert = $wpdb->get_row( $wpdb->prepare(
            "SELECT c.*, co.course_title, co.course_code, s.student_id as student_id_number
             FROM {$wpdb->prefix}sscv_certificates c
             LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
             LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
             WHERE c.id = %d",
            $cert_db_id
        ) );

        if ( ! $cert ) {
            wp_send_json_error( array( 'message' => __( 'Certificate not found.', 'skillscores-cert' ) ) );
        }

        // Render the certificate preview using the engine
        $rendered = SSCV_Certificate_Engine::render( $cert->certificate_id );

        if ( ! $rendered ) {
            wp_send_json_error( array( 'message' => __( 'Could not render certificate preview. Please check that a template is configured.', 'skillscores-cert' ) ) );
        }

        wp_send_json_success( array(
            'html'      => $rendered['html'],
            'css'       => $rendered['css'],
            'full_name' => $cert->full_name,
            'cert_id'   => $cert->id,
        ) );
    }

    /**
     * Update certificate name (admin correction before approval).
     */
    public function sscv_update_certificate_name() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $cert_db_id = intval( $_POST['cert_id'] ?? 0 );
        $new_name   = sanitize_text_field( $_POST['full_name'] ?? '' );

        if ( ! $cert_db_id || empty( $new_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Certificate ID and name are required.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        // Update the name in the certificates table
        $updated = $wpdb->update(
            $wpdb->prefix . 'sscv_certificates',
            array( 'full_name' => $new_name ),
            array( 'id' => $cert_db_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( $updated === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to update name.', 'skillscores-cert' ) ) );
        }

        wp_send_json_success( array(
            'message'   => __( 'Name updated successfully.', 'skillscores-cert' ),
            'full_name' => $new_name,
        ) );
    }

    /**
     * Bulk export certificates as PDF with filters.
     */
    public function sscv_bulk_export_pdf() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_POST['date_to'] ?? '' );
        $course_id = intval( $_POST['course_id'] ?? 0 );
        $status    = sanitize_text_field( $_POST['export_status'] ?? '' );

        // Default to approved if no status specified
        if ( $status === '' ) {
            $status = 'approved';
        }

        $where  = '1=1';
        $params = array();

        if ( $status !== 'all' ) {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        // Use date_issued for approved certs, created_at as fallback
        if ( ! empty( $date_from ) ) {
            $where .= " AND DATE(COALESCE(date_issued, created_at)) >= %s";
            $params[] = $date_from;
        }

        if ( ! empty( $date_to ) ) {
            $where .= " AND DATE(COALESCE(date_issued, created_at)) <= %s";
            $params[] = $date_to;
        }

        if ( ! empty( $course_id ) ) {
            $where .= " AND course_id = %d";
            $params[] = $course_id;
        }

        $sql = "SELECT certificate_id FROM {$wpdb->prefix}sscv_certificates WHERE {$where} ORDER BY COALESCE(date_issued, created_at) DESC";
        $certificates = $wpdb->get_results( empty( $params ) ? $sql : $wpdb->prepare( $sql, $params ) );

        if ( empty( $certificates ) ) {
            wp_send_json_error( array( 'message' => __( 'No certificates found matching the selected filters.', 'skillscores-cert' ) ) );
        }

        $result = SSCV_PDF_Generator::generate_bulk( $certificates );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to generate bulk PDF.', 'skillscores-cert' ) ) );
        }

        wp_send_json_success( array(
            'message'  => sprintf( __( 'Exported %d certificates.', 'skillscores-cert' ), count( $certificates ) ),
            'pdf_url'  => $result['url'],
            'count'    => count( $certificates ),
        ) );
    }

    /**
     * Export students as CSV.
     */
    public function sscv_export_students() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $students = $wpdb->get_results(
            "SELECT student_id, full_name, email, phone, passport_url, created_at FROM {$wpdb->prefix}sscv_students ORDER BY created_at DESC"
        );

        if ( empty( $students ) ) {
            wp_send_json_error( array( 'message' => __( 'No students to export.', 'skillscores-cert' ) ) );
        }

        $upload_dir = wp_upload_dir();
        $csv_dir    = $upload_dir['basedir'] . '/sscv-certificates/';

        if ( ! file_exists( $csv_dir ) ) {
            wp_mkdir_p( $csv_dir );
        }

        $filename = 'students-export-' . date( 'Y-m-d-His' ) . '.csv';
        $filepath = $csv_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-certificates/' . $filename;

        $fp = fopen( $filepath, 'w' );
        fputcsv( $fp, array( 'Student ID', 'Full Name', 'Email', 'Phone', 'Passport URL', 'Registered Date' ) );

        foreach ( $students as $stu ) {
            fputcsv( $fp, array(
                $stu->student_id,
                $stu->full_name,
                $stu->email,
                $stu->phone,
                $stu->passport_url,
                $stu->created_at,
            ) );
        }

        fclose( $fp );

        wp_send_json_success( array(
            'message'  => sprintf( __( 'Exported %d students.', 'skillscores-cert' ), count( $students ) ),
            'csv_url'  => $fileurl,
            'count'    => count( $students ),
        ) );
    }

    /**
     * Import students from CSV.
     */
    public function sscv_import_students() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( array( 'message' => __( 'Please upload a valid CSV file.', 'skillscores-cert' ) ) );
        }

        $file = $_FILES['csv_file'];
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( $ext !== 'csv' ) {
            wp_send_json_error( array( 'message' => __( 'Only CSV files are allowed.', 'skillscores-cert' ) ) );
        }

        global $wpdb;

        $fp = fopen( $file['tmp_name'], 'r' );
        $header = fgetcsv( $fp );

        if ( ! $header ) {
            fclose( $fp );
            wp_send_json_error( array( 'message' => __( 'Empty or invalid CSV file.', 'skillscores-cert' ) ) );
        }

        // Normalize header names
        $header = array_map( function( $h ) {
            return strtolower( trim( str_replace( array( ' ', '-' ), '_', $h ) ) );
        }, $header );

        $name_col  = array_search( 'full_name', $header );
        $email_col = array_search( 'email', $header );
        $id_col    = array_search( 'student_id', $header );
        $phone_col = array_search( 'phone', $header );

        if ( $name_col === false || $email_col === false ) {
            fclose( $fp );
            wp_send_json_error( array( 'message' => __( 'CSV must have at least "Full Name" and "Email" columns.', 'skillscores-cert' ) ) );
        }

        $imported = 0;
        $skipped  = 0;

        while ( ( $row = fgetcsv( $fp ) ) !== false ) {
            $full_name = sanitize_text_field( $row[ $name_col ] ?? '' );
            $email     = sanitize_email( $row[ $email_col ] ?? '' );

            if ( empty( $full_name ) || empty( $email ) ) {
                $skipped++;
                continue;
            }

            // Use provided student ID or auto-generate
            $student_id = '';
            if ( $id_col !== false && ! empty( $row[ $id_col ] ) ) {
                $student_id = sanitize_text_field( $row[ $id_col ] );
            }

            if ( empty( $student_id ) ) {
                $student_id = SSCV_Helpers::generate_student_id();
            }

            // Check if student already exists
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_students WHERE student_id = %s OR email = %s",
                $student_id,
                $email
            ) );

            if ( $exists ) {
                $skipped++;
                continue;
            }

            $phone = ( $phone_col !== false && isset( $row[ $phone_col ] ) ) ? sanitize_text_field( $row[ $phone_col ] ) : '';

            $wpdb->insert(
                $wpdb->prefix . 'sscv_students',
                array(
                    'student_id' => $student_id,
                    'full_name'  => $full_name,
                    'email'      => $email,
                    'phone'      => $phone,
                ),
                array( '%s', '%s', '%s', '%s' )
            );

            $imported++;
        }

        fclose( $fp );

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Import complete. %d students imported, %d skipped (duplicates or invalid).', 'skillscores-cert' ),
                $imported,
                $skipped
            ),
            'imported' => $imported,
            'skipped'  => $skipped,
        ) );
    }

    /**
     * Save image-based certificate template.
     */
    public function sscv_save_image_template() {
        if ( ! SSCV_Security::verify_nonce( 'nonce', 'sscv_admin_nonce' ) || ! SSCV_Security::is_admin() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'skillscores-cert' ) ) );
        }

        $template_id    = intval( $_POST['template_id'] ?? 0 );
        $template_name  = sanitize_text_field( $_POST['template_name'] ?? '' );
        $image_url      = esc_url_raw( $_POST['image_url'] ?? '' );
        $field_positions = wp_unslash( $_POST['field_positions'] ?? '{}' );
        $is_default     = intval( $_POST['is_default'] ?? 0 );

        if ( empty( $template_name ) || empty( $image_url ) ) {
            wp_send_json_error( array( 'message' => __( 'Template name and image are required.', 'skillscores-cert' ) ) );
        }

        // Validate JSON
        $positions = json_decode( $field_positions, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( array( 'message' => __( 'Invalid field positions data.', 'skillscores-cert' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sscv_certificate_templates';

        if ( $is_default ) {
            $wpdb->update( $table, array( 'is_default' => 0 ), array( 'is_default' => 1 ) );
        }

        // Store image template: image_url in html_content, positions JSON in css_content, template_type marker
        $save_data = array(
            'template_name' => $template_name,
            'html_content'  => $image_url,
            'css_content'   => wp_json_encode( $positions ),
            'is_default'    => $is_default,
        );

        if ( $template_id ) {
            $wpdb->update( $table, $save_data, array( 'id' => $template_id ) );
            $message = __( 'Image template updated.', 'skillscores-cert' );
        } else {
            $wpdb->insert( $table, $save_data );
            $template_id = $wpdb->insert_id;
            $message = __( 'Image template created.', 'skillscores-cert' );
        }

        // Mark this template as image type
        update_option( 'sscv_template_type_' . $template_id, 'image' );

        wp_send_json_success( array( 'message' => $message, 'template_id' => $template_id ) );
    }
}
