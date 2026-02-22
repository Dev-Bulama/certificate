<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SkillScores_Cert_Verification
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables
$tables = array(
    $wpdb->prefix . 'sscv_students',
    $wpdb->prefix . 'sscv_certificates',
    $wpdb->prefix . 'sscv_courses',
    $wpdb->prefix . 'sscv_projects',
    $wpdb->prefix . 'sscv_certificate_templates',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove plugin options
$options = array(
    'sscv_db_version',
    'sscv_institution_name',
    'sscv_logo_url',
    'sscv_signature_url',
    'sscv_stamp_url',
    'sscv_theme_color',
    'sscv_accent_color',
    'sscv_font_family',
    'sscv_qr_size',
    'sscv_email_subject',
    'sscv_email_body',
    'sscv_recaptcha_site_key',
    'sscv_recaptcha_secret_key',
    'sscv_project_categories',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Clean up uploaded files
$upload_dir = wp_upload_dir();
$dirs = array(
    $upload_dir['basedir'] . '/sscv-certificates',
    $upload_dir['basedir'] . '/sscv-qrcodes',
);

foreach ( $dirs as $dir ) {
    if ( is_dir( $dir ) ) {
        $files = glob( $dir . '/*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        // Remove subdirectories
        $subdirs = glob( $dir . '/*', GLOB_ONLYDIR );
        foreach ( $subdirs as $subdir ) {
            $subfiles = glob( $subdir . '/*' );
            foreach ( $subfiles as $subfile ) {
                if ( is_file( $subfile ) ) {
                    unlink( $subfile );
                }
            }
            rmdir( $subdir );
        }
        rmdir( $dir );
    }
}

// Clean up transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%sscv_rate_%'" );

// Flush rewrite rules
flush_rewrite_rules();
