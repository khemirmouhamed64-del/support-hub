<?php $__env->startSection('page-title', __('clients.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted"><?php echo e(__('clients.clients_registered', ['count' => $clients->count()])); ?></span>
        <a href="<?php echo e(route('clients.create')); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> <?php echo e(__('clients.add_client')); ?>

        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><?php echo e(__('clients.client')); ?></th>
                        <th><?php echo e(__('clients.identifier')); ?></th>
                        <th><?php echo e(__('clients.priority_level')); ?></th>
                        <th><?php echo e(__('clients.api_key')); ?></th>
                        <th><?php echo e(__('clients.callback_url')); ?></th>
                        <th class="text-center"><?php echo e(__('clients.tickets')); ?></th>
                        <th class="text-center"><?php echo e(__('app.status')); ?></th>
                        <th class="text-right"><?php echo e(__('app.actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="<?php echo e($client->is_active ? '' : 'text-muted'); ?>">
                            <td><strong><?php echo e($client->business_name); ?></strong></td>
                            <td><code><?php echo e($client->client_identifier); ?></code></td>
                            <td>
                                <?php
                                    $colors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'vip' => 'danger'];
                                ?>
                                <span class="badge badge-<?php echo e($colors[$client->priority_level] ?? 'secondary'); ?>">
                                    <?php echo e(strtoupper($client->priority_level)); ?>

                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <code class="small" style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="<?php echo e($client->api_key); ?>">
                                        <?php echo e($client->api_key); ?>

                                    </code>
                                    
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm py-0 px-1 ml-1 btn-copy-key"
                                            data-key="<?php echo e($client->api_key); ?>"
                                            title="<?php echo e(__('clients.key_copied')); ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    
                                    <form method="POST" action="<?php echo e(route('clients.regenerate-key', $client)); ?>" class="ml-1" onsubmit="return confirm('<?php echo e(__('clients.regenerate_confirm')); ?>')">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-1" title="<?php echo e(__('clients.regenerate_key')); ?>">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="small"><?php echo e($client->api_callback_url ?: '—'); ?></td>
                            <td class="text-center"><?php echo e($client->tickets_count); ?></td>
                            <td class="text-center">
                                <?php if($client->is_active): ?>
                                    <span class="badge badge-success"><?php echo e(__('app.active')); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo e(__('app.inactive')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right text-nowrap">
                                
                                <button type="button"
                                        class="btn btn-sm btn-outline-info btn-test-conn"
                                        data-url="<?php echo e(route('clients.test-connection', $client)); ?>"
                                        data-client="<?php echo e($client->business_name); ?>"
                                        title="<?php echo e(__('clients.test_connection')); ?>">
                                    <i class="fas fa-plug"></i>
                                </button>
                                <a href="<?php echo e(route('clients.edit', $client)); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo e(__('app.edit')); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('clients.toggle-active', $client)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo e($client->is_active ? 'warning' : 'success'); ?>" title="<?php echo e($client->is_active ? __('app.deactivate') : __('app.activate')); ?>">
                                        <i class="fas fa-<?php echo e($client->is_active ? 'ban' : 'check'); ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4"><?php echo e(__('clients.no_clients')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="modal fade" id="testConnModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header py-2" id="testConnModalHeader">
                    <h6 class="modal-title mb-0" id="testConnModalTitle"><?php echo e(__('clients.test_connection')); ?></h6>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center py-4" id="testConnModalBody">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    
    <div id="copy-toast" style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999;"
         class="alert alert-success py-2 px-3 shadow">
        <i class="fas fa-check mr-1"></i> <?php echo e(__('clients.key_copied')); ?>

    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// --- Copy API key to clipboard ---
$(document).on('click', '.btn-copy-key', function () {
    var key = $(this).data('key');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(key);
    } else {
        var $tmp = $('<textarea>').val(key).appendTo('body').select();
        document.execCommand('copy');
        $tmp.remove();
    }
    var $toast = $('#copy-toast');
    $toast.fadeIn(200).delay(1800).fadeOut(400);
});

// --- Test connection ---
$(document).on('click', '.btn-test-conn', function () {
    var url        = $(this).data('url');
    var clientName = $(this).data('client');
    var $header    = $('#testConnModalHeader');
    var $body      = $('#testConnModalBody');
    var $title     = $('#testConnModalTitle');

    $header.removeClass('bg-success bg-danger text-white');
    $title.text('<?php echo e(__("clients.test_connection")); ?> — ' + clientName);
    $body.html('<i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted small"><?php echo e(__("clients.testing")); ?></p>');
    $('#testConnModal').modal('show');

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                $header.addClass('bg-success text-white');
                $body.html(
                    '<i class="fas fa-check-circle fa-2x text-white mb-2"></i>' +
                    '<p class="mb-0 font-weight-bold"><?php echo e(__("clients.test_ok")); ?></p>'
                );
            } else {
                $header.addClass('bg-danger text-white');
                $body.html(
                    '<i class="fas fa-times-circle fa-2x text-white mb-2"></i>' +
                    '<p class="mb-1 font-weight-bold"><?php echo e(__("clients.test_unreachable")); ?></p>' +
                    '<p class="small mb-0">' + $('<div>').text(res.message).html() + '</p>'
                );
            }
        },
        error: function () {
            $header.addClass('bg-danger text-white');
            $body.html('<i class="fas fa-times-circle fa-2x text-white mb-2"></i><p class="mb-0">Error inesperado.</p>');
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\support-hub\resources\views/clients/index.blade.php ENDPATH**/ ?>