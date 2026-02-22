<?php
/**
 * Elementor widgets integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Elementor {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
    }

    /**
     * Register widget category.
     */
    public function register_category( $elements_manager ) {
        $elements_manager->add_category( 'sscv-widgets', array(
            'title' => __( 'Certificate & Verification', 'skillscores-cert' ),
            'icon'  => 'fa fa-certificate',
        ) );
    }

    /**
     * Register all widgets.
     */
    public function register_widgets( $widgets_manager ) {
        require_once SSCV_PLUGIN_DIR . 'elementor/widgets/class-widget-application-form.php';
        require_once SSCV_PLUGIN_DIR . 'elementor/widgets/class-widget-verification.php';
        require_once SSCV_PLUGIN_DIR . 'elementor/widgets/class-widget-project-submission.php';
        require_once SSCV_PLUGIN_DIR . 'elementor/widgets/class-widget-project-directory.php';
        require_once SSCV_PLUGIN_DIR . 'elementor/widgets/class-widget-student-dashboard.php';

        $widgets_manager->register( new SSCV_Widget_Application_Form() );
        $widgets_manager->register( new SSCV_Widget_Verification() );
        $widgets_manager->register( new SSCV_Widget_Project_Submission() );
        $widgets_manager->register( new SSCV_Widget_Project_Directory() );
        $widgets_manager->register( new SSCV_Widget_Student_Dashboard() );
    }
}
