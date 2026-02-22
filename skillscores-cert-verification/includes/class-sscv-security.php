<?php
/**
 * Security features: nonce, reCAPTCHA, RBAC, file validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_Security {

    /**
     * Verify nonce from AJAX request.
     */
    public static function verify_nonce( $nonce_field = 'nonce', $action = 'sscv_nonce' ) {
        $nonce = '';
        if ( isset( $_POST[ $nonce_field ] ) ) {
            $nonce = sanitize_text_field( $_POST[ $nonce_field ] );
        } elseif ( isset( $_GET[ $nonce_field ] ) ) {
            $nonce = sanitize_text_field( $_GET[ $nonce_field ] );
        } elseif ( isset( $_REQUEST[ $nonce_field ] ) ) {
            $nonce = sanitize_text_field( $_REQUEST[ $nonce_field ] );
        }

        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            return false;
        }

        return true;
    }

    /**
     * Verify reCAPTCHA v3 token.
     */
    public static function verify_recaptcha( $token ) {
        $secret_key = get_option( 'sscv_recaptcha_secret_key', '' );
        if ( empty( $secret_key ) ) {
            return true; // reCAPTCHA not configured, skip
        }

        if ( empty( $token ) ) {
            return false;
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => self::get_client_ip(),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return isset( $body['success'] ) && $body['success'] && ( ! isset( $body['score'] ) || $body['score'] >= 0.5 );
    }

    /**
     * Get client IP address.
     */
    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $key ] );
                // Handle comma-separated IPs (from proxies)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validate uploaded file.
     */
    public static function validate_file( $file, $context = 'image' ) {
        $errors = array();

        if ( empty( $file['name'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
            $errors[] = __( 'File upload failed.', 'skillscores-cert' );
            return $errors;
        }

        $allowed = array();
        $max_size = 5 * 1024 * 1024; // 5MB default

        switch ( $context ) {
            case 'image':
                $allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
                $max_size = 2 * 1024 * 1024; // 2MB for images
                break;
            case 'cv':
                $allowed = array( 'pdf', 'doc', 'docx' );
                $max_size = 5 * 1024 * 1024;
                break;
            case 'passport':
                $allowed = array( 'jpg', 'jpeg', 'png' );
                $max_size = 1 * 1024 * 1024; // 1MB for passport
                break;
            default:
                $allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf' );
        }

        // Check extension
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, $allowed, true ) ) {
            $errors[] = sprintf(
                __( 'Invalid file type "%s". Allowed: %s', 'skillscores-cert' ),
                $ext,
                implode( ', ', $allowed )
            );
        }

        // Check file size
        if ( $file['size'] > $max_size ) {
            $errors[] = sprintf(
                __( 'File too large. Maximum size: %sMB', 'skillscores-cert' ),
                $max_size / ( 1024 * 1024 )
            );
        }

        // Verify MIME type matches extension
        if ( function_exists( 'finfo_open' ) && empty( $errors ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mime  = finfo_file( $finfo, $file['tmp_name'] );
            finfo_close( $finfo );

            $allowed_mimes = array(
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            );

            if ( isset( $allowed_mimes[ $ext ] ) && $mime !== $allowed_mimes[ $ext ] ) {
                $errors[] = __( 'File MIME type does not match extension.', 'skillscores-cert' );
            }
        }

        return $errors;
    }

    /**
     * Check if current user has admin capability.
     */
    public static function is_admin() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Rate limiting check.
     */
    public static function check_rate_limit( $action, $limit = 5, $window = 3600 ) {
        $ip        = self::get_client_ip();
        $transient = 'sscv_rate_' . md5( $action . $ip );
        $count     = (int) get_transient( $transient );

        if ( $count >= $limit ) {
            return false;
        }

        set_transient( $transient, $count + 1, $window );
        return true;
    }

    /**
     * Sanitize all form input.
     */
    public static function sanitize_input( $data, $fields ) {
        $sanitized = array();

        foreach ( $fields as $field => $type ) {
            $value = isset( $data[ $field ] ) ? $data[ $field ] : '';

            switch ( $type ) {
                case 'text':
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    break;
                case 'email':
                    $sanitized[ $field ] = sanitize_email( $value );
                    break;
                case 'url':
                    $sanitized[ $field ] = esc_url_raw( $value );
                    break;
                case 'textarea':
                    $sanitized[ $field ] = sanitize_textarea_field( $value );
                    break;
                case 'int':
                    $sanitized[ $field ] = intval( $value );
                    break;
                case 'html':
                    $sanitized[ $field ] = wp_kses_post( $value );
                    break;
                default:
                    $sanitized[ $field ] = sanitize_text_field( $value );
            }
        }

        return $sanitized;
    }
}
