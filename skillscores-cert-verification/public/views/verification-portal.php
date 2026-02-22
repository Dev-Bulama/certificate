<?php if ( ! defined( 'ABSPATH' ) ) exit;
$settings = SSCV_Helpers::get_settings();
?>
<div class="sscv-verify-wrapper <?php echo esc_attr( $atts['class'] ?? '' ); ?>">
    <div class="sscv-verify-card">
        <div class="sscv-verify-header">
            <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="" class="sscv-form-logo" />
            <?php endif; ?>
            <h2><?php esc_html_e( 'Certificate Verification Portal', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Verify the authenticity of a certificate by searching below.', 'skillscores-cert' ); ?></p>
        </div>

        <form id="sscv-verification-form">
            <input type="hidden" name="action" value="sscv_verify_certificate" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_nonce' ); ?>" />

            <div class="sscv-search-bar">
                <select name="search_type" id="sscv-search-type">
                    <option value="certificate_id"><?php esc_html_e( 'Certificate ID', 'skillscores-cert' ); ?></option>
                    <option value="student_id"><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></option>
                    <option value="student_name"><?php esc_html_e( 'Student Name', 'skillscores-cert' ); ?></option>
                </select>
                <input type="text" name="search_value" id="sscv-search-value" required
                       placeholder="<?php esc_attr_e( 'Enter Certificate ID...', 'skillscores-cert' ); ?>" />
                <button type="submit" class="sscv-btn sscv-btn-primary" id="sscv-verify-btn">
                    <span class="sscv-btn-text"><?php esc_html_e( 'Verify', 'skillscores-cert' ); ?></span>
                    <span class="sscv-btn-loading" style="display:none;"><?php esc_html_e( 'Searching...', 'skillscores-cert' ); ?></span>
                </button>
            </div>
        </form>

        <div id="sscv-verify-message" class="sscv-message" style="display:none;"></div>

        <!-- Results Area -->
        <div id="sscv-verify-results" style="display:none;"></div>
    </div>
</div>

<!-- Verification Result Template -->
<script type="text/html" id="sscv-verify-result-tpl">
<div class="sscv-verify-result">
    <div class="sscv-verified-badge">
        <span class="sscv-checkmark">&#10003;</span>
        <span><?php esc_html_e( 'VERIFIED', 'skillscores-cert' ); ?></span>
    </div>

    <div class="sscv-result-grid">
        <div class="sscv-result-photo">
            <img src="{{passport_url}}" alt="" class="sscv-result-passport" />
        </div>
        <div class="sscv-result-details">
            <table class="sscv-detail-table">
                <tr><th><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th><td><span class="sscv-badge sscv-badge-approved"><?php esc_html_e( 'Verified', 'skillscores-cert' ); ?></span></td></tr>
                <tr><th><?php esc_html_e( 'Student Name', 'skillscores-cert' ); ?></th><td>{{student_name}}</td></tr>
                <tr><th><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></th><td>{{student_id}}</td></tr>
                <tr><th><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></th><td>{{course_title}}</td></tr>
                <tr><th><?php esc_html_e( 'Grade', 'skillscores-cert' ); ?></th><td>{{grade}}</td></tr>
                <tr><th><?php esc_html_e( 'Date Completed', 'skillscores-cert' ); ?></th><td>{{date_completed}}</td></tr>
                <tr><th><?php esc_html_e( 'Date Issued', 'skillscores-cert' ); ?></th><td>{{date_issued}}</td></tr>
                <tr><th><?php esc_html_e( 'Certificate ID', 'skillscores-cert' ); ?></th><td><code>{{certificate_id}}</code></td></tr>
                <tr><th><?php esc_html_e( 'Institution', 'skillscores-cert' ); ?></th><td>{{institution_name}}</td></tr>
            </table>
        </div>
        <div class="sscv-result-qr">
            <img src="{{qr_code_url}}" alt="QR Code" class="sscv-result-qr-img" />
            <div class="sscv-result-stamp">
                <img src="{{stamp_url}}" alt="Stamp" />
            </div>
        </div>
    </div>

    <div class="sscv-result-actions">
        {{#if certificate_url}}
        <a href="{{certificate_url}}" target="_blank" class="sscv-btn sscv-btn-primary"><?php esc_html_e( 'View Certificate', 'skillscores-cert' ); ?></a>
        {{/if}}
        {{#if pdf_url}}
        <a href="{{pdf_url}}" target="_blank" class="sscv-btn sscv-btn-secondary"><?php esc_html_e( 'Download Certificate', 'skillscores-cert' ); ?></a>
        {{/if}}
    </div>
</div>
</script>
