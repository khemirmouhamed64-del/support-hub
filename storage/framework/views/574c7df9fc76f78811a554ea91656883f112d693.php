<?php $__env->startSection('page-title', $client->exists ? __('clients.edit_client') : __('clients.add_client')); ?>

<?php $__env->startSection('content'); ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo e($client->exists ? route('clients.update', $client) : route('clients.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if($client->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

                        <div class="form-group">
                            <label for="client_identifier"><?php echo e(__('clients.identifier')); ?> <small class="text-muted"><?php echo e(__('clients.identifier_note')); ?></small></label>
                            <input type="text" name="client_identifier" id="client_identifier"
                                   class="form-control <?php $__errorArgs = ['client_identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('client_identifier', $client->client_identifier)); ?>" required
                                   placeholder="<?php echo e(__('clients.identifier_placeholder')); ?>">
                            <?php $__errorArgs = ['client_identifier'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label for="business_name"><?php echo e(__('clients.business_name')); ?></label>
                            <input type="text" name="business_name" id="business_name"
                                   class="form-control <?php $__errorArgs = ['business_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('business_name', $client->business_name)); ?>" required
                                   placeholder="<?php echo e(__('clients.name_placeholder')); ?>">
                            <?php $__errorArgs = ['business_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label for="api_callback_url"><?php echo e(__('clients.callback_url')); ?> <small class="text-muted"><?php echo e(__('clients.callback_note')); ?></small></label>
                            <input type="url" name="api_callback_url" id="api_callback_url"
                                   class="form-control <?php $__errorArgs = ['api_callback_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('api_callback_url', $client->api_callback_url)); ?>"
                                   placeholder="<?php echo e(__('clients.callback_placeholder')); ?>">
                            <?php $__errorArgs = ['api_callback_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label for="priority_level"><?php echo e(__('clients.priority_level')); ?></label>
                            <select name="priority_level" id="priority_level" class="form-control <?php $__errorArgs = ['priority_level'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = ['low' => __('clients.priority_low'), 'medium' => __('clients.priority_medium'), 'high' => __('clients.priority_high'), 'vip' => __('clients.priority_vip')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($val); ?>" <?php echo e(old('priority_level', $client->priority_level ?? 'medium') === $val ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['priority_level'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <?php if($client->exists): ?>
                            <div class="form-group">
                                <label><?php echo e(__('clients.api_key')); ?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" value="<?php echo e($client->api_key); ?>" readonly id="apiKeyField">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="copyApiKey()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo e(__('clients.key_help')); ?></small>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> <?php echo e(__('app.back')); ?>

                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> <?php echo e($client->exists ? __('clients.update_client') : __('clients.create_client')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php if($client->exists): ?>
<?php $__env->startPush('scripts'); ?>
<script>
function copyApiKey() {
    var field = document.getElementById('apiKeyField');
    field.select();
    document.execCommand('copy');
    alert('<?php echo e(__('clients.key_copied')); ?>');
}
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\support-hub\resources\views/clients/form.blade.php ENDPATH**/ ?>