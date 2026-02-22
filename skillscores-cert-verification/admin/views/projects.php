<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Student Projects', 'skillscores-cert' ); ?></h1>

    <div class="sscv-filters">
        <form method="get">
            <input type="hidden" name="page" value="sscv-projects" />
            <div class="sscv-filter-row">
                <select name="status">
                    <option value=""><?php esc_html_e( 'All Statuses', 'skillscores-cert' ); ?></option>
                    <option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'skillscores-cert' ); ?></option>
                    <option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Approved', 'skillscores-cert' ); ?></option>
                    <option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'skillscores-cert' ); ?></option>
                </select>
                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'skillscores-cert' ); ?></button>
            </div>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped sscv-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="12%"><?php esc_html_e( 'Student', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></th>
                <th width="15%"><?php esc_html_e( 'Project', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Category', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Image', 'skillscores-cert' ); ?></th>
                <th width="12%"><?php esc_html_e( 'Links', 'skillscores-cert' ); ?></th>
                <th width="8%"><?php esc_html_e( 'Status', 'skillscores-cert' ); ?></th>
                <th width="10%"><?php esc_html_e( 'Date', 'skillscores-cert' ); ?></th>
                <th width="14%"><?php esc_html_e( 'Actions', 'skillscores-cert' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $projects ) ) : ?>
                <tr><td colspan="10"><?php esc_html_e( 'No projects found.', 'skillscores-cert' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $projects as $i => $proj ) : ?>
                    <tr id="project-row-<?php echo esc_attr( $proj->id ); ?>">
                        <td><?php echo esc_html( ( $paged - 1 ) * $per_page + $i + 1 ); ?></td>
                        <td><strong><?php echo esc_html( $proj->student_name ); ?></strong></td>
                        <td><?php echo esc_html( $proj->student_id_number ); ?></td>
                        <td>
                            <strong><?php echo esc_html( $proj->project_title ); ?></strong>
                            <?php if ( $proj->description ) : ?>
                                <br><small><?php echo esc_html( wp_trim_words( $proj->description, 15 ) ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $proj->category ); ?></td>
                        <td>
                            <?php if ( $proj->project_image_url ) : ?>
                                <img src="<?php echo esc_url( $proj->project_image_url ); ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" />
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $proj->website_url ) : ?>
                                <a href="<?php echo esc_url( $proj->website_url ); ?>" target="_blank">Web</a>
                            <?php endif; ?>
                            <?php if ( $proj->github_url ) : ?>
                                <a href="<?php echo esc_url( $proj->github_url ); ?>" target="_blank">GitHub</a>
                            <?php endif; ?>
                            <?php if ( $proj->cv_url ) : ?>
                                <a href="<?php echo esc_url( $proj->cv_url ); ?>" target="_blank">CV</a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo SSCV_Helpers::status_badge( $proj->status ); ?></td>
                        <td><?php echo esc_html( SSCV_Helpers::format_date( $proj->created_at ) ); ?></td>
                        <td>
                            <?php if ( $proj->status === 'pending' ) : ?>
                                <button class="button button-primary button-small sscv-approve-project" data-id="<?php echo esc_attr( $proj->id ); ?>">
                                    <?php esc_html_e( 'Approve', 'skillscores-cert' ); ?>
                                </button>
                                <button class="button button-small sscv-reject-project" data-id="<?php echo esc_attr( $proj->id ); ?>">
                                    <?php esc_html_e( 'Reject', 'skillscores-cert' ); ?>
                                </button>
                            <?php else : ?>
                                <span class="description"><?php echo esc_html( ucfirst( $proj->status ) ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'total' => $total_pages, 'current' => $paged ) ); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
