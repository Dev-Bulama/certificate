<?php
/**
 * PHP QR Code Generator - Minimal Bundled Version
 *
 * Based on phpqrcode by Dominik Dzienia (MIT License)
 * For production, replace this with the full phpqrcode library from:
 * https://github.com/t0k4rt/phpqrcode
 *
 * This is a minimal stub that provides the QRcode::png() interface.
 * If the full library is installed, it will be used instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Error correction levels
if ( ! defined( 'QR_ECLEVEL_L' ) ) define( 'QR_ECLEVEL_L', 0 );
if ( ! defined( 'QR_ECLEVEL_M' ) ) define( 'QR_ECLEVEL_M', 1 );
if ( ! defined( 'QR_ECLEVEL_Q' ) ) define( 'QR_ECLEVEL_Q', 2 );
if ( ! defined( 'QR_ECLEVEL_H' ) ) define( 'QR_ECLEVEL_H', 3 );

if ( ! class_exists( 'QRcode' ) ) {

    /**
     * Minimal QR code class using Google Charts API fallback.
     */
    class QRcode {

        /**
         * Generate QR code PNG image.
         *
         * @param string $text Data to encode
         * @param string|false $outfile Output file path, false for direct output
         * @param int $level Error correction level
         * @param int $size Module size in pixels
         * @param int $margin Margin in modules
         */
        public static function png( $text, $outfile = false, $level = QR_ECLEVEL_L, $size = 4, $margin = 2 ) {
            $pixel_size = max( 150, $size * 33 );
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $pixel_size . 'x' . $pixel_size . '&data=' . urlencode( $text ) . '&format=png&margin=' . $margin;

            $response = wp_remote_get( $url, array( 'timeout' => 15 ) );

            if ( is_wp_error( $response ) ) {
                // Generate a simple placeholder PNG
                self::generate_placeholder( $text, $outfile, $pixel_size );
                return;
            }

            $body = wp_remote_retrieve_body( $response );

            if ( $outfile ) {
                file_put_contents( $outfile, $body );
            } else {
                header( 'Content-Type: image/png' );
                echo $body;
            }
        }

        /**
         * Generate a placeholder QR image when API is unavailable.
         */
        private static function generate_placeholder( $text, $outfile, $size ) {
            if ( ! function_exists( 'imagecreate' ) ) {
                return;
            }

            $img = imagecreate( $size, $size );
            $white = imagecolorallocate( $img, 255, 255, 255 );
            $black = imagecolorallocate( $img, 0, 0, 0 );
            $gray = imagecolorallocate( $img, 128, 128, 128 );

            imagefilledrectangle( $img, 0, 0, $size, $size, $white );
            imagerectangle( $img, 2, 2, $size - 3, $size - 3, $black );

            $font_size = 3;
            $text_short = substr( $text, -20 );
            $tw = imagefontwidth( $font_size ) * strlen( 'QR Code' );
            imagestring( $img, $font_size, ( $size - $tw ) / 2, $size / 2 - 15, 'QR Code', $black );

            $tw2 = imagefontwidth( 2 ) * strlen( $text_short );
            imagestring( $img, 2, ( $size - $tw2 ) / 2, $size / 2 + 5, $text_short, $gray );

            if ( $outfile ) {
                imagepng( $img, $outfile );
            } else {
                header( 'Content-Type: image/png' );
                imagepng( $img );
            }

            imagedestroy( $img );
        }
    }
}
