<?php
/**
 * PDF generation for certificates.
 * Uses bundled SSCV_PDF_Builder for native PDF generation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the PDF builder
require_once SSCV_PLUGIN_DIR . 'vendor/sscv-pdf/class-sscv-pdf-builder.php';

class SSCV_PDF_Generator {

    /**
     * Generate PDF from certificate data.
     */
    public static function generate( $certificate_id ) {
        $cert = SSCV_Helpers::get_certificate( $certificate_id );
        if ( ! $cert ) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/sscv-certificates/pdf/';

        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
            file_put_contents( $pdf_dir . 'index.php', '<?php // Silence is golden.' );
        }

        $filename = 'certificate-' . sanitize_file_name( $certificate_id ) . '.pdf';
        $filepath = $pdf_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-certificates/pdf/' . $filename;

        $settings = SSCV_Helpers::get_settings();

        $success = self::build_certificate_pdf( $cert, $settings, $filepath );

        if ( ! $success ) {
            return false;
        }

        return array(
            'file' => $filepath,
            'url'  => $fileurl,
            'type' => 'pdf',
        );
    }

    /**
     * Generate a bulk PDF with all approved certificates.
     */
    public static function generate_bulk( $certificates ) {
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/sscv-certificates/pdf/';

        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
            file_put_contents( $pdf_dir . 'index.php', '<?php // Silence is golden.' );
        }

        $filename = 'certificates-bulk-export-' . date( 'Y-m-d-His' ) . '.pdf';
        $filepath = $pdf_dir . $filename;
        $fileurl  = $upload_dir['baseurl'] . '/sscv-certificates/pdf/' . $filename;

        $settings = SSCV_Helpers::get_settings();

        $pdf = new SSCV_PDF_Builder( 'L', 'A4' );

        foreach ( $certificates as $certRef ) {
            $cert = SSCV_Helpers::get_certificate( $certRef->certificate_id );
            if ( ! $cert || $cert->status !== 'approved' ) {
                continue;
            }

            // End previous page stream if not first page
            if ( isset( $pageAdded ) ) {
                $pdf->endPage();
            }

            $pdf->addPage();
            $pageAdded = true;
            self::render_certificate_page( $pdf, $cert, $settings );
        }

        if ( ! isset( $pageAdded ) ) {
            return false;
        }

        $success = $pdf->output( $filepath );
        if ( ! $success ) {
            return false;
        }

        return array(
            'file' => $filepath,
            'url'  => $fileurl,
            'type' => 'pdf',
        );
    }

    /**
     * Build a single certificate PDF.
     */
    private static function build_certificate_pdf( $cert, $settings, $filepath ) {
        $pdf = new SSCV_PDF_Builder( 'L', 'A4' );
        $pdf->addPage();
        self::render_certificate_page( $pdf, $cert, $settings );
        return $pdf->output( $filepath );
    }

    /**
     * Render one certificate page onto the PDF builder.
     */
    private static function render_certificate_page( $pdf, $cert, $settings ) {
        $pw = $pdf->getPageWidth();
        $ph = $pdf->getPageHeight();

        // Parse colors
        $themeRGB  = self::hexToRgb( $settings['theme_color'] ?: '#1a365d' );
        $accentRGB = self::hexToRgb( $settings['accent_color'] ?: '#c8a951' );

        // ---- Background ----
        $pdf->filledRect( 0, 0, $pw, $ph, 255, 255, 255 );

        // ---- Outer border (accent color) ----
        $pdf->strokeRect( 15, 15, $pw - 30, $ph - 30, $accentRGB[0], $accentRGB[1], $accentRGB[2], 3 );

        // ---- Inner border (theme color) ----
        $pdf->strokeRect( 25, 25, $pw - 50, $ph - 50, $themeRGB[0], $themeRGB[1], $themeRGB[2], 1 );

        // ---- Corner decorations (accent color small rectangles) ----
        $cornerSize = 20;
        $offset = 18;
        // Top-left
        $pdf->filledRect( $offset, $offset, $cornerSize, 4, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        $pdf->filledRect( $offset, $offset, 4, $cornerSize, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        // Top-right
        $pdf->filledRect( $pw - $offset - $cornerSize, $offset, $cornerSize, 4, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        $pdf->filledRect( $pw - $offset - 4, $offset, 4, $cornerSize, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        // Bottom-left
        $pdf->filledRect( $offset, $ph - $offset - 4, $cornerSize, 4, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        $pdf->filledRect( $offset, $ph - $offset - $cornerSize, 4, $cornerSize, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        // Bottom-right
        $pdf->filledRect( $pw - $offset - $cornerSize, $ph - $offset - 4, $cornerSize, 4, $accentRGB[0], $accentRGB[1], $accentRGB[2] );
        $pdf->filledRect( $pw - $offset - 4, $ph - $offset - $cornerSize, 4, $cornerSize, $accentRGB[0], $accentRGB[1], $accentRGB[2] );

        // ---- Header accent bar ----
        $pdf->filledRect( 40, 40, $pw - 80, 6, $accentRGB[0], $accentRGB[1], $accentRGB[2] );

        $yPos = 65;

        // ---- Institution Logo ----
        if ( ! empty( $settings['logo_url'] ) ) {
            $pdf->imageFromUrl( $settings['logo_url'], ( $pw - 60 ) / 2, $yPos, 60, 60 );
            $yPos += 65;
        }

        // ---- Institution Name ----
        $pdf->setFont( 'Helvetica', 'B', 16 );
        $pdf->setTextColor( $themeRGB[0], $themeRGB[1], $themeRGB[2] );
        $pdf->centeredText( $yPos, $settings['institution_name'] ?: 'Institution' );
        $yPos += 28;

        // ---- Title: CERTIFICATE OF COMPLETION ----
        $pdf->setFont( 'Helvetica', 'B', 28 );
        $pdf->setTextColor( $themeRGB[0], $themeRGB[1], $themeRGB[2] );
        $pdf->centeredText( $yPos, 'CERTIFICATE OF COMPLETION' );
        $yPos += 16;

        // Decorative line under title
        $lineW = 250;
        $pdf->line( ( $pw - $lineW ) / 2, $yPos, ( $pw + $lineW ) / 2, $yPos, $accentRGB[0], $accentRGB[1], $accentRGB[2], 2 );
        $yPos += 20;

        // ---- "This is to certify that" ----
        $pdf->setFont( 'Helvetica', '', 12 );
        $pdf->setTextColor( 80, 80, 80 );
        $pdf->centeredText( $yPos, 'This is to certify that' );
        $yPos += 24;

        // ---- Student Name ----
        $pdf->setFont( 'Helvetica', 'B', 24 );
        $pdf->setTextColor( $themeRGB[0], $themeRGB[1], $themeRGB[2] );
        $pdf->centeredText( $yPos, $cert->full_name );
        $yPos += 14;

        // Name underline
        $nameW = min( $pdf->getStringWidth( $cert->full_name ) + 40, $pw - 200 );
        $pdf->line( ( $pw - $nameW ) / 2, $yPos, ( $pw + $nameW ) / 2, $yPos, $accentRGB[0], $accentRGB[1], $accentRGB[2], 1 );
        $yPos += 22;

        // ---- Course description ----
        $pdf->setFont( 'Helvetica', '', 12 );
        $pdf->setTextColor( 80, 80, 80 );
        $pdf->centeredText( $yPos, 'has successfully completed the course' );
        $yPos += 22;

        // ---- Course Title ----
        $courseTitle = $cert->course_title ?? '';
        $pdf->setFont( 'Helvetica', 'B', 18 );
        $pdf->setTextColor( $themeRGB[0], $themeRGB[1], $themeRGB[2] );
        $pdf->centeredText( $yPos, $courseTitle );
        $yPos += 24;

        // ---- Grade (if enabled and present) ----
        $gradeEnabled = get_option( 'sscv_grade_field_enabled', '1' );
        if ( $gradeEnabled === '1' && ! empty( $cert->grade ) ) {
            $pdf->setFont( 'Helvetica', '', 13 );
            $pdf->setTextColor( 80, 80, 80 );
            $pdf->centeredText( $yPos, 'Grade: ' . $cert->grade );
            $yPos += 20;
        }

        // ---- Dates row ----
        $pdf->setFont( 'Helvetica', '', 10 );
        $pdf->setTextColor( 100, 100, 100 );

        $dateCompleted = ! empty( $cert->date_completed ) ? SSCV_Helpers::format_date( $cert->date_completed ) : '';
        $dateIssued    = ! empty( $cert->date_issued ) ? SSCV_Helpers::format_date( $cert->date_issued ) : SSCV_Helpers::format_date( current_time( 'mysql' ) );

        if ( $dateCompleted ) {
            $pdf->text( 100, $yPos, 'Date Completed: ' . $dateCompleted );
        }
        $pdf->rightText( $pw - 100, $yPos, 'Date Issued: ' . $dateIssued );
        $yPos += 16;

        // ---- Certificate ID ----
        $pdf->setFont( 'Helvetica', '', 9 );
        $pdf->setTextColor( 120, 120, 120 );
        $pdf->centeredText( $yPos, 'Certificate ID: ' . $cert->certificate_id );
        $yPos += 14;

        // ---- Student ID ----
        global $wpdb;
        $studentIdNum = $wpdb->get_var( $wpdb->prepare(
            "SELECT student_id FROM {$wpdb->prefix}sscv_students WHERE id = %d",
            $cert->student_id
        ) );
        if ( $studentIdNum ) {
            $pdf->setFont( 'Helvetica', '', 9 );
            $pdf->centeredText( $yPos, 'Student ID: ' . $studentIdNum );
            $yPos += 14;
        }

        // ---- Bottom section: Signature, Stamp, QR Code ----
        $bottomY = $ph - 110;

        // Signature (left area)
        if ( ! empty( $settings['signature_url'] ) ) {
            $pdf->imageFromUrl( $settings['signature_url'], 100, $bottomY, 80, 40 );
            $pdf->line( 80, $bottomY + 45, 200, $bottomY + 45, 100, 100, 100, 0.5 );
            $pdf->setFont( 'Helvetica', '', 9 );
            $pdf->setTextColor( 100, 100, 100 );
            $pdf->text( 105, $bottomY + 52, 'Authorized Signature' );
        }

        // Stamp (center)
        if ( ! empty( $settings['stamp_url'] ) ) {
            $pdf->imageFromUrl( $settings['stamp_url'], ( $pw - 60 ) / 2, $bottomY - 5, 60, 60 );
        }

        // QR Code (right area)
        if ( ! empty( $cert->qr_code_url ) ) {
            $pdf->imageFromUrl( $cert->qr_code_url, $pw - 180, $bottomY - 5, 60, 60 );
            $pdf->setFont( 'Helvetica', '', 7 );
            $pdf->setTextColor( 120, 120, 120 );
            $pdf->text( $pw - 190, $bottomY + 60, 'Scan to verify' );
        }

        // Passport photo (far right)
        if ( ! empty( $cert->passport_url ) ) {
            $pdf->imageFromUrl( $cert->passport_url, $pw - 105, $bottomY - 5, 50, 60 );
        }

        // ---- Footer accent bar ----
        $pdf->filledRect( 40, $ph - 46, $pw - 80, 6, $accentRGB[0], $accentRGB[1], $accentRGB[2] );

        // ---- Verification URL ----
        $verifyUrl = SSCV_Helpers::get_verification_url( $cert->certificate_id );
        $pdf->setFont( 'Helvetica', '', 7 );
        $pdf->setTextColor( 140, 140, 140 );
        $pdf->centeredText( $ph - 32, 'Verify at: ' . $verifyUrl );
    }

    /**
     * Convert hex color to RGB array.
     */
    private static function hexToRgb( $hex ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
            hexdec( substr( $hex, 0, 2 ) ),
            hexdec( substr( $hex, 2, 2 ) ),
            hexdec( substr( $hex, 4, 2 ) ),
        );
    }
}
