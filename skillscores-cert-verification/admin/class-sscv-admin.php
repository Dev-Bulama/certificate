<?php
/**
 * Admin dashboard controller.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_init', array( $this, 'check_db_version' ) );
    }

    /**
     * Check and update DB version.
     */
    public function check_db_version() {
        if ( get_option( 'sscv_db_version' ) !== SSCV_DB_VERSION ) {
            SSCV_Database::activate();
        }
    }

    /**
     * Register admin menus.
     */
    public function register_menus() {
        // Main menu
        add_menu_page(
            __( 'Certificates', 'skillscores-cert' ),
            __( 'Certificates', 'skillscores-cert' ),
            'manage_options',
            'sscv-dashboard',
            array( $this, 'page_dashboard' ),
            'dashicons-awards',
            30
        );

        // Submenu pages
        add_submenu_page(
            'sscv-dashboard',
            __( 'Dashboard', 'skillscores-cert' ),
            __( 'Dashboard', 'skillscores-cert' ),
            'manage_options',
            'sscv-dashboard',
            array( $this, 'page_dashboard' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Certificate Applications', 'skillscores-cert' ),
            __( 'Applications', 'skillscores-cert' ),
            'manage_options',
            'sscv-applications',
            array( $this, 'page_applications' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Courses', 'skillscores-cert' ),
            __( 'Courses', 'skillscores-cert' ),
            'manage_options',
            'sscv-courses',
            array( $this, 'page_courses' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Certificate Templates', 'skillscores-cert' ),
            __( 'Templates', 'skillscores-cert' ),
            'manage_options',
            'sscv-templates',
            array( $this, 'page_templates' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Student Projects', 'skillscores-cert' ),
            __( 'Projects', 'skillscores-cert' ),
            'manage_options',
            'sscv-projects',
            array( $this, 'page_projects' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Students', 'skillscores-cert' ),
            __( 'Students', 'skillscores-cert' ),
            'manage_options',
            'sscv-students',
            array( $this, 'page_students' )
        );

        add_submenu_page(
            'sscv-dashboard',
            __( 'Settings', 'skillscores-cert' ),
            __( 'Settings', 'skillscores-cert' ),
            'manage_options',
            'sscv-settings',
            array( $this, 'page_settings' )
        );
    }

    /**
     * Dashboard page.
     */
    public function page_dashboard() {
        global $wpdb;

        $stats = array(
            'total_students'       => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_students" ),
            'total_certificates'   => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates" ),
            'pending_certificates' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates WHERE status = 'pending'" ),
            'approved_certificates' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates WHERE status = 'approved'" ),
            'total_courses'        => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_courses" ),
            'total_projects'       => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects" ),
            'pending_projects'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects WHERE status = 'pending'" ),
        );

        $recent_applications = $wpdb->get_results(
            "SELECT c.*, co.course_title
             FROM {$wpdb->prefix}sscv_certificates c
             LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
             ORDER BY c.created_at DESC LIMIT 10"
        );

        include SSCV_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Applications page.
     */
    public function page_applications() {
        global $wpdb;

        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );
        $search        = sanitize_text_field( $_GET['s'] ?? '' );
        $paged         = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page      = 20;

        $where = '1=1';
        $params = array();

        if ( ! empty( $status_filter ) ) {
            $where .= " AND c.status = %s";
            $params[] = $status_filter;
        }

        if ( ! empty( $search ) ) {
            $where .= " AND (c.full_name LIKE %s OR c.certificate_id LIKE %s OR c.email LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_certificates c WHERE {$where}";
        $total = $wpdb->get_var( empty( $params ) ? $count_sql : $wpdb->prepare( $count_sql, $params ) );

        $offset = ( $paged - 1 ) * $per_page;
        $sql = "SELECT c.*, co.course_title, s.student_id as student_id_number
                FROM {$wpdb->prefix}sscv_certificates c
                LEFT JOIN {$wpdb->prefix}sscv_courses co ON c.course_id = co.id
                LEFT JOIN {$wpdb->prefix}sscv_students s ON c.student_id = s.id
                WHERE {$where}
                ORDER BY c.created_at DESC
                LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $applications = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        $total_pages  = ceil( $total / $per_page );

        include SSCV_PLUGIN_DIR . 'admin/views/applications.php';
    }

    /**
     * Courses page.
     */
    public function page_courses() {
        global $wpdb;

        $courses   = $wpdb->get_results( "SELECT c.*, t.template_name FROM {$wpdb->prefix}sscv_courses c LEFT JOIN {$wpdb->prefix}sscv_certificate_templates t ON c.template_id = t.id ORDER BY c.created_at DESC" );
        $templates = SSCV_Helpers::get_templates_dropdown();

        include SSCV_PLUGIN_DIR . 'admin/views/courses.php';
    }

    /**
     * Templates page.
     */
    public function page_templates() {
        global $wpdb;

        $templates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sscv_certificate_templates ORDER BY created_at DESC" );
        $editing = null;

        if ( ! empty( $_GET['edit'] ) ) {
            $edit_id = intval( $_GET['edit'] );
            $editing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sscv_certificate_templates WHERE id = %d",
                $edit_id
            ) );
        }

        include SSCV_PLUGIN_DIR . 'admin/views/templates.php';
    }

    /**
     * Projects page.
     */
    public function page_projects() {
        global $wpdb;

        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );
        $paged         = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page      = 20;

        $where  = '1=1';
        $params = array();

        if ( ! empty( $status_filter ) ) {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_projects WHERE {$where}";
        $total = $wpdb->get_var( empty( $params ) ? $count_sql : $wpdb->prepare( $count_sql, $params ) );

        $offset = ( $paged - 1 ) * $per_page;
        $sql = "SELECT * FROM {$wpdb->prefix}sscv_projects WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $projects    = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        $total_pages = ceil( $total / $per_page );

        include SSCV_PLUGIN_DIR . 'admin/views/projects.php';
    }

    /**
     * Students page.
     */
    public function page_students() {
        global $wpdb;

        $search   = sanitize_text_field( $_GET['s'] ?? '' );
        $paged    = max( 1, intval( $_GET['paged'] ?? 1 ) );
        $per_page = 20;

        $where = '1=1';
        $params = array();

        if ( ! empty( $search ) ) {
            $where .= " AND (full_name LIKE %s OR student_id LIKE %s OR email LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sscv_students WHERE {$where}";
        $total = $wpdb->get_var( empty( $params ) ? $count_sql : $wpdb->prepare( $count_sql, $params ) );

        $offset = ( $paged - 1 ) * $per_page;
        $sql = "SELECT * FROM {$wpdb->prefix}sscv_students WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $students    = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        $total_pages = ceil( $total / $per_page );

        include SSCV_PLUGIN_DIR . 'admin/views/students.php';
    }

    /**
     * Settings page.
     */
    public function page_settings() {
        $settings = SSCV_Helpers::get_settings();
        include SSCV_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
