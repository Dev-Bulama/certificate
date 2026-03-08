/**
 * Admin JavaScript for Certificate & Verification System
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.sscv-color-picker').wpColorPicker();
        }

        // ========================
        // Media Upload Handler
        // ========================
        $(document).on('click', '.sscv-upload-media', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            var frame = wp.media({
                title: 'Select or Upload Media',
                button: { text: 'Use this media' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#' + target).val(attachment.url);
            });

            frame.open();
        });

        // ========================
        // Certificate Approval
        // ========================
        $(document).on('click', '.sscv-approve-cert', function() {
            var btn = $(this);
            var certId = btn.data('id');

            if (!confirm('Are you sure you want to approve this certificate? This will generate the certificate and send it to the student.')) {
                return;
            }

            btn.prop('disabled', true).text('Processing...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_approve_certificate',
                nonce: sscv_admin.nonce,
                cert_id: certId
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error occurred.');
                    btn.prop('disabled', false).text('Approve');
                }
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Approve');
            });
        });

        // ========================
        // Certificate Rejection
        // ========================
        var rejectCertId = null;

        $(document).on('click', '.sscv-reject-cert', function() {
            rejectCertId = $(this).data('id');
            $('#sscv-reject-cert-id').val(rejectCertId);
            $('#sscv-reject-notes').val('');
            $('#sscv-reject-modal').show();
        });

        $(document).on('click', '.sscv-modal-close', function() {
            $(this).closest('.sscv-modal').hide();
            rejectCertId = null;
        });

        $('#sscv-confirm-reject').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Rejecting...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_reject_certificate',
                nonce: sscv_admin.nonce,
                cert_id: rejectCertId,
                notes: $('#sscv-reject-notes').val()
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error occurred.');
                    btn.prop('disabled', false).text('Confirm Rejection');
                }
            });
        });

        // ========================
        // Certificate Preview
        // ========================
        var previewCertId = null;

        $(document).on('click', '.sscv-preview-cert', function() {
            var btn = $(this);
            previewCertId = btn.data('id');
            $('#sscv-preview-cert-id').val(previewCertId);
            $('#sscv-preview-loading').show();
            $('#sscv-certificate-preview').hide().empty();
            $('#sscv-preview-modal').show();

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_preview_certificate',
                nonce: sscv_admin.nonce,
                cert_id: previewCertId
            }, function(response) {
                $('#sscv-preview-loading').hide();
                if (response.success) {
                    var previewHtml = '<style>' + response.data.css + '</style>' + response.data.html;
                    $('#sscv-certificate-preview').html(previewHtml).show();
                } else {
                    $('#sscv-certificate-preview').html('<p style="color:#dc3232;padding:20px;">' + (response.data.message || 'Failed to load preview.') + '</p>').show();
                }
            }).fail(function() {
                $('#sscv-preview-loading').hide();
                $('#sscv-certificate-preview').html('<p style="color:#dc3232;padding:20px;">Request failed. Please try again.</p>').show();
            });
        });

        // Approve from preview modal
        $('#sscv-preview-approve').on('click', function() {
            var certId = $('#sscv-preview-cert-id').val();
            if (!certId) return;

            if (!confirm('Are you sure you want to approve this certificate? This will generate the certificate and send it to the student.')) {
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).text('Processing...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_approve_certificate',
                nonce: sscv_admin.nonce,
                cert_id: certId
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error occurred.');
                    btn.prop('disabled', false).text('Approve Certificate');
                }
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Approve Certificate');
            });
        });

        // Open edit name from preview modal
        $('#sscv-preview-edit-name').on('click', function() {
            var certId = $('#sscv-preview-cert-id').val();
            // Find the name from the table row
            var rowName = $('#cert-row-' + certId).find('td:eq(2) strong').text();
            $('#sscv-edit-name-cert-id').val(certId);
            $('#sscv-edit-name-input').val(rowName);
            $('#sscv-preview-modal').hide();
            $('#sscv-edit-name-modal').show();
        });

        // ========================
        // Edit Certificate Name
        // ========================
        $(document).on('click', '.sscv-edit-cert-name', function() {
            var btn = $(this);
            var certId = btn.data('id');
            var currentName = btn.data('name');
            $('#sscv-edit-name-cert-id').val(certId);
            $('#sscv-edit-name-input').val(currentName);
            $('#sscv-edit-name-modal').show();
        });

        $('#sscv-confirm-edit-name').on('click', function() {
            var btn = $(this);
            var certId = $('#sscv-edit-name-cert-id').val();
            var newName = $('#sscv-edit-name-input').val().trim();

            if (!newName) {
                alert('Please enter a name.');
                return;
            }

            btn.prop('disabled', true).text('Saving...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_update_certificate_name',
                nonce: sscv_admin.nonce,
                cert_id: certId,
                full_name: newName
            }, function(response) {
                if (response.success) {
                    // Update the name in the table row
                    var row = $('#cert-row-' + certId);
                    row.find('td:eq(2) strong').text(response.data.full_name);
                    // Update data attribute on buttons
                    row.find('.sscv-edit-cert-name').data('name', response.data.full_name).attr('data-name', response.data.full_name);
                    row.find('.sscv-preview-cert').data('name', response.data.full_name).attr('data-name', response.data.full_name);
                    alert(response.data.message);
                    $('#sscv-edit-name-modal').hide();
                } else {
                    alert(response.data.message || 'Error occurred.');
                }
                btn.prop('disabled', false).text('Save Name');
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Save Name');
            });
        });

        // ========================
        // Certificate Revocation
        // ========================
        $(document).on('click', '.sscv-revoke-cert', function() {
            if (!confirm('Are you sure you want to revoke this certificate?')) return;

            var btn = $(this);
            var certId = btn.data('id');
            btn.prop('disabled', true);

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_revoke_certificate',
                nonce: sscv_admin.nonce,
                cert_id: certId
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            });
        });

        // ========================
        // Course Management
        // ========================
        $('#sscv-course-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var btn = $(this).find('[type="submit"]');
            btn.prop('disabled', true).text('Saving...');

            $.post(sscv_admin.ajax_url, formData, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false).text('Save Course');
                }
            });
        });

        $(document).on('click', '.sscv-edit-course', function() {
            var btn = $(this);
            $('#sscv-course-id').val(btn.data('id'));
            $('#course_title').val(btn.data('title'));
            $('#course_code').val(btn.data('code'));
            $('#description').val(btn.data('desc'));
            $('#template_id').val(btn.data('template'));
            $('#status').val(btn.data('status'));
            $('#sscv-course-form-title').text('Edit Course');
            $('#sscv-reset-course-form').show();
            $('html, body').animate({ scrollTop: $('#sscv-course-form').offset().top - 50 }, 300);
        });

        $('#sscv-reset-course-form').on('click', function() {
            $('#sscv-course-form')[0].reset();
            $('#sscv-course-id').val('');
            $('#sscv-course-form-title').text('Add New Course');
            $(this).hide();
        });

        $(document).on('click', '.sscv-delete-course', function() {
            if (!confirm('Are you sure you want to delete this course?')) return;

            var courseId = $(this).data('id');
            $.post(sscv_admin.ajax_url, {
                action: 'sscv_delete_course',
                nonce: sscv_admin.nonce,
                course_id: courseId
            }, function(response) {
                if (response.success) {
                    $('#course-row-' + courseId).fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(response.data.message);
                }
            });
        });

        // ========================
        // Template Management
        // ========================
        $('#sscv-template-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var btn = $(this).find('[type="submit"]');
            btn.prop('disabled', true).text('Saving...');

            $.post(sscv_admin.ajax_url, formData, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false).text('Save Template');
                }
            });
        });

        $(document).on('click', '.sscv-delete-template', function() {
            if (!confirm('Are you sure you want to delete this template?')) return;

            var tplId = $(this).data('id');
            $.post(sscv_admin.ajax_url, {
                action: 'sscv_delete_template',
                nonce: sscv_admin.nonce,
                template_id: tplId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });

        // Template Preview
        $('#sscv-preview-template').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Loading Preview...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_preview_template',
                nonce: sscv_admin.nonce,
                html_content: $('#html_content').val(),
                css_content: $('#css_content').val()
            }, function(response) {
                if (response.success) {
                    var previewHtml = '<style>' + response.data.css + '</style>' + response.data.html;
                    $('#sscv-preview-content').html(previewHtml);
                    $('#sscv-template-preview').show();
                    $('html, body').animate({ scrollTop: $('#sscv-template-preview').offset().top - 30 }, 300);
                }
                btn.prop('disabled', false).text('Preview Template');
            });
        });

        // ========================
        // Project Approval/Rejection
        // ========================
        $(document).on('click', '.sscv-approve-project', function() {
            var btn = $(this);
            var projectId = btn.data('id');
            btn.prop('disabled', true);

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_approve_project',
                nonce: sscv_admin.nonce,
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.sscv-reject-project', function() {
            if (!confirm('Are you sure you want to reject this project?')) return;

            var projectId = $(this).data('id');
            $.post(sscv_admin.ajax_url, {
                action: 'sscv_reject_project',
                nonce: sscv_admin.nonce,
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });

        // ========================
        // Settings Save
        // ========================
        $('#sscv-settings-form').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('[type="submit"]');
            btn.prop('disabled', true).text('Saving...');

            $.post(sscv_admin.ajax_url, $(this).serialize(), function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message || 'Error saving settings.');
                }
                btn.prop('disabled', false).text('Save All Settings');
            }).fail(function() {
                alert('Request failed.');
                btn.prop('disabled', false).text('Save All Settings');
            });
        });

        // ========================
        // Bulk Export PDF
        // ========================
        $('#sscv-bulk-export-pdf').on('click', function() {
            var btn = $(this);

            if (!confirm('This will generate a single PDF containing all approved certificates. This may take a moment. Continue?')) {
                return;
            }

            btn.prop('disabled', true).text('Generating PDF...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_bulk_export_pdf',
                nonce: sscv_admin.nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    alert(response.data.message || 'Export failed.');
                }
                btn.prop('disabled', false).text('Export All Approved as PDF');
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Export All Approved as PDF');
            });
        });

    });
})(jQuery);
