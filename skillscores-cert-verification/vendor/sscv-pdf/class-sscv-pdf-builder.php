<?php
/**
 * Minimal native PHP PDF builder for certificate generation.
 *
 * Generates valid PDF files without external dependencies.
 * Supports: text, images (JPEG/PNG), rectangles, lines, colors.
 * Uses the 14 standard PDF fonts (built into all PDF viewers).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCV_PDF_Builder {

    private $buffer = '';
    private $objects = array();
    private $offsets = array();
    private $n = 0;
    private $pages = array();
    private $pageContents = array();
    private $currentPage = -1;
    private $pageStream = '';
    private $fonts = array();
    private $fontCounter = 0;
    private $images = array();
    private $imageCounter = 0;
    private $currentFontKey = '';
    private $currentFontSize = 12;
    private $pageWidth;
    private $pageHeight;
    private $k = 1; // Scale factor (points)

    // Standard PDF font widths (Helvetica) - character widths for each ASCII char 0-255
    private static $coreWidths = null;

    public function __construct( $orientation = 'L', $size = 'A4' ) {
        // A4 in points: 595.28 x 841.89
        if ( $size === 'A4' ) {
            if ( $orientation === 'L' ) {
                $this->pageWidth  = 841.89;
                $this->pageHeight = 595.28;
            } else {
                $this->pageWidth  = 595.28;
                $this->pageHeight = 841.89;
            }
        }
    }

    /**
     * Get page dimensions.
     */
    public function getPageWidth() {
        return $this->pageWidth;
    }

    public function getPageHeight() {
        return $this->pageHeight;
    }

    /**
     * Add a new page.
     */
    public function addPage() {
        $this->currentPage++;
        $this->pages[ $this->currentPage ] = array();
        $this->pageStream = '';
    }

    /**
     * Set font. Supports: Helvetica, Times, Courier in styles: '', B, I, BI.
     */
    public function setFont( $family = 'Helvetica', $style = '', $size = 12 ) {
        $family = ucfirst( strtolower( $family ) );
        if ( $family === 'Times' ) {
            $family = 'Times-Roman';
        }

        $fontKey = $family . $style;

        // Map to PDF standard font names
        $fontMap = array(
            'Helvetica'    => 'Helvetica',
            'HelveticaB'   => 'Helvetica-Bold',
            'HelveticaI'   => 'Helvetica-Oblique',
            'HelveticaBI'  => 'Helvetica-BoldOblique',
            'Times-Roman'  => 'Times-Roman',
            'Times-RomanB' => 'Times-Bold',
            'Times-RomanI' => 'Times-Italic',
            'Times-RomanBI'=> 'Times-BoldItalic',
            'Courier'      => 'Courier',
            'CourierB'     => 'Courier-Bold',
            'CourierI'     => 'Courier-Oblique',
            'CourierBI'    => 'Courier-BoldOblique',
        );

        $pdfName = isset( $fontMap[ $fontKey ] ) ? $fontMap[ $fontKey ] : 'Helvetica';

        if ( ! isset( $this->fonts[ $fontKey ] ) ) {
            $this->fontCounter++;
            $this->fonts[ $fontKey ] = array(
                'i'    => $this->fontCounter,
                'name' => $pdfName,
            );
        }

        $this->currentFontKey  = $fontKey;
        $this->currentFontSize = $size;
        $this->pageStream .= sprintf( "BT /F%d %.2f Tf ET\n", $this->fonts[ $fontKey ]['i'], $size );
    }

    /**
     * Set text color (RGB 0-255).
     */
    public function setTextColor( $r, $g, $b ) {
        $this->pageStream .= sprintf( "%.3f %.3f %.3f rg\n", $r / 255, $g / 255, $b / 255 );
    }

    /**
     * Set draw color (RGB 0-255).
     */
    public function setDrawColor( $r, $g, $b ) {
        $this->pageStream .= sprintf( "%.3f %.3f %.3f RG\n", $r / 255, $g / 255, $b / 255 );
    }

    /**
     * Set fill color (RGB 0-255).
     */
    public function setFillColor( $r, $g, $b ) {
        $this->pageStream .= sprintf( "%.3f %.3f %.3f rg\n", $r / 255, $g / 255, $b / 255 );
    }

    /**
     * Set line width.
     */
    public function setLineWidth( $width ) {
        $this->pageStream .= sprintf( "%.2f w\n", $width );
    }

    /**
     * Draw a filled rectangle.
     */
    public function filledRect( $x, $y, $w, $h, $r, $g, $b ) {
        $py = $this->pageHeight - $y - $h;
        $this->pageStream .= sprintf( "%.3f %.3f %.3f rg\n", $r / 255, $g / 255, $b / 255 );
        $this->pageStream .= sprintf( "%.2f %.2f %.2f %.2f re f\n", $x, $py, $w, $h );
    }

    /**
     * Draw a stroked rectangle.
     */
    public function strokeRect( $x, $y, $w, $h, $r = 0, $g = 0, $b = 0, $lineWidth = 1 ) {
        $py = $this->pageHeight - $y - $h;
        $this->pageStream .= sprintf( "%.2f w\n", $lineWidth );
        $this->pageStream .= sprintf( "%.3f %.3f %.3f RG\n", $r / 255, $g / 255, $b / 255 );
        $this->pageStream .= sprintf( "%.2f %.2f %.2f %.2f re S\n", $x, $py, $w, $h );
    }

    /**
     * Draw a line.
     */
    public function line( $x1, $y1, $x2, $y2, $r = 0, $g = 0, $b = 0, $lineWidth = 1 ) {
        $py1 = $this->pageHeight - $y1;
        $py2 = $this->pageHeight - $y2;
        $this->pageStream .= sprintf( "%.2f w\n", $lineWidth );
        $this->pageStream .= sprintf( "%.3f %.3f %.3f RG\n", $r / 255, $g / 255, $b / 255 );
        $this->pageStream .= sprintf( "%.2f %.2f m %.2f %.2f l S\n", $x1, $py1, $x2, $py2 );
    }

    /**
     * Output text at position. Y is from top.
     */
    public function text( $x, $y, $text ) {
        $py   = $this->pageHeight - $y;
        $text = $this->escapeText( $text );
        $this->pageStream .= sprintf( "BT %.2f %.2f Td (%s) Tj ET\n", $x, $py, $text );
    }

    /**
     * Output centered text at a given Y position.
     */
    public function centeredText( $y, $text, $leftBound = 0, $rightBound = 0 ) {
        $width      = $rightBound > 0 ? ( $rightBound - $leftBound ) : $this->pageWidth;
        $textWidth  = $this->getStringWidth( $text );
        $x          = $leftBound + ( $width - $textWidth ) / 2;
        $this->text( $x, $y, $text );
    }

    /**
     * Output right-aligned text.
     */
    public function rightText( $x, $y, $text ) {
        $textWidth = $this->getStringWidth( $text );
        $this->text( $x - $textWidth, $y, $text );
    }

    /**
     * Get approximate string width in points.
     */
    public function getStringWidth( $text ) {
        // Approximate width: average char width for Helvetica is ~0.52 of font size
        $avgWidth = $this->currentFontSize * 0.52;
        $len = strlen( $text );
        return $len * $avgWidth;
    }

    /**
     * Add image from file. Supports JPEG and PNG.
     */
    public function image( $file, $x, $y, $w, $h = 0 ) {
        if ( ! file_exists( $file ) ) {
            // Try to download from URL
            if ( filter_var( $file, FILTER_VALIDATE_URL ) ) {
                $tmpFile = download_url( $file, 10 );
                if ( is_wp_error( $tmpFile ) ) {
                    return;
                }
                $file = $tmpFile;
            } else {
                return;
            }
        }

        $info = $this->parseImage( $file );
        if ( ! $info ) {
            return;
        }

        // Auto-calculate height to maintain aspect ratio
        if ( $h == 0 && $w > 0 ) {
            $h = $w * $info['h'] / $info['w'];
        } elseif ( $w == 0 && $h > 0 ) {
            $w = $h * $info['w'] / $info['h'];
        }

        $imgKey = md5( $file );
        if ( ! isset( $this->images[ $imgKey ] ) ) {
            $this->imageCounter++;
            $info['i'] = $this->imageCounter;
            $this->images[ $imgKey ] = $info;
        }

        $i  = $this->images[ $imgKey ]['i'];
        $py = $this->pageHeight - $y - $h;
        $this->pageStream .= sprintf( "q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q\n", $w, $h, $x, $py, $i );
    }

    /**
     * Add image from URL (WordPress-aware).
     */
    public function imageFromUrl( $url, $x, $y, $w, $h = 0 ) {
        if ( empty( $url ) ) {
            return;
        }

        // Convert URL to local path if possible
        $upload_dir = wp_upload_dir();
        $local_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );

        if ( file_exists( $local_path ) ) {
            $this->image( $local_path, $x, $y, $w, $h );
        } else {
            // Download to temp
            $tmp = download_url( $url, 5 );
            if ( ! is_wp_error( $tmp ) ) {
                $this->image( $tmp, $x, $y, $w, $h );
                @unlink( $tmp );
            }
        }
    }

    /**
     * Output the PDF to a file. Returns true on success.
     */
    public function output( $filepath ) {
        $this->buildDocument();
        return (bool) file_put_contents( $filepath, $this->buffer );
    }

    /**
     * Get the raw PDF content.
     */
    public function getOutput() {
        $this->buildDocument();
        return $this->buffer;
    }

    // ========================
    // Internal PDF construction
    // ========================

    private function buildDocument() {
        $this->buffer = '';
        $this->n      = 0;
        $this->offsets = array();

        // Finalize last page
        if ( $this->currentPage >= 0 ) {
            $this->pageContents[ $this->currentPage ] = $this->pageStream;
        }

        $this->putHeader();
        $this->putFonts();
        $this->putImages();
        $this->putPages();
        $this->putResources();
        $this->putInfo();
        $this->putCatalog();

        // Cross-reference table
        $xrefOffset = strlen( $this->buffer );
        $this->out( 'xref' );
        $this->out( '0 ' . ( $this->n + 1 ) );
        $this->out( '0000000000 65535 f ' );
        for ( $i = 1; $i <= $this->n; $i++ ) {
            $this->out( sprintf( '%010d 00000 n ', $this->offsets[ $i ] ) );
        }

        // Trailer
        $this->out( 'trailer' );
        $this->out( '<<' );
        $this->out( '/Size ' . ( $this->n + 1 ) );
        $this->out( '/Root ' . $this->n . ' 0 R' ); // Catalog is last object
        $this->out( '/Info ' . ( $this->n - 1 ) . ' 0 R' );
        $this->out( '>>' );
        $this->out( 'startxref' );
        $this->out( $xrefOffset );
        $this->out( '%%EOF' );
    }

    private function putHeader() {
        $this->out( '%PDF-1.4' );
        $this->out( '%' . chr( 226 ) . chr( 227 ) . chr( 207 ) . chr( 211 ) );
    }

    private function putFonts() {
        foreach ( $this->fonts as $key => $font ) {
            $this->newObj();
            $font['objN'] = $this->n;
            $this->fonts[ $key ]['objN'] = $this->n;
            $this->out( '<<' );
            $this->out( '/Type /Font' );
            $this->out( '/Subtype /Type1' );
            $this->out( '/BaseFont /' . $font['name'] );
            $this->out( '/Encoding /WinAnsiEncoding' );
            $this->out( '>>' );
            $this->out( 'endobj' );
        }
    }

    private function putImages() {
        foreach ( $this->images as $key => $img ) {
            $this->newObj();
            $this->images[ $key ]['objN'] = $this->n;
            $this->out( '<<' );
            $this->out( '/Type /XObject' );
            $this->out( '/Subtype /Image' );
            $this->out( '/Width ' . $img['w'] );
            $this->out( '/Height ' . $img['h'] );
            $this->out( '/ColorSpace /' . $img['cs'] );
            $this->out( '/BitsPerComponent ' . $img['bpc'] );
            $this->out( '/Filter /' . $img['f'] );
            $this->out( '/Length ' . strlen( $img['data'] ) );
            $this->out( '>>' );
            $this->putRawStream( $img['data'] );
            $this->out( 'endobj' );

            // If PNG with alpha, add soft mask
            if ( ! empty( $img['smask'] ) ) {
                $this->newObj();
                $smaskData = gzcompress( $img['smask'] );
                $this->out( '<<' );
                $this->out( '/Type /XObject' );
                $this->out( '/Subtype /Image' );
                $this->out( '/Width ' . $img['w'] );
                $this->out( '/Height ' . $img['h'] );
                $this->out( '/ColorSpace /DeviceGray' );
                $this->out( '/BitsPerComponent 8' );
                $this->out( '/Filter /FlateDecode' );
                $this->out( '/Length ' . strlen( $smaskData ) );
                $this->out( '>>' );
                $this->putRawStream( $smaskData );
                $this->out( 'endobj' );
            }
        }
    }

    private function putPages() {
        $pageObjNums = array();

        // Create content streams for each page
        $contentObjNums = array();
        for ( $i = 0; $i <= $this->currentPage; $i++ ) {
            $stream = isset( $this->pageContents[ $i ] ) ? $this->pageContents[ $i ] : '';
            $compressed = function_exists( 'gzcompress' ) ? gzcompress( $stream ) : $stream;
            $filter = function_exists( 'gzcompress' ) ? '/Filter /FlateDecode' : '';

            $this->newObj();
            $contentObjNums[ $i ] = $this->n;
            $this->out( '<<' );
            if ( $filter ) {
                $this->out( $filter );
            }
            $this->out( '/Length ' . strlen( $compressed ) );
            $this->out( '>>' );
            $this->putRawStream( $compressed );
            $this->out( 'endobj' );
        }

        // Create page objects
        $pagesObjN = $this->n + 1 + ( $this->currentPage + 1 ); // Reserved for Pages object

        for ( $i = 0; $i <= $this->currentPage; $i++ ) {
            $this->newObj();
            $pageObjNums[ $i ] = $this->n;
            $this->out( '<<' );
            $this->out( '/Type /Page' );
            $this->out( '/Parent ' . $pagesObjN . ' 0 R' );
            $this->out( sprintf( '/MediaBox [0 0 %.2f %.2f]', $this->pageWidth, $this->pageHeight ) );
            $this->out( '/Contents ' . $contentObjNums[ $i ] . ' 0 R' );
            $this->out( '/Resources <<' );

            // Fonts
            if ( ! empty( $this->fonts ) ) {
                $this->out( '/Font <<' );
                foreach ( $this->fonts as $font ) {
                    $this->out( '/F' . $font['i'] . ' ' . $font['objN'] . ' 0 R' );
                }
                $this->out( '>>' );
            }

            // Images
            if ( ! empty( $this->images ) ) {
                $this->out( '/XObject <<' );
                foreach ( $this->images as $img ) {
                    $this->out( '/I' . $img['i'] . ' ' . $img['objN'] . ' 0 R' );
                }
                $this->out( '>>' );
            }

            $this->out( '>>' );
            $this->out( '>>' );
            $this->out( 'endobj' );
        }

        // Pages object (parent)
        $this->newObj();
        $this->out( '<<' );
        $this->out( '/Type /Pages' );
        $kids = array();
        foreach ( $pageObjNums as $num ) {
            $kids[] = $num . ' 0 R';
        }
        $this->out( '/Kids [' . implode( ' ', $kids ) . ']' );
        $this->out( '/Count ' . count( $pageObjNums ) );
        $this->out( '>>' );
        $this->out( 'endobj' );

        $this->pagesObjN = $this->n;
    }

    private function putResources() {
        // No separate resources dict needed — embedded in pages
    }

    private function putInfo() {
        $this->newObj();
        $this->out( '<<' );
        $this->out( '/Producer (SSCV Certificate System)' );
        $this->out( '/CreationDate (D:' . date( 'YmdHis' ) . ')' );
        $this->out( '>>' );
        $this->out( 'endobj' );
    }

    private function putCatalog() {
        $this->newObj();
        $this->out( '<<' );
        $this->out( '/Type /Catalog' );
        $this->out( '/Pages ' . $this->pagesObjN . ' 0 R' );
        $this->out( '>>' );
        $this->out( 'endobj' );
    }

    private function newObj() {
        $this->n++;
        $this->offsets[ $this->n ] = strlen( $this->buffer );
        $this->out( $this->n . ' 0 obj' );
    }

    private function out( $s ) {
        $this->buffer .= $s . "\n";
    }

    private function putRawStream( $data ) {
        $this->buffer .= "stream\n" . $data . "\nendstream\n";
    }

    private function escapeText( $s ) {
        $s = str_replace( '\\', '\\\\', $s );
        $s = str_replace( '(', '\\(', $s );
        $s = str_replace( ')', '\\)', $s );
        $s = str_replace( "\r", '\\r', $s );
        // Convert UTF-8 to Windows-1252 for standard PDF fonts
        if ( function_exists( 'mb_convert_encoding' ) ) {
            $s = @mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' );
        } elseif ( function_exists( 'iconv' ) ) {
            $conv = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s );
            if ( $conv !== false ) {
                $s = $conv;
            }
        }
        return $s;
    }

    /**
     * Parse image file and return image info array.
     */
    private function parseImage( $file ) {
        $info = @getimagesize( $file );
        if ( ! $info ) {
            return false;
        }

        $type = $info[2]; // IMAGETYPE_*

        switch ( $type ) {
            case IMAGETYPE_JPEG:
                return $this->parseJPEG( $file, $info );
            case IMAGETYPE_PNG:
                return $this->parsePNG( $file, $info );
            default:
                // Try converting with GD
                if ( function_exists( 'imagecreatefromstring' ) ) {
                    return $this->parseWithGD( $file, $info );
                }
                return false;
        }
    }

    private function parseJPEG( $file, $info ) {
        $data = file_get_contents( $file );
        if ( $data === false ) {
            return false;
        }

        $cs = 'DeviceRGB';
        if ( isset( $info['channels'] ) && $info['channels'] == 4 ) {
            $cs = 'DeviceCMYK';
        } elseif ( isset( $info['channels'] ) && $info['channels'] == 1 ) {
            $cs = 'DeviceGray';
        }

        return array(
            'w'    => $info[0],
            'h'    => $info[1],
            'cs'   => $cs,
            'bpc'  => isset( $info['bits'] ) ? $info['bits'] : 8,
            'f'    => 'DCTDecode',
            'data' => $data,
        );
    }

    private function parsePNG( $file, $info ) {
        // For PNG, convert to JPEG using GD to avoid complex PNG stream parsing
        if ( ! function_exists( 'imagecreatefrompng' ) ) {
            return false;
        }

        $im = @imagecreatefrompng( $file );
        if ( ! $im ) {
            return false;
        }

        // Create a white background for transparency
        $width  = imagesx( $im );
        $height = imagesy( $im );
        $bg = imagecreatetruecolor( $width, $height );
        $white = imagecolorallocate( $bg, 255, 255, 255 );
        imagefill( $bg, 0, 0, $white );
        imagecopy( $bg, $im, 0, 0, 0, 0, $width, $height );

        // Save as JPEG to temp
        $tmp = tempnam( sys_get_temp_dir(), 'sscv_pdf_' );
        imagejpeg( $bg, $tmp, 90 );
        imagedestroy( $im );
        imagedestroy( $bg );

        $result = $this->parseJPEG( $tmp, getimagesize( $tmp ) );
        @unlink( $tmp );

        return $result;
    }

    private function parseWithGD( $file, $info ) {
        $im = @imagecreatefromstring( file_get_contents( $file ) );
        if ( ! $im ) {
            return false;
        }

        $width  = imagesx( $im );
        $height = imagesy( $im );
        $bg = imagecreatetruecolor( $width, $height );
        $white = imagecolorallocate( $bg, 255, 255, 255 );
        imagefill( $bg, 0, 0, $white );
        imagecopy( $bg, $im, 0, 0, 0, 0, $width, $height );

        $tmp = tempnam( sys_get_temp_dir(), 'sscv_pdf_' );
        imagejpeg( $bg, $tmp, 90 );
        imagedestroy( $im );
        imagedestroy( $bg );

        $result = $this->parseJPEG( $tmp, getimagesize( $tmp ) );
        @unlink( $tmp );

        return $result;
    }

    /**
     * Finalize page before moving to next (called internally).
     */
    public function endPage() {
        if ( $this->currentPage >= 0 ) {
            $this->pageContents[ $this->currentPage ] = $this->pageStream;
            $this->pageStream = '';
        }
    }

    /**
     * Multi-line text within a box (word-wrap).
     */
    public function multiLineText( $x, $y, $maxWidth, $text, $lineHeight = null ) {
        if ( $lineHeight === null ) {
            $lineHeight = $this->currentFontSize * 1.4;
        }

        $words = explode( ' ', $text );
        $line  = '';
        $cy    = $y;

        foreach ( $words as $word ) {
            $testLine = $line ? $line . ' ' . $word : $word;
            $testWidth = $this->getStringWidth( $testLine );

            if ( $testWidth > $maxWidth && $line !== '' ) {
                $this->text( $x, $cy, $line );
                $cy  += $lineHeight;
                $line = $word;
            } else {
                $line = $testLine;
            }
        }

        if ( $line !== '' ) {
            $this->text( $x, $cy, $line );
            $cy += $lineHeight;
        }

        return $cy;
    }

    /**
     * Centered multi-line text.
     */
    public function centeredMultiLine( $y, $maxWidth, $text, $lineHeight = null ) {
        if ( $lineHeight === null ) {
            $lineHeight = $this->currentFontSize * 1.4;
        }

        $words = explode( ' ', $text );
        $line  = '';
        $cy    = $y;

        foreach ( $words as $word ) {
            $testLine = $line ? $line . ' ' . $word : $word;
            $testWidth = $this->getStringWidth( $testLine );

            if ( $testWidth > $maxWidth && $line !== '' ) {
                $this->centeredText( $cy, $line );
                $cy  += $lineHeight;
                $line = $word;
            } else {
                $line = $testLine;
            }
        }

        if ( $line !== '' ) {
            $this->centeredText( $cy, $line );
            $cy += $lineHeight;
        }

        return $cy;
    }
}
