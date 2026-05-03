(function() {
    'use strict';

    var t = window.i18n || {};

    $(document).ready(function() {
        initSummernoteEditors();
        bindCommentForms();
        bindAssignSelect();
        bindDevFieldsForm();
        bindDoneButton();
        bindColumnSelect();
        bindArchiveDelete();
        bindLabels();
        bindEditableContent();
    });

    // =========================================================================
    // SUMMERNOTE INITIALIZATION
    // =========================================================================
    function initSummernoteEditors() {
        $('.summernote-editor').each(function() {
            var $el = $(this);
            var placeholder = $el.data('placeholder') || '';

            // Custom button for file attachments (non-image)
            var AttachFileButton = function(context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="fas fa-paperclip"></i>',
                    tooltip: 'Attach file',
                    click: function() {
                        var $input = $('<input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.csv,.ppt,.pptx">');
                        $input.on('change', function() {
                            var files = this.files;
                            for (var i = 0; i < files.length; i++) {
                                uploadFile(files[i], $el);
                            }
                        });
                        $input.click();
                    }
                });
                return button.render();
            };

            var opts = {
                placeholder: placeholder,
                height: 120,
                toolbar: [
                    ['style', ['bold', 'italic', 'strikethrough', 'underline']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link', 'picture', 'hr']],
                    ['custom', ['attachFile']],
                    ['misc', ['codeview', 'undo', 'redo']]
                ],
                buttons: {
                    attachFile: AttachFileButton
                },
                callbacks: {
                    onImageUpload: function(files) {
                        // Handles both toolbar image button AND Ctrl+V paste of images
                        for (var i = 0; i < files.length; i++) {
                            uploadFile(files[i], $el);
                        }
                    }
                },
                popover: {
                    image: [
                        ['resize', ['resizeFull', 'resizeHalf', 'resizeQuarter']],
                        ['float', ['floatLeft', 'floatRight', 'floatNone']],
                        ['remove', ['removeMedia']]
                    ],
                    link: [
                        ['link', ['linkDialogShow', 'unlink']]
                    ]
                },
                disableDragAndDrop: false
            };
            if (document.documentElement.lang === 'es') {
                opts.lang = 'es-ES';
            }
            $el.summernote(opts);
        });
    }

    function uploadFile(file, $editor) {
        if (file.size > 10 * 1024 * 1024) {
            alert(t.file_too_large || 'File too large. Maximum: 10MB.');
            return;
        }

        var fd = new FormData();
        fd.append('file', file);

        var isImage = file.type.indexOf('image/') === 0;

        $.ajax({
            url: '/tickets/' + window.ticketId + '/upload-file',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.url) {
                    if (isImage) {
                        $editor.summernote('insertImage', res.url, function($image) {
                            $image.css('max-width', '100%');
                            $image.attr('loading', 'lazy');
                        });
                    } else {
                        // Insert as inline download link (like Trello)
                        var icon = getFileIcon(file.name);
                        var linkHtml = '<a href="' + res.url + '" target="_blank" rel="noopener" class="comment-file-link">'
                            + '<i class="fas ' + icon + '"></i> ' + escHtml(file.name)
                            + '</a>&nbsp;';
                        $editor.summernote('pasteHTML', linkHtml);
                    }
                }
            },
            error: function(xhr) {
                var msg = t.upload_failed || 'Failed to upload file.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                alert(msg);
            }
        });
    }

    function getFileIcon(filename) {
        var ext = (filename.split('.').pop() || '').toLowerCase();
        var icons = {
            pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word',
            xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-csv',
            ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
            zip: 'fa-file-archive', rar: 'fa-file-archive',
            txt: 'fa-file-alt'
        };
        return icons[ext] || 'fa-file';
    }

    // =========================================================================
    // REUSABLE CONFIRMATION MODAL
    // =========================================================================
    function showConfirmModal(message, onConfirm, onCancel) {
        var $modal = $('#confirmActionModal');
        $('#confirm-action-text').text(message);

        $('#confirm-action-btn').off('click').on('click', function() {
            $modal.data('confirmed', true);
            $modal.modal('hide');
            if (onConfirm) onConfirm();
        });

        $modal.off('hidden.bs.modal').on('hidden.bs.modal', function() {
            if (!$modal.data('confirmed')) {
                if (onCancel) onCancel();
            }
            $modal.data('confirmed', false);
        });

        $modal.data('confirmed', false);
        $modal.modal('show');
    }

    function showToast(message) {
        var $toast = $('#action-toast');
        $('#action-toast-text').text(message);
        $toast.fadeIn(200).delay(2000).fadeOut(400);
    }

    // =========================================================================
    // COMMENT FORM SUBMIT
    // =========================================================================
    function bindCommentForms() {
        $(document).on('submit', '.comment-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var visibility = $form.data('visibility');
            var $editor = $form.find('.summernote-editor');
            var content = $editor.summernote('code');

            // Check if editor is truly empty (Summernote puts <p><br></p> when empty)
            var textOnly = $('<div>').html(content).text().trim();
            var hasImages = content.indexOf('<img') !== -1;

            if (!textOnly && !hasImages) return;

            var btn = $form.find('button[type="submit"]');
            btn.prop('disabled', true);

            $.ajax({
                url: '/tickets/' + window.ticketId + '/comment',
                type: 'POST',
                data: { content: content, visibility: visibility },
                success: function(res) {
                    if (res.success) {
                        var html = buildCommentHtml(res.comment);
                        $('#comments-' + visibility).append(html);

                        // Clear editor
                        $editor.summernote('code', '');

                        var container = $('#comments-' + visibility);
                        container.scrollTop(container[0].scrollHeight);
                    }
                },
                error: function(xhr) {
                    var msg = t.comment_failed || 'Failed to add comment.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    }
                    alert(msg);
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    }

    function buildCommentHtml(c) {
        var sourceClass = c.source === 'client' ? ' source-client' : '';
        var colorClass = ' author-color-' + ((c.author_id || 0) % 8);
        var clientBadge = c.source === 'client'
            ? '<span class="badge badge-client-source mr-1"><i class="fas fa-user mr-1"></i>' + (t.client_label || 'Client') + '</span>'
            : '';

        // Dev comments get edit/delete buttons
        var actionsHtml = (c.source !== 'client')
            ? '<div class="comment-actions">'
              + '<button type="button" class="btn btn-sm btn-link text-muted p-0 mr-1 btn-edit-comment" data-id="' + c.id + '" title="Editar"><i class="fas fa-pencil-alt" style="font-size:0.7rem;"></i></button>'
              + '<button type="button" class="btn btn-sm btn-link text-danger p-0 btn-delete-comment" data-id="' + c.id + '" title="Eliminar"><i class="fas fa-trash" style="font-size:0.7rem;"></i></button>'
              + '</div>'
            : '';

        var html = '<div class="comment-item ' + c.visibility + sourceClass + colorClass + '" data-comment-id="' + c.id + '">'
            + '<div class="d-flex justify-content-between align-items-center">'
            + '<div>' + clientBadge + '<strong class="small">' + escHtml(c.author_name) + '</strong></div>'
            + '<div class="d-flex align-items-center">'
            + '<small class="text-muted mr-2">' + escHtml(c.created_at) + '</small>'
            + actionsHtml
            + '</div>'
            + '</div>'
            + '<div class="comment-body">' + c.content + '</div>'
            + '</div>';
        return html;
    }

    // =========================================================================
    // EDIT / DELETE COMMENTS
    // =========================================================================
    $(document).on('click', '.btn-delete-comment', function() {
        var $item = $(this).closest('.comment-item');
        var commentId = $(this).data('id');
        if (!confirm(t.delete_comment_confirm || 'Eliminar este comentario?')) return;

        $.ajax({
            url: '/tickets/' + window.ticketId + '/comment/' + commentId + '/delete',
            type: 'POST',
            success: function(res) {
                if (res.success) $item.fadeOut(300, function() { $(this).remove(); });
            },
            error: function(xhr) {
                var res = xhr.responseJSON || {};
                alert(res.error || 'Error al eliminar comentario.');
            }
        });
    });

    $(document).on('click', '.btn-edit-comment', function() {
        var $item = $(this).closest('.comment-item');
        var commentId = $(this).data('id');
        var $body = $item.find('.comment-body');
        var originalHtml = $body.html();

        // Replace body with Summernote editor
        var editorId = 'edit-comment-' + commentId;
        $body.html('<div id="' + editorId + '">' + originalHtml + '</div>'
            + '<div class="mt-1">'
            + '<button class="btn btn-sm btn-success btn-save-edit-comment" data-id="' + commentId + '"><i class="fas fa-check mr-1"></i>Guardar</button> '
            + '<button class="btn btn-sm btn-secondary btn-cancel-edit-comment"><i class="fas fa-times mr-1"></i>Cancelar</button>'
            + '</div>');

        var opts = { height: 100, toolbar: [['style', ['bold', 'italic', 'underline']], ['para', ['ul', 'ol']], ['insert', ['link']], ['misc', ['codeview']]] };
        if (document.documentElement.lang === 'es') opts.lang = 'es-ES';
        $('#' + editorId).summernote(opts);

        // Cancel
        $body.find('.btn-cancel-edit-comment').on('click', function() {
            $('#' + editorId).summernote('destroy');
            $body.html(originalHtml);
        });

        // Save
        $body.find('.btn-save-edit-comment').on('click', function() {
            var newContent = $('#' + editorId).summernote('code');
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/tickets/' + window.ticketId + '/comment/' + commentId + '/edit',
                type: 'POST',
                data: { content: newContent },
                success: function(res) {
                    if (res.success) {
                        $('#' + editorId).summernote('destroy');
                        $body.html(res.content);
                    }
                },
                error: function(xhr) {
                    var res = xhr.responseJSON || {};
                    alert(res.error || 'Error al editar comentario.');
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });
    });

    // =========================================================================
    // ASSIGNMENT (with confirmation)
    // =========================================================================
    function bindAssignSelect() {
        var prevAssign = $('#assign-select').val();

        $('#assign-select').on('change', function() {
            var select = $(this);
            var memberId = select.val();
            var memberName = select.find('option:selected').text().trim();

            var msg = (t.confirm_assign || 'Assign to :name?').replace(':name', memberName);
            showConfirmModal(msg, function() {
                $.ajax({
                    url: '/tickets/' + window.ticketId + '/assign',
                    type: 'POST',
                    data: { assigned_to: memberId || '' },
                    success: function(res) {
                        if (res.success) {
                            prevAssign = memberId;
                            showToast(t.assign_success || 'Assigned successfully.');
                        }
                    },
                    error: function() {
                        select.val(prevAssign);
                        alert(t.assign_failed || 'Failed to assign ticket.');
                    }
                });
            }, function() {
                select.val(prevAssign);
            });
        });
    }

    // =========================================================================
    // DEV FIELDS (sp_notes + deploy_notes)
    // =========================================================================
    function bindDevFieldsForm() {
        $('#form-dev-fields').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = {
                sp_notes:     form.find('[name="sp_notes"]').val(),
                deploy_notes: form.find('[name="deploy_notes"]').val()
            };

            $.ajax({
                url: '/tickets/' + window.ticketId + '/dev-fields',
                type: 'POST',
                data: data,
                success: function(res) {
                    if (res.success) {
                        $('#dev-fields-status').show().delay(2000).fadeOut();
                    }
                },
                error: function() {
                    alert(t.dev_notes_failed || 'Failed to save dev notes.');
                }
            });
        });

        // --- Due date (internal tickets) ---
        $('#due-date-input').on('change', function() {
            var $status = $('#due-date-status');
            $.ajax({
                url: '/tickets/' + window.ticketId + '/dev-fields',
                type: 'POST',
                data: { due_date: $(this).val() || null },
                success: function(res) {
                    if (res.success) $status.show().delay(2000).fadeOut();
                }
            });
        });

        // --- PR Links ---
        $('#btn-add-pr').on('click', function() {
            $(this).hide();
            $('#pr-link-form').show();
            $('#pr-link-url').focus();
        });

        $('#btn-cancel-pr').on('click', function() {
            $('#pr-link-form').hide();
            $('#pr-link-url, #pr-link-title').val('');
            $('#btn-add-pr').show();
        });

        $('#btn-save-pr').on('click', function() {
            var url = $('#pr-link-url').val().trim();
            var title = $('#pr-link-title').val().trim();
            if (!url) { $('#pr-link-url').focus(); return; }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/tickets/' + window.ticketId + '/pr-links',
                type: 'POST',
                data: { url: url, title: title },
                success: function(res) {
                    if (res.success) {
                        var link = res.link;
                        var html = '<div class="dev-link-item d-flex align-items-center mb-1" data-id="' + link.id + '">'
                            + '<a href="' + escHtml(link.url) + '" target="_blank" rel="noopener" class="small text-truncate flex-grow-1">'
                            + '<i class="fas fa-code-branch mr-1"></i>' + escHtml(link.title || link.url)
                            + '</a>'
                            + '<button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-pr" data-id="' + link.id + '">'
                            + '<i class="fas fa-times"></i></button>'
                            + '</div>';
                        $('#pr-links-list').append(html);
                        $('#pr-link-url, #pr-link-title').val('');
                        $('#pr-link-form').hide();
                        $('#btn-add-pr').show();
                    }
                },
                error: function() {
                    alert(t.dev_notes_failed || 'Failed to save PR link.');
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });

        $(document).on('click', '.btn-remove-pr', function() {
            var $item = $(this).closest('.dev-link-item');
            var linkId = $(this).data('id');

            $.ajax({
                url: '/tickets/' + window.ticketId + '/pr-links/' + linkId + '/delete',
                type: 'POST',
                success: function(res) {
                    if (res.success) $item.remove();
                }
            });
        });

        // --- Commits (Summernote mini — captures pasted links from GitHub) ---
        var commitEditorInit = false;

        $('#btn-add-commit').on('click', function() {
            $(this).hide();
            $('#commit-form').show();
            if (!commitEditorInit) {
                $('#commit-editor').summernote({
                    height: 40,
                    toolbar: [], // no toolbar — just a paste area
                    placeholder: t.commit_paste_placeholder || 'Paste commit URL or title from GitHub',
                    disableDragAndDrop: true,
                    shortcuts: false
                });
                commitEditorInit = true;
            }
            $('#commit-editor').summernote('focus');
        });

        $('#btn-cancel-commit').on('click', function() {
            $('#commit-editor').summernote('code', '');
            $('#commit-form').hide();
            $('#btn-add-commit').show();
        });

        function extractCommitFromHtml(html) {
            var $temp = $('<div>').html(html);

            // Strategy 1: Find ANY <a> with href containing "github.com"
            var $link = $temp.find('a[href*="github.com"]').first();
            if ($link.length) {
                var url = $link.attr('href');
                var title = $temp.text().trim();
                // Try to extract commit hash from URL
                var hashMatch = url.match(/\/commit\/([a-f0-9]+)/i) || url.match(/\/([a-f0-9]{7,40})(?:\?|$|#)/i);
                var hash = hashMatch ? hashMatch[1].substring(0, 7) : url.substring(url.length - 7);
                return { hash: hash, message: title || $link.text().trim(), url: url };
            }

            // Strategy 2: Find GitHub URL in the raw HTML string (regex on source)
            var rawUrlMatch = html.match(/href=["'](https?:\/\/github\.com\/[^"']+)["']/i);
            if (rawUrlMatch) {
                var rawUrl = rawUrlMatch[1];
                var rawText = $temp.text().trim();
                var rawHash = rawUrl.match(/\/commit\/([a-f0-9]+)/i);
                return { hash: rawHash ? rawHash[1].substring(0, 7) : 'unknown', message: rawText, url: rawUrl };
            }

            // Strategy 3: Plain text fallback
            var text = $temp.text().trim();
            if (!text) return null;

            // Plain GitHub URL in text
            var urlMatch = text.match(/(https?:\/\/github\.com\/[^\s]+\/commit\/([a-f0-9]+))/i);
            if (urlMatch) {
                return { hash: urlMatch[2].substring(0, 7), message: null, url: urlMatch[1] };
            }

            // Plain URL
            var plainUrl = text.match(/(https?:\/\/github\.com\/[^\s]+)/i);
            if (plainUrl) {
                return { hash: text.substring(0, 7), message: text, url: plainUrl[1] };
            }

            // Hash + message
            var hm = text.match(/^([a-f0-9]{7,40})\s*(.*)/i);
            if (hm) {
                return { hash: hm[1].substring(0, 7), message: hm[2] || null, url: null };
            }

            // Just text
            return { hash: text.substring(0, 7), message: text, url: null };
        }

        function saveCommit(parsed) {
            var $btn = $('#btn-save-commit');
            $btn.prop('disabled', true);
            $.ajax({
                url: '/tickets/' + window.ticketId + '/commits',
                type: 'POST',
                data: { hash: parsed.hash, message: parsed.message, url: parsed.url },
                success: function(res) {
                    if (res.success) {
                        var c = res.commit;
                        var shortHash = c.hash.substring(0, 7);
                        var inner = c.url
                            ? '<a href="' + escHtml(c.url) + '" target="_blank" rel="noopener" class="small text-truncate flex-grow-1"><code class="mr-1">' + escHtml(shortHash) + '</code>' + escHtml(c.message || '') + '</a>'
                            : '<span class="small text-truncate flex-grow-1"><code class="mr-1">' + escHtml(shortHash) + '</code>' + escHtml(c.message || '') + '</span>';
                        var html = '<div class="dev-link-item d-flex align-items-center mb-1" data-id="' + c.id + '">'
                            + inner
                            + '<button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-commit" data-id="' + c.id + '">'
                            + '<i class="fas fa-times"></i></button></div>';
                        $('#commits-list').append(html);
                        $('#commit-editor').summernote('code', '');
                        $('#commit-form').hide();
                        $('#btn-add-commit').show();
                    }
                },
                error: function() { alert(t.dev_notes_failed || 'Failed to save commit.'); },
                complete: function() { $btn.prop('disabled', false); }
            });
        }

        $('#btn-save-commit').on('click', function() {
            var html = $('#commit-editor').summernote('code');
            var parsed = extractCommitFromHtml(html);
            if (!parsed) { $('#commit-editor').summernote('focus'); return; }
            saveCommit(parsed);
        });

        // --- SP file upload ---
        $('#sp-file-input').on('change', function() {
            var file = this.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('file', file);

            $.ajax({
                url: '/tickets/' + window.ticketId + '/sp-files',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        var f = res.file;
                        var html = '<div class="dev-link-item d-flex align-items-center mb-1" data-id="' + f.id + '">'
                            + '<a href="' + escHtml(f.url) + '" download="' + escHtml(f.name) + '" class="small text-truncate flex-grow-1">'
                            + '<i class="fas fa-database mr-1 text-info"></i>' + escHtml(f.name)
                            + '<small class="text-muted ml-1">(' + (f.size / 1024).toFixed(1) + ' KB)</small>'
                            + '</a>'
                            + '<button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-sp-file" data-id="' + f.id + '">'
                            + '<i class="fas fa-times"></i></button>'
                            + '</div>';
                        $('#sp-files-list').append(html);
                    }
                },
                error: function() {
                    alert(t.upload_failed || 'Failed to upload file.');
                }
            });
            $(this).val('');
        });

        $(document).on('click', '.btn-remove-sp-file', function() {
            var $item = $(this).closest('.dev-link-item');
            var fileId = $(this).data('id');
            $.ajax({
                url: '/tickets/' + window.ticketId + '/sp-files/' + fileId + '/delete',
                type: 'POST',
                success: function(res) { if (res.success) $item.remove(); }
            });
        });

        $(document).on('click', '.btn-remove-commit', function() {
            var $item = $(this).closest('.dev-link-item');
            var commitId = $(this).data('id');

            $.ajax({
                url: '/tickets/' + window.ticketId + '/commits/' + commitId + '/delete',
                type: 'POST',
                success: function(res) {
                    if (res.success) $item.remove();
                }
            });
        });
    }

    // =========================================================================
    // MARK AS DONE
    // =========================================================================
    function bindDoneButton() {
        // Internal tickets: move directly to done, no resolution message
        $('#btn-mark-done-internal').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true);
            $.ajax({
                url: '/tickets/' + window.ticketId + '/move-column',
                type: 'POST',
                data: { board_column: 'done' },
                success: function() { window.location.reload(); },
                error: function() {
                    alert(t.move_done_failed || 'Failed to move ticket to Done.');
                    btn.prop('disabled', false);
                }
            });
        });

        // Initialize done modal on open (not on page load to avoid errors)
        $('#doneModal').on('shown.bs.modal', function() {
            var $checkbox = $('#done-generic');
            if (!$checkbox.data('init')) {
                $checkbox.data('init', true);
                $checkbox.on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#done-message').val(t.default_resolution || 'El problema fue resuelto. Por favor intente de nuevo.').prop('disabled', true);
                    } else {
                        $('#done-message').val('').prop('disabled', false).focus();
                    }
                });
            }
        });

        $('#btn-mark-done').on('click', function() {
            var btn = $(this);
            var message = $('#done-message').val();
            btn.prop('disabled', true);

            $.ajax({
                url: '/tickets/' + window.ticketId + '/resolve',
                type: 'POST',
                data: { resolution_message: message },
                success: function() {
                    $.ajax({
                        url: '/tickets/' + window.ticketId + '/move-column',
                        type: 'POST',
                        data: { board_column: 'done' },
                        success: function() { window.location.reload(); },
                        error: function() {
                            alert(t.move_done_failed || 'Failed to move ticket to Done.');
                            btn.prop('disabled', false);
                        }
                    });
                },
                error: function() {
                    alert(t.resolve_failed || 'Failed to save resolution.');
                    btn.prop('disabled', false);
                }
            });
        });
    }

    // =========================================================================
    // COLUMN CHANGE (with confirmation)
    // =========================================================================
    function bindColumnSelect() {
        var prevColumn = $('#column-select').val();

        $('#column-select').on('change', function() {
            var select = $(this);
            var newColumn = select.val();
            var columnLabel = select.find('option:selected').text().trim();

            var msg = (t.confirm_move_column || 'Move to :column?').replace(':column', columnLabel);
            showConfirmModal(msg, function() {
                select.prop('disabled', true);
                $.ajax({
                    url: '/tickets/' + window.ticketId + '/move-column',
                    type: 'POST',
                    data: { board_column: newColumn },
                    success: function(res) {
                        if (res.success) {
                            window.location.reload();
                        }
                    },
                    error: function() {
                        select.val(prevColumn);
                        alert(t.move_column_failed || 'Failed to move ticket.');
                    },
                    complete: function() { select.prop('disabled', false); }
                });
            }, function() { select.val(prevColumn); });
        });
    }

    // =========================================================================
    // ARCHIVE & DELETE (modal-based)
    // =========================================================================
    function bindArchiveDelete() {
        $('#btn-archive').on('click', function() {
            $('#archive-confirm-input').val('');
            $('#archive-confirm-btn').prop('disabled', true);
            $('#archive-confirm-error').hide();
            $('#archiveTicketModal').modal('show');
        });

        $(document).on('input', '#archive-confirm-input', function() {
            var val = $.trim($(this).val());
            $('#archive-confirm-btn').prop('disabled', val !== 'archivar');
            $('#archive-confirm-error').toggle(val.length > 0 && val !== 'archivar');
        });

        $(document).on('click', '#archive-confirm-btn', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>');
            $.ajax({
                url: '/tickets/' + window.ticketId + '/archive',
                type: 'POST',
                success: function(res) {
                    if (res.success) { $('#archiveTicketModal').modal('hide'); window.location.reload(); }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error) || t.archive_failed || 'Failed to archive ticket.';
                    $('#archiveTicketModal').modal('hide');
                    alert(msg);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-archive mr-1"></i> ' + (t.archive_ticket || 'Archive ticket'));
                }
            });
        });

        $('#btn-delete').on('click', function() {
            $('#delete-confirm-input').val('');
            $('#delete-confirm-btn').prop('disabled', true);
            $('#delete-confirm-error').hide();
            $('#deleteTicketModal').modal('show');
        });

        $(document).on('input', '#delete-confirm-input', function() {
            var val = $.trim($(this).val());
            $('#delete-confirm-btn').prop('disabled', val !== 'eliminar');
            $('#delete-confirm-error').toggle(val.length > 0 && val !== 'eliminar');
        });

        $(document).on('click', '#delete-confirm-btn', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>');
            $.ajax({
                url: '/tickets/' + window.ticketId + '/delete',
                type: 'POST',
                success: function(res) {
                    if (res.success) { $('#deleteTicketModal').modal('hide'); window.location.href = res.redirect || '/dashboard'; }
                },
                error: function() { $('#deleteTicketModal').modal('hide'); alert(t.delete_failed || 'Failed to delete ticket.'); },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> ' + (t.delete_ticket || 'Delete ticket'));
                }
            });
        });
    }

    // =========================================================================
    // LABELS
    // =========================================================================
    function bindLabels() {
        var currentLabels = window.ticketLabels || [];
        var allLabels = [];

        function isAssigned(labelId) {
            return currentLabels.some(function(l) { return l.id === labelId; });
        }

        function renderLabelBadges() {
            var $container = $('#ticket-labels-inline');
            $container.empty();
            currentLabels.forEach(function(l) {
                $container.append(
                    '<span class="badge label-badge mr-1 mb-1" style="background-color:' + escHtml(l.color) + ';color:#fff;" data-id="' + l.id + '">'
                    + escHtml(l.name) + '</span>'
                );
            });

            // Toggle button text: show "Add label" text only when no labels
            var $btn = $('#labelDropdownBtn');
            if (currentLabels.length === 0) {
                $btn.html('<i class="fas fa-plus mr-1"></i><small>' + escHtml(t.add_label || 'Add label') + '</small>');
            } else {
                $btn.html('<i class="fas fa-plus"></i>');
            }
        }

        function renderDropdownOptions(filter) {
            var $list = $('#label-options');
            $list.empty();
            var search = (filter || '').toLowerCase();

            allLabels.forEach(function(label) {
                if (search && label.name.toLowerCase().indexOf(search) === -1) return;

                var assigned = isAssigned(label.id);
                var checkIcon = assigned ? '<i class="fas fa-check mr-1"></i>' : '';
                var activeClass = assigned ? 'font-weight-bold' : '';

                $list.append(
                    '<div class="label-option d-flex align-items-center px-2 py-1 rounded mb-1 ' + activeClass + '" data-id="' + label.id + '" style="cursor:pointer;">'
                    + '<span class="label-color-dot mr-2" style="background:' + escHtml(label.color) + ';"></span>'
                    + checkIcon + escHtml(label.name)
                    + '</div>'
                );
            });

            if ($list.children().length === 0) {
                $list.append('<div class="text-muted small px-2 py-1">—</div>');
            }
        }

        function loadLabels(callback) {
            $.getJSON('/labels', function(data) {
                allLabels = data;
                if (callback) callback();
            });
        }

        function syncLabels() {
            var ids = currentLabels.map(function(l) { return l.id; });
            $.ajax({
                url: '/tickets/' + window.ticketId + '/labels',
                type: 'POST',
                data: { label_ids: ids },
                success: function(res) {
                    if (res.success) {
                        currentLabels = res.labels;
                        renderLabelBadges();
                    }
                }
            });
        }

        // Load labels when dropdown opens
        $('#labelDropdownBtn').on('click', function() {
            loadLabels(function() {
                renderDropdownOptions($('#label-search').val());
            });
        });

        // Prevent dropdown from closing on click inside
        $('.label-dropdown').on('click', function(e) {
            e.stopPropagation();
        });

        // Search filter
        $('#label-search').on('input', function() {
            renderDropdownOptions($(this).val());
        });

        // Toggle label on/off (delegate from #label-options, not document, because .label-dropdown stops propagation)
        $('#label-options').on('click', '.label-option', function(e) {
            e.stopPropagation();
            var labelId = parseInt($(this).data('id'));
            var idx = -1;
            currentLabels.forEach(function(l, i) { if (l.id === labelId) idx = i; });

            if (idx >= 0) {
                currentLabels.splice(idx, 1);
            } else {
                var label = allLabels.find(function(l) { return l.id === labelId; });
                if (label) currentLabels.push({ id: label.id, name: label.name, color: label.color });
            }

            syncLabels();
            renderDropdownOptions($('#label-search').val());
        });

        // Create new label
        $('#btn-create-label').on('click', function() {
            var name = $('#new-label-name').val().trim();
            var color = $('#new-label-color').val();
            if (!name) { $('#new-label-name').focus(); return; }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/labels',
                type: 'POST',
                data: { name: name, color: color },
                success: function(res) {
                    if (res.success) {
                        allLabels.push(res.label);
                        currentLabels.push(res.label);
                        syncLabels();
                        renderDropdownOptions('');
                        $('#new-label-name').val('');
                        $('#label-search').val('');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        alert(t.label_exists || 'Label already exists.');
                    }
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });
    }

    // =========================================================================
    // EDITABLE SUBJECT & DESCRIPTION (internal tickets only)
    // =========================================================================
    function bindEditableContent() {
        var updateUrl = '/tickets/' + window.ticketId + '/update-content';

        // --- Description collapse/expand ---
        var $descContent = $('#description-content');
        var $toggleWrap = $('#description-toggle-wrap');
        if ($descContent.length && $descContent[0].scrollHeight > 320) {
            $descContent.addClass('has-overflow');
            $toggleWrap.show();
        } else {
            $toggleWrap.hide();
        }

        $('#btn-toggle-description').on('click', function() {
            var $btn = $(this);
            if ($descContent.hasClass('description-expanded')) {
                $descContent.removeClass('description-expanded').addClass('description-collapsed has-overflow');
                $btn.html('<i class="fas fa-chevron-down mr-1"></i>' + (t.show_more || 'Ver más'));
            } else {
                $descContent.removeClass('description-collapsed has-overflow').addClass('description-expanded');
                $btn.html('<i class="fas fa-chevron-up mr-1"></i>' + (t.show_less || 'Ver menos'));
            }
        });

        // --- Subject inline edit ---
        var $subject = $('#ticket-subject');
        if ($subject.length) {
            $('#btn-edit-subject, #ticket-subject').on('click', function() {
                var current = $subject.text().trim();
                var $input = $('<input type="text" class="form-control form-control-sm d-inline-block" style="width:80%;max-width:500px;">');
                $input.val(current);
                $subject.replaceWith($input);
                $input.focus().select();

                function saveSubject() {
                    var val = $input.val().trim();
                    if (!val) { $input.focus(); return; }
                    $.ajax({
                        url: updateUrl,
                        type: 'POST',
                        data: { subject: val },
                        success: function(res) {
                            if (res.success) {
                                var $new = $('<strong id="ticket-subject" class="editable-field" title="Click para editar">' + escHtml(val) + '</strong>');
                                $input.replaceWith($new);
                                $subject = $new;
                                $new.on('click', function() { $('#btn-edit-subject').click(); });
                            }
                        }
                    });
                }

                $input.on('keydown', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); saveSubject(); }
                    if (e.key === 'Escape') {
                        var $new = $('<strong id="ticket-subject" class="editable-field" title="Click para editar">' + escHtml(current) + '</strong>');
                        $input.replaceWith($new);
                        $subject = $new;
                    }
                }).on('blur', function() { saveSubject(); });
            });
        }

        // --- Description Summernote editor ---
        var descEditorInit = false;

        function looksLikeMarkdown(text) {
            var lines = text.split('\n').slice(0, 10);
            var mdPatterns = /^#{1,6}\s|^\*\*|^- |\*\*.*\*\*|^---$|^```|^\d+\.\s/;
            var matches = 0;
            for (var i = 0; i < lines.length; i++) {
                if (mdPatterns.test(lines[i].trim())) matches++;
            }
            return matches >= 2;
        }

        $('#btn-edit-description').on('click', function() {
            $('#description-display').hide();
            $('#description-editor-wrap').show();

            if (!descEditorInit) {
                var opts = {
                    height: 200,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'strikethrough']],
                        ['para', ['ul', 'ol']],
                        ['insert', ['link', 'picture', 'hr']],
                        ['misc', ['codeview', 'undo', 'redo']]
                    ],
                    callbacks: {
                        onPaste: function(e) {
                            var clipData = (e.originalEvent || e).clipboardData;
                            if (!clipData) return;
                            var plainText = clipData.getData('text/plain');
                            if (plainText && looksLikeMarkdown(plainText) && typeof marked !== 'undefined') {
                                e.preventDefault();
                                $('#description-editor').summernote('pasteHTML', marked.parse(plainText));
                            }
                        }
                    }
                };
                if (document.documentElement.lang === 'es') opts.lang = 'es-ES';
                $('#description-editor').summernote(opts);
                descEditorInit = true;
            }
        });

        $('#btn-save-description').on('click', function() {
            var html = $('#description-editor').summernote('code');
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: updateUrl,
                type: 'POST',
                data: { description: html },
                success: function(res) {
                    if (res.success) {
                        $('#description-content').html(html);
                        $('#description-editor-wrap').hide();
                        $('#description-display').show();
                    }
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });

        $('#btn-cancel-description').on('click', function() {
            var original = $('#description-content').html();
            $('#description-editor').summernote('code', original);
            $('#description-editor-wrap').hide();
            $('#description-display').show();
        });
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})();
