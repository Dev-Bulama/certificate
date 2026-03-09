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
        // Certificate Approval (with email opt-in popup)
        // ========================
        $(document).on('click', '.sscv-approve-cert', function() {
            var btn = $(this);
            var certId = btn.data('id');
            $('#sscv-approve-cert-id').val(certId);
            $('#sscv-approve-send-email').prop('checked', true);
            $('#sscv-approve-modal').show();
        });

        $('#sscv-confirm-approve').on('click', function() {
            var btn = $(this);
            var certId = $('#sscv-approve-cert-id').val();
            var sendEmail = $('#sscv-approve-send-email').is(':checked') ? 1 : 0;

            btn.prop('disabled', true).text('Processing...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_approve_certificate',
                nonce: sscv_admin.nonce,
                cert_id: certId,
                send_email: sendEmail
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error occurred.');
                    btn.prop('disabled', false).text('Confirm Approval');
                }
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Confirm Approval');
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

        // Approve from preview modal - open approve modal
        $('#sscv-preview-approve').on('click', function() {
            var certId = $('#sscv-preview-cert-id').val();
            if (!certId) return;
            $('#sscv-approve-cert-id').val(certId);
            $('#sscv-approve-send-email').prop('checked', true);
            $('#sscv-preview-modal').hide();
            $('#sscv-approve-modal').show();
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
        // Bulk Export PDF with Filters
        // ========================
        $('#sscv-bulk-export-pdf').on('click', function() {
            var btn = $(this);

            if (!confirm('This will generate a PDF with the selected filters. This may take a moment. Continue?')) {
                return;
            }

            btn.prop('disabled', true).text('Generating PDF...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_bulk_export_pdf',
                nonce: sscv_admin.nonce,
                date_from: $('#sscv-export-date-from').val(),
                date_to: $('#sscv-export-date-to').val(),
                course_id: $('#sscv-export-course').val(),
                export_status: $('#sscv-export-status').val()
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    alert(response.data.message || 'Export failed.');
                }
                btn.prop('disabled', false).text('Export as PDF');
            }).fail(function() {
                alert('Request failed. Please try again.');
                btn.prop('disabled', false).text('Export as PDF');
            });
        });

        // ========================
        // Student Export CSV
        // ========================
        $('#sscv-export-students').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Exporting...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_export_students',
                nonce: sscv_admin.nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.open(response.data.csv_url, '_blank');
                } else {
                    alert(response.data.message || 'Export failed.');
                }
                btn.prop('disabled', false).text('Export Students (CSV)');
            }).fail(function() {
                alert('Request failed.');
                btn.prop('disabled', false).text('Export Students (CSV)');
            });
        });

        // ========================
        // Student Import CSV
        // ========================
        $('#sscv-import-students-form').on('submit', function(e) {
            e.preventDefault();
            var btn = $(this).find('[type="submit"]');
            var fileInput = $('#sscv-import-csv')[0];

            if (!fileInput.files.length) {
                alert('Please select a CSV file to import.');
                return;
            }

            btn.prop('disabled', true).text('Importing...');

            var formData = new FormData();
            formData.append('action', 'sscv_import_students');
            formData.append('nonce', sscv_admin.nonce);
            formData.append('csv_file', fileInput.files[0]);

            $.ajax({
                url: sscv_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Import failed.');
                    }
                    btn.prop('disabled', false).text('Import');
                },
                error: function() {
                    alert('Request failed.');
                    btn.prop('disabled', false).text('Import');
                }
            });
        });

        // ========================
        // Image Template Builder
        // ========================

        // Add field to image template canvas
        $(document).on('click', '.sscv-img-add-field', function() {
            var fieldName = $(this).data('field');
            var canvas = $('#sscv-img-template-canvas');
            if (!canvas.length) return;

            var fieldId = 'img-field-' + fieldName;
            if ($('#' + fieldId).length) {
                alert('This field is already on the canvas.');
                return;
            }

            var fieldEl = $('<div class="sscv-img-field-draggable" id="' + fieldId + '" data-field="' + fieldName + '">' +
                '<span class="sscv-img-field-label">{{' + fieldName + '}}</span>' +
                '<span class="sscv-img-field-remove">&times;</span>' +
                '</div>');

            canvas.append(fieldEl);
            fieldEl.css({ position: 'absolute', top: '50px', left: '50px', cursor: 'move' });

            var isDragging = false, startX, startY, origLeft, origTop;

            fieldEl.on('mousedown', function(e) {
                if ($(e.target).hasClass('sscv-img-field-remove')) return;
                isDragging = true;
                startX = e.pageX;
                startY = e.pageY;
                origLeft = parseInt(fieldEl.css('left'));
                origTop = parseInt(fieldEl.css('top'));
                e.preventDefault();
            });

            $(document).on('mousemove.drag' + fieldName, function(e) {
                if (!isDragging) return;
                fieldEl.css({
                    left: origLeft + (e.pageX - startX),
                    top: origTop + (e.pageY - startY)
                });
            });

            $(document).on('mouseup.drag' + fieldName, function() {
                if (isDragging) {
                    isDragging = false;
                    sscvUpdateImgPositions();
                }
            });

            sscvUpdateImgPositions();
        });

        // Remove field from canvas
        $(document).on('click', '.sscv-img-field-remove', function() {
            $(this).parent().remove();
            sscvUpdateImgPositions();
        });

        function sscvUpdateImgPositions() {
            var positions = {};
            var canvas = $('#sscv-img-template-canvas');
            if (!canvas.length) return;

            var canvasW = canvas.width();
            var canvasH = canvas.height();

            canvas.find('.sscv-img-field-draggable').each(function() {
                var el = $(this);
                var field = el.data('field');
                positions[field] = {
                    x: Math.round((parseInt(el.css('left')) / canvasW) * 100 * 100) / 100,
                    y: Math.round((parseInt(el.css('top')) / canvasH) * 100 * 100) / 100,
                    fontSize: el.data('fontsize') || '16',
                    color: el.data('color') || '#000000'
                };
            });

            $('#sscv-img-field-positions').val(JSON.stringify(positions));
        }

        // Save image template
        $('#sscv-image-template-form').on('submit', function(e) {
            e.preventDefault();

            // Sync visible URL input to hidden field before saving
            var imgUrl = $('#sscv-img-template-url-input').val() || $('#sscv-img-template-url').val();
            imgUrl = (imgUrl || '').trim();
            $('#sscv-img-template-url').val(imgUrl);

            var templateName = $('#sscv-img-template-name').val();

            if (!templateName || !imgUrl) {
                alert('Please enter a template name and provide a background image URL.');
                return;
            }

            sscvUpdateImgPositions();

            var btn = $(this).find('[type="submit"]');
            btn.prop('disabled', true).text('Saving...');

            $.post(sscv_admin.ajax_url, {
                action: 'sscv_save_image_template',
                nonce: sscv_admin.nonce,
                template_id: $('#sscv-img-template-id').val(),
                template_name: templateName,
                image_url: imgUrl,
                field_positions: $('#sscv-img-field-positions').val(),
                is_default: $('#sscv-img-is-default').is(':checked') ? 1 : 0
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Error saving template.');
                }
                btn.prop('disabled', false).text('Save Image Template');
            }).fail(function(xhr) {
                alert('Request failed. Server returned: ' + xhr.status);
                btn.prop('disabled', false).text('Save Image Template');
            });
        });

        // Upload image for image template via WP Media Library
        $(document).on('click', '#sscv-img-upload-btn', function(e) {
            e.preventDefault();

            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('WordPress Media Library is not available. Please use the direct URL input instead.');
                return;
            }

            var frame = wp.media({
                title: 'Select Certificate Background Image',
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#sscv-img-template-url').val(attachment.url);
                $('#sscv-img-template-url-input').val(attachment.url);
                sscvShowImageCanvas(attachment.url);
            });

            frame.open();
        });

        // Sync direct URL input to hidden field and show canvas
        $(document).on('input change paste', '#sscv-img-template-url-input', function() {
            var url = $(this).val().trim();
            $('#sscv-img-template-url').val(url);
            if (url) {
                sscvShowImageCanvas(url);
            }
        });

        // Also handle blur for pasted URLs
        $(document).on('blur', '#sscv-img-template-url-input', function() {
            var url = $(this).val().trim();
            $('#sscv-img-template-url').val(url);
            if (url) {
                sscvShowImageCanvas(url);
            }
        });

        // Helper: show canvas with background image
        function sscvShowImageCanvas(url) {
            var canvas = $('#sscv-img-template-canvas');
            canvas.css('background-image', 'url(' + url + ')');
            canvas.show();
        }

        // Show canvas on page load if URL already has a value
        (function() {
            var existingUrl = $('#sscv-img-template-url').val() || $('#sscv-img-template-url-input').val();
            if (existingUrl && existingUrl.trim()) {
                sscvShowImageCanvas(existingUrl.trim());
                $('#sscv-img-template-url').val(existingUrl.trim());
                $('#sscv-img-template-url-input').val(existingUrl.trim());
            }
        })();

    });
})(jQuery);
