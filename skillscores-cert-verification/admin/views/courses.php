<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Manage Courses', 'skillscores-cert' ); ?></h1>

    <div class="sscv-two-col">
        <!-- Course Form -->
        <div class="sscv-col-form">
            <div class="sscv-card">
                <h2 id="sscv-course-form-title"><?php esc_html_e( 'Add New Course', 'skillscores-cert' ); ?></h2>
                <form id="sscv-course-form">
                    <input type="hidden" name="action" value="sscv_save_course" />
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_admin_nonce' ); ?>" />
                    <input type="hidden" name="course_id" id="sscv-course-id" value="" />

                    <div class="sscv-field">
                        <label for="course_title"><?php esc_html_e( 'Course Title *', 'skillscores-cert' ); ?></label>
                        <input type="text" name="course_title" id="course_title" required class="regular-text" />
                    </div>

                    <div class="sscv-field">
                        <label for="course_code"><?php esc_html_e( 'Course Code', 'skillscores-cert' ); ?></label>
                        <input type="text" name="course_code" id="course_code" class="regular-text" />
                    </div>

                    <div class="sscv-field">
                        <label for="description"><?php esc_html_e( 'Description', 'skillscores-cert' ); ?></label>
                        <textarea name="description" id="description" rows="3" class="large-text"></textarea>
                    </div>

                    <div class="sscv-field">
                        <label for="template_id"><?php esc_html_e( 'Certificate Template', 'skillscores-cert' ); ?></label>
                        <select name="template_id" id="template_id">
                            <option value=""><?php esc_html_e( '— Use Default —', 'skillscores-cert' ); ?></option>
                            <?php foreach ( $templates as $tpl ) : ?>
                                <option value="<?php echo esc_attr( $tpl->id ); ?>"><?php echo esc_html( $tpl->template_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sscv-field">
                        <label for="status"><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></label>
                        <select name="status" id="status">
                            <option value="active"><?php esc_html_e( 'Active', 'skillscores-cert' ); ?></option>
                            <option value="inactive"><?php esc_html_e( 'Inactive', 'skillscores-cert' ); ?></option>
                        </select>
                    </div>

                    <div class="sscv-field">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Course', 'skillscores-cert' ); ?></button>
                        <button type="button" class="button" id="sscv-reset-course-form" style="display:none;"><?php esc_html_e( 'Cancel Edit', 'skillscores-cert' ); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Courses List -->
        <div class="sscv-col-list">
            <div class="sscv-card">
                <h2><?php esc_html_e( 'All Courses', 'skillscores-cert' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Title', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Code', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Template', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'skillscores-cert' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sscv-courses-list">
                        <?php if ( empty( $courses ) ) : ?>
                            <tr><td colspan="5"><?php esc_html_e( 'No courses yet.', 'skillscores-cert' ); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ( $courses as $course ) : ?>
                                <tr id="course-row-<?php echo esc_attr( $course->id ); ?>">
                                    <td><strong><?php echo esc_html( $course->course_title ); ?></strong></td>
                                    <td><?php echo esc_html( $course->course_code ); ?></td>
                                    <td><?php echo esc_html( $course->template_name ?? __( 'Default', 'skillscores-cert' ) ); ?></td>
                                    <td><?php echo $course->status === 'active' ? '<span class="sscv-badge sscv-badge-approved">Active</span>' : '<span class="sscv-badge sscv-badge-pending">Inactive</span>'; ?></td>
                                    <td>
                                        <button class="button button-small sscv-edit-course"
                                                data-id="<?php echo esc_attr( $course->id ); ?>"
                                                data-title="<?php echo esc_attr( $course->course_title ); ?>"
                                                data-code="<?php echo esc_attr( $course->course_code ); ?>"
                                                data-desc="<?php echo esc_attr( $course->description ); ?>"
                                                data-template="<?php echo esc_attr( $course->template_id ); ?>"
                                                data-status="<?php echo esc_attr( $course->status ); ?>">
                                            <?php esc_html_e( 'Edit', 'skillscores-cert' ); ?>
                                        </button>
                                        <button class="button button-small sscv-delete-course" data-id="<?php echo esc_attr( $course->id ); ?>">
                                            <?php esc_html_e( 'Delete', 'skillscores-cert' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
