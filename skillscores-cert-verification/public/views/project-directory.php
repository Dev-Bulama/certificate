<?php if ( ! defined( 'ABSPATH' ) ) exit;
$categories = SSCV_Helpers::get_project_categories();
$per_page = intval( $atts['per_page'] ?? 10 );
?>
<div class="sscv-directory-wrapper <?php echo esc_attr( $atts['class'] ?? '' ); ?>">
    <div class="sscv-directory-header">
        <h2><?php esc_html_e( 'Student Project Directory', 'skillscores-cert' ); ?></h2>

        <div class="sscv-directory-controls">
            <div class="sscv-search-box">
                <input type="text" id="sscv-dir-search" placeholder="<?php esc_attr_e( 'Search projects...', 'skillscores-cert' ); ?>" />
            </div>
            <div class="sscv-filter-box">
                <select id="sscv-dir-category">
                    <option value=""><?php esc_html_e( 'All Categories', 'skillscores-cert' ); ?></option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sscv-sort-box">
                <select id="sscv-dir-sort">
                    <option value="created_at-DESC"><?php esc_html_e( 'Newest First', 'skillscores-cert' ); ?></option>
                    <option value="created_at-ASC"><?php esc_html_e( 'Oldest First', 'skillscores-cert' ); ?></option>
                    <option value="student_name-ASC"><?php esc_html_e( 'Name A-Z', 'skillscores-cert' ); ?></option>
                    <option value="student_name-DESC"><?php esc_html_e( 'Name Z-A', 'skillscores-cert' ); ?></option>
                    <option value="project_title-ASC"><?php esc_html_e( 'Project A-Z', 'skillscores-cert' ); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="sscv-directory-loading" id="sscv-dir-loading" style="display:none;">
        <div class="sscv-spinner"></div>
        <p><?php esc_html_e( 'Loading projects...', 'skillscores-cert' ); ?></p>
    </div>

    <div class="sscv-table-responsive">
        <table class="sscv-project-table" id="sscv-projects-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Student ID', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Image', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Project', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Website', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'GitHub', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Social', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'CV', 'skillscores-cert' ); ?></th>
                </tr>
            </thead>
            <tbody id="sscv-projects-body">
                <tr><td colspan="8" class="sscv-loading-cell"><?php esc_html_e( 'Loading...', 'skillscores-cert' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="sscv-pagination" id="sscv-dir-pagination"></div>
    <input type="hidden" id="sscv-dir-per-page" value="<?php echo esc_attr( $per_page ); ?>" />
</div>
