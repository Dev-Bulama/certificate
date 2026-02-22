<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1>
        <span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span>
        <?php esc_html_e( 'Demo Data Manager', 'skillscores-cert' ); ?>
    </h1>

    <p class="description" style="font-size:14px;margin-bottom:24px;">
        <?php esc_html_e( 'Quickly populate the plugin with realistic test data or reset everything to start fresh.', 'skillscores-cert' ); ?>
    </p>

    <!-- Current Data Status -->
    <div class="sscv-card">
        <h2><?php esc_html_e( 'Current Data Status', 'skillscores-cert' ); ?></h2>

        <div class="sscv-demo-status-badge" style="margin-bottom:16px;">
            <?php if ( $demo_active ) : ?>
                <span class="sscv-badge sscv-badge-approved" style="font-size:13px;padding:6px 14px;">
                    <?php esc_html_e( 'Demo Data Active', 'skillscores-cert' ); ?>
                </span>
            <?php else : ?>
                <span class="sscv-badge sscv-badge-pending" style="font-size:13px;padding:6px 14px;">
                    <?php echo $counts['students'] > 0
                        ? esc_html__( 'Live Data', 'skillscores-cert' )
                        : esc_html__( 'No Data', 'skillscores-cert' ); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="sscv-stats-grid" id="sscv-demo-counts">
            <div class="sscv-stat-card sscv-stat-blue">
                <div class="sscv-stat-icon"><span class="dashicons dashicons-groups"></span></div>
                <div class="sscv-stat-content">
                    <h3 id="sscv-demo-count-students"><?php echo esc_html( $counts['students'] ); ?></h3>
                    <p><?php esc_html_e( 'Students', 'skillscores-cert' ); ?></p>
                </div>
            </div>
            <div class="sscv-stat-card sscv-stat-purple">
                <div class="sscv-stat-icon"><span class="dashicons dashicons-book-alt"></span></div>
                <div class="sscv-stat-content">
                    <h3 id="sscv-demo-count-courses"><?php echo esc_html( $counts['courses'] ); ?></h3>
                    <p><?php esc_html_e( 'Courses', 'skillscores-cert' ); ?></p>
                </div>
            </div>
            <div class="sscv-stat-card sscv-stat-green">
                <div class="sscv-stat-icon"><span class="dashicons dashicons-awards"></span></div>
                <div class="sscv-stat-content">
                    <h3 id="sscv-demo-count-certificates"><?php echo esc_html( $counts['certificates'] ); ?></h3>
                    <p><?php esc_html_e( 'Certificates', 'skillscores-cert' ); ?></p>
                </div>
            </div>
            <div class="sscv-stat-card sscv-stat-teal">
                <div class="sscv-stat-icon"><span class="dashicons dashicons-portfolio"></span></div>
                <div class="sscv-stat-content">
                    <h3 id="sscv-demo-count-projects"><?php echo esc_html( $counts['projects'] ); ?></h3>
                    <p><?php esc_html_e( 'Projects', 'skillscores-cert' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="sscv-demo-actions-grid">

        <!-- Seed Demo Data Card -->
        <div class="sscv-card sscv-demo-action-card">
            <div class="sscv-demo-action-icon" style="background:#eef2ff;color:#4f46e5;">
                <span class="dashicons dashicons-database-import"></span>
            </div>
            <h2><?php esc_html_e( 'Load Demo Data', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Populate the database with realistic test data including:', 'skillscores-cert' ); ?></p>
            <ul class="sscv-demo-list">
                <li><strong>20</strong> <?php esc_html_e( 'Students with unique IDs and emails', 'skillscores-cert' ); ?></li>
                <li><strong>10</strong> <?php esc_html_e( 'Courses (Web Dev, Mobile, Data Science, etc.)', 'skillscores-cert' ); ?></li>
                <li><strong>25+</strong> <?php esc_html_e( 'Certificate applications (mixed statuses)', 'skillscores-cert' ); ?></li>
                <li><strong>15</strong> <?php esc_html_e( 'Student projects across categories', 'skillscores-cert' ); ?></li>
            </ul>

            <?php if ( $counts['students'] > 0 ) : ?>
                <div class="sscv-demo-warning">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e( 'Existing data will be preserved. Only new demo records will be added.', 'skillscores-cert' ); ?>
                </div>
            <?php endif; ?>

            <button type="button" class="button button-primary button-hero" id="sscv-seed-demo">
                <span class="dashicons dashicons-database-import" style="vertical-align:middle;margin-right:6px;"></span>
                <span class="sscv-demo-btn-text"><?php esc_html_e( 'Load Demo Data', 'skillscores-cert' ); ?></span>
                <span class="sscv-demo-btn-loading" style="display:none;"><?php esc_html_e( 'Populating...', 'skillscores-cert' ); ?></span>
            </button>
        </div>

        <!-- Reset Data Card -->
        <div class="sscv-card sscv-demo-action-card">
            <div class="sscv-demo-action-icon" style="background:#fef2f2;color:#dc2626;">
                <span class="dashicons dashicons-trash"></span>
            </div>
            <h2><?php esc_html_e( 'Reset All Data', 'skillscores-cert' ); ?></h2>
            <p><?php esc_html_e( 'Completely wipe all data from the plugin tables. This will remove:', 'skillscores-cert' ); ?></p>
            <ul class="sscv-demo-list sscv-demo-list-danger">
                <li><?php esc_html_e( 'All student records', 'skillscores-cert' ); ?></li>
                <li><?php esc_html_e( 'All certificate applications & issued certificates', 'skillscores-cert' ); ?></li>
                <li><?php esc_html_e( 'All course records', 'skillscores-cert' ); ?></li>
                <li><?php esc_html_e( 'All student projects', 'skillscores-cert' ); ?></li>
                <li><?php esc_html_e( 'All generated QR codes & certificate files', 'skillscores-cert' ); ?></li>
            </ul>

            <div class="sscv-demo-warning sscv-demo-warning-danger">
                <span class="dashicons dashicons-warning"></span>
                <strong><?php esc_html_e( 'This action cannot be undone!', 'skillscores-cert' ); ?></strong>
                <?php esc_html_e( 'Certificate templates and settings will be preserved.', 'skillscores-cert' ); ?>
            </div>

            <button type="button" class="button button-hero" id="sscv-reset-data" style="color:#dc2626;border-color:#dc2626;">
                <span class="dashicons dashicons-trash" style="vertical-align:middle;margin-right:6px;"></span>
                <span class="sscv-demo-btn-text"><?php esc_html_e( 'Reset All Data', 'skillscores-cert' ); ?></span>
                <span class="sscv-demo-btn-loading" style="display:none;"><?php esc_html_e( 'Resetting...', 'skillscores-cert' ); ?></span>
            </button>
        </div>
    </div>

    <!-- Results Message -->
    <div id="sscv-demo-message" class="sscv-demo-message" style="display:none;"></div>

    <!-- Confirmation Modal -->
    <div id="sscv-reset-confirm-modal" class="sscv-modal" style="display:none;">
        <div class="sscv-modal-content" style="max-width:450px;">
            <div style="text-align:center;margin-bottom:20px;">
                <span class="dashicons dashicons-warning" style="font-size:48px;width:48px;height:48px;color:#dc2626;"></span>
            </div>
            <h3 style="text-align:center;margin:0 0 10px;"><?php esc_html_e( 'Confirm Data Reset', 'skillscores-cert' ); ?></h3>
            <p style="text-align:center;color:#6b7280;">
                <?php esc_html_e( 'This will permanently delete ALL students, certificates, courses, and projects. This cannot be undone.', 'skillscores-cert' ); ?>
            </p>
            <div class="sscv-confirm-input" style="margin:20px 0;">
                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;">
                    <?php esc_html_e( 'Type RESET to confirm:', 'skillscores-cert' ); ?>
                </label>
                <input type="text" id="sscv-reset-confirm-input" autocomplete="off" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;" />
            </div>
            <div class="sscv-modal-actions">
                <button class="button button-primary" id="sscv-confirm-reset-btn" disabled style="background:#dc2626;border-color:#dc2626;">
                    <?php esc_html_e( 'Yes, Reset Everything', 'skillscores-cert' ); ?>
                </button>
                <button class="button sscv-modal-close"><?php esc_html_e( 'Cancel', 'skillscores-cert' ); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.sscv-demo-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

@media (max-width: 900px) {
    .sscv-demo-actions-grid {
        grid-template-columns: 1fr;
    }
}

.sscv-demo-action-card {
    position: relative;
    padding-top: 30px;
}

.sscv-demo-action-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.sscv-demo-action-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.sscv-demo-action-card h2 {
    border-bottom: none !important;
    padding-bottom: 0 !important;
    margin-bottom: 8px !important;
}

.sscv-demo-action-card p {
    color: #6b7280;
    margin-bottom: 12px;
}

.sscv-demo-list {
    margin: 0 0 16px 20px;
    list-style: disc;
    color: #374151;
    line-height: 1.8;
}

.sscv-demo-list li {
    font-size: 13px;
}

.sscv-demo-list-danger li {
    color: #991b1b;
}

.sscv-demo-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 18px;
    font-size: 13px;
    color: #92400e;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.sscv-demo-warning .dashicons {
    flex-shrink: 0;
    margin-top: 1px;
}

.sscv-demo-warning-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}

.sscv-demo-message {
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
}

.sscv-demo-message-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

.sscv-demo-message-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.button-hero .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
</style>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {

        // ---- Load Demo Data ----
        $('#sscv-seed-demo').on('click', function() {
            var btn = $(this);

            if (!confirm('This will add demo data to the database. Continue?')) {
                return;
            }

            btn.prop('disabled', true);
            btn.find('.sscv-demo-btn-text').hide();
            btn.find('.sscv-demo-btn-loading').show();
            $('#sscv-demo-message').hide();

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_seed_demo_data',
                nonce: sscv_admin.nonce
            }, function(response) {
                if (response.success) {
                    $('#sscv-demo-message')
                        .removeClass('sscv-demo-message-error')
                        .addClass('sscv-demo-message-success')
                        .html('<strong>Demo data loaded successfully!</strong> ' + response.data.summary)
                        .show();

                    // Update counts
                    if (response.data.counts) {
                        $('#sscv-demo-count-students').text(response.data.counts.students);
                        $('#sscv-demo-count-courses').text(response.data.counts.courses);
                        $('#sscv-demo-count-certificates').text(response.data.counts.certificates);
                        $('#sscv-demo-count-projects').text(response.data.counts.projects);
                    }
                } else {
                    $('#sscv-demo-message')
                        .removeClass('sscv-demo-message-success')
                        .addClass('sscv-demo-message-error')
                        .html('<strong>Error:</strong> ' + (response.data.message || 'Unknown error.'))
                        .show();
                }

                btn.prop('disabled', false);
                btn.find('.sscv-demo-btn-text').show();
                btn.find('.sscv-demo-btn-loading').hide();
            }).fail(function() {
                $('#sscv-demo-message')
                    .removeClass('sscv-demo-message-success')
                    .addClass('sscv-demo-message-error')
                    .html('<strong>Error:</strong> Network request failed.')
                    .show();

                btn.prop('disabled', false);
                btn.find('.sscv-demo-btn-text').show();
                btn.find('.sscv-demo-btn-loading').hide();
            });
        });

        // ---- Reset Data (open modal) ----
        $('#sscv-reset-data').on('click', function() {
            $('#sscv-reset-confirm-input').val('');
            $('#sscv-confirm-reset-btn').prop('disabled', true);
            $('#sscv-reset-confirm-modal').show();
        });

        // Enable/disable confirm button
        $('#sscv-reset-confirm-input').on('input', function() {
            $('#sscv-confirm-reset-btn').prop('disabled', $(this).val() !== 'RESET');
        });

        // Close modal
        $(document).on('click', '.sscv-modal-close', function() {
            $(this).closest('.sscv-modal').hide();
        });

        // Confirm reset
        $('#sscv-confirm-reset-btn').on('click', function() {
            var btn = $('#sscv-reset-data');
            $('#sscv-reset-confirm-modal').hide();

            btn.prop('disabled', true);
            btn.find('.sscv-demo-btn-text').hide();
            btn.find('.sscv-demo-btn-loading').show();
            $('#sscv-demo-message').hide();

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_reset_demo_data',
                nonce: sscv_admin.nonce
            }, function(response) {
                if (response.success) {
                    $('#sscv-demo-message')
                        .removeClass('sscv-demo-message-error')
                        .addClass('sscv-demo-message-success')
                        .html('<strong>All data has been reset!</strong> The database is now empty and ready for fresh data.')
                        .show();

                    // Zero out counts
                    $('#sscv-demo-count-students').text('0');
                    $('#sscv-demo-count-courses').text('0');
                    $('#sscv-demo-count-certificates').text('0');
                    $('#sscv-demo-count-projects').text('0');
                } else {
                    $('#sscv-demo-message')
                        .removeClass('sscv-demo-message-success')
                        .addClass('sscv-demo-message-error')
                        .html('<strong>Error:</strong> ' + (response.data.message || 'Unknown error.'))
                        .show();
                }

                btn.prop('disabled', false);
                btn.find('.sscv-demo-btn-text').show();
                btn.find('.sscv-demo-btn-loading').hide();
            }).fail(function() {
                $('#sscv-demo-message')
                    .removeClass('sscv-demo-message-success')
                    .addClass('sscv-demo-message-error')
                    .html('<strong>Error:</strong> Network request failed.')
                    .show();

                btn.prop('disabled', false);
                btn.find('.sscv-demo-btn-text').show();
                btn.find('.sscv-demo-btn-loading').hide();
            });
        });

    });
})(jQuery);
</script>
