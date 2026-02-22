<?php
/**
 * Plugin Name: Certificate & Student Verification System
 * Plugin URI: https://tla.com.ng
 * Description: A robust WordPress plugin for certificate application, generation, QR code verification, and student project showcase.
 * Version: 1.0.0
 * Author: Tijani Bulama
 * Author URI: https://tla.com.ng
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: skillscores-cert
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'SSCV_VERSION', '1.0.0' );
define( 'SSCV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSCV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSCV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SSCV_DB_VERSION', '1.0.0' );

/**
 * Main plugin class.
 */
final class SkillScores_Cert_Verification {

    /**
     * Single instance.
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files.
     */
    private function load_dependencies() {
        // Core includes
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-database.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-helpers.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-certificate-engine.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-qr-generator.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-pdf-generator.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-email.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-shortcodes.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-ajax-handler.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-security.php';
        require_once SSCV_PLUGIN_DIR . 'includes/class-sscv-demo-data.php';

        // Admin
        if ( is_admin() ) {
            require_once SSCV_PLUGIN_DIR . 'admin/class-sscv-admin.php';
        }

        // Public
        require_once SSCV_PLUGIN_DIR . 'public/class-sscv-public.php';

        // REST API
        require_once SSCV_PLUGIN_DIR . 'api/class-sscv-rest-api.php';

        // Elementor integration
        if ( did_action( 'elementor/loaded' ) ) {
            require_once SSCV_PLUGIN_DIR . 'elementor/class-sscv-elementor.php';
        } else {
            add_action( 'elementor/loaded', function() {
                require_once SSCV_PLUGIN_DIR . 'elementor/class-sscv-elementor.php';
                SSCV_Elementor::instance();
            });
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( 'SSCV_Database', 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init_classes' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Rewrite rules for verification
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_verification_redirect' ) );
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'skillscores-cert', false, dirname( SSCV_PLUGIN_BASENAME ) . '/languages/' );
    }

    /**
     * Initialize classes.
     */
    public function init_classes() {
        SSCV_Shortcodes::instance();
        SSCV_Ajax_Handler::instance();

        if ( is_admin() ) {
            SSCV_Admin::instance();
        }

        SSCV_Public::instance();
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        $api = new SSCV_Rest_API();
        $api->register_routes();
    }

    /**
     * Add rewrite rules for verification page.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^verify/?$',
            'index.php?sscv_verify=1',
            'top'
        );
    }

    /**
     * Add custom query vars.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'sscv_verify';
        $vars[] = 'cert_id';
        return $vars;
    }

    /**
     * Handle verification redirect.
     */
    public function handle_verification_redirect() {
        if ( get_query_var( 'sscv_verify' ) ) {
            $cert_id = isset( $_GET['cert_id'] ) ? sanitize_text_field( $_GET['cert_id'] ) : '';
            include SSCV_PLUGIN_DIR . 'public/views/verification-page.php';
            exit;
        }
    }

    /**
     * Enqueue public scripts and styles.
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'sscv-public',
            SSCV_PLUGIN_URL . 'public/css/sscv-public.css',
            array(),
            SSCV_VERSION
        );

        wp_enqueue_script(
            'sscv-public',
            SSCV_PLUGIN_URL . 'public/js/sscv-public.js',
            array( 'jquery' ),
            SSCV_VERSION,
            true
        );

        wp_localize_script( 'sscv-public', 'sscv_ajax', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'rest_url'    => rest_url( 'sscv/v1/' ),
            'nonce'       => wp_create_nonce( 'sscv_nonce' ),
            'rest_nonce'  => wp_create_nonce( 'wp_rest' ),
            'plugin_url'  => SSCV_PLUGIN_URL,
            'verify_url'  => home_url( '/verify/' ),
        ) );

        // Load reCAPTCHA if configured
        $recaptcha_site_key = get_option( 'sscv_recaptcha_site_key', '' );
        if ( ! empty( $recaptcha_site_key ) ) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ),
                array(),
                null,
                true
            );
        }
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'sscv' ) === false && strpos( $hook, 'skillscores' ) === false ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'sscv-admin',
            SSCV_PLUGIN_URL . 'admin/css/sscv-admin.css',
            array(),
            SSCV_VERSION
        );

        wp_enqueue_script(
            'sscv-admin',
            SSCV_PLUGIN_URL . 'admin/js/sscv-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            SSCV_VERSION,
            true
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_localize_script( 'sscv-admin', 'sscv_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sscv_admin_nonce' ),
        ) );
    }

    /**
     * Deactivation hook.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize plugin.
 */
function sscv_init() {
    return SkillScores_Cert_Verification::instance();
}

add_action( 'plugins_loaded', 'sscv_init' );
