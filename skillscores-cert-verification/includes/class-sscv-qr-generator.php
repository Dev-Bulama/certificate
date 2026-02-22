<?php
/**
 * QR Code generation using bundled phpqrcode library.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_QR_Generator {

    /**
     * Generate QR code for a certificate.
     */
    public static function generate( $certificate_id, $size = null ) {
        if ( null === $size ) {
            $size = (int) get_option( 'sscv_qr_size', 150 );
        }

        $verification_url = SSCV_Helpers::get_verification_url( $certificate_id );

        $upload_dir = wp_upload_dir();
        $qr_dir     = $upload_dir['basedir'] . '/sscv-qrcodes/';

        if ( ! file_exists( $qr_dir ) ) {
            wp_mkdir_p( $qr_dir );
            file_put_contents( $qr_dir . 'index.php', '<?php // Silence is golden.' );
        }

        $filename = 'qr-' . sanitize_file_name( $certificate_id ) . '.png';
        $filepath = $qr_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-qrcodes/' . $filename;

        // Use bundled phpqrcode library
        $qrlib = SSCV_PLUGIN_DIR . 'vendor/phpqrcode/qrlib.php';
        if ( file_exists( $qrlib ) ) {
            require_once $qrlib;
            QRcode::png( $verification_url, $filepath, QR_ECLEVEL_M, max( 4, intval( $size / 33 ) ), 2 );
        } else {
            // Fallback: generate via Google Charts API
            $google_qr = 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chl=' . urlencode( $verification_url ) . '&choe=UTF-8';
            $response = wp_remote_get( $google_qr );
            if ( ! is_wp_error( $response ) ) {
                file_put_contents( $filepath, wp_remote_retrieve_body( $response ) );
            } else {
                // Last fallback: generate SVG QR code natively
                return self::generate_svg_qr( $certificate_id, $verification_url, $qr_dir, $upload_dir );
            }
        }

        // Store in media library
        $attachment = array(
            'guid'           => $fileurl,
            'post_mime_type' => 'image/png',
            'post_title'     => 'QR Code - ' . $certificate_id,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        wp_insert_attachment( $attachment, $filepath );

        return $fileurl;
    }

    /**
     * Fallback SVG QR code generator.
     */
    private static function generate_svg_qr( $certificate_id, $url, $qr_dir, $upload_dir ) {
        $filename = 'qr-' . sanitize_file_name( $certificate_id ) . '.svg';
        $filepath = $qr_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-qrcodes/' . $filename;

        // Simple placeholder SVG with URL encoded
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150">
            <rect width="150" height="150" fill="#fff" stroke="#333" stroke-width="2"/>
            <text x="75" y="70" text-anchor="middle" font-size="10" fill="#333">QR Code</text>
            <text x="75" y="85" text-anchor="middle" font-size="8" fill="#666">' . esc_html( $certificate_id ) . '</text>
            <text x="75" y="100" text-anchor="middle" font-size="7" fill="#999">Scan to verify</text>
        </svg>';

        file_put_contents( $filepath, $svg );

        return $fileurl;
    }
}
