<?php if ( ! defined( 'ABSPATH' ) ) exit;
$categories = SSCV_Helpers::get_project_categories();
$recaptcha_key = get_option( 'sscv_recaptcha_site_key', '' );
?>
<div class="sscv-form-wrapper <?php echo esc_attr( $atts['class'] ?? '' ); ?>">
    <div class="sscv-form-card">
        <div class="sscv-form-header">
            <h2><?php esc_html_e( 'Submit Your Project', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Showcase your project to potential employers and the public.', 'skillscores-cert' ); ?></p>
        </div>

        <form id="sscv-project-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="sscv_submit_project" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_nonce' ); ?>" />

            <div class="sscv-form-grid">
                <div class="sscv-form-group">
                    <label for="sscv-proj-student-id"><?php esc_html_e( 'Student ID *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-proj-student-id" name="student_id_number" required />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-name"><?php esc_html_e( 'Full Name *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-proj-name" name="student_name" required />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-title"><?php esc_html_e( 'Project Title *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-proj-title" name="project_title" required />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-category"><?php esc_html_e( 'Category', 'skillscores-cert' ); ?></label>
                    <select id="sscv-proj-category" name="category">
                        <option value=""><?php esc_html_e( '— Select —', 'skillscores-cert' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-website"><?php esc_html_e( 'Website URL', 'skillscores-cert' ); ?></label>
                    <input type="url" id="sscv-proj-website" name="website_url" placeholder="https://" />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-github"><?php esc_html_e( 'GitHub Repository', 'skillscores-cert' ); ?></label>
                    <input type="url" id="sscv-proj-github" name="github_url" placeholder="https://github.com/..." />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-social"><?php esc_html_e( 'Social Media Link', 'skillscores-cert' ); ?></label>
                    <input type="url" id="sscv-proj-social" name="social_url" placeholder="https://" />
                </div>
            </div>

            <div class="sscv-form-group sscv-form-full">
                <label for="sscv-proj-desc"><?php esc_html_e( 'Project Description', 'skillscores-cert' ); ?></label>
                <textarea id="sscv-proj-desc" name="description" rows="4" placeholder="<?php esc_attr_e( 'Describe your project...', 'skillscores-cert' ); ?>"></textarea>
            </div>

            <div class="sscv-form-grid">
                <div class="sscv-form-group">
                    <label for="sscv-proj-image"><?php esc_html_e( 'Project Image', 'skillscores-cert' ); ?></label>
                    <input type="file" id="sscv-proj-image" name="project_image" accept="image/*" />
                    <p class="sscv-file-hint"><?php esc_html_e( 'JPG, PNG, or WebP. Max 2MB', 'skillscores-cert' ); ?></p>
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-proj-cv"><?php esc_html_e( 'CV Upload', 'skillscores-cert' ); ?></label>
                    <input type="file" id="sscv-proj-cv" name="cv_file" accept=".pdf,.doc,.docx" />
                    <p class="sscv-file-hint"><?php esc_html_e( 'PDF, DOC, or DOCX. Max 5MB', 'skillscores-cert' ); ?></p>
                </div>
            </div>

            <?php if ( ! empty( $recaptcha_key ) ) : ?>
                <input type="hidden" name="recaptcha_token" id="sscv-project-recaptcha-token" />
            <?php endif; ?>

            <div class="sscv-form-group sscv-form-full">
                <button type="submit" class="sscv-btn sscv-btn-primary" id="sscv-submit-project">
                    <span class="sscv-btn-text"><?php esc_html_e( 'Submit Project', 'skillscores-cert' ); ?></span>
                    <span class="sscv-btn-loading" style="display:none;"><?php esc_html_e( 'Submitting...', 'skillscores-cert' ); ?></span>
                </button>
            </div>
        </form>

        <div id="sscv-project-message" class="sscv-message" style="display:none;"></div>
    </div>
</div>
