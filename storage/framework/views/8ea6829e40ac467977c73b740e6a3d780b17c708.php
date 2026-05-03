

<?php $__env->startSection('page-title'); ?>
    <a href="<?php echo e(route('dashboard')); ?>" class="text-dark text-decoration-none"><i class="fas fa-arrow-left mr-2"></i></a>
    <?php echo e($ticket->ticket_number); ?> — <?php echo e(Str::limit($ticket->subject, 40)); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/ticket-detail.css')); ?>">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    
    <div class="col-lg-8">
        
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <?php
                        $typeIcons = ['bug'=>'fa-bug text-danger','configuration'=>'fa-cog text-info','question'=>'fa-question-circle text-primary','feature_request'=>'fa-lightbulb text-warning'];
                    ?>
                    <i class="fas <?php echo e($typeIcons[$ticket->ticket_type] ?? 'fa-ticket-alt'); ?> mr-1"></i>
                    <?php if($ticket->isInternal()): ?>
                        <strong id="ticket-subject" class="editable-field" title="Click para editar"><?php echo e($ticket->subject); ?></strong>
                        <i class="fas fa-pencil-alt text-muted ml-1" style="font-size:0.7rem;cursor:pointer;" id="btn-edit-subject"></i>
                    <?php else: ?>
                        <strong><?php echo e($ticket->subject); ?></strong>
                    <?php endif; ?>
                </div>
                <span class="badge badge-priority-<?php echo e($ticket->issue_priority); ?>"><?php echo e(strtoupper($ticket->issue_priority)); ?></span>
            </div>
            
            <div class="card-body py-2 border-bottom" id="ticket-labels-bar">
                <div class="d-flex flex-wrap align-items-center">
                    <small class="text-muted font-weight-bold mr-2"><i class="fas fa-tags mr-1"></i><?php echo e(__('tickets.labels')); ?>:</small>
                    <span id="ticket-labels-inline">
                        <?php $__currentLoopData = $ticket->labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="badge label-badge mr-1 mb-1" style="background-color:<?php echo e($label->color); ?>;color:#fff;" data-id="<?php echo e($label->id); ?>"><?php echo e($label->name); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-link text-muted p-0 ml-1" type="button" id="labelDropdownBtn" data-toggle="dropdown" title="<?php echo e(__('tickets.add_label')); ?>">
                            <?php if($ticket->labels->isEmpty()): ?>
                                <i class="fas fa-plus mr-1"></i><small><?php echo e(__('tickets.add_label')); ?></small>
                            <?php else: ?>
                                <i class="fas fa-plus"></i>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu p-2 label-dropdown" style="width:260px;" aria-labelledby="labelDropdownBtn">
                            <input type="text" class="form-control form-control-sm mb-2" id="label-search" placeholder="<?php echo e(__('tickets.search_labels')); ?>">
                            <div id="label-options" style="max-height:150px;overflow-y:auto;"></div>
                            <hr class="my-2">
                            <div class="small font-weight-bold mb-1"><?php echo e(__('tickets.create_label')); ?></div>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control form-control-sm mr-1" id="new-label-name" placeholder="<?php echo e(__('tickets.label_name_placeholder')); ?>" maxlength="50" style="flex:1;">
                                <input type="color" id="new-label-color" value="#3498db" class="mr-1" style="width:30px;height:30px;padding:0;border:1px solid #ced4da;border-radius:3px;cursor:pointer;">
                                <button type="button" class="btn btn-sm btn-success" id="btn-create-label"><i class="fas fa-check"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><small class="text-muted"><?php echo e(__('tickets.module')); ?></small><br><strong><?php echo e(ucfirst(str_replace('_',' ',$ticket->module))); ?></strong><?php if($ticket->sub_module): ?> <span class="text-muted">/ <?php echo e($ticket->sub_module); ?></span><?php endif; ?></div>
                    <div class="col-sm-4"><small class="text-muted"><?php echo e(__('tickets.type')); ?></small><br><strong><?php echo e(ucfirst(str_replace('_',' ',$ticket->ticket_type))); ?></strong></div>
                    <div class="col-sm-4"><small class="text-muted"><?php echo e(__('tickets.reporter')); ?></small><br><strong><?php echo e($ticket->reporter_name); ?></strong><br><small class="text-muted"><?php echo e($ticket->reporter_email); ?></small></div>
                </div>

                <h6><?php echo e(__('tickets.description')); ?></h6>
                <?php if($ticket->isInternal()): ?>
                    <div class="ticket-text-block position-relative" id="description-display">
                        <button type="button" class="btn btn-sm btn-link position-absolute" style="top:4px;right:4px;z-index:1;" id="btn-edit-description" title="Editar">
                            <i class="fas fa-pencil-alt text-muted"></i>
                        </button>
                        <div id="description-content" class="description-collapsed"><?php echo $ticket->description; ?></div>
                        <div class="text-center mt-1" id="description-toggle-wrap">
                            <button type="button" class="btn btn-sm btn-link" id="btn-toggle-description">
                                <i class="fas fa-chevron-down mr-1"></i><?php echo e(__('tickets.show_more')); ?>

                            </button>
                        </div>
                    </div>
                    <div id="description-editor-wrap" style="display:none;">
                        <div id="description-editor"><?php echo $ticket->description; ?></div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-success" id="btn-save-description"><i class="fas fa-check mr-1"></i>Guardar</button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btn-cancel-description">Cancelar</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="ticket-text-block"><?php echo nl2br(e($ticket->description)); ?></div>
                <?php endif; ?>

                <?php if($ticket->steps_to_reproduce): ?>
                    <h6 class="mt-3"><?php echo e(__('tickets.steps_to_reproduce')); ?></h6>
                    <div class="ticket-text-block"><?php echo nl2br(e($ticket->steps_to_reproduce)); ?></div>
                <?php endif; ?>

                <?php if($ticket->expected_behavior): ?>
                    <h6 class="mt-3"><?php echo e(__('tickets.expected_behavior')); ?></h6>
                    <div class="ticket-text-block"><?php echo nl2br(e($ticket->expected_behavior)); ?></div>
                <?php endif; ?>

                <?php if($ticket->browser_url): ?>
                    <h6 class="mt-3"><?php echo e(__('tickets.browser_url')); ?></h6>
                    <code><?php echo e($ticket->browser_url); ?></code>
                <?php endif; ?>

                
                <?php if($ticket->attachments->whereNotIn('source', ['sp_file'])->isNotEmpty()): ?>
                    <h6 class="mt-3"><?php echo e(__('tickets.attachments')); ?></h6>
                    <div class="d-flex flex-wrap">
                        <?php $__currentLoopData = $ticket->attachments->whereNotIn('source', ['sp_file']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="attachment-thumb mr-2 mb-2">
                                <?php if($att->isImage()): ?>
                                    <a href="<?php echo e(asset('storage/' . $att->file_path)); ?>" target="_blank" download="<?php echo e($att->file_name); ?>">
                                        <img src="<?php echo e(asset('storage/' . $att->file_path)); ?>" alt="<?php echo e($att->file_name); ?>">
                                    </a>
                                <?php elseif($att->isVideo()): ?>
                                    <a href="<?php echo e(asset('storage/' . $att->file_path)); ?>" target="_blank" download="<?php echo e($att->file_name); ?>" class="video-thumb">
                                        <i class="fas fa-play-circle"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo e(asset('storage/' . $att->file_path)); ?>" target="_blank" download="<?php echo e($att->file_name); ?>" class="doc-thumb">
                                        <i class="fas fa-file"></i>
                                    </a>
                                <?php endif; ?>
                                <small class="d-block text-center text-muted"><?php echo e(Str::limit($att->file_name, 15)); ?></small>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="card mb-3" id="comments-card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-internal" role="tab">
                            <i class="fas fa-lock mr-1"></i> <?php echo e(__('tickets.internal_notes')); ?>

                            <span class="badge badge-secondary ml-1"><?php echo e($ticket->comments->where('visibility','internal')->count()); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-client" role="tab">
                            <i class="fas fa-reply mr-1"></i> <?php echo e(__('tickets.client_response')); ?>

                            <span class="badge badge-secondary ml-1"><?php echo e($ticket->comments->where('visibility','client')->count()); ?></span>
                            <?php if($ticket->comments->where('visibility','client')->where('source','client')->count() > 0): ?>
                                <span class="badge badge-warning ml-1" title="<?php echo e(__('tickets.client_replies_count', ['count' => $ticket->comments->where('visibility','client')->where('source','client')->count()])); ?>">
                                    <i class="fas fa-user"></i> <?php echo e($ticket->comments->where('visibility','client')->where('source','client')->count()); ?>

                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    
                    <div class="tab-pane active" id="tab-internal" role="tabpanel">
                        <div id="comments-internal" class="comments-list">
                            <?php $__currentLoopData = $ticket->comments->where('visibility','internal'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo $__env->make('tickets._comment', ['comment' => $c], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <form id="form-internal" class="comment-form mt-3" data-visibility="internal">
                            <div class="summernote-editor" data-placeholder="<?php echo e(__('tickets.note_placeholder')); ?>"></div>
                            <div class="mt-2 text-right">
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> <?php echo e(__('tickets.add_note')); ?></button>
                            </div>
                        </form>
                    </div>

                    
                    <div class="tab-pane" id="tab-client" role="tabpanel">
                        <div class="alert alert-info small mb-2"><i class="fas fa-info-circle mr-1"></i> <?php echo e(__('tickets.client_visible_msg')); ?></div>
                        <div id="comments-client" class="comments-list">
                            <?php $__currentLoopData = $ticket->comments->where('visibility','client'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo $__env->make('tickets._comment', ['comment' => $c], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <form id="form-client" class="comment-form mt-3" data-visibility="client">
                            <div class="summernote-editor" data-placeholder="<?php echo e(__('tickets.response_placeholder')); ?>"></div>
                            <div class="mt-2 text-right">
                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-paper-plane mr-1"></i> <?php echo e(__('tickets.send_to_client')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <?php $advancedState = in_array($ticket->board_column, ['code_review', 'qa_testing', 'ready_for_release', 'done']); ?>
        <div class="card mb-3 <?php echo e($advancedState ? 'dev-notes-highlight' : ''); ?>" id="dev-notes-card">
            <div class="card-header <?php echo e($advancedState ? 'bg-warning text-dark' : ''); ?>">
                <i class="fas fa-code mr-1"></i> <?php echo e(__('tickets.dev_notes')); ?>

                <?php if($advancedState): ?>
                    <span class="badge badge-dark ml-2"><i class="fas fa-exclamation-circle mr-1"></i>Revisar antes de deploy</span>
                <?php endif; ?>
            </div>
            <div class="card-body">

                
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold"><i class="fab fa-github mr-1"></i><?php echo e(__('tickets.pr_links')); ?></label>
                    <div id="pr-links-list">
                        <?php $__currentLoopData = $ticket->prLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="dev-link-item d-flex align-items-center mb-1" data-id="<?php echo e($link->id); ?>">
                                <a href="<?php echo e($link->url); ?>" target="_blank" rel="noopener" class="small text-truncate flex-grow-1">
                                    <i class="fas fa-code-branch mr-1"></i><?php echo e($link->title ?: $link->url); ?>

                                </a>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-pr" data-id="<?php echo e($link->id); ?>" title="<?php echo e(__('app.delete')); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div id="pr-link-form" style="display:none;" class="mt-1">
                        <div class="input-group input-group-sm">
                            <input type="url" id="pr-link-url" class="form-control" placeholder="<?php echo e(__('tickets.pr_placeholder')); ?>">
                            <input type="text" id="pr-link-title" class="form-control" placeholder="<?php echo e(__('tickets.pr_title_placeholder')); ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success btn-sm" id="btn-save-pr"><i class="fas fa-check"></i></button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-pr"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn-add-pr">
                        <i class="fas fa-plus mr-1"></i><?php echo e(__('tickets.add_pr_link')); ?>

                    </button>
                </div>

                
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold"><i class="fas fa-code-branch mr-1"></i><?php echo e(__('tickets.commits')); ?></label>
                    <div id="commits-list">
                        <?php $__currentLoopData = $ticket->commits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $commit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="dev-link-item d-flex align-items-center mb-1" data-id="<?php echo e($commit->id); ?>">
                                <?php if($commit->url): ?>
                                    <a href="<?php echo e($commit->url); ?>" target="_blank" rel="noopener" class="small text-truncate flex-grow-1">
                                        <code class="mr-1"><?php echo e(substr($commit->hash, 0, 7)); ?></code><?php echo e($commit->message ?: ''); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="small text-truncate flex-grow-1">
                                        <code class="mr-1"><?php echo e(substr($commit->hash, 0, 7)); ?></code><?php echo e($commit->message ?: ''); ?>

                                    </span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-commit" data-id="<?php echo e($commit->id); ?>" title="<?php echo e(__('app.delete')); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div id="commit-form" style="display:none;" class="mt-1">
                        <div id="commit-editor"></div>
                        <div class="d-flex align-items-center mt-1">
                            <button type="button" class="btn btn-success btn-sm" id="btn-save-commit"><i class="fas fa-check mr-1"></i>Guardar</button>
                            <button type="button" class="btn btn-secondary btn-sm ml-1" id="btn-cancel-commit"><i class="fas fa-times mr-1"></i>Cancelar</button>
                            <small class="text-muted ml-2"><?php echo e(__('tickets.commit_paste_hint')); ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn-add-commit">
                        <i class="fas fa-plus mr-1"></i><?php echo e(__('tickets.add_commit')); ?>

                    </button>
                </div>

                <hr class="my-2">

                
                <form id="form-dev-fields">
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold"><i class="fas fa-database mr-1"></i><?php echo e(__('tickets.sp_files_label')); ?></label>
                        <div id="sp-files-list" class="mt-2">
                            <?php $__currentLoopData = $ticket->attachments->where('source', 'sp_file'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spFile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="dev-link-item d-flex align-items-center mb-1" data-id="<?php echo e($spFile->id); ?>">
                                    <a href="<?php echo e(Storage::disk('public')->url($spFile->file_path)); ?>" download="<?php echo e($spFile->file_name); ?>" class="small text-truncate flex-grow-1">
                                        <i class="fas fa-database mr-1 text-info"></i><?php echo e($spFile->file_name); ?>

                                        <small class="text-muted ml-1">(<?php echo e(number_format($spFile->file_size / 1024, 1)); ?> KB)</small>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-sp-file" data-id="<?php echo e($spFile->id); ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <label class="btn btn-sm btn-outline-info mt-1 mb-0" id="sp-file-upload-label">
                            <i class="fas fa-paperclip mr-1"></i><?php echo e(__('tickets.attach_sql')); ?>

                            <input type="file" id="sp-file-input" accept=".sql,.txt" style="display:none;">
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="small text-muted"><?php echo e(__('tickets.deploy_notes')); ?></label>
                        <textarea name="deploy_notes" class="form-control form-control-sm" rows="2" placeholder="<?php echo e(__('tickets.deploy_placeholder')); ?>"><?php echo e($ticket->deploy_notes); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-save mr-1"></i> <?php echo e(__('tickets.save_dev_notes')); ?></button>
                    <span id="dev-fields-status" class="ml-2 small text-success" style="display:none;"><?php echo e(__('app.saved')); ?></span>
                </form>
            </div>
        </div>
    </div>

    
    <div class="col-lg-4">
        
        <div class="card mb-3">
            <div class="card-body">
                <?php
                    $colColors = ['to_do'=>'#3498db','in_progress'=>'#f39c12','blocked'=>'#e74c3c','code_review'=>'#9b59b6','qa_testing'=>'#1abc9c','ready_for_release'=>'#2ecc71','done'=>'#27ae60'];
                ?>

                
                <div class="form-group mb-3">
                    <label class="small text-muted mb-1"><?php echo e(__('tickets.board_column')); ?></label>
                    <select id="column-select" class="form-control form-control-sm">
                        <?php $__currentLoopData = \App\Models\Ticket::BOARD_COLUMNS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($col); ?>" <?php echo e($ticket->board_column === $col ? 'selected' : ''); ?>

                                style="color: <?php echo e($colColors[$col] ?? '#6c757d'); ?>;">
                                <?php echo e(__('tickets.col_' . $col)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <?php if($ticket->board_column !== 'done'): ?>
                    <?php if($ticket->isInternal()): ?>
                        <button class="btn btn-success btn-block btn-sm mb-3" id="btn-mark-done-internal">
                            <i class="fas fa-check mr-1"></i> <?php echo e(__('tickets.mark_as_done')); ?>

                        </button>
                    <?php else: ?>
                        <button class="btn btn-success btn-block btn-sm mb-3" data-toggle="modal" data-target="#doneModal">
                            <i class="fas fa-check mr-1"></i> <?php echo e(__('tickets.mark_as_done')); ?>

                        </button>
                    <?php endif; ?>
                <?php endif; ?>

                
                <div class="form-group mb-3">
                    <label class="small text-muted mb-1"><?php echo e(__('tickets.assigned_to')); ?></label>
                    <select id="assign-select" class="form-control form-control-sm">
                        <option value=""><?php echo e(__('tickets.unassigned')); ?></option>
                        <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($m->id); ?>" <?php echo e($ticket->assigned_to == $m->id ? 'selected' : ''); ?>>
                                <?php echo e($m->name); ?> (<?php echo e(strtoupper($m->role)); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if($suggested): ?>
                        <small class="text-info"><i class="fas fa-magic mr-1"></i><?php echo e(__('tickets.suggested', ['name' => $suggested->name, 'module' => $ticket->module])); ?></small>
                    <?php endif; ?>
                </div>

                <hr class="my-2">

                
                <?php if(!$ticket->archived_at && $ticket->board_column === 'done'): ?>
                    <button type="button" class="btn btn-outline-warning btn-block btn-sm" id="btn-archive">
                        <i class="fas fa-archive mr-1"></i> <?php echo e(__('tickets.archive_ticket')); ?>

                    </button>
                <?php endif; ?>

                
                <?php if($ticket->archived_at): ?>
                    <div class="text-center mb-2">
                        <span class="badge badge-warning px-3 py-2"><i class="fas fa-archive mr-1"></i> <?php echo e(__('tickets.archived')); ?></span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-block btn-sm" id="btn-delete">
                        <i class="fas fa-trash mr-1"></i> <?php echo e(__('tickets.delete_ticket')); ?>

                    </button>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($ticket->client): ?>
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-building mr-1"></i> <?php echo e(__('tickets.client')); ?></div>
            <div class="card-body py-2">
                <strong><?php echo e($ticket->client->business_name); ?></strong>
                <br><code class="small"><?php echo e($ticket->client->client_identifier); ?></code>
                <?php $pColors = ['low'=>'secondary','medium'=>'info','high'=>'warning','vip'=>'danger']; ?>
                <span class="badge badge-<?php echo e($pColors[$ticket->client_priority] ?? 'secondary'); ?> ml-1"><?php echo e(strtoupper($ticket->client_priority)); ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-laptop-code mr-1"></i> <?php echo e(__('tickets.source_filter')); ?></div>
            <div class="card-body py-2">
                <span class="badge badge-success"><i class="fas fa-laptop-code mr-1"></i><?php echo e(__('tickets.internal_label')); ?></span>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo e(__('tickets.priority')); ?></div>
            <div class="card-body py-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted"><?php echo e(__('tickets.issue')); ?></span>
                    <span class="badge badge-priority-<?php echo e($ticket->issue_priority); ?>"><?php echo e(strtoupper($ticket->issue_priority)); ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted"><?php echo e(__('tickets.client')); ?></span>
                    <span class="badge badge-<?php echo e($pColors[$ticket->client_priority] ?? 'secondary'); ?>"><?php echo e(strtoupper($ticket->client_priority)); ?></span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted"><?php echo e(__('tickets.effective')); ?></span>
                    <strong>#<?php echo e($ticket->effective_priority); ?></strong>
                </div>
            </div>
        </div>

        
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-robot mr-1"></i> <?php echo e(__('tickets.ai_classification')); ?></div>
            <div class="card-body py-2">
                <?php
                    $incidentTypes = config('support.incident_types');
                    $aiStatusLabels = [
                        'pending'    => ['label' => __('tickets.ai_status_pending'),    'class' => 'secondary', 'icon' => 'fa-spinner fa-spin'],
                        'classified' => ['label' => __('tickets.ai_status_classified'), 'class' => 'success',   'icon' => 'fa-check'],
                        'failed'     => ['label' => __('tickets.ai_status_failed'),     'class' => 'danger',    'icon' => 'fa-exclamation-triangle'],
                        'manual'     => ['label' => __('tickets.ai_status_manual'),     'class' => 'info',      'icon' => 'fa-user'],
                    ];
                    $aiSt = $aiStatusLabels[$ticket->ai_status] ?? $aiStatusLabels['pending'];
                ?>

                
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted"><?php echo e(__('tickets.ai_status')); ?></span>
                    <span class="badge badge-<?php echo e($aiSt['class']); ?>">
                        <i class="fas <?php echo e($aiSt['icon']); ?> mr-1"></i><?php echo e($aiSt['label']); ?>

                    </span>
                </div>

                
                <?php if($ticket->incident_type && isset($incidentTypes[$ticket->incident_type])): ?>
                    <?php $inc = $incidentTypes[$ticket->incident_type]; ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><?php echo e(__('tickets.ai_type')); ?></span>
                        <span class="badge badge-<?php echo e($inc['badge']); ?>"><?php echo e(__('reports.incident_' . $ticket->incident_type)); ?></span>
                    </div>
                <?php endif; ?>

                
                <?php if($ticket->ai_suggestion): ?>
                    <div class="small mb-2">
                        <span class="text-muted d-block"><?php echo e(__('tickets.ai_suggestion')); ?></span>
                        <em><?php echo e($ticket->ai_suggestion); ?></em>
                    </div>
                <?php endif; ?>

                <?php if($ticket->ai_justification): ?>
                    <div class="small mb-2">
                        <span class="text-muted d-block"><?php echo e(__('tickets.ai_justification')); ?></span>
                        <em><?php echo e($ticket->ai_justification); ?></em>
                    </div>
                <?php endif; ?>

                
                <hr class="my-2">
                <div class="small text-muted mb-1"><?php echo e(__('tickets.ai_reclassify')); ?></div>
                <div class="d-flex">
                    <select id="classify-override-select" class="form-control form-control-sm mr-2">
                        <?php $__currentLoopData = $incidentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cfg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e($ticket->incident_type === $key ? 'selected' : ''); ?>>
                                <?php echo e(__('reports.incident_' . $key)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-classify-override" title="<?php echo e(__('tickets.ai_save')); ?>">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
                <span id="classify-override-status" class="small text-success mt-1" style="display:none;"><?php echo e(__('tickets.ai_saved')); ?></span>
            </div>
        </div>

        
        <?php if($ticket->isInternal()): ?>
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-calendar-alt mr-1"></i> <?php echo e(__('tickets.due_date')); ?></div>
            <div class="card-body py-2">
                <div class="d-flex align-items-center">
                    <input type="date" id="due-date-input" class="form-control form-control-sm" value="<?php echo e($ticket->due_date ? $ticket->due_date->format('Y-m-d') : ''); ?>" style="max-width:180px;">
                    <span id="due-date-status" class="ml-2 small text-success" style="display:none;"><?php echo e(__('app.saved')); ?></span>
                    <?php if($ticket->due_date): ?>
                        <?php if($ticket->isInAdvancedState()): ?>
                            <span class="badge badge-success ml-2" style="font-size:0.8rem;">✅ <?php echo e($ticket->due_date->format('d M Y')); ?></span>
                        <?php elseif($ticket->isOverdue()): ?>
                            <span class="badge badge-danger ml-2" style="font-size:0.8rem;"><i class="fas fa-exclamation-circle mr-1"></i><?php echo e(__('tickets.overdue')); ?> — <?php echo e($ticket->due_date->format('d M Y')); ?></span>
                        <?php elseif($ticket->isDueSoon()): ?>
                            <span class="badge badge-warning ml-2" style="font-size:0.8rem;"><i class="fas fa-clock mr-1"></i><?php echo e(__('tickets.due_today')); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="card mb-3">
            <div class="card-header small"><i class="far fa-clock mr-1"></i> <?php echo e(__('tickets.timestamps')); ?></div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted"><?php echo e(__('tickets.created')); ?></span><span><?php echo e($ticket->created_at->format('M d, Y H:i')); ?></span></div>
                <?php if($ticket->resolved_at): ?>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted"><?php echo e(__('tickets.resolved')); ?></span><span><?php echo e($ticket->resolved_at->format('M d, Y H:i')); ?></span></div>
                <?php endif; ?>
                <?php if($ticket->archived_at): ?>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted"><?php echo e(__('tickets.archived')); ?></span><span><?php echo e($ticket->archived_at->format('M d, Y H:i')); ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-history mr-1"></i> <?php echo e(__('tickets.timeline')); ?></div>
            <div class="card-body py-2">
                <div class="timeline">
                    <?php $__currentLoopData = $ticket->statusHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background:<?php echo e($colColors[$entry->new_column] ?? '#6c757d'); ?>;"></div>
                            <div class="timeline-content">
                                <strong class="small"><?php echo e($entry->new_column ? ucfirst(str_replace('_',' ',$entry->new_column)) : ''); ?></strong>
                                <?php if($entry->notes): ?> <span class="small text-muted d-block"><?php echo e($entry->notes); ?></span> <?php endif; ?>
                                <span class="small text-muted">
                                    <?php echo e($entry->changedByMember ? $entry->changedByMember->name : __('tickets.system')); ?>

                                    · <?php echo e($entry->created_at ? $entry->created_at->diffForHumans() : ''); ?>

                                </span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="doneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo e(__('tickets.resolve_ticket', ['ticket' => $ticket->ticket_number])); ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo e(__('tickets.resolution_message')); ?></label>
                    <textarea id="done-message" class="form-control" rows="3" placeholder="<?php echo e(__('tickets.resolve_placeholder')); ?>"><?php echo e($ticket->resolution_message); ?></textarea>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="done-generic">
                    <label class="custom-control-label" for="done-generic"><?php echo e(__('tickets.use_default_message')); ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('app.cancel')); ?></button>
                <button type="button" class="btn btn-success" id="btn-mark-done"><i class="fas fa-check mr-1"></i> <?php echo e(__('tickets.mark_as_done')); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmActionModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <p id="confirm-action-text" class="mb-3"></p>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm mr-1" data-dismiss="modal"><?php echo e(__('app.cancel')); ?></button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirm-action-btn"><?php echo e(__('app.confirm')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if(!$ticket->archived_at && $ticket->board_column === 'done'): ?>
<div class="modal fade" id="archiveTicketModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-archive mr-1"></i> <?php echo e(__('tickets.archive_ticket')); ?></h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small"><?php echo e(__('tickets.archive_confirm')); ?></p>
                <div class="form-group mb-1">
                    <label class="small"><?php echo e(__('tickets.archive_type_confirm')); ?></label>
                    <input type="text" id="archive-confirm-input" class="form-control form-control-sm" autocomplete="off" placeholder="archivar">
                </div>
                <p id="archive-confirm-error" class="text-danger small mb-0" style="display:none;"><?php echo e(__('tickets.confirm_mismatch')); ?></p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php echo e(__('app.cancel')); ?></button>
                <button type="button" class="btn btn-sm btn-warning" id="archive-confirm-btn" disabled>
                    <i class="fas fa-archive mr-1"></i> <?php echo e(__('tickets.archive_ticket')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($ticket->archived_at): ?>
<div class="modal fade" id="deleteTicketModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo e(__('tickets.delete_ticket')); ?></h6>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-danger small"><strong><?php echo e(__('tickets.delete_warning')); ?></strong></p>
                <div class="form-group mb-1">
                    <label class="small"><?php echo e(__('tickets.delete_type_confirm')); ?></label>
                    <input type="text" id="delete-confirm-input" class="form-control form-control-sm" autocomplete="off" placeholder="eliminar">
                </div>
                <p id="delete-confirm-error" class="text-danger small mb-0" style="display:none;"><?php echo e(__('tickets.confirm_mismatch')); ?></p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php echo e(__('app.cancel')); ?></button>
                <button type="button" class="btn btn-sm btn-danger" id="delete-confirm-btn" disabled>
                    <i class="fas fa-trash mr-1"></i> <?php echo e(__('tickets.delete_ticket')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<div id="action-toast" style="display:none;position:fixed;top:20px;right:20px;z-index:9999;background:#27ae60;color:#fff;padding:10px 20px;border-radius:6px;font-size:0.9rem;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-check-circle mr-1"></i> <span id="action-toast-text"></span>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    window.ticketId = <?php echo e($ticket->id); ?>;
    window.i18n = {
        comment_failed: <?php echo json_encode(__('tickets.comment_failed'), 15, 512) ?>,
        comment_empty: <?php echo json_encode(__('tickets.comment_empty'), 15, 512) ?>,
        upload_failed: <?php echo json_encode(__('tickets.upload_failed'), 15, 512) ?>,
        file_too_large: <?php echo json_encode(__('tickets.file_too_large'), 15, 512) ?>,
        assign_failed: <?php echo json_encode(__('tickets.assign_failed'), 15, 512) ?>,
        dev_notes_failed: <?php echo json_encode(__('tickets.dev_notes_failed'), 15, 512) ?>,
        resolve_failed: <?php echo json_encode(__('tickets.resolve_failed'), 15, 512) ?>,
        move_done_failed: <?php echo json_encode(__('tickets.move_done_failed'), 15, 512) ?>,
        default_resolution: <?php echo json_encode(__('tickets.default_resolution'), 15, 512) ?>,
        client_label: <?php echo json_encode(__('tickets.client_label'), 15, 512) ?>,
        move_column_failed: <?php echo json_encode(__('tickets.move_column_failed'), 15, 512) ?>,
        archive_confirm: <?php echo json_encode(__('tickets.archive_confirm'), 15, 512) ?>,
        archive_type_confirm: <?php echo json_encode(__('tickets.archive_type_confirm'), 15, 512) ?>,
        delete_type_confirm: <?php echo json_encode(__('tickets.delete_type_confirm'), 15, 512) ?>,
        archive_requires_done: <?php echo json_encode(__('tickets.archive_requires_done'), 15, 512) ?>,
        delete_confirm: <?php echo json_encode(__('tickets.delete_confirm'), 15, 512) ?>,
        archive_failed: <?php echo json_encode(__('tickets.archive_failed'), 15, 512) ?>,
        delete_failed: <?php echo json_encode(__('tickets.delete_failed'), 15, 512) ?>,
        archive_ticket: <?php echo json_encode(__('tickets.archive_ticket'), 15, 512) ?>,
        delete_ticket: <?php echo json_encode(__('tickets.delete_ticket'), 15, 512) ?>,
        confirm_move_column: <?php echo json_encode(__('tickets.confirm_move_column'), 15, 512) ?>,
        confirm_assign: <?php echo json_encode(__('tickets.confirm_assign'), 15, 512) ?>,
        column_moved: <?php echo json_encode(__('tickets.column_moved'), 15, 512) ?>,
        assign_success: <?php echo json_encode(__('tickets.assign_success'), 15, 512) ?>,
        no_labels: <?php echo json_encode(__('tickets.no_labels'), 15, 512) ?>,
        label_exists: <?php echo json_encode(__('tickets.label_exists'), 15, 512) ?>,
        add_label: <?php echo json_encode(__('tickets.add_label'), 15, 512) ?>,
        show_more: <?php echo json_encode(__('tickets.show_more'), 15, 512) ?>,
        show_less: <?php echo json_encode(__('tickets.show_less'), 15, 512) ?>,
        commit_paste_placeholder: <?php echo json_encode(__('tickets.commit_paste_placeholder'), 15, 512) ?>,
        delete_comment_confirm: <?php echo json_encode(__('tickets.delete_comment_confirm'), 15, 512) ?>
    };
    window.ticketLabels = <?php echo json_encode($ticket->labels->map(function($l) { return ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]; })) ?>;
    <?php if($advancedState): ?>
    // Move dev notes before comments in advanced states
    $(function() { $('#dev-notes-card').insertBefore('#comments-card'); });
    <?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script src="<?php echo e(asset('js/ticket-detail.js')); ?>"></script>
<script>
$(document).ready(function () {
    var classifyUrl = '<?php echo e(route('tickets.classify-override', $ticket)); ?>';

    $('#btn-classify-override').on('click', function () {
        var btn = $(this);
        var incidentType = $('#classify-override-select').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: classifyUrl,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                incident_type: incidentType
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#classify-override-status').fadeIn().delay(2000).fadeOut();
                }
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i>');
            }
        });
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\support-hub\resources\views/tickets/show.blade.php ENDPATH**/ ?>