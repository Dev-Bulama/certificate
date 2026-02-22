/**
 * Public JavaScript for Certificate & Verification System
 */
(function($) {
    'use strict';

    // Utility: show message
    function showMessage(container, message, type) {
        var cls = type === 'success' ? 'sscv-message-success' : 'sscv-message-error';
        $(container).html('<div class="' + cls + '">' + message + '</div>').show();
    }

    // Utility: toggle button loading state
    function toggleBtn(btn, loading) {
        if (loading) {
            btn.prop('disabled', true);
            btn.find('.sscv-btn-text').hide();
            btn.find('.sscv-btn-loading').show();
        } else {
            btn.prop('disabled', false);
            btn.find('.sscv-btn-text').show();
            btn.find('.sscv-btn-loading').hide();
        }
    }

    // Utility: get reCAPTCHA token
    function getRecaptchaToken(action, callback) {
        var siteKey = '';
        if (typeof grecaptcha !== 'undefined' && sscv_ajax.plugin_url) {
            // Check if reCAPTCHA is loaded
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                if (scripts[i].src && scripts[i].src.indexOf('recaptcha') !== -1) {
                    var match = scripts[i].src.match(/render=([^&]+)/);
                    if (match) siteKey = match[1];
                }
            }
        }

        if (siteKey && typeof grecaptcha !== 'undefined') {
            grecaptcha.ready(function() {
                grecaptcha.execute(siteKey, { action: action }).then(function(token) {
                    callback(token);
                });
            });
        } else {
            callback('');
        }
    }

    $(document).ready(function() {

        // ========================
        // Certificate Application Form
        // ========================
        $('#sscv-application-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var btn = form.find('#sscv-submit-application');
            var msgContainer = '#sscv-application-message';

            toggleBtn(btn, true);
            $(msgContainer).hide();

            getRecaptchaToken('certificate_application', function(token) {
                var formData = new FormData(form[0]);
                if (token) {
                    formData.set('recaptcha_token', token);
                }

                $.ajax({
                    url: sscv_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showMessage(msgContainer, response.data.message, 'success');
                            form[0].reset();
                        } else {
                            showMessage(msgContainer, response.data.message, 'error');
                        }
                        toggleBtn(btn, false);
                    },
                    error: function() {
                        showMessage(msgContainer, 'Network error. Please try again.', 'error');
                        toggleBtn(btn, false);
                    }
                });
            });
        });

        // ========================
        // Certificate Verification
        // ========================
        // Update placeholder based on search type
        $('#sscv-search-type').on('change', function() {
            var placeholders = {
                'certificate_id': 'Enter Certificate ID...',
                'student_id': 'Enter Student ID...',
                'student_name': 'Enter Student Name...'
            };
            $('#sscv-search-value').attr('placeholder', placeholders[$(this).val()] || '');
        });

        $('#sscv-verification-form').on('submit', function(e) {
            e.preventDefault();

            var btn = $('#sscv-verify-btn');
            var msgContainer = '#sscv-verify-message';
            var resultsContainer = '#sscv-verify-results';

            toggleBtn(btn, true);
            $(msgContainer).hide();
            $(resultsContainer).hide();

            $.post(sscv_ajax.ajax_url, $(this).serialize(), function(response) {
                if (response.success) {
                    var html = '';
                    var tpl = $('#sscv-verify-result-tpl').html();

                    $.each(response.data.certificates, function(i, cert) {
                        var result = tpl;
                        // Replace template placeholders
                        $.each(cert, function(key, value) {
                            var regex = new RegExp('\\{\\{' + key + '\\}\\}', 'g');
                            result = result.replace(regex, value || '');
                        });

                        // Handle conditional blocks
                        result = result.replace(/\{\{#if (\w+)\}\}([\s\S]*?)\{\{\/if\}\}/g, function(match, key, content) {
                            return cert[key] ? content : '';
                        });

                        html += result;
                    });

                    $(resultsContainer).html(html).show();
                } else {
                    showMessage(msgContainer, response.data.message, 'error');
                }
                toggleBtn(btn, false);
            }).fail(function() {
                showMessage(msgContainer, 'Network error. Please try again.', 'error');
                toggleBtn(btn, false);
            });
        });

        // Auto-verify from URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        var certIdParam = urlParams.get('cert_id');
        if (certIdParam && $('#sscv-search-value').length) {
            $('#sscv-search-value').val(certIdParam);
            $('#sscv-verification-form').trigger('submit');
        }

        // ========================
        // Project Submission Form
        // ========================
        $('#sscv-project-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var btn = form.find('#sscv-submit-project');
            var msgContainer = '#sscv-project-message';

            toggleBtn(btn, true);
            $(msgContainer).hide();

            getRecaptchaToken('project_submission', function(token) {
                var formData = new FormData(form[0]);
                if (token) {
                    formData.set('recaptcha_token', token);
                }

                $.ajax({
                    url: sscv_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showMessage(msgContainer, response.data.message, 'success');
                            form[0].reset();
                        } else {
                            showMessage(msgContainer, response.data.message, 'error');
                        }
                        toggleBtn(btn, false);
                    },
                    error: function() {
                        showMessage(msgContainer, 'Network error. Please try again.', 'error');
                        toggleBtn(btn, false);
                    }
                });
            });
        });

        // ========================
        // Project Directory
        // ========================
        var dirCurrentPage = 1;
        var dirSearchTimer = null;

        function loadProjects(page) {
            page = page || 1;
            dirCurrentPage = page;

            var sortVal = ($('#sscv-dir-sort').val() || 'created_at-DESC').split('-');
            var perPage = $('#sscv-dir-per-page').val() || 10;

            $('#sscv-dir-loading').show();
            $('#sscv-projects-body').html('<tr><td colspan="8" class="sscv-loading-cell">Loading...</td></tr>');

            $.post(sscv_ajax.ajax_url, {
                action: 'sscv_load_projects',
                page: page,
                per_page: perPage,
                search: $('#sscv-dir-search').val() || '',
                category: $('#sscv-dir-category').val() || '',
                sort_by: sortVal[0] || 'created_at',
                sort_dir: sortVal[1] || 'DESC'
            }, function(response) {
                $('#sscv-dir-loading').hide();

                if (response.success && response.data.projects.length > 0) {
                    var rows = '';
                    $.each(response.data.projects, function(i, proj) {
                        rows += '<tr>';
                        rows += '<td>' + escHtml(proj.student_id) + '</td>';
                        rows += '<td><strong>' + escHtml(proj.student_name) + '</strong></td>';
                        rows += '<td>';
                        if (proj.project_image_url) {
                            rows += '<img src="' + escAttr(proj.project_image_url) + '" alt="" class="sscv-proj-img" />';
                        } else {
                            rows += '<div class="sscv-no-img">-</div>';
                        }
                        rows += '</td>';
                        rows += '<td>' + escHtml(proj.project_title) + '<br><small>' + escHtml(proj.category) + '</small></td>';
                        rows += '<td>' + linkOrDash(proj.website_url, 'Visit') + '</td>';
                        rows += '<td>' + linkOrDash(proj.github_url, 'Repo') + '</td>';
                        rows += '<td>' + linkOrDash(proj.social_url, 'Profile') + '</td>';
                        rows += '<td>' + linkOrDash(proj.cv_url, 'Download') + '</td>';
                        rows += '</tr>';
                    });
                    $('#sscv-projects-body').html(rows);
                    renderPagination(response.data.pages, response.data.current_page);
                } else {
                    $('#sscv-projects-body').html('<tr><td colspan="8" class="sscv-loading-cell">No projects found.</td></tr>');
                    $('#sscv-dir-pagination').html('');
                }
            }).fail(function() {
                $('#sscv-dir-loading').hide();
                $('#sscv-projects-body').html('<tr><td colspan="8" class="sscv-loading-cell">Failed to load projects.</td></tr>');
            });
        }

        function renderPagination(totalPages, currentPage) {
            if (totalPages <= 1) {
                $('#sscv-dir-pagination').html('');
                return;
            }

            var html = '';
            html += '<button ' + (currentPage <= 1 ? 'disabled' : '') + ' data-page="' + (currentPage - 1) + '">&laquo; Prev</button>';

            for (var i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += '<button class="' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += '<button disabled>...</button>';
                }
            }

            html += '<button ' + (currentPage >= totalPages ? 'disabled' : '') + ' data-page="' + (currentPage + 1) + '">Next &raquo;</button>';

            $('#sscv-dir-pagination').html(html);
        }

        // Directory event handlers
        if ($('#sscv-projects-table').length) {
            loadProjects(1);

            $('#sscv-dir-search').on('keyup', function() {
                clearTimeout(dirSearchTimer);
                dirSearchTimer = setTimeout(function() {
                    loadProjects(1);
                }, 400);
            });

            $('#sscv-dir-category, #sscv-dir-sort').on('change', function() {
                loadProjects(1);
            });

            $(document).on('click', '#sscv-dir-pagination button:not(:disabled)', function() {
                loadProjects($(this).data('page'));
            });
        }

        // ========================
        // Student Dashboard
        // ========================
        $('#sscv-dashboard-login-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var msgContainer = '#sscv-dashboard-login-msg';

            toggleBtn(btn, true);
            $(msgContainer).hide();

            $.post(sscv_ajax.ajax_url, form.serialize(), function(response) {
                if (response.success) {
                    var data = response.data;

                    // Populate profile
                    if (data.student.passport_url) {
                        $('#sscv-dash-avatar').html('<img src="' + escAttr(data.student.passport_url) + '" alt="" />');
                    }
                    $('#sscv-dash-name').text(data.student.full_name);
                    $('#sscv-dash-sid-display').text('ID: ' + data.student.student_id);
                    $('#sscv-dash-email-display').text(data.student.email);

                    // Populate certificates
                    var certRows = '';
                    if (data.certificates.length > 0) {
                        $.each(data.certificates, function(i, cert) {
                            certRows += '<tr>';
                            certRows += '<td><code>' + escHtml(cert.certificate_id) + '</code></td>';
                            certRows += '<td>' + escHtml(cert.course_title) + '</td>';
                            certRows += '<td>' + escHtml(cert.grade) + '</td>';
                            certRows += '<td>' + statusBadge(cert.status) + '</td>';
                            certRows += '<td>' + escHtml(cert.date_issued || '—') + '</td>';
                            certRows += '<td>';
                            if (cert.status === 'approved') {
                                if (cert.certificate_url) {
                                    certRows += '<a href="' + escAttr(cert.certificate_url) + '" target="_blank" class="sscv-btn sscv-btn-secondary" style="padding:6px 12px;font-size:12px;">View</a> ';
                                }
                                if (cert.pdf_url) {
                                    certRows += '<a href="' + escAttr(cert.pdf_url) + '" target="_blank" class="sscv-btn sscv-btn-primary" style="padding:6px 12px;font-size:12px;">Download</a>';
                                }
                            } else {
                                certRows += '—';
                            }
                            certRows += '</td>';
                            certRows += '</tr>';
                        });
                    } else {
                        certRows = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#999;">No certificates yet.</td></tr>';
                    }
                    $('#sscv-dash-certs').html(certRows);

                    // Populate projects
                    var projRows = '';
                    if (data.projects.length > 0) {
                        $.each(data.projects, function(i, proj) {
                            projRows += '<tr>';
                            projRows += '<td>' + escHtml(proj.project_title) + '</td>';
                            projRows += '<td>' + escHtml(proj.category) + '</td>';
                            projRows += '<td>' + statusBadge(proj.status) + '</td>';
                            projRows += '<td>' + linkOrDash(proj.website_url, 'Visit') + '</td>';
                            projRows += '</tr>';
                        });
                    } else {
                        projRows = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#999;">No projects yet.</td></tr>';
                    }
                    $('#sscv-dash-projects').html(projRows);

                    // Show dashboard, hide login
                    $('#sscv-dashboard-login').hide();
                    $('#sscv-dashboard-content').show();
                } else {
                    showMessage(msgContainer, response.data.message, 'error');
                }
                toggleBtn(btn, false);
            }).fail(function() {
                showMessage(msgContainer, 'Network error. Please try again.', 'error');
                toggleBtn(btn, false);
            });
        });

        $('#sscv-dash-logout').on('click', function() {
            $('#sscv-dashboard-content').hide();
            $('#sscv-dashboard-login').show();
            $('#sscv-dashboard-login-form')[0].reset();
        });

        // ========================
        // Utility Functions
        // ========================
        function escHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function escAttr(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function linkOrDash(url, text) {
            if (url) {
                return '<a href="' + escAttr(url) + '" target="_blank" rel="noopener noreferrer">' + escHtml(text) + '</a>';
            }
            return '—';
        }

        function statusBadge(status) {
            var classes = {
                'pending': 'sscv-badge-pending',
                'approved': 'sscv-badge-approved',
                'rejected': 'sscv-badge-rejected',
                'revoked': 'sscv-badge-revoked'
            };
            var cls = classes[status] || 'sscv-badge-pending';
            return '<span class="sscv-badge ' + cls + '">' + escHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
        }

    });

})(jQuery);
