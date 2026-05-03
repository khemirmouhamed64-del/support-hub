(function() {
    'use strict';

    var boardDataUrl  = '/api/board-data';
    var moveColumnUrl = '/tickets/{id}/move-column';
    var ticketShowUrl = '/tickets/{id}';
    var pendingDone   = null; // ticket waiting for done modal confirmation
    var t = window.i18n || {};

    var createInternalUrl = '/tickets/create-internal';

    // --- Init ---
    $(document).ready(function() {
        initSortable();
        loadBoard();
        bindFilters();
        bindDoneModal();
        bindCreateInternal();
    });

    // --- Load board data via AJAX ---
    function loadBoard() {
        var params = {
            client_id:   $('#filter-client').val(),
            assigned_to: $('#filter-assignee').val(),
            priority:    $('#filter-priority').val(),
            module:      $('#filter-module').val(),
            source:      $('#filter-source').val()
        };

        $.getJSON(boardDataUrl, params, function(columns) {
            var total = 0;
            $.each(columns, function(colKey, tickets) {
                var container = $('#column-' + colKey);
                container.empty();

                if (tickets.length === 0) {
                    container.append('<div class="kanban-empty">' + escHtml(t.no_tickets_col || 'No tickets') + '</div>');
                } else {
                    $.each(tickets, function(i, ticket) {
                        ticket._column = colKey;
                        container.append(buildCard(ticket));
                    });
                }

                $('#count-' + colKey).text(tickets.length);
                total += tickets.length;
            });
            $('#ticket-count').text(total + ' ' + (total !== 1 ? (t.tickets_word || 'tickets') : (t.ticket_word || 'ticket')));
        });
    }

    // --- Build AI incident type badge for kanban card ---
    function buildIncidentBadge(incidentType, aiStatus) {
        if (aiStatus === 'pending') {
            return '<span class="badge badge-secondary" style="font-size:0.6rem;font-weight:400;">'
                + '<i class="fas fa-spinner fa-spin mr-1"></i>Clasificando...</span>';
        }
        if (aiStatus === 'failed') {
            return '<span class="badge badge-warning" style="font-size:0.6rem;font-weight:400;">'
                + '<i class="fas fa-exclamation-triangle mr-1"></i>Sin clasificar</span>';
        }

        var types = window.incidentTypes || {};
        if (!incidentType || !types[incidentType]) {
            return '';
        }

        var badgeClass = {
            danger: 'badge-danger',
            warning: 'badge-warning',
            info: 'badge-info',
            'default': 'badge-secondary'
        }[types[incidentType].badge] || 'badge-secondary';

        var aiIcon = aiStatus === 'manual'
            ? '<i class="fas fa-user mr-1" title="Clasificado manualmente"></i>'
            : '<i class="fas fa-robot mr-1" title="Clasificado por IA"></i>';

        return '<span class="badge ' + badgeClass + '" style="font-size:0.6rem;font-weight:400;">'
            + aiIcon + escHtml(types[incidentType].label) + '</span>';
    }

    // --- Build due date badge ---
    var advancedStates = ['code_review', 'qa_testing', 'ready_for_release', 'done'];

    function buildDueDateBadge(dueDate, boardColumn) {
        if (!dueDate) return '';
        var due = new Date(dueDate + 'T00:00:00');
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
        var dateStr = due.toLocaleDateString('es', { month: 'short', day: 'numeric' });

        // If ticket is in advanced state, show green with check
        if (advancedStates.indexOf(boardColumn) >= 0) {
            return '<span class="badge badge-success" style="font-size:0.6rem;">✅ ' + escHtml(dateStr) + '</span>';
        }

        var badgeClass, icon, label;
        if (diffDays < 0) {
            badgeClass = 'badge-danger';
            icon = 'fa-exclamation-circle';
            label = (t.overdue || 'Vencido') + ' · ' + dateStr;
        } else if (diffDays === 0) {
            badgeClass = 'badge-warning text-dark';
            icon = 'fa-clock';
            label = (t.due_today || 'Vence hoy');
        } else if (diffDays <= 2) {
            badgeClass = 'badge-warning text-dark';
            icon = 'fa-calendar-alt';
            label = dateStr;
        } else {
            badgeClass = 'badge-light text-muted';
            icon = 'fa-calendar-alt';
            label = dateStr;
        }

        return '<span class="badge ' + badgeClass + '" style="font-size:0.6rem;"><i class="fas ' + icon + ' mr-1"></i>' + escHtml(label) + '</span>';
    }

    // --- Build card HTML ---
    function buildCard(card) {
        var isInternal = card.source === 'internal';

        var typeIcons = {
            bug: 'fa-bug text-danger',
            configuration: 'fa-cog text-info',
            question: 'fa-question-circle text-primary',
            feature_request: 'fa-lightbulb text-warning',
            task: 'fa-tasks text-secondary'
        };

        var icon = isInternal ? 'fa-laptop-code text-success' : (typeIcons[card.ticket_type] || 'fa-ticket-alt');

        var assigneeHtml = card.assignee_initials
            ? '<div class="kanban-card-assignee" title="' + escHtml(card.assignee_name) + '">' + escHtml(card.assignee_initials) + '</div>'
            : '<div class="kanban-card-assignee" style="background:#dee2e6;color:#6c757d;" title="' + escHtml(t.unassigned || 'Unassigned') + '">?</div>';

        var clientReplyBadge = card.unread_client_replies > 0
            ? '<span class="kanban-client-reply-badge" title="' + card.unread_client_replies + ' ' + escHtml(t.unread_client_replies || 'unread client replies') + '"><i class="fas fa-comment-dots"></i> ' + card.unread_client_replies + '</span>'
            : '';

        var incidentBadgeHtml = isInternal ? '' : buildIncidentBadge(card.incident_type, card.ai_status);
        var dueBadgeHtml = buildDueDateBadge(card.due_date, card._column);

        // Meta line: show client name or "Internal" badge
        var metaHtml = isInternal
            ? '<span class="badge badge-outline-success" style="font-size:0.65rem;border:1px solid #28a745;color:#28a745;"><i class="fas fa-laptop-code mr-1"></i>' + escHtml(t.internal_label || 'Internal') + '</span>'
            : '<span class="kanban-card-client" title="' + escHtml(card.client_name) + '"><i class="fas fa-building mr-1"></i>' + escHtml(card.client_name) + '</span>';

        // Label color bars (Trello style)
        var labelBarsHtml = '';
        if (card.labels && card.labels.length > 0) {
            labelBarsHtml = '<div class="kanban-card-labels">';
            for (var li = 0; li < card.labels.length; li++) {
                labelBarsHtml += '<span class="kanban-label-bar" style="background:' + escHtml(card.labels[li].color) + ';" title="' + escHtml(card.labels[li].name) + '"></span>';
            }
            labelBarsHtml += '</div>';
        }

        var html = ''
            + '<div class="kanban-card priority-' + card.issue_priority + (isInternal ? ' kanban-card-internal' : '') + '" data-id="' + card.id + '" data-source="' + card.source + '">'
            + labelBarsHtml
            + '  <div class="kanban-card-header">'
            + '    <span class="kanban-card-number"><i class="fas ' + icon + ' type-icon"></i> ' + escHtml(card.ticket_number) + '</span>'
            + '    ' + clientReplyBadge
            + '    <span class="badge badge-priority-' + card.issue_priority + '" style="font-size:0.65rem;">' + card.issue_priority.toUpperCase() + '</span>'
            + '  </div>'
            + '  <div class="kanban-card-subject">' + escHtml(card.subject) + '</div>'
            + '  <div class="kanban-card-incident">' + incidentBadgeHtml + '</div>'
            + '  <div class="kanban-card-meta">'
            + '    ' + metaHtml
            + '    ' + assigneeHtml
            + '  </div>'
            + '  <div class="kanban-card-footer">'
            + '    <span title="' + escHtml(t.effective_priority || 'Effective priority') + '">#' + card.effective_priority + '</span>'
            + '    ' + dueBadgeHtml
            + '    <span><i class="far fa-clock mr-1"></i>' + escHtml(card.time_in_column) + '</span>'
            + '  </div>'
            + '</div>';

        return html;
    }

    // --- Init Sortable.js on each column ---
    function initSortable() {
        var columns = document.querySelectorAll('.kanban-column-body');

        columns.forEach(function(el) {
            new Sortable(el, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: '.kanban-empty',
                onEnd: function(evt) {
                    var ticketId = evt.item.getAttribute('data-id');
                    var newColumn = evt.to.id.replace('column-', '');
                    var oldColumn = evt.from.id.replace('column-', '');

                    if (oldColumn === newColumn) return;

                    // Remove empty placeholders
                    $(evt.to).find('.kanban-empty').remove();
                    if ($(evt.from).children('.kanban-card').length === 0) {
                        $(evt.from).append('<div class="kanban-empty">' + escHtml(t.no_tickets_col || 'No tickets') + '</div>');
                    }

                    // If moving to "done": skip resolution modal for internal tickets
                    if (newColumn === 'done') {
                        var isInternalTicket = evt.item.getAttribute('data-source') === 'internal';
                        if (isInternalTicket) {
                            moveTicket(ticketId, newColumn, evt.from, evt.item);
                            updateCounts();
                            return;
                        }
                        pendingDone = {
                            ticketId: ticketId,
                            fromEl: evt.from,
                            toEl: evt.to,
                            item: evt.item,
                            oldColumn: oldColumn
                        };
                        $('#done-ticket-id').val(ticketId);
                        $('#done-message').val('');
                        $('#done-generic').prop('checked', true);
                        $('#doneModal').modal('show');
                        return;
                    }

                    moveTicket(ticketId, newColumn, evt.from, evt.item);
                    updateCounts();
                }
            });
        });
    }

    // --- Move ticket AJAX ---
    // fromEl and item are used to revert the card visually if the request fails.
    function moveTicket(ticketId, newColumn, fromEl, item, callback) {
        var url = moveColumnUrl.replace('{id}', ticketId);

        $.ajax({
            url: url,
            type: 'POST',
            data: { board_column: newColumn },
            success: function(res) {
                if (res.success) {
                    updateCounts();
                    if (callback) callback(true);
                }
            },
            error: function(xhr) {
                var res = xhr.responseJSON || {};

                // Revert card to original column
                if (fromEl && item) {
                    var currentParent = $(item).parent()[0];
                    $(fromEl).append(item);
                    $(fromEl).find('.kanban-empty').remove();
                    if (currentParent && $(currentParent).children('.kanban-card').length === 0) {
                        $(currentParent).append('<div class="kanban-empty">' + escHtml(t.no_tickets_col || 'No tickets') + '</div>');
                    }
                }
                updateCounts();

                if (res.erp_error) {
                    showErpErrorModal(
                        t.erp_connection_error || 'ERP Connection Error',
                        res.message || t.erp_move_blocked || 'Could not reach the ERP.'
                    );
                } else {
                    loadBoard();
                }

                if (callback) callback(false);
            }
        });
    }

    // --- Show ERP error modal ---
    function showErpErrorModal(title, message) {
        $('#erpErrorModal .erp-error-title').text(title);
        $('#erpErrorModal .erp-error-message').text(message);
        $('#erpErrorModal').modal('show');
    }

    // --- Done modal ---
    function bindDoneModal() {
        $('#done-generic').on('change', function() {
            if ($(this).is(':checked')) {
                $('#done-message').val('').prop('disabled', true);
            } else {
                $('#done-message').prop('disabled', false).focus();
            }
        }).trigger('change');

        $('#btn-confirm-done').on('click', function() {
            if (!pendingDone) return;

            var ticketId = pendingDone.ticketId;
            var fromEl   = pendingDone.fromEl;
            var item     = pendingDone.item;
            var message  = $('#done-generic').is(':checked')
                ? (t.default_resolution || 'The issue has been resolved. Please try again.')
                : $('#done-message').val();

            // Save resolution message then move
            $.ajax({
                url: '/tickets/' + ticketId + '/resolve',
                type: 'POST',
                data: { resolution_message: message },
                success: function() {
                    // Clear pendingDone before hiding so the modal dismiss handler does not revert
                    pendingDone = null;
                    $('#doneModal').modal('hide');
                    moveTicket(ticketId, 'done', fromEl, item, function(ok) {
                        if (ok) updateCounts();
                    });
                },
                error: function() {
                    alert(t.resolve_save_failed || 'Failed to save resolution. Try again.');
                }
            });
        });

        // If modal dismissed without confirming, revert the card
        $('#doneModal').on('hidden.bs.modal', function() {
            if (pendingDone) {
                // Move card back
                $(pendingDone.fromEl).append(pendingDone.item);
                if ($(pendingDone.toEl).children('.kanban-card').length === 0) {
                    $(pendingDone.toEl).append('<div class="kanban-empty">' + escHtml(t.no_tickets_col || 'No tickets') + '</div>');
                }
                $(pendingDone.fromEl).find('.kanban-empty').remove();
                updateCounts();
                pendingDone = null;
            }
        });
    }

    // --- Filters ---
    function bindFilters() {
        $('#filter-client, #filter-assignee, #filter-priority, #filter-module, #filter-source').on('change', function() {
            loadBoard();
        });

        $('#btn-clear-filters').on('click', function() {
            $('#filter-client, #filter-assignee, #filter-priority, #filter-module, #filter-source').val('');
            loadBoard();
        });
    }

    // --- Update column counts ---
    function updateCounts() {
        var total = 0;
        document.querySelectorAll('.kanban-column-body').forEach(function(col) {
            var count = col.querySelectorAll('.kanban-card').length;
            var colKey = col.id.replace('column-', '');
            $('#count-' + colKey).text(count);
            total += count;
        });
        $('#ticket-count').text(total + ' ' + (total !== 1 ? (t.tickets_word || 'tickets') : (t.ticket_word || 'ticket')));
    }

    // --- Navigate to ticket detail on double-click ---
    $(document).on('dblclick', '.kanban-card', function() {
        var id = $(this).data('id');
        window.location.href = ticketShowUrl.replace('{id}', id);
    });

    // --- Toggle ALL label bars: click one to expand/collapse all (Trello style) ---
    $(document).on('click', '.kanban-label-bar', function(e) {
        e.stopPropagation();
        var allBars = $('.kanban-label-bar');
        var isExpanded = $(this).hasClass('label-expanded');

        if (isExpanded) {
            allBars.each(function() { $(this).text('').removeClass('label-expanded'); });
        } else {
            allBars.each(function() { $(this).text($(this).attr('title')).addClass('label-expanded'); });
        }
    });

    // --- Create internal ticket ---
    function bindCreateInternal() {
        var modalLabels = []; // all available labels
        var modalSelected = []; // selected label IDs

        function renderModalLabels(filter) {
            var $list = $('#modal-label-options');
            $list.empty();
            var search = (filter || '').toLowerCase();
            modalLabels.forEach(function(label) {
                if (search && label.name.toLowerCase().indexOf(search) === -1) return;
                var isOn = modalSelected.indexOf(label.id) >= 0;
                var check = isOn ? '<i class="fas fa-check mr-1"></i>' : '';
                var bold = isOn ? 'font-weight-bold' : '';
                $list.append(
                    '<div class="modal-label-opt d-flex align-items-center px-2 py-1 rounded mb-1 ' + bold + '" data-id="' + label.id + '" style="cursor:pointer;">'
                    + '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + escHtml(label.color) + ';" class="mr-2"></span>'
                    + check + escHtml(label.name)
                    + '</div>'
                );
            });
        }

        function renderModalSelectedBadges() {
            var $cont = $('#modal-labels-selected');
            $cont.empty();
            modalSelected.forEach(function(id) {
                var label = modalLabels.find(function(l) { return l.id === id; });
                if (label) {
                    $cont.append('<span class="badge mr-1 mb-1" style="background:' + escHtml(label.color) + ';color:#fff;font-size:0.7rem;">' + escHtml(label.name) + '</span>');
                }
            });
            $('#modal-label-ids').val(modalSelected.join(','));
        }

        // Init Summernote on modal open
        var editorInitialized = false;
        $('#createInternalModal').on('shown.bs.modal', function() {
            if (!editorInitialized) {
                var editorOpts = {
                    placeholder: t.description_placeholder || 'Describe el problema o requerimiento...',
                    height: 150,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'strikethrough']],
                        ['font', ['superscript', 'subscript']],
                        ['para', ['ul', 'ol']],
                        ['insert', ['link', 'hr']],
                        ['misc', ['codeview', 'undo', 'redo']]
                    ],
                    disableDragAndDrop: true,
                    callbacks: {
                        onPaste: function(e) {
                            // Get plain text from clipboard
                            var clipData = (e.originalEvent || e).clipboardData;
                            if (!clipData) return;

                            var plainText = clipData.getData('text/plain');
                            var htmlText = clipData.getData('text/html');

                            // Detect if pasted content is markdown (has # headers, **, ---, etc.)
                            if (plainText && looksLikeMarkdown(plainText) && typeof marked !== 'undefined') {
                                e.preventDefault();
                                var html = marked.parse(plainText);
                                $('#modal-description-editor').summernote('pasteHTML', html);
                            }
                            // If it has HTML from Word/browser, let Summernote handle it normally
                        }
                    }
                };
                if (document.documentElement.lang === 'es') {
                    editorOpts.lang = 'es-ES';
                }
                $('#modal-description-editor').summernote(editorOpts);
                editorInitialized = true;
            }

            $.getJSON('/labels', function(data) {
                modalLabels = data;
                renderModalLabels('');
            });
        });

        // Prevent dropdown from closing
        $('#modal-label-menu').on('click', function(e) { e.stopPropagation(); });

        // Search
        $('#modal-label-search').on('input', function() { renderModalLabels($(this).val()); });

        // Toggle label (delegate from #modal-label-options, not document)
        $('#modal-label-options').on('click', '.modal-label-opt', function(e) {
            e.stopPropagation();
            var id = parseInt($(this).data('id'));
            var idx = modalSelected.indexOf(id);
            if (idx >= 0) { modalSelected.splice(idx, 1); } else { modalSelected.push(id); }
            renderModalLabels($('#modal-label-search').val());
            renderModalSelectedBadges();
        });

        // Create new label from modal
        $('#modal-btn-create-label').on('click', function() {
            var name = $('#modal-new-label-name').val().trim();
            var color = $('#modal-new-label-color').val();
            if (!name) return;
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.ajax({
                url: '/labels',
                type: 'POST',
                data: { name: name, color: color },
                success: function(res) {
                    if (res.success) {
                        modalLabels.push(res.label);
                        modalSelected.push(res.label.id);
                        renderModalLabels('');
                        renderModalSelectedBadges();
                        $('#modal-new-label-name').val('');
                    }
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });

        // Attachment handling
        var pendingFiles = [];

        $('#modal-file-input').on('change', function() {
            for (var i = 0; i < this.files.length; i++) {
                pendingFiles.push(this.files[i]);
                addFileToList(this.files[i].name);
            }
            $(this).val('');
        });

        // Paste images via Ctrl+V
        document.getElementById('modal-paste-zone').addEventListener('paste', function(e) {
            var items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    e.preventDefault();
                    var file = items[i].getAsFile();
                    var name = 'pasted-image-' + Date.now() + '.png';
                    var namedFile = new File([file], name, { type: file.type });
                    pendingFiles.push(namedFile);
                    addFileToList(name);
                }
            }
            this.textContent = '';
        });

        function addFileToList(name) {
            var idx = pendingFiles.length - 1;
            $('#modal-attachments-list').append(
                '<div class="d-flex align-items-center small mb-1" data-file-idx="' + idx + '">'
                + '<i class="fas fa-paperclip mr-1 text-muted"></i>' + escHtml(name)
                + ' <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-pending-file" data-idx="' + idx + '"><i class="fas fa-times"></i></button>'
                + '</div>'
            );
        }

        $(document).on('click', '.btn-remove-pending-file', function() {
            var idx = $(this).data('idx');
            pendingFiles[idx] = null; // mark as removed
            $(this).closest('div').remove();
        });

        // Submit ticket
        $('#btn-create-internal').on('click', function() {
            var $btn = $(this);
            var $form = $('#form-create-internal');
            var $errors = $('#internal-ticket-errors');

            $btn.prop('disabled', true);
            $errors.hide().empty();

            // Get description from Summernote
            var description = $('#modal-description-editor').summernote('code');
            var textOnly = $('<div>').html(description).text().trim();
            if (!textOnly) {
                $errors.html('<ul class="mb-0"><li>' + (t.description_required || 'La descripción es obligatoria.') + '</li></ul>').show();
                $btn.prop('disabled', false);
                return;
            }

            // Build FormData (supports files)
            var fd = new FormData($form[0]);
            fd.append('description', description);
            modalSelected.forEach(function(id) {
                fd.append('label_ids[]', id);
            });
            pendingFiles.forEach(function(file) {
                if (file) fd.append('attachments[]', file);
            });

            $.ajax({
                url: createInternalUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        $('#createInternalModal').modal('hide');
                        $form[0].reset();
                        $form.find('select[name="issue_priority"]').val('medium');
                        $form.find('select[name="board_column"]').val('to_do');
                        $('#modal-description-editor').summernote('code', '');
                        modalSelected = [];
                        pendingFiles = [];
                        $('#modal-labels-selected').empty();
                        $('#modal-attachments-list').empty();
                        loadBoard();
                    }
                },
                error: function(xhr) {
                    var res = xhr.responseJSON || {};
                    if (res.errors) {
                        var html = '<ul class="mb-0">';
                        $.each(res.errors, function(field, msgs) {
                            $.each(msgs, function(i, msg) {
                                html += '<li>' + escHtml(msg) + '</li>';
                            });
                        });
                        html += '</ul>';
                        $errors.html(html).show();
                    } else {
                        $errors.text(res.message || 'Error').show();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    // --- Detect if text looks like markdown ---
    function looksLikeMarkdown(text) {
        var lines = text.split('\n').slice(0, 10); // check first 10 lines
        var mdPatterns = /^#{1,6}\s|^\*\*|^- |\*\*.*\*\*|^---$|^```|^\d+\.\s/;
        var matches = 0;
        for (var i = 0; i < lines.length; i++) {
            if (mdPatterns.test(lines[i].trim())) matches++;
        }
        return matches >= 2; // at least 2 markdown patterns = likely markdown
    }

    // --- HTML escape ---
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})();
