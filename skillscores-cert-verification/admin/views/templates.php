<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sscv-admin-wrap">
    <h1><?php esc_html_e( 'Certificate Templates', 'skillscores-cert' ); ?></h1>

    <!-- Template Editor -->
    <div class="sscv-card sscv-template-editor">
        <h2><?php echo $editing ? esc_html__( 'Edit Template', 'skillscores-cert' ) : esc_html__( 'Create New Template', 'skillscores-cert' ); ?></h2>

        <form id="sscv-template-form">
            <input type="hidden" name="action" value="sscv_save_template" />
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'sscv_admin_nonce' ); ?>" />
            <input type="hidden" name="template_id" value="<?php echo $editing ? esc_attr( $editing->id ) : ''; ?>" />

            <div class="sscv-field-row">
                <div class="sscv-field" style="flex:2;">
                    <label for="template_name"><?php esc_html_e( 'Template Name *', 'skillscores-cert' ); ?></label>
                    <input type="text" name="template_name" id="template_name" required class="regular-text"
                           value="<?php echo $editing ? esc_attr( $editing->template_name ) : ''; ?>" />
                </div>
                <div class="sscv-field" style="flex:1;">
                    <label>
                        <input type="checkbox" name="is_default" value="1" <?php echo ( $editing && $editing->is_default ) ? 'checked' : ''; ?> />
                        <?php esc_html_e( 'Set as Default Template', 'skillscores-cert' ); ?>
                    </label>
                </div>
            </div>

            <!-- Placeholder Reference -->
            <div class="sscv-placeholder-ref">
                <h4><?php esc_html_e( 'Available Placeholders:', 'skillscores-cert' ); ?></h4>
                <div class="sscv-placeholder-tags">
                    <?php
                    $placeholders = array(
                        '{{student_name}}', '{{course_title}}', '{{completion_date}}',
                        '{{student_id}}', '{{grade}}', '{{certificate_id}}',
                        '{{qr_code}}', '{{passport}}', '{{institution_name}}',
                        '{{institution_logo}}', '{{signature}}', '{{institution_stamp}}',
                        '{{date_issued}}', '{{verification_url}}',
                        '{{theme_color}}', '{{accent_color}}', '{{font_family}}',
                    );
                    foreach ( $placeholders as $ph ) {
                        echo '<span class="sscv-placeholder-tag" onclick="sscvInsertPlaceholder(\'' . esc_js( $ph ) . '\')">' . esc_html( $ph ) . '</span> ';
                    }
                    ?>
                </div>
            </div>

            <div class="sscv-field">
                <label for="html_content"><?php esc_html_e( 'HTML Template', 'skillscores-cert' ); ?></label>
                <textarea name="html_content" id="html_content" rows="20" class="large-text code"><?php echo $editing ? esc_textarea( $editing->html_content ) : esc_textarea( SSCV_Database::get_default_template_html() ); ?></textarea>
            </div>

            <div class="sscv-field">
                <label for="css_content"><?php esc_html_e( 'CSS Styles', 'skillscores-cert' ); ?></label>
                <textarea name="css_content" id="css_content" rows="15" class="large-text code"><?php echo $editing ? esc_textarea( $editing->css_content ) : esc_textarea( SSCV_Database::get_default_template_css() ); ?></textarea>
            </div>

            <div class="sscv-field sscv-field-actions">
                <button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Template', 'skillscores-cert' ); ?></button>
                <button type="button" class="button button-large" id="sscv-preview-template"><?php esc_html_e( 'Preview Template', 'skillscores-cert' ); ?></button>
                <?php if ( $editing ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=sscv-templates' ); ?>" class="button button-large"><?php esc_html_e( 'Cancel / New Template', 'skillscores-cert' ); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Preview Area -->
    <div id="sscv-template-preview" class="sscv-card" style="display:none;">
        <h2><?php esc_html_e( 'Template Preview', 'skillscores-cert' ); ?></h2>
        <div id="sscv-preview-content" class="sscv-preview-frame"></div>
    </div>

    <!-- Image-Based Certificate Template Builder -->
    <div class="sscv-card">
        <h2><?php esc_html_e( 'Image-Based Certificate Template', 'skillscores-cert' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Upload a certificate background image and drag-and-drop variable fields onto it to position them.', 'skillscores-cert' ); ?></p>

        <form id="sscv-image-template-form" style="margin-top:15px;">
            <input type="hidden" id="sscv-img-template-id" value="" />
            <input type="hidden" id="sscv-img-template-url" value="" />
            <input type="hidden" id="sscv-img-field-positions" value="{}" />

            <div class="sscv-field-row" style="margin-bottom:15px;">
                <div class="sscv-field" style="flex:2;">
                    <label for="sscv-img-template-name"><?php esc_html_e( 'Template Name *', 'skillscores-cert' ); ?></label>
                    <input type="text" id="sscv-img-template-name" required class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Image Certificate 2024', 'skillscores-cert' ); ?>" />
                </div>
                <div class="sscv-field" style="flex:1;">
                    <label>
                        <input type="checkbox" id="sscv-img-is-default" value="1" />
                        <?php esc_html_e( 'Set as Default', 'skillscores-cert' ); ?>
                    </label>
                </div>
            </div>

            <div class="sscv-field" style="margin-bottom:15px;">
                <label><?php esc_html_e( 'Certificate Background Image', 'skillscores-cert' ); ?></label>
                <div class="sscv-media-field">
                    <input type="text" id="sscv-img-template-url-input" class="regular-text" placeholder="<?php esc_attr_e( 'Image URL', 'skillscores-cert' ); ?>" />
                    <button type="button" class="button" id="sscv-img-upload-btn"><?php esc_html_e( 'Upload Image', 'skillscores-cert' ); ?></button>
                </div>
            </div>

            <!-- Draggable Fields Palette -->
            <div class="sscv-placeholder-ref">
                <h4><?php esc_html_e( 'Drag fields onto the certificate (click to add):', 'skillscores-cert' ); ?></h4>
                <div class="sscv-placeholder-tags">
                    <?php
                    $img_fields = array(
                        'student_name', 'course_title', 'completion_date',
                        'student_id', 'grade', 'certificate_id',
                        'institution_name', 'date_issued',
                    );
                    foreach ( $img_fields as $field ) {
                        echo '<span class="sscv-placeholder-tag sscv-img-add-field" data-field="' . esc_attr( $field ) . '">{{' . esc_html( $field ) . '}}</span> ';
                    }
                    ?>
                </div>
            </div>

            <!-- Canvas Area -->
            <div id="sscv-img-template-canvas" class="sscv-img-canvas" style="display:none;">
                <p class="sscv-img-canvas-hint"><?php esc_html_e( 'Click fields above to add them, then drag to position.', 'skillscores-cert' ); ?></p>
            </div>

            <div class="sscv-field sscv-field-actions" style="margin-top:15px;">
                <button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Image Template', 'skillscores-cert' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Existing Templates -->
    <div class="sscv-card">
        <h2><?php esc_html_e( 'All Templates', 'skillscores-cert' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Default', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'skillscores-cert' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'skillscores-cert' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $templates ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No templates found.', 'skillscores-cert' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $templates as $tpl ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $tpl->template_name ); ?></strong></td>
                            <td>
                                <?php if ( SSCV_Helpers::is_image_template( $tpl->id ) ) : ?>
                                    <span class="sscv-badge sscv-badge-pending"><?php esc_html_e( 'Image', 'skillscores-cert' ); ?></span>
                                <?php else : ?>
                                    <span class="sscv-badge" style="background:#e0e7ff;color:#3730a3;"><?php esc_html_e( 'HTML', 'skillscores-cert' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $tpl->is_default ? '<span class="sscv-badge sscv-badge-approved">Default</span>' : '—'; ?></td>
                            <td><?php echo esc_html( SSCV_Helpers::format_date( $tpl->created_at ) ); ?></td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=sscv-templates&edit=' . $tpl->id ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'skillscores-cert' ); ?></a>
                                <button class="button button-small sscv-delete-template" data-id="<?php echo esc_attr( $tpl->id ); ?>"><?php esc_html_e( 'Delete', 'skillscores-cert' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function sscvInsertPlaceholder(placeholder) {
    var textarea = document.getElementById('html_content');
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    textarea.value = text.substring(0, start) + placeholder + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
    textarea.focus();
}
</script>
