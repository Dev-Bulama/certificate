<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Students', 'skillscores-cert' ); ?></h1>

    <div class="sscv-filters">
        <form method="get">
            <input type="hidden" name="page" value="sscv-students" />
            <div class="sscv-filter-row">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name, student ID, or email...', 'skillscores-cert' ); ?>" />
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'skillscores-cert' ); ?></button>
            </div>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped sscv-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="8%"><?php esc_html_e( 'Photo', 'skillscores-cert' ); ?></th>
                <th width="15%"><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></th>
                <th width="20%"><?php esc_html_e( 'Full Name', 'skillscores-cert' ); ?></th>
                <th width="22%"><?php esc_html_e( 'Email', 'skillscores-cert' ); ?></th>
                <th width="15%"><?php esc_html_e( 'Registered', 'skillscores-cert' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $students ) ) : ?>
                <tr><td colspan="6"><?php esc_html_e( 'No students found.', 'skillscores-cert' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $students as $i => $stu ) : ?>
                    <tr>
                        <td><?php echo esc_html( ( $paged - 1 ) * $per_page + $i + 1 ); ?></td>
                        <td>
                            <?php if ( $stu->passport_url ) : ?>
                                <img src="<?php echo esc_url( $stu->passport_url ); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;" />
                            <?php else : ?>
                                <span class="dashicons dashicons-admin-users" style="font-size:30px;color:#ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html( $stu->student_id ); ?></code></td>
                        <td><strong><?php echo esc_html( $stu->full_name ); ?></strong></td>
                        <td><?php echo esc_html( $stu->email ); ?></td>
                        <td><?php echo esc_html( SSCV_Helpers::format_date( $stu->created_at ) ); ?></td>
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
