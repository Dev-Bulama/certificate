<?php
/**
 * Standalone verification page (for QR code scanning).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$cert_id = isset( $cert_id ) ? $cert_id : '';
$settings = SSCV_Helpers::get_settings();
$certificate = null;

if ( ! empty( $cert_id ) ) {
    $certificate = SSCV_Helpers::get_certificate( $cert_id );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc_html( $settings['institution_name'] ); ?> - <?php esc_html_e( 'Certificate Verification', 'skillscores-cert' ); ?></title>
    <?php wp_head(); ?>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #333; }
        .sscv-verify-page { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .sscv-verify-page-header { text-align: center; margin-bottom: 30px; }
        .sscv-verify-page-header img { max-height: 70px; margin-bottom: 10px; }
        .sscv-verify-page-header h1 { color: <?php echo esc_attr( $settings['theme_color'] ); ?>; font-size: 24px; margin: 10px 0 5px; }
        .sscv-verify-page-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 40px; }
        .sscv-verified-status { text-align: center; padding: 20px; margin-bottom: 20px; }
        .sscv-verified-status.valid { background: #f0fdf4; border-radius: 8px; }
        .sscv-verified-status.invalid { background: #fef2f2; border-radius: 8px; }
        .sscv-verified-status .icon { font-size: 48px; }
        .sscv-verified-status.valid .icon { color: #16a34a; }
        .sscv-verified-status.invalid .icon { color: #dc2626; }
        .sscv-verified-status h2 { margin: 10px 0 5px; }
        .sscv-cert-detail { display: flex; gap: 30px; flex-wrap: wrap; }
        .sscv-cert-detail-photo { flex: 0 0 120px; text-align: center; }
        .sscv-cert-detail-photo img { width: 100px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; }
        .sscv-cert-detail-info { flex: 1; min-width: 250px; }
        .sscv-cert-detail-info table { width: 100%; border-collapse: collapse; }
        .sscv-cert-detail-info th { text-align: left; padding: 8px 12px 8px 0; color: #6b7280; font-weight: 500; width: 140px; border-bottom: 1px solid #f3f4f6; }
        .sscv-cert-detail-info td { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .sscv-cert-detail-qr { flex: 0 0 130px; text-align: center; }
        .sscv-cert-detail-qr img { max-width: 120px; }
        .sscv-cert-actions { text-align: center; margin-top: 25px; }
        .sscv-cert-actions a { display: inline-block; padding: 10px 24px; margin: 5px; border-radius: 6px; text-decoration: none; font-weight: 500; }
        .sscv-cert-actions .btn-primary { background: <?php echo esc_attr( $settings['theme_color'] ); ?>; color: #fff; }
        .sscv-cert-actions .btn-secondary { background: #f3f4f6; color: #374151; }
        .sscv-search-form { display: flex; gap: 10px; max-width: 500px; margin: 0 auto; }
        .sscv-search-form input { flex: 1; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .sscv-search-form button { padding: 10px 20px; background: <?php echo esc_attr( $settings['theme_color'] ); ?>; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        @media (max-width: 600px) { .sscv-cert-detail { flex-direction: column; align-items: center; text-align: center; } .sscv-cert-detail-info th { width: 100px; } }
    </style>
</head>
<body>
    <div class="sscv-verify-page">
        <div class="sscv-verify-page-header">
            <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="<?php echo esc_attr( $settings['institution_name'] ); ?>" />
            <?php endif; ?>
            <h1><?php echo esc_html( $settings['institution_name'] ); ?></h1>
            <p><?php esc_html_e( 'Certificate Verification System', 'skillscores-cert' ); ?></p>
        </div>

        <div class="sscv-verify-page-card">
            <?php if ( ! empty( $cert_id ) && $certificate && $certificate->status === 'approved' ) : ?>
                <!-- Valid Certificate -->
                <div class="sscv-verified-status valid">
                    <div class="icon">&#10003;</div>
                    <h2><?php esc_html_e( 'Certificate Verified', 'skillscores-cert' ); ?></h2>
                    <p><?php esc_html_e( 'This certificate is authentic and has been issued by our institution.', 'skillscores-cert' ); ?></p>
                </div>

                <div class="sscv-cert-detail">
                    <div class="sscv-cert-detail-photo">
                        <?php if ( ! empty( $certificate->passport_url ) ) : ?>
                            <img src="<?php echo esc_url( $certificate->passport_url ); ?>" alt="Photo" />
                        <?php endif; ?>
                        <?php if ( ! empty( $settings['stamp_url'] ) ) : ?>
                            <img src="<?php echo esc_url( $settings['stamp_url'] ); ?>" alt="Stamp" style="width:80px;height:auto;margin-top:10px;opacity:0.7;" />
                        <?php endif; ?>
                    </div>
                    <div class="sscv-cert-detail-info">
                        <table>
                            <tr><th><?php esc_html_e( 'Student Name', 'skillscores-cert' ); ?></th><td><strong><?php echo esc_html( $certificate->full_name ); ?></strong></td></tr>
                            <tr><th><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></th><td><?php echo esc_html( $certificate->course_title ?? '' ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Grade', 'skillscores-cert' ); ?></th><td><?php echo esc_html( $certificate->grade ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Date Completed', 'skillscores-cert' ); ?></th><td><?php echo $certificate->date_completed ? esc_html( SSCV_Helpers::format_date( $certificate->date_completed ) ) : '—'; ?></td></tr>
                            <tr><th><?php esc_html_e( 'Date Issued', 'skillscores-cert' ); ?></th><td><?php echo $certificate->date_issued ? esc_html( SSCV_Helpers::format_date( $certificate->date_issued ) ) : '—'; ?></td></tr>
                            <tr><th><?php esc_html_e( 'Certificate ID', 'skillscores-cert' ); ?></th><td><code><?php echo esc_html( $certificate->certificate_id ); ?></code></td></tr>
                        </table>
                    </div>
                    <div class="sscv-cert-detail-qr">
                        <?php if ( ! empty( $certificate->qr_code_url ) ) : ?>
                            <img src="<?php echo esc_url( $certificate->qr_code_url ); ?>" alt="QR Code" />
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sscv-cert-actions">
                    <?php if ( ! empty( $certificate->certificate_url ) ) : ?>
                        <a href="<?php echo esc_url( $certificate->certificate_url ); ?>" target="_blank" class="btn-primary"><?php esc_html_e( 'View Certificate', 'skillscores-cert' ); ?></a>
                    <?php endif; ?>
                    <?php if ( ! empty( $certificate->pdf_url ) ) : ?>
                        <a href="<?php echo esc_url( $certificate->pdf_url ); ?>" target="_blank" class="btn-secondary"><?php esc_html_e( 'Download PDF', 'skillscores-cert' ); ?></a>
                    <?php endif; ?>
                </div>

            <?php elseif ( ! empty( $cert_id ) ) : ?>
                <!-- Invalid / Not Found -->
                <div class="sscv-verified-status invalid">
                    <div class="icon">&#10007;</div>
                    <h2><?php esc_html_e( 'Certificate Not Found', 'skillscores-cert' ); ?></h2>
                    <p><?php esc_html_e( 'No valid certificate was found with the provided ID. Please check the ID and try again.', 'skillscores-cert' ); ?></p>
                </div>
            <?php else : ?>
                <h3 style="text-align:center;margin-bottom:20px;"><?php esc_html_e( 'Enter a Certificate ID to verify', 'skillscores-cert' ); ?></h3>
            <?php endif; ?>

            <!-- Search form -->
            <form class="sscv-search-form" method="get" action="<?php echo esc_url( home_url( '/verify/' ) ); ?>">
                <input type="text" name="cert_id" placeholder="<?php esc_attr_e( 'Enter Certificate ID...', 'skillscores-cert' ); ?>" value="<?php echo esc_attr( $cert_id ); ?>" />
                <button type="submit"><?php esc_html_e( 'Verify', 'skillscores-cert' ); ?></button>
            </form>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
