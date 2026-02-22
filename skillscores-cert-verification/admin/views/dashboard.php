<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1 class="sscv-admin-title">
        <span class="dashicons dashicons-awards"></span>
        <?php esc_html_e( 'Certificate & Verification Dashboard', 'skillscores-cert' ); ?>
    </h1>

    <!-- Stats Cards -->
    <div class="sscv-stats-grid">
        <div class="sscv-stat-card sscv-stat-blue">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['total_students'] ); ?></h3>
                <p><?php esc_html_e( 'Total Students', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <div class="sscv-stat-card sscv-stat-orange">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['pending_certificates'] ); ?></h3>
                <p><?php esc_html_e( 'Pending Applications', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <div class="sscv-stat-card sscv-stat-green">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['approved_certificates'] ); ?></h3>
                <p><?php esc_html_e( 'Issued Certificates', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <div class="sscv-stat-card sscv-stat-purple">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-book-alt"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['total_courses'] ); ?></h3>
                <p><?php esc_html_e( 'Active Courses', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <div class="sscv-stat-card sscv-stat-teal">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-portfolio"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['total_projects'] ); ?></h3>
                <p><?php esc_html_e( 'Total Projects', 'skillscores-cert' ); ?></p>
            </div>
        </div>

        <div class="sscv-stat-card sscv-stat-red">
            <div class="sscv-stat-icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="sscv-stat-content">
                <h3><?php echo esc_html( $stats['pending_projects'] ); ?></h3>
                <p><?php esc_html_e( 'Pending Projects', 'skillscores-cert' ); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sscv-quick-actions">
        <h2><?php esc_html_e( 'Quick Actions', 'skillscores-cert' ); ?></h2>
        <div class="sscv-action-buttons">
            <a href="<?php echo admin_url( 'admin.php?page=sscv-applications&status=pending' ); ?>" class="button button-primary">
                <span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Review Pending Applications', 'skillscores-cert' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=sscv-courses' ); ?>" class="button">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add Course', 'skillscores-cert' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=sscv-templates' ); ?>" class="button">
                <span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Manage Templates', 'skillscores-cert' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=sscv-settings' ); ?>" class="button">
                <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Settings', 'skillscores-cert' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=sscv-demo-data' ); ?>" class="button" style="border-color:#8b5cf6;color:#8b5cf6;">
                <span class="dashicons dashicons-database-import"></span> <?php esc_html_e( 'Demo Data', 'skillscores-cert' ); ?>
            </a>
        </div>
    </div>

    <!-- Shortcodes Reference -->
    <div class="sscv-shortcode-reference">
        <h2><?php esc_html_e( 'Shortcodes Reference', 'skillscores-cert' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Shortcode', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'skillscores-cert' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[certificate_application_form]</code></td>
                    <td><?php esc_html_e( 'Certificate application form for students', 'skillscores-cert' ); ?></td>
                </tr>
                <tr>
                    <td><code>[certificate_verification]</code></td>
                    <td><?php esc_html_e( 'Public certificate verification portal', 'skillscores-cert' ); ?></td>
                </tr>
                <tr>
                    <td><code>[student_project_submission]</code></td>
                    <td><?php esc_html_e( 'Student project submission form', 'skillscores-cert' ); ?></td>
                </tr>
                <tr>
                    <td><code>[public_project_directory]</code></td>
                    <td><?php esc_html_e( 'Public project directory with search and pagination', 'skillscores-cert' ); ?></td>
                </tr>
                <tr>
                    <td><code>[student_dashboard]</code></td>
                    <td><?php esc_html_e( 'Student dashboard (certificate status & projects)', 'skillscores-cert' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Applications -->
    <div class="sscv-recent-apps">
        <h2><?php esc_html_e( 'Recent Applications', 'skillscores-cert' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Certificate ID', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Student', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'skillscores-cert' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $recent_applications ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No applications yet.', 'skillscores-cert' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $recent_applications as $app ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $app->certificate_id ); ?></code></td>
                            <td><?php echo esc_html( $app->full_name ); ?></td>
                            <td><?php echo esc_html( $app->course_title ?? '—' ); ?></td>
                            <td><?php echo SSCV_Helpers::status_badge( $app->status ); ?></td>
                            <td><?php echo esc_html( SSCV_Helpers::format_date( $app->created_at ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
