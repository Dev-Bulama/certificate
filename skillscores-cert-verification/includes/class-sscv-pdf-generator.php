<?php
/**
 * PDF generation for certificates.
 * Uses DomPDF or falls back to browser print.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_PDF_Generator {

    /**
     * Generate PDF from certificate HTML.
     */
    public static function generate( $certificate_id ) {
        $rendered = SSCV_Certificate_Engine::render( $certificate_id );
        if ( ! $rendered ) {
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

        // Try DomPDF if available via Composer
        $dompdf_autoload = SSCV_PLUGIN_DIR . 'vendor/autoload.php';
        if ( file_exists( $dompdf_autoload ) ) {
            require_once $dompdf_autoload;

            if ( class_exists( '\\Dompdf\\Dompdf' ) ) {
                return self::generate_with_dompdf( $rendered, $filepath, $fileurl, $certificate_id );
            }
        }

        // Fallback: Save HTML version that can be printed as PDF from the browser
        $html = self::build_printable_html( $rendered, $certificate_id );
        $html_filename = 'certificate-' . sanitize_file_name( $certificate_id ) . '-printable.html';
        $html_filepath = $pdf_dir . $html_filename;
        $html_fileurl  = $upload_dir['baseurl'] . '/sscv-certificates/pdf/' . $html_filename;

        file_put_contents( $html_filepath, $html );

        return array(
            'file'   => $html_filepath,
            'url'    => $html_fileurl,
            'type'   => 'html',
            'notice' => __( 'PDF generation requires DomPDF. A printable HTML version has been created instead.', 'skillscores-cert' ),
        );
    }

    /**
     * Generate PDF using DomPDF.
     */
    private static function generate_with_dompdf( $rendered, $filepath, $fileurl, $certificate_id ) {
        $dompdf = new \Dompdf\Dompdf( array(
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ) );

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { size: landscape; margin: 0; }
body { margin: 0; padding: 0; }
' . $rendered['css'] . '
</style>
</head>
<body>' . $rendered['html'] . '</body>
</html>';

        $dompdf->loadHtml( $html );
        $dompdf->setPaper( 'A4', 'landscape' );
        $dompdf->render();

        file_put_contents( $filepath, $dompdf->output() );

        // Add to media library
        $attachment = array(
            'guid'           => $fileurl,
            'post_mime_type' => 'application/pdf',
            'post_title'     => 'Certificate PDF - ' . $certificate_id,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        wp_insert_attachment( $attachment, $filepath );

        return array(
            'file' => $filepath,
            'url'  => $fileurl,
            'type' => 'pdf',
        );
    }

    /**
     * Build printable HTML as PDF fallback.
     */
    private static function build_printable_html( $rendered, $certificate_id ) {
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate - ' . esc_attr( $certificate_id ) . '</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #fff; }
@page { size: A4 landscape; margin: 0; }
@media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-btn { display: none !important; }
}
.print-btn {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    padding: 12px 24px; background: #1a365d; color: #fff;
    border: none; border-radius: 6px; cursor: pointer; font-size: 14px;
}
.print-btn:hover { background: #2a4a7f; }
' . $rendered['css'] . '
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Download as PDF</button>
' . $rendered['html'] . '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Auto-trigger print dialog for PDF download
    if (window.location.hash === "#print") {
        setTimeout(function() { window.print(); }, 500);
    }
});
</script>
</body>
</html>';
    }
}
