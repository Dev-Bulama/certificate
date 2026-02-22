<?php
/**
 * Public-facing functionality.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Public {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Public hooks can be added here
    }
}
