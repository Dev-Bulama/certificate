<?php
/**
 * Shortcode registrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Shortcodes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'certificate_application_form', array( $this, 'application_form' ) );
        add_shortcode( 'certificate_verification', array( $this, 'verification_portal' ) );
        add_shortcode( 'student_project_submission', array( $this, 'project_submission_form' ) );
        add_shortcode( 'public_project_directory', array( $this, 'project_directory' ) );
        add_shortcode( 'student_dashboard', array( $this, 'student_dashboard' ) );
    }

    /**
     * Certificate application form shortcode.
     */
    public function application_form( $atts ) {
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts, 'certificate_application_form' );

        ob_start();
        include SSCV_PLUGIN_DIR . 'public/views/application-form.php';
        return ob_get_clean();
    }

    /**
     * Verification portal shortcode.
     */
    public function verification_portal( $atts ) {
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts, 'certificate_verification' );

        ob_start();
        include SSCV_PLUGIN_DIR . 'public/views/verification-portal.php';
        return ob_get_clean();
    }

    /**
     * Project submission form shortcode.
     */
    public function project_submission_form( $atts ) {
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts, 'student_project_submission' );

        ob_start();
        include SSCV_PLUGIN_DIR . 'public/views/project-submission-form.php';
        return ob_get_clean();
    }

    /**
     * Public project directory shortcode.
     */
    public function project_directory( $atts ) {
        $atts = shortcode_atts( array(
            'class'    => '',
            'per_page' => 10,
        ), $atts, 'public_project_directory' );

        ob_start();
        include SSCV_PLUGIN_DIR . 'public/views/project-directory.php';
        return ob_get_clean();
    }

    /**
     * Student dashboard shortcode.
     */
    public function student_dashboard( $atts ) {
        $atts = shortcode_atts( array(
            'class' => '',
        ), $atts, 'student_dashboard' );

        ob_start();
        include SSCV_PLUGIN_DIR . 'public/views/student-dashboard.php';
        return ob_get_clean();
    }
}
