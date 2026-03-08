<?php if ( ! defined( 'ABSPATH' ) ) exit;
$courses = SSCV_Helpers::get_courses_dropdown();
$recaptcha_key = get_option( 'sscv_recaptcha_site_key', '' );
$settings = SSCV_Helpers::get_settings();
?>
<div class="sscv-form-wrapper <?php echo esc_attr( $atts['class'] ?? '' ); ?>">
    <div class="sscv-form-card">
        <div class="sscv-form-header">
            <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="" class="sscv-form-logo" />
            <?php endif; ?>
            <h2><?php esc_html_e( 'Certificate Application', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Fill in the form below to apply for your certificate.', 'skillscores-cert' ); ?></p>
        </div>

        <form id="sscv-application-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="sscv_submit_application" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_nonce' ); ?>" />

            <div class="sscv-form-grid">
                <div class="sscv-form-group">
                    <label for="sscv-full-name"><?php esc_html_e( 'Full Name *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-full-name" name="full_name" required placeholder="<?php esc_attr_e( 'Enter your full name', 'skillscores-cert' ); ?>" />
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-email"><?php esc_html_e( 'Email Address *', 'skillscores-cert' ); ?></label>
                    <input type="email" id="sscv-email" name="email" required placeholder="<?php esc_attr_e( 'Enter your email', 'skillscores-cert' ); ?>" />
                </div>

                <?php if ( get_option( 'sscv_student_id_field_enabled', '1' ) === '1' ) : ?>
                <div class="sscv-form-group">
                    <label for="sscv-student-id"><?php esc_html_e( 'Student ID *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-student-id" name="student_id_number" required placeholder="<?php esc_attr_e( 'Enter your Student ID', 'skillscores-cert' ); ?>" />
                </div>
                <?php endif; ?>

                <div class="sscv-form-group">
                    <label for="sscv-course"><?php esc_html_e( 'Course Taken *', 'skillscores-cert' ); ?></label>
                    <select id="sscv-course" name="course_id" required>
                        <option value=""><?php esc_html_e( '— Select Course —', 'skillscores-cert' ); ?></option>
                        <?php foreach ( $courses as $course ) : ?>
                            <option value="<?php echo esc_attr( $course->id ); ?>">
                                <?php echo esc_html( $course->course_title ); ?>
                                <?php if ( $course->course_code ) echo ' (' . esc_html( $course->course_code ) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sscv-form-group">
                    <label for="sscv-date-completed"><?php esc_html_e( 'Date Completed', 'skillscores-cert' ); ?></label>
                    <input type="date" id="sscv-date-completed" name="date_completed" />
                </div>

                <?php if ( get_option( 'sscv_grade_field_enabled', '1' ) === '1' ) : ?>
                <div class="sscv-form-group">
                    <label for="sscv-grade"><?php esc_html_e( 'Grade / Score', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-grade" name="grade" placeholder="<?php esc_attr_e( 'e.g., A, 95%, Distinction', 'skillscores-cert' ); ?>" />
                </div>
                <?php endif; ?>
            </div>

            <?php if ( get_option( 'sscv_passport_field_enabled', '1' ) === '1' ) : ?>
            <div class="sscv-form-group sscv-form-full">
                <label for="sscv-passport"><?php esc_html_e( 'Passport Photograph', 'skillscores-cert' ); ?></label>
                <div class="sscv-file-upload">
                    <input type="file" id="sscv-passport" name="passport" accept="image/jpeg,image/png" />
                    <p class="sscv-file-hint"><?php esc_html_e( 'JPG or PNG, max 1MB', 'skillscores-cert' ); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $recaptcha_key ) ) : ?>
                <input type="hidden" name="recaptcha_token" id="sscv-recaptcha-token" />
            <?php endif; ?>

            <div class="sscv-form-group sscv-form-full">
                <button type="submit" class="sscv-btn sscv-btn-primary" id="sscv-submit-application">
                    <span class="sscv-btn-text"><?php esc_html_e( 'Submit Application', 'skillscores-cert' ); ?></span>
                    <span class="sscv-btn-loading" style="display:none;"><?php esc_html_e( 'Submitting...', 'skillscores-cert' ); ?></span>
                </button>
            </div>
        </form>

        <div id="sscv-application-message" class="sscv-message" style="display:none;"></div>
    </div>
</div>
