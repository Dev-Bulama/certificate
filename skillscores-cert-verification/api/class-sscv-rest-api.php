<?php
/**
 * REST API endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Rest_API {

    const NAMESPACE = 'sscv/v1';

    /**
     * Register all routes.
     */
    public function register_routes() {
        // Certificate verification
        register_rest_route( self::NAMESPACE, '/verify/(?P<cert_id>[a-zA-Z0-9\-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'verify_certificate' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'cert_id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Search certificates
        register_rest_route( self::NAMESPACE, '/certificates', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_certificates' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'type' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => 'certificate_id',
                ),
            ),
        ) );

        // Project listings
        register_rest_route( self::NAMESPACE, '/projects', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_projects' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
                'per_page' => array( 'default' => 10, 'sanitize_callback' => 'absint' ),
                'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
                'category' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                'sort_by'  => array( 'default' => 'created_at', 'sanitize_callback' => 'sanitize_text_field' ),
                'sort_dir' => array( 'default' => 'DESC', 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        // Student data (requires authentication)
        register_rest_route( self::NAMESPACE, '/student/(?P<student_id>[a-zA-Z0-9\-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_student' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'student_id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Courses list
        register_rest_route( self::NAMESPACE, '/courses', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_courses' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Admin permission check.
     */
    public function admin_permission_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Verify a single certificate.
     */
    public function verify_certificate( $request ) {
        $cert_id = $request->get_param( 'cert_id' );
        $cert    = SSCV_Helpers::get_certificate( $cert_id );

        if ( ! $cert || $cert->status !== 'approved' ) {
            return new WP_REST_Response( array(
                'verified' => false,
                'message'  => __( 'Certificate not found or not verified.', 'skillscores-cert' ),
            ), 404 );
        }

        $settings = SSCV_Helpers::get_settings();

        return new WP_REST_Response( array(
            'verified'    => true,
            'certificate' => array(
                'certificate_id'   => $cert->certificate_id,
                'student_name'     => $cert->full_name,
                'course_title'     => $cert->course_title,
                'grade'            => $cert->grade,
                'date_completed'   => $cert->date_completed,
                'date_issued'      => $cert->date_issued,
                'institution_name' => $settings['institution_name'],
                'qr_code_url'      => $cert->qr_code_url,
                'certificate_url'  => $cert->certificate_url,
            ),
        ), 200 );
    }

    /**
     * Search certificates.
     */
    public function search_certificates( $request ) {
        $search = $request->get_param( 'search' );
        $type   = $request->get_param( 'type' );

        if ( empty( $search ) ) {
            return new WP_REST_Response( array( 'results' => array() ), 200 );
        }

        global $wpdb;

        switch ( $type ) {
            case 'student_id':
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.certificate_id, c.full_name, c.grade, c.date_issued, co.course_title
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
                     WHERE s.student_id = %s AND c.status = 'approved'",
                    $search
                ) );
                break;

            case 'student_name':
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.certificate_id, c.full_name, c.grade, c.date_issued, co.course_title
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     WHERE c.full_name LIKE %s AND c.status = 'approved'",
                    '%' . $wpdb->esc_like( $search ) . '%'
                ) );
                break;

            default: // certificate_id
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.certificate_id, c.full_name, c.grade, c.date_issued, co.course_title
                     FROM {$wpdb->prefix}sscv_certificates c
                     LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                     WHERE c.certificate_id = %s AND c.status = 'approved'",
                    $search
                ) );
                break;
        }

        return new WP_REST_Response( array( 'results' => $results ?: array() ), 200 );
    }

    /**
     * Get approved projects.
     */
    public function get_projects( $request ) {
        global $wpdb;

        $page     = max( 1, $request->get_param( 'page' ) );
        $per_page = min( 50, max( 1, $request->get_param( 'per_page' ) ) );
        $search   = $request->get_param( 'search' );
        $category = $request->get_param( 'category' );
        $sort_by  = $request->get_param( 'sort_by' );
        $sort_dir = strtoupper( $request->get_param( 'sort_dir' ) );

        $allowed_sort = array( 'student_name', 'project_title', 'category', 'created_at' );
        if ( ! in_array( $sort_by, $allowed_sort, true ) ) $sort_by = 'created_at';
        if ( ! in_array( $sort_dir, array( 'ASC', 'DESC' ), true ) ) $sort_dir = 'DESC';

        $where  = "WHERE status = 'approved'";
        $params = array();

        if ( ! empty( $search ) ) {
            $where .= " AND (student_name LIKE %s OR project_title LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty( $category ) ) {
            $where .= " AND category = %s";
            $params[] = $category;
        }

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects {$where}";
        $total = $wpdb->get_var( empty( $params ) ? $count_sql : $wpdb->prepare( $count_sql, $params ) );

        $offset = ( $page - 1 ) * $per_page;
        $sql = "SELECT id, student_id_number, student_name, project_title, description, project_image_url, website_url, github_url, social_url, cv_url, category
                FROM {$wpdb->prefix}sscv_projects {$where} ORDER BY {$sort_by} {$sort_dir} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $projects = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        return new WP_REST_Response( array(
            'projects'     => $projects,
            'total'        => (int) $total,
            'pages'        => ceil( $total / $per_page ),
            'current_page' => $page,
        ), 200 );
    }

    /**
     * Get student data (admin only).
     */
    public function get_student( $request ) {
        $student_id = $request->get_param( 'student_id' );
        $student    = SSCV_Helpers::get_student_by_id( $student_id );

        if ( ! $student ) {
            return new WP_REST_Response( array( 'message' => __( 'Student not found.', 'skillscores-cert' ) ), 404 );
        }

        global $wpdb;

        $certificates = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.certificate_id, c.status, c.grade, c.date_issued, co.course_title
             FROM {$wpdb->prefix}sscv_certificates c
             LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
             WHERE c.student_id = %d",
            $student->id
        ) );

        return new WP_REST_Response( array(
            'student'      => array(
                'student_id' => $student->student_id,
                'full_name'  => $student->full_name,
                'email'      => $student->email,
            ),
            'certificates' => $certificates,
        ), 200 );
    }

    /**
     * Get active courses.
     */
    public function get_courses( $request ) {
        $courses = SSCV_Helpers::get_courses_dropdown();
        return new WP_REST_Response( array( 'courses' => $courses ), 200 );
    }
}
