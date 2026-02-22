<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="sscv-dashboard-wrapper <?php echo esc_attr( $atts['class'] ?? '' ); ?>">
    <!-- Login Section -->
    <div id="sscv-dashboard-login" class="sscv-form-card">
        <div class="sscv-form-header">
            <h2><?php esc_html_e( 'Student Dashboard', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Enter your credentials to view your certificates and projects.', 'skillscores-cert' ); ?></p>
        </div>

        <form id="sscv-dashboard-login-form">
            <input type="hidden" name="action" value="sscv_student_dashboard_data" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_nonce' ); ?>" />

            <div class="sscv-form-group">
                <label for="sscv-dash-sid"><?php esc_html_e( 'Student ID *', 'skillscores-cert' ); ?></label>
                <input type="text" id="sscv-dash-sid" name="student_id" required />
            </div>

            <div class="sscv-form-group">
                <label for="sscv-dash-email"><?php esc_html_e( 'Email Address *', 'skillscores-cert' ); ?></label>
                <input type="email" id="sscv-dash-email" name="email" required />
            </div>

            <div class="sscv-form-group">
                <button type="submit" class="sscv-btn sscv-btn-primary">
                    <span class="sscv-btn-text"><?php esc_html_e( 'View Dashboard', 'skillscores-cert' ); ?></span>
                    <span class="sscv-btn-loading" style="display:none;"><?php esc_html_e( 'Loading...', 'skillscores-cert' ); ?></span>
                </button>
            </div>
        </form>
        <div id="sscv-dashboard-login-msg" class="sscv-message" style="display:none;"></div>
    </div>

    <!-- Dashboard Content -->
    <div id="sscv-dashboard-content" style="display:none;">
        <!-- Student Info -->
        <div class="sscv-dash-profile sscv-form-card">
            <div class="sscv-dash-profile-inner">
                <div class="sscv-dash-avatar" id="sscv-dash-avatar"></div>
                <div class="sscv-dash-info">
                    <h2 id="sscv-dash-name"></h2>
                    <p id="sscv-dash-sid-display"></p>
                    <p id="sscv-dash-email-display"></p>
                </div>
                <button class="sscv-btn sscv-btn-secondary" id="sscv-dash-logout"><?php esc_html_e( 'Logout', 'skillscores-cert' ); ?></button>
            </div>
        </div>

        <!-- Certificates Table -->
        <div class="sscv-form-card">
            <h3><?php esc_html_e( 'My Certificates', 'skillscores-cert' ); ?></h3>
            <div class="sscv-table-responsive">
                <table class="sscv-project-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Certificate ID', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Grade', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Date Issued', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'skillscores-cert' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sscv-dash-certs"></tbody>
                </table>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="sscv-form-card">
            <h3><?php esc_html_e( 'My Projects', 'skillscores-cert' ); ?></h3>
            <div class="sscv-table-responsive">
                <table class="sscv-project-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Project', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Category', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Website', 'skillscores-cert' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sscv-dash-projects"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
