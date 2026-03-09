<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Plugin Settings', 'skillscores-cert' ); ?></h1>

    <form id="sscv-settings-form">
        <input type="hidden" name="action" value="sscv_save_settings" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_admin_nonce' ); ?>" />

        <!-- Institution Settings -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Institution Settings', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="institution_name"><?php esc_html_e( 'Institution Name', 'skillscores-cert' ); ?></label>
                <input type="text" name="institution_name" id="institution_name" class="regular-text"
                       value="<?php echo esc_attr( $settings['institution_name'] ); ?>" />
            </div>

            <div class="sscv-field">
                <label><?php esc_html_e( 'Institution Logo', 'skillscores-cert' ); ?></label>
                <div class="sscv-media-field">
                    <input type="text" name="logo_url" id="logo_url" class="regular-text"
                           value="<?php echo esc_url( $settings['logo_url'] ); ?>" />
                    <button type="button" class="button sscv-upload-media" data-target="logo_url"><?php esc_html_e( 'Upload', 'skillscores-cert' ); ?></button>
                </div>
                <?php if ( $settings['logo_url'] ) : ?>
                    <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="" class="sscv-preview-image" style="max-height:60px;margin-top:8px;" />
                <?php endif; ?>
            </div>

            <div class="sscv-field">
                <label><?php esc_html_e( 'Certificate Signature Image', 'skillscores-cert' ); ?></label>
                <div class="sscv-media-field">
                    <input type="text" name="signature_url" id="signature_url" class="regular-text"
                           value="<?php echo esc_url( $settings['signature_url'] ); ?>" />
                    <button type="button" class="button sscv-upload-media" data-target="signature_url"><?php esc_html_e( 'Upload', 'skillscores-cert' ); ?></button>
                </div>
                <?php if ( $settings['signature_url'] ) : ?>
                    <img src="<?php echo esc_url( $settings['signature_url'] ); ?>" alt="" class="sscv-preview-image" style="max-height:50px;margin-top:8px;" />
                <?php endif; ?>
            </div>

            <div class="sscv-field">
                <label><?php esc_html_e( 'Institution Stamp Image', 'skillscores-cert' ); ?></label>
                <div class="sscv-media-field">
                    <input type="text" name="stamp_url" id="stamp_url" class="regular-text"
                           value="<?php echo esc_url( $settings['stamp_url'] ); ?>" />
                    <button type="button" class="button sscv-upload-media" data-target="stamp_url"><?php esc_html_e( 'Upload', 'skillscores-cert' ); ?></button>
                </div>
                <?php if ( $settings['stamp_url'] ) : ?>
                    <img src="<?php echo esc_url( $settings['stamp_url'] ); ?>" alt="" class="sscv-preview-image" style="max-height:60px;margin-top:8px;" />
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Fields Settings -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Application Form Fields', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="enable_student_id_field">
                    <input type="checkbox" name="enable_student_id_field" id="enable_student_id_field" value="1"
                           <?php checked( $settings['student_id_field_enabled'], '1' ); ?> />
                    <?php esc_html_e( 'Enable Student ID field on the certificate application form', 'skillscores-cert' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'When disabled, the Student ID field will be hidden from the application form.', 'skillscores-cert' ); ?></p>
            </div>

            <div class="sscv-field">
                <label for="enable_grade_field">
                    <input type="checkbox" name="enable_grade_field" id="enable_grade_field" value="1"
                           <?php checked( $settings['grade_field_enabled'], '1' ); ?> />
                    <?php esc_html_e( 'Enable Grade / Score field on the certificate application form', 'skillscores-cert' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'When disabled, the Grade field will be hidden from the application form and certificate templates.', 'skillscores-cert' ); ?></p>
            </div>

            <div class="sscv-field">
                <label for="enable_passport_field">
                    <input type="checkbox" name="enable_passport_field" id="enable_passport_field" value="1"
                           <?php checked( $settings['passport_field_enabled'], '1' ); ?> />
                    <?php esc_html_e( 'Enable Passport Photograph field on the certificate application form', 'skillscores-cert' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'When disabled, the Passport Photograph upload will be hidden from the application form and certificate templates.', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <!-- Certificate Template Type -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Certificate Template Mode', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="certificate_template_mode"><?php esc_html_e( 'Template Mode', 'skillscores-cert' ); ?></label>
                <select name="certificate_template_mode" id="certificate_template_mode">
                    <option value="html" <?php selected( $settings['certificate_template_mode'], 'html' ); ?>><?php esc_html_e( 'HTML-Based Templates', 'skillscores-cert' ); ?></option>
                    <option value="image" <?php selected( $settings['certificate_template_mode'], 'image' ); ?>><?php esc_html_e( 'Image-Based Templates', 'skillscores-cert' ); ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'Choose whether to use HTML/CSS templates or image-based templates with variable positioning for certificate generation.', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <!-- Certificate Expiry -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Certificate Validity', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="enable_certificate_expiry">
                    <input type="checkbox" name="enable_certificate_expiry" id="enable_certificate_expiry" value="1"
                           <?php checked( $settings['certificate_expiry_enabled'], '1' ); ?> />
                    <?php esc_html_e( 'Enable certificate expiry', 'skillscores-cert' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'When enabled, certificates will expire after the specified period.', 'skillscores-cert' ); ?></p>
            </div>

            <div class="sscv-field">
                <label for="certificate_expiry_months"><?php esc_html_e( 'Validity Period (months)', 'skillscores-cert' ); ?></label>
                <input type="number" name="certificate_expiry_months" id="certificate_expiry_months" min="1" max="120"
                       value="<?php echo esc_attr( $settings['certificate_expiry_months'] ); ?>" />
                <p class="description"><?php esc_html_e( 'Number of months a certificate remains valid after issue date.', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <!-- Appearance Settings -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Appearance', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field-row">
                <div class="sscv-field">
                    <label for="theme_color"><?php esc_html_e( 'Theme Color', 'skillscores-cert' ); ?></label>
                    <input type="text" name="theme_color" id="theme_color" class="sscv-color-picker"
                           value="<?php echo esc_attr( $settings['theme_color'] ); ?>" />
                </div>
                <div class="sscv-field">
                    <label for="accent_color"><?php esc_html_e( 'Accent Color', 'skillscores-cert' ); ?></label>
                    <input type="text" name="accent_color" id="accent_color" class="sscv-color-picker"
                           value="<?php echo esc_attr( $settings['accent_color'] ); ?>" />
                </div>
            </div>

            <div class="sscv-field">
                <label for="font_family"><?php esc_html_e( 'Font Family', 'skillscores-cert' ); ?></label>
                <select name="font_family" id="font_family">
                    <option value="Georgia, serif" <?php selected( $settings['font_family'], 'Georgia, serif' ); ?>>Georgia (Serif)</option>
                    <option value="'Times New Roman', serif" <?php selected( $settings['font_family'], "'Times New Roman', serif" ); ?>>Times New Roman</option>
                    <option value="Arial, sans-serif" <?php selected( $settings['font_family'], 'Arial, sans-serif' ); ?>>Arial (Sans-serif)</option>
                    <option value="'Segoe UI', sans-serif" <?php selected( $settings['font_family'], "'Segoe UI', sans-serif" ); ?>>Segoe UI</option>
                    <option value="'Helvetica Neue', sans-serif" <?php selected( $settings['font_family'], "'Helvetica Neue', sans-serif" ); ?>>Helvetica Neue</option>
                    <option value="Verdana, sans-serif" <?php selected( $settings['font_family'], 'Verdana, sans-serif' ); ?>>Verdana</option>
                </select>
            </div>

            <div class="sscv-field">
                <label for="qr_size"><?php esc_html_e( 'QR Code Size (px)', 'skillscores-cert' ); ?></label>
                <input type="number" name="qr_size" id="qr_size" min="50" max="500"
                       value="<?php echo esc_attr( $settings['qr_size'] ); ?>" />
            </div>
        </div>

        <!-- Email Settings -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Email Notification', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="email_subject"><?php esc_html_e( 'Email Subject', 'skillscores-cert' ); ?></label>
                <input type="text" name="email_subject" id="email_subject" class="large-text"
                       value="<?php echo esc_attr( $settings['email_subject'] ); ?>" />
                <p class="description"><?php esc_html_e( 'Use placeholders: {{student_name}}, {{course_title}}, {{certificate_id}}', 'skillscores-cert' ); ?></p>
            </div>

            <div class="sscv-field">
                <label for="email_body"><?php esc_html_e( 'Email Body', 'skillscores-cert' ); ?></label>
                <textarea name="email_body" id="email_body" rows="10" class="large-text"><?php echo esc_textarea( $settings['email_body'] ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Available: {{student_name}}, {{course_title}}, {{certificate_id}}, {{date_issued}}, {{grade}}, {{institution_name}}, {{verification_url}}', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Security (reCAPTCHA v3)', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="recaptcha_site_key"><?php esc_html_e( 'reCAPTCHA Site Key', 'skillscores-cert' ); ?></label>
                <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="regular-text"
                       value="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>" />
            </div>

            <div class="sscv-field">
                <label for="recaptcha_secret_key"><?php esc_html_e( 'reCAPTCHA Secret Key', 'skillscores-cert' ); ?></label>
                <input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" class="regular-text"
                       value="<?php echo esc_attr( $settings['recaptcha_secret_key'] ); ?>" />
            </div>

            <p class="description"><?php esc_html_e( 'Leave empty to disable reCAPTCHA protection.', 'skillscores-cert' ); ?></p>
        </div>

        <!-- Project Categories -->
        <div class="sscv-card">
            <h2><?php esc_html_e( 'Project Categories', 'skillscores-cert' ); ?></h2>

            <div class="sscv-field">
                <label for="project_categories"><?php esc_html_e( 'Categories (one per line)', 'skillscores-cert' ); ?></label>
                <textarea name="project_categories" id="project_categories" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'sscv_project_categories', "Web Development\nMobile App\nData Science\nCybersecurity\nAI/ML\nDesign\nOther" ) ); ?></textarea>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save All Settings', 'skillscores-cert' ); ?></button>
        </p>
    </form>
</div>
