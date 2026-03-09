<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Certificate Applications', 'skillscores-cert' ); ?></h1>

    <!-- Filters -->
    <div class="sscv-filters">
        <form method="get">
            <input type="hidden" name="page" value="sscv-applications" />
            <div class="sscv-filter-row">
                <select name="status">
                    <option value=""><?php esc_html_e( 'All Statuses', 'skillscores-cert' ); ?></option>
                    <option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'skillscores-cert' ); ?></option>
                    <option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Approved', 'skillscores-cert' ); ?></option>
                    <option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'skillscores-cert' ); ?></option>
                    <option value="revoked" <?php selected( $status_filter, 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'skillscores-cert' ); ?></option>
                </select>
                <select name="course_id">
                    <option value=""><?php esc_html_e( 'All Courses', 'skillscores-cert' ); ?></option>
                    <?php foreach ( $courses_list as $course ) : ?>
                        <option value="<?php echo esc_attr( $course->id ); ?>" <?php selected( $course_filter, $course->id ); ?>>
                            <?php echo esc_html( $course->course_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name, email, or cert ID...', 'skillscores-cert' ); ?>" />
                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'skillscores-cert' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Bulk Export Panel -->
    <div class="sscv-card" style="margin-bottom:20px;">
        <h2><?php esc_html_e( 'Bulk Export Certificates (PDF)', 'skillscores-cert' ); ?></h2>
        <div class="sscv-filter-row" style="gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div class="sscv-field" style="margin:0;">
                <label for="sscv-export-date-from" style="font-size:12px;"><?php esc_html_e( 'Date From', 'skillscores-cert' ); ?></label>
                <input type="date" id="sscv-export-date-from" />
            </div>
            <div class="sscv-field" style="margin:0;">
                <label for="sscv-export-date-to" style="font-size:12px;"><?php esc_html_e( 'Date To', 'skillscores-cert' ); ?></label>
                <input type="date" id="sscv-export-date-to" />
            </div>
            <div class="sscv-field" style="margin:0;">
                <label for="sscv-export-course" style="font-size:12px;"><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></label>
                <select id="sscv-export-course">
                    <option value=""><?php esc_html_e( 'All Courses', 'skillscores-cert' ); ?></option>
                    <?php foreach ( $courses_list as $course ) : ?>
                        <option value="<?php echo esc_attr( $course->id ); ?>"><?php echo esc_html( $course->course_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sscv-field" style="margin:0;">
                <label for="sscv-export-status" style="font-size:12px;"><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></label>
                <select id="sscv-export-status">
                    <option value="approved"><?php esc_html_e( 'Approved', 'skillscores-cert' ); ?></option>
                    <option value=""><?php esc_html_e( 'All Statuses', 'skillscores-cert' ); ?></option>
                </select>
            </div>
            <button type="button" class="button button-primary" id="sscv-bulk-export-pdf">
                <?php esc_html_e( 'Export as PDF', 'skillscores-cert' ); ?>
            </button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped sscv-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="12%"><?php esc_html_e( 'Cert ID', 'skillscores-cert' ); ?></th>
                <th width="15%"><?php esc_html_e( 'Student', 'skillscores-cert' ); ?></th>
                <th width="10%"><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></th>
                <th width="15%"><?php esc_html_e( 'Course', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Grade', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                <th width="10%"><?php esc_html_e( 'Date', 'skillscores-cert' ); ?></th>
                <th width="17%"><?php esc_html_e( 'Actions', 'skillscores-cert' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $applications ) ) : ?>
                <tr><td colspan="9"><?php esc_html_e( 'No applications found.', 'skillscores-cert' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $applications as $i => $app ) : ?>
                    <tr id="cert-row-<?php echo esc_attr( $app->id ); ?>">
                        <td><?php echo esc_html( ( $paged - 1 ) * $per_page + $i + 1 ); ?></td>
                        <td><code><?php echo esc_html( $app->certificate_id ); ?></code></td>
                        <td>
                            <strong><?php echo esc_html( $app->full_name ); ?></strong><br>
                            <small><?php echo esc_html( $app->email ); ?></small>
                            <?php if ( $app->passport_url ) : ?>
                                <br><a href="<?php echo esc_url( $app->passport_url ); ?>" target="_blank" class="sscv-view-photo"><?php esc_html_e( 'View Photo', 'skillscores-cert' ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $app->student_id_number ?? '' ); ?></td>
                        <td><?php echo esc_html( $app->course_title ?? '—' ); ?></td>
                        <td><?php echo esc_html( $app->grade ); ?></td>
                        <td><?php echo SSCV_Helpers::status_badge( $app->status ); ?></td>
                        <td><?php echo esc_html( SSCV_Helpers::format_date( $app->created_at ) ); ?></td>
                        <td class="sscv-actions">
                            <?php if ( $app->status === 'pending' ) : ?>
                                <button class="button button-small sscv-preview-cert" data-id="<?php echo esc_attr( $app->id ); ?>" data-name="<?php echo esc_attr( $app->full_name ); ?>">
                                    <?php esc_html_e( 'Preview', 'skillscores-cert' ); ?>
                                </button>
                                <button class="button button-small sscv-edit-cert-name" data-id="<?php echo esc_attr( $app->id ); ?>" data-name="<?php echo esc_attr( $app->full_name ); ?>">
                                    <?php esc_html_e( 'Edit Name', 'skillscores-cert' ); ?>
                                </button>
                                <button class="button button-primary button-small sscv-approve-cert" data-id="<?php echo esc_attr( $app->id ); ?>">
                                    <?php esc_html_e( 'Approve', 'skillscores-cert' ); ?>
                                </button>
                                <button class="button button-small sscv-reject-cert" data-id="<?php echo esc_attr( $app->id ); ?>">
                                    <?php esc_html_e( 'Reject', 'skillscores-cert' ); ?>
                                </button>
                            <?php elseif ( $app->status === 'approved' ) : ?>
                                <?php if ( $app->certificate_url ) : ?>
                                    <a href="<?php echo esc_url( $app->certificate_url ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View', 'skillscores-cert' ); ?></a>
                                <?php endif; ?>
                                <button class="button button-small sscv-revoke-cert" data-id="<?php echo esc_attr( $app->id ); ?>">
                                    <?php esc_html_e( 'Revoke', 'skillscores-cert' ); ?>
                                </button>
                            <?php else : ?>
                                <span class="description"><?php echo esc_html( ucfirst( $app->status ) ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $paged,
                ) );
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reject Modal -->
    <div id="sscv-reject-modal" class="sscv-modal" style="display:none;">
        <div class="sscv-modal-content">
            <h3><?php esc_html_e( 'Reject Certificate Application', 'skillscores-cert' ); ?></h3>
            <textarea id="sscv-reject-notes" rows="4" placeholder="<?php esc_attr_e( 'Reason for rejection (optional)...', 'skillscores-cert' ); ?>"></textarea>
            <div class="sscv-modal-actions">
                <button class="button button-primary" id="sscv-confirm-reject"><?php esc_html_e( 'Confirm Rejection', 'skillscores-cert' ); ?></button>
                <button class="button sscv-modal-close"><?php esc_html_e( 'Cancel', 'skillscores-cert' ); ?></button>
            </div>
            <input type="hidden" id="sscv-reject-cert-id" value="" />
        </div>
    </div>

    <!-- Certificate Preview Modal -->
    <div id="sscv-preview-modal" class="sscv-modal" style="display:none;">
        <div class="sscv-modal-content sscv-modal-large">
            <div class="sscv-modal-header">
                <h3><?php esc_html_e( 'Certificate Preview', 'skillscores-cert' ); ?></h3>
                <button type="button" class="sscv-modal-close-btn sscv-modal-close">&times;</button>
            </div>
            <div id="sscv-preview-loading" style="text-align:center;padding:40px;">
                <p><?php esc_html_e( 'Loading preview...', 'skillscores-cert' ); ?></p>
            </div>
            <div id="sscv-certificate-preview" class="sscv-preview-frame" style="display:none;"></div>
            <div class="sscv-modal-actions">
                <button class="button button-primary" id="sscv-preview-approve"><?php esc_html_e( 'Approve Certificate', 'skillscores-cert' ); ?></button>
                <button class="button" id="sscv-preview-edit-name"><?php esc_html_e( 'Edit Name', 'skillscores-cert' ); ?></button>
                <button class="button sscv-modal-close"><?php esc_html_e( 'Close', 'skillscores-cert' ); ?></button>
            </div>
            <input type="hidden" id="sscv-preview-cert-id" value="" />
        </div>
    </div>

    <!-- Edit Name Modal -->
    <div id="sscv-edit-name-modal" class="sscv-modal" style="display:none;">
        <div class="sscv-modal-content">
            <h3><?php esc_html_e( 'Edit Certificate Name', 'skillscores-cert' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Correct the name that will appear on the certificate.', 'skillscores-cert' ); ?></p>
            <div class="sscv-field" style="margin-top:12px;">
                <label for="sscv-edit-name-input"><?php esc_html_e( 'Full Name', 'skillscores-cert' ); ?></label>
                <input type="text" id="sscv-edit-name-input" class="large-text" value="" />
            </div>
            <div class="sscv-modal-actions">
                <button class="button button-primary" id="sscv-confirm-edit-name"><?php esc_html_e( 'Save Name', 'skillscores-cert' ); ?></button>
                <button class="button sscv-modal-close"><?php esc_html_e( 'Cancel', 'skillscores-cert' ); ?></button>
            </div>
            <input type="hidden" id="sscv-edit-name-cert-id" value="" />
        </div>
    </div>
</div>
