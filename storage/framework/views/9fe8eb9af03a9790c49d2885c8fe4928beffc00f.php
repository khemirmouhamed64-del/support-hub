<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', __('app.brand_name')); ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎧</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --navbar-height: 56px;
            --color-todo: #3498db;
            --color-in-progress: #f39c12;
            --color-blocked: #e74c3c;
            --color-code-review: #9b59b6;
            --color-qa-testing: #1abc9c;
            --color-ready-release: #2ecc71;
            --color-done: #27ae60;
        }
        body { background: #f4f6f9; }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #343a40;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .brand {
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand small { display: block; font-size: 0.7rem; opacity: 0.6; font-weight: 400; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 10px 20px;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.05);
            border-left-color: var(--color-todo);
        }
        .sidebar .nav-link i { width: 24px; text-align: center; margin-right: 8px; }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .top-navbar {
            height: var(--navbar-height);
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .content-wrapper { padding: 24px; }
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 0.65rem;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
        }
        .notif-item { transition: background 0.15s; display: flex; align-items: flex-start; }
        .notif-item.notif-hidden { display: none !important; }
        .notif-item:hover { background: #e9ecef !important; }
        .mention-suggestions {
            position: absolute;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1050;
            max-width: 250px;
            display: none;
        }
        .mention-suggestions .mention-item {
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .mention-suggestions .mention-item:hover,
        .mention-suggestions .mention-item.active {
            background: #007bff;
            color: #fff;
        }
        .mention-suggestions .mention-item small { opacity: 0.7; }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    
    <aside class="sidebar">
        <div class="brand">
            <i class="fas fa-headset"></i> <?php echo e(__('app.brand_name')); ?>

            <small><?php echo e(__('app.brand_subtitle')); ?></small>
        </div>
        <nav class="mt-2">
            <a href="<?php echo e(url('/dashboard')); ?>" class="nav-link <?php echo e(request()->is('dashboard') ? 'active' : ''); ?>">
                <i class="fas fa-columns"></i> <?php echo e(__('app.nav_kanban')); ?>

            </a>
            <a href="<?php echo e(url('/tickets')); ?>" class="nav-link <?php echo e(request()->is('tickets*') ? 'active' : ''); ?>">
                <i class="fas fa-ticket-alt"></i> <?php echo e(__('app.nav_tickets')); ?>

            </a>
            <a href="<?php echo e(url('/clients')); ?>" class="nav-link <?php echo e(request()->is('clients*') ? 'active' : ''); ?>">
                <i class="fas fa-building"></i> <?php echo e(__('app.nav_clients')); ?>

            </a>
            <a href="<?php echo e(url('/team')); ?>" class="nav-link <?php echo e(request()->is('team*') ? 'active' : ''); ?>">
                <i class="fas fa-users"></i> <?php echo e(__('app.nav_team')); ?>

            </a>
            <a href="<?php echo e(url('/reports')); ?>" class="nav-link <?php echo e(request()->is('reports*') ? 'active' : ''); ?>">
                <i class="fas fa-chart-bar"></i> <?php echo e(__('app.nav_reports')); ?>

            </a>
            <a href="<?php echo e(url('/tickets/archived')); ?>" class="nav-link <?php echo e(request()->is('tickets/archived') ? 'active' : ''); ?>" style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <i class="fas fa-archive"></i> <?php echo e(__('app.nav_archived')); ?>

            </a>
        </nav>
    </aside>

    
    <div class="main-content">
        
        <div class="top-navbar">
            <h5 class="mb-0"><?php echo $__env->yieldContent('page-title', __('app.dashboard')); ?></h5>
            <div class="d-flex align-items-center">
                
                <div class="dropdown mr-3" id="notifications-dropdown">
                    <a href="#" class="text-dark position-relative" data-toggle="dropdown">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php $unreadCount = auth()->user()->unreadNotificationsCount(); ?>
                        <span class="badge badge-danger notification-badge" id="notif-badge" style="<?php echo e($unreadCount > 0 ? '' : 'display:none;'); ?>"><?php echo e($unreadCount); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right p-0" style="width: 320px; max-height: 420px; overflow-y: auto;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <h6 class="mb-0"><?php echo e(__('app.notifications')); ?></h6>
                            <div>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted mr-2" id="btn-toggle-read" title="<?php echo e(__('app.show_read_notifs')); ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="btn-mark-all-read" title="<?php echo e(__('app.mark_all_read')); ?>">
                                    <i class="fas fa-check-double"></i>
                                </button>
                            </div>
                        </div>
                        <div id="notif-list">
                        <?php
                            $allNotifs = auth()->user()->notifications()->latest('created_at')->limit(30)->get();
                        ?>
                        <?php $__empty_1 = true; $__currentLoopData = $allNotifs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="notif-item px-3 py-2 border-bottom <?php echo e($notif->read_at ? 'notif-read notif-hidden' : 'bg-light'); ?>"
                                 data-id="<?php echo e($notif->id); ?>" data-ticket-id="<?php echo e($notif->ticket_id); ?>" style="cursor:pointer;">
                                <div class="flex-grow-1">
                                    <strong class="small"><?php echo e($notif->title); ?></strong>
                                    <?php if($notif->message): ?><br><span class="small text-muted"><?php echo e(Str::limit($notif->message, 60)); ?></span><?php endif; ?>
                                    <br><small class="text-muted"><?php echo e($notif->created_at->diffForHumans()); ?></small>
                                </div>
                                <?php if(!$notif->read_at): ?>
                                    <span class="notif-dot ml-2 mt-1" style="width:8px;height:8px;border-radius:50%;background:#007bff;display:inline-block;flex-shrink:0;" title="Unread"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div id="notif-empty" class="px-3 py-3 text-center text-muted small"><?php echo e(__('app.no_notifications')); ?></div>
                        <?php endif; ?>
                        <?php if($allNotifs->isNotEmpty() && $allNotifs->whereNull('read_at')->isEmpty()): ?>
                            <div id="notif-all-read-msg" class="px-3 py-3 text-center text-muted small"><?php echo e(__('app.all_caught_up')); ?></div>
                        <?php else: ?>
                            <div id="notif-all-read-msg" class="px-3 py-3 text-center text-muted small" style="display:none;">All caught up!</div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none" data-toggle="dropdown">
                        <span class="mr-2"><?php echo e(auth()->user()->name); ?></span>
                        <span class="badge badge-secondary"><?php echo e(strtoupper(auth()->user()->role)); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="<?php echo e(url('/profile')); ?>"><i class="fas fa-user mr-2"></i><?php echo e(__('app.profile')); ?></a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i><?php echo e(__('app.logout')); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="content-wrapper">
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo e(session('error')); ?>

                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    
    <div class="mention-suggestions" id="mention-dropdown"></div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // --- Notifications ---
        (function() {
            var showingRead = false;

            // Prevent Bootstrap from closing the dropdown when clicking inside it
            // (but allow notif-item clicks to work via delegation below)
            $('#notifications-dropdown .dropdown-menu').on('click', function(e) {
                if (!$(e.target).closest('.notif-item').length) {
                    e.stopPropagation();
                }
            });

            // Click on notification → mark as read + navigate to ticket
            $('#notif-list').on('click', '.notif-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var item = $(this);
                var id = item.data('id');
                var ticketId = item.data('ticket-id');

                $.post('/notifications/' + id + '/read', function(res) {
                    if (res.success) {
                        item.removeClass('bg-light').addClass('notif-read');
                        item.find('.notif-dot').remove();

                        if (!showingRead) {
                            item.addClass('notif-hidden');
                        }

                        updateBadgeCount();
                        checkEmpty();

                        if (ticketId && res.url) {
                            window.location.href = res.url;
                        }
                    }
                });
            });

            // Mark all as read
            $('#btn-mark-all-read').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                $.post('/notifications/mark-all-read', function(res) {
                    if (res.success) {
                        $('.notif-item').removeClass('bg-light').addClass('notif-read');
                        $('.notif-dot').remove();
                        $('#notif-badge').hide().text('0');

                        if (!showingRead) {
                            $('.notif-item').addClass('notif-hidden');
                        }
                        checkEmpty();
                    }
                });
            });

            // Toggle show/hide read notifications
            $('#btn-toggle-read').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showingRead = !showingRead;

                if (showingRead) {
                    $(this).html('<i class="fas fa-eye-slash"></i>');
                    $(this).attr('title', '<?php echo e(__('app.hide_read_notifs')); ?>');
                    $('.notif-read').removeClass('notif-hidden');
                } else {
                    $(this).html('<i class="fas fa-eye"></i>');
                    $(this).attr('title', '<?php echo e(__('app.show_read_notifs')); ?>');
                    $('.notif-read').addClass('notif-hidden');
                }
                checkEmpty();
            });

            function updateBadgeCount() {
                var count = $('.notif-dot').length;
                var badge = $('#notif-badge');
                if (count > 0) {
                    badge.text(count).show();
                } else {
                    badge.hide();
                }
            }

            function checkEmpty() {
                var visibleItems = $('.notif-item').not('.notif-hidden').length;
                if (visibleItems === 0) {
                    $('#notif-all-read-msg').show();
                } else {
                    $('#notif-all-read-msg').hide();
                }
            }
        })();

        // --- Notification polling (every 30s) ---
        (function() {
            setInterval(function() {
                $.getJSON('/notifications/unread-count', function(data) {
                    var badge = $('#notif-badge');
                    if (data.count > 0) {
                        badge.text(data.count).show();
                    } else {
                        badge.hide();
                    }
                });
            }, 30000);
        })();

        // --- @Mention  autocomplete (works with Summernote contenteditable) ---
        (function() {
            var dropdown = $('#mention-dropdown');
            var activeEditable = null;
            var mentionRange = null;
            var mentionStartOffset = -1;
            var selectedIndex = 0;
            var members = [];

            // Listen on Summernote's contenteditable (.note-editable)
            $(document).on('input', '.note-editable', function() {
                activeEditable = this;
                var sel = window.getSelection();
                if (!sel.rangeCount) return;

                var range = sel.getRangeAt(0);
                var textNode = range.startContainer;
                if (textNode.nodeType !== Node.TEXT_NODE) {
                    hideDropdown();
                    return;
                }

                var textBefore = textNode.textContent.substring(0, range.startOffset);
                var atMatch = textBefore.match(/@(\w*)$/);

                if (atMatch) {
                    mentionRange = range.cloneRange();
                    mentionStartOffset = range.startOffset - atMatch[0].length;
                    searchMembers(atMatch[1]);
                } else {
                    hideDropdown();
                }
            });

            $(document).on('keydown', '.note-editable', function(e) {
                if (!dropdown.is(':visible')) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, members.length - 1);
                    highlightItem();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    highlightItem();
                } else if (e.key === 'Enter' || e.key === 'Tab') {
                    if (members.length > 0) {
                        e.preventDefault();
                        selectMember(members[selectedIndex]);
                    }
                } else if (e.key === 'Escape') {
                    hideDropdown();
                }
            });

            $(document).on('click', '.mention-item', function() {
                var idx = $(this).data('index');
                selectMember(members[idx]);
            });

            $(document).on('blur', '.note-editable', function() {
                setTimeout(function() { hideDropdown(); }, 200);
            });

            function searchMembers(query) {
                $.getJSON('/api/team-members/search', { q: query }, function(data) {
                    members = data;
                    selectedIndex = 0;
                    if (members.length > 0) {
                        showDropdown();
                    } else {
                        hideDropdown();
                    }
                });
            }

            function showDropdown() {
                var html = '';
                for (var i = 0; i < members.length; i++) {
                    var m = members[i];
                    html += '<div class="mention-item' + (i === selectedIndex ? ' active' : '') + '" data-index="' + i + '">'
                        + '<strong>' + escHtml(m.name) + '</strong> <small>(' + escHtml(m.role) + ')</small>'
                        + '</div>';
                }
                dropdown.html(html);

                // Position near the editable area
                var $editable = $(activeEditable);
                var offset = $editable.offset();
                dropdown.css({
                    top: offset.top + $editable.outerHeight() + 2,
                    left: offset.left,
                    display: 'block'
                });
            }

            function hideDropdown() {
                dropdown.hide();
                members = [];
            }

            function highlightItem() {
                dropdown.find('.mention-item').removeClass('active');
                dropdown.find('.mention-item[data-index="' + selectedIndex + '"]').addClass('active');
            }

            function selectMember(member) {
                if (!activeEditable || !member || !mentionRange) return;

                var sel = window.getSelection();
                var textNode = mentionRange.startContainer;
                if (textNode.nodeType !== Node.TEXT_NODE) return;

                var handle = '@' + member.name.replace(/\s+/g, '.') + ' ';
                var text = textNode.textContent;
                var currentOffset = mentionRange.startOffset;

                // Replace from @... to current cursor position with the handle
                textNode.textContent = text.substring(0, mentionStartOffset) + handle + text.substring(currentOffset);

                // Move cursor after the inserted handle
                var newRange = document.createRange();
                var newPos = mentionStartOffset + handle.length;
                newRange.setStart(textNode, newPos);
                newRange.collapse(true);
                sel.removeAllRanges();
                sel.addRange(newRange);

                activeEditable.focus();
                hideDropdown();
            }

            function escHtml(str) {
                if (!str) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }
        })();
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\support-hub\resources\views/layouts/app.blade.php ENDPATH**/ ?>