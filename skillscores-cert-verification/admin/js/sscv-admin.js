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
            $('#sscv-reject-modal').hide();
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

    });
})(jQuery);
