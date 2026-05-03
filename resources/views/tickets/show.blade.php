@extends('layouts.app')

@section('page-title')
    <a href="{{ route('dashboard') }}" class="text-dark text-decoration-none"><i class="fas fa-arrow-left mr-2"></i></a>
    {{ $ticket->ticket_number }} — {{ Str::limit($ticket->subject, 40) }}
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ticket-detail.css') }}">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="row">
    {{-- Main panel (left) --}}
    <div class="col-lg-8">
        {{-- Ticket info card --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    @php
                        $typeIcons = ['bug'=>'fa-bug text-danger','configuration'=>'fa-cog text-info','question'=>'fa-question-circle text-primary','feature_request'=>'fa-lightbulb text-warning'];
                    @endphp
                    <i class="fas {{ $typeIcons[$ticket->ticket_type] ?? 'fa-ticket-alt' }} mr-1"></i>
                    @if($ticket->isInternal())
                        <strong id="ticket-subject" class="editable-field" title="Click para editar">{{ $ticket->subject }}</strong>
                        <i class="fas fa-pencil-alt text-muted ml-1" style="font-size:0.7rem;cursor:pointer;" id="btn-edit-subject"></i>
                    @else
                        <strong>{{ $ticket->subject }}</strong>
                    @endif
                </div>
                <span class="badge badge-priority-{{ $ticket->issue_priority }}">{{ strtoupper($ticket->issue_priority) }}</span>
            </div>
            {{-- Labels bar (Trello style, under title) --}}
            <div class="card-body py-2 border-bottom" id="ticket-labels-bar">
                <div class="d-flex flex-wrap align-items-center">
                    <small class="text-muted font-weight-bold mr-2"><i class="fas fa-tags mr-1"></i>{{ __('tickets.labels') }}:</small>
                    <span id="ticket-labels-inline">
                        @foreach($ticket->labels as $label)
                            <span class="badge label-badge mr-1 mb-1" style="background-color:{{ $label->color }};color:#fff;" data-id="{{ $label->id }}">{{ $label->name }}</span>
                        @endforeach
                    </span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-link text-muted p-0 ml-1" type="button" id="labelDropdownBtn" data-toggle="dropdown" title="{{ __('tickets.add_label') }}">
                            @if($ticket->labels->isEmpty())
                                <i class="fas fa-plus mr-1"></i><small>{{ __('tickets.add_label') }}</small>
                            @else
                                <i class="fas fa-plus"></i>
                            @endif
                        </button>
                        <div class="dropdown-menu p-2 label-dropdown" style="width:260px;" aria-labelledby="labelDropdownBtn">
                            <input type="text" class="form-control form-control-sm mb-2" id="label-search" placeholder="{{ __('tickets.search_labels') }}">
                            <div id="label-options" style="max-height:150px;overflow-y:auto;"></div>
                            <hr class="my-2">
                            <div class="small font-weight-bold mb-1">{{ __('tickets.create_label') }}</div>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control form-control-sm mr-1" id="new-label-name" placeholder="{{ __('tickets.label_name_placeholder') }}" maxlength="50" style="flex:1;">
                                <input type="color" id="new-label-color" value="#3498db" class="mr-1" style="width:30px;height:30px;padding:0;border:1px solid #ced4da;border-radius:3px;cursor:pointer;">
                                <button type="button" class="btn btn-sm btn-success" id="btn-create-label"><i class="fas fa-check"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><small class="text-muted">{{ __('tickets.module') }}</small><br><strong>{{ ucfirst(str_replace('_',' ',$ticket->module)) }}</strong>@if($ticket->sub_module) <span class="text-muted">/ {{ $ticket->sub_module }}</span>@endif</div>
                    <div class="col-sm-4"><small class="text-muted">{{ __('tickets.type') }}</small><br><strong>{{ ucfirst(str_replace('_',' ',$ticket->ticket_type)) }}</strong></div>
                    <div class="col-sm-4"><small class="text-muted">{{ __('tickets.reporter') }}</small><br><strong>{{ $ticket->reporter_name }}</strong><br><small class="text-muted">{{ $ticket->reporter_email }}</small></div>
                </div>

                <h6>{{ __('tickets.description') }}</h6>
                @if($ticket->isInternal())
                    <div class="ticket-text-block position-relative" id="description-display">
                        <button type="button" class="btn btn-sm btn-link position-absolute" style="top:4px;right:4px;z-index:1;" id="btn-edit-description" title="Editar">
                            <i class="fas fa-pencil-alt text-muted"></i>
                        </button>
                        <div id="description-content" class="description-collapsed">{!! $ticket->description !!}</div>
                        <div class="text-center mt-1" id="description-toggle-wrap">
                            <button type="button" class="btn btn-sm btn-link" id="btn-toggle-description">
                                <i class="fas fa-chevron-down mr-1"></i>{{ __('tickets.show_more') }}
                            </button>
                        </div>
                    </div>
                    <div id="description-editor-wrap" style="display:none;">
                        <div id="description-editor">{!! $ticket->description !!}</div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-success" id="btn-save-description"><i class="fas fa-check mr-1"></i>Guardar</button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btn-cancel-description">Cancelar</button>
                        </div>
                    </div>
                @else
                    <div class="ticket-text-block">{!! nl2br(e($ticket->description)) !!}</div>
                @endif

                @if($ticket->steps_to_reproduce)
                    <h6 class="mt-3">{{ __('tickets.steps_to_reproduce') }}</h6>
                    <div class="ticket-text-block">{!! nl2br(e($ticket->steps_to_reproduce)) !!}</div>
                @endif

                @if($ticket->expected_behavior)
                    <h6 class="mt-3">{{ __('tickets.expected_behavior') }}</h6>
                    <div class="ticket-text-block">{!! nl2br(e($ticket->expected_behavior)) !!}</div>
                @endif

                @if($ticket->browser_url)
                    <h6 class="mt-3">{{ __('tickets.browser_url') }}</h6>
                    <code>{{ $ticket->browser_url }}</code>
                @endif

                {{-- Ticket attachments (client + dev, exclude sp_file) --}}
                @if($ticket->attachments->whereNotIn('source', ['sp_file'])->isNotEmpty())
                    <h6 class="mt-3">{{ __('tickets.attachments') }}</h6>
                    <div class="d-flex flex-wrap">
                        @foreach($ticket->attachments->whereNotIn('source', ['sp_file']) as $att)
                            <div class="attachment-thumb mr-2 mb-2">
                                @if($att->isImage())
                                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}">
                                        <img src="{{ asset('storage/' . $att->file_path) }}" alt="{{ $att->file_name }}">
                                    </a>
                                @elseif($att->isVideo())
                                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}" class="video-thumb">
                                        <i class="fas fa-play-circle"></i>
                                    </a>
                                @else
                                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" download="{{ $att->file_name }}" class="doc-thumb">
                                        <i class="fas fa-file"></i>
                                    </a>
                                @endif
                                <small class="d-block text-center text-muted">{{ Str::limit($att->file_name, 15) }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Comments tabs --}}
        <div class="card mb-3" id="comments-card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-internal" role="tab">
                            <i class="fas fa-lock mr-1"></i> {{ __('tickets.internal_notes') }}
                            <span class="badge badge-secondary ml-1">{{ $ticket->comments->where('visibility','internal')->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-client" role="tab">
                            <i class="fas fa-reply mr-1"></i> {{ __('tickets.client_response') }}
                            <span class="badge badge-secondary ml-1">{{ $ticket->comments->where('visibility','client')->count() }}</span>
                            @if($ticket->comments->where('visibility','client')->where('source','client')->count() > 0)
                                <span class="badge badge-warning ml-1" title="{{ __('tickets.client_replies_count', ['count' => $ticket->comments->where('visibility','client')->where('source','client')->count()]) }}">
                                    <i class="fas fa-user"></i> {{ $ticket->comments->where('visibility','client')->where('source','client')->count() }}
                                </span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    {{-- Internal notes tab --}}
                    <div class="tab-pane active" id="tab-internal" role="tabpanel">
                        <div id="comments-internal" class="comments-list">
                            @foreach($ticket->comments->where('visibility','internal') as $c)
                                @include('tickets._comment', ['comment' => $c])
                            @endforeach
                        </div>
                        <form id="form-internal" class="comment-form mt-3" data-visibility="internal">
                            <div class="summernote-editor" data-placeholder="{{ __('tickets.note_placeholder') }}"></div>
                            <div class="mt-2 text-right">
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> {{ __('tickets.add_note') }}</button>
                            </div>
                        </form>
                    </div>

                    {{-- Client response tab --}}
                    <div class="tab-pane" id="tab-client" role="tabpanel">
                        <div class="alert alert-info small mb-2"><i class="fas fa-info-circle mr-1"></i> {{ __('tickets.client_visible_msg') }}</div>
                        <div id="comments-client" class="comments-list">
                            @foreach($ticket->comments->where('visibility','client') as $c)
                                @include('tickets._comment', ['comment' => $c])
                            @endforeach
                        </div>
                        <form id="form-client" class="comment-form mt-3" data-visibility="client">
                            <div class="summernote-editor" data-placeholder="{{ __('tickets.response_placeholder') }}"></div>
                            <div class="mt-2 text-right">
                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-paper-plane mr-1"></i> {{ __('tickets.send_to_client') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dev fields --}}
        @php $advancedState = in_array($ticket->board_column, ['code_review', 'qa_testing', 'ready_for_release', 'done']); @endphp
        <div class="card mb-3 {{ $advancedState ? 'dev-notes-highlight' : '' }}" id="dev-notes-card">
            <div class="card-header {{ $advancedState ? 'bg-warning text-dark' : '' }}">
                <i class="fas fa-code mr-1"></i> {{ __('tickets.dev_notes') }}
                @if($advancedState)
                    <span class="badge badge-dark ml-2"><i class="fas fa-exclamation-circle mr-1"></i>Revisar antes de deploy</span>
                @endif
            </div>
            <div class="card-body">

                {{-- PR Links (dynamic) --}}
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold"><i class="fab fa-github mr-1"></i>{{ __('tickets.pr_links') }}</label>
                    <div id="pr-links-list">
                        @foreach($ticket->prLinks as $link)
                            <div class="dev-link-item d-flex align-items-center mb-1" data-id="{{ $link->id }}">
                                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="small text-truncate flex-grow-1">
                                    <i class="fas fa-code-branch mr-1"></i>{{ $link->title ?: $link->url }}
                                </a>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-pr" data-id="{{ $link->id }}" title="{{ __('app.delete') }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div id="pr-link-form" style="display:none;" class="mt-1">
                        <div class="input-group input-group-sm">
                            <input type="url" id="pr-link-url" class="form-control" placeholder="{{ __('tickets.pr_placeholder') }}">
                            <input type="text" id="pr-link-title" class="form-control" placeholder="{{ __('tickets.pr_title_placeholder') }}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success btn-sm" id="btn-save-pr"><i class="fas fa-check"></i></button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-pr"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn-add-pr">
                        <i class="fas fa-plus mr-1"></i>{{ __('tickets.add_pr_link') }}
                    </button>
                </div>

                {{-- Commits (dynamic) --}}
                <div class="mb-3">
                    <label class="small text-muted font-weight-bold"><i class="fas fa-code-branch mr-1"></i>{{ __('tickets.commits') }}</label>
                    <div id="commits-list">
                        @foreach($ticket->commits as $commit)
                            <div class="dev-link-item d-flex align-items-center mb-1" data-id="{{ $commit->id }}">
                                @if($commit->url)
                                    <a href="{{ $commit->url }}" target="_blank" rel="noopener" class="small text-truncate flex-grow-1">
                                        <code class="mr-1">{{ substr($commit->hash, 0, 7) }}</code>{{ $commit->message ?: '' }}
                                    </a>
                                @else
                                    <span class="small text-truncate flex-grow-1">
                                        <code class="mr-1">{{ substr($commit->hash, 0, 7) }}</code>{{ $commit->message ?: '' }}
                                    </span>
                                @endif
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-commit" data-id="{{ $commit->id }}" title="{{ __('app.delete') }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div id="commit-form" style="display:none;" class="mt-1">
                        <div id="commit-editor"></div>
                        <div class="d-flex align-items-center mt-1">
                            <button type="button" class="btn btn-success btn-sm" id="btn-save-commit"><i class="fas fa-check mr-1"></i>Guardar</button>
                            <button type="button" class="btn btn-secondary btn-sm ml-1" id="btn-cancel-commit"><i class="fas fa-times mr-1"></i>Cancelar</button>
                            <small class="text-muted ml-2">{{ __('tickets.commit_paste_hint') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn-add-commit">
                        <i class="fas fa-plus mr-1"></i>{{ __('tickets.add_commit') }}
                    </button>
                </div>

                <hr class="my-2">

                {{-- SP notes & Deploy notes (text fields, unchanged) --}}
                <form id="form-dev-fields">
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold"><i class="fas fa-database mr-1"></i>{{ __('tickets.sp_files_label') }}</label>
                        <div id="sp-files-list" class="mt-2">
                            @foreach($ticket->attachments->where('source', 'sp_file') as $spFile)
                                <div class="dev-link-item d-flex align-items-center mb-1" data-id="{{ $spFile->id }}">
                                    <a href="{{ Storage::disk('public')->url($spFile->file_path) }}" download="{{ $spFile->file_name }}" class="small text-truncate flex-grow-1">
                                        <i class="fas fa-database mr-1 text-info"></i>{{ $spFile->file_name }}
                                        <small class="text-muted ml-1">({{ number_format($spFile->file_size / 1024, 1) }} KB)</small>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2 btn-remove-sp-file" data-id="{{ $spFile->id }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <label class="btn btn-sm btn-outline-info mt-1 mb-0" id="sp-file-upload-label">
                            <i class="fas fa-paperclip mr-1"></i>{{ __('tickets.attach_sql') }}
                            <input type="file" id="sp-file-input" accept=".sql,.txt" style="display:none;">
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="small text-muted">{{ __('tickets.deploy_notes') }}</label>
                        <textarea name="deploy_notes" class="form-control form-control-sm" rows="2" placeholder="{{ __('tickets.deploy_placeholder') }}">{{ $ticket->deploy_notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-save mr-1"></i> {{ __('tickets.save_dev_notes') }}</button>
                    <span id="dev-fields-status" class="ml-2 small text-success" style="display:none;">{{ __('app.saved') }}</span>
                </form>
            </div>
        </div>
    </div>

    {{-- Side panel (right) --}}
    <div class="col-lg-4">
        {{-- Status & actions --}}
        <div class="card mb-3">
            <div class="card-body">
                @php
                    $colColors = ['to_do'=>'#3498db','in_progress'=>'#f39c12','blocked'=>'#e74c3c','code_review'=>'#9b59b6','qa_testing'=>'#1abc9c','ready_for_release'=>'#2ecc71','done'=>'#27ae60'];
                @endphp

                {{-- Board column selector --}}
                <div class="form-group mb-3">
                    <label class="small text-muted mb-1">{{ __('tickets.board_column') }}</label>
                    <select id="column-select" class="form-control form-control-sm">
                        @foreach(\App\Models\Ticket::BOARD_COLUMNS as $col)
                            <option value="{{ $col }}" {{ $ticket->board_column === $col ? 'selected' : '' }}
                                style="color: {{ $colColors[$col] ?? '#6c757d' }};">
                                {{ __('tickets.col_' . $col) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($ticket->board_column !== 'done')
                    @if($ticket->isInternal())
                        <button class="btn btn-success btn-block btn-sm mb-3" id="btn-mark-done-internal">
                            <i class="fas fa-check mr-1"></i> {{ __('tickets.mark_as_done') }}
                        </button>
                    @else
                        <button class="btn btn-success btn-block btn-sm mb-3" data-toggle="modal" data-target="#doneModal">
                            <i class="fas fa-check mr-1"></i> {{ __('tickets.mark_as_done') }}
                        </button>
                    @endif
                @endif

                {{-- Assignment --}}
                <div class="form-group mb-3">
                    <label class="small text-muted mb-1">{{ __('tickets.assigned_to') }}</label>
                    <select id="assign-select" class="form-control form-control-sm">
                        <option value="">{{ __('tickets.unassigned') }}</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ $ticket->assigned_to == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ strtoupper($m->role) }})
                            </option>
                        @endforeach
                    </select>
                    @if($suggested)
                        <small class="text-info"><i class="fas fa-magic mr-1"></i>{{ __('tickets.suggested', ['name' => $suggested->name, 'module' => $ticket->module]) }}</small>
                    @endif
                </div>

                <hr class="my-2">

                {{-- Archive: only for done + not archived --}}
                @if(!$ticket->archived_at && $ticket->board_column === 'done')
                    <button type="button" class="btn btn-outline-warning btn-block btn-sm" id="btn-archive">
                        <i class="fas fa-archive mr-1"></i> {{ __('tickets.archive_ticket') }}
                    </button>
                @endif

                {{-- Delete: only for archived tickets --}}
                @if($ticket->archived_at)
                    <div class="text-center mb-2">
                        <span class="badge badge-warning px-3 py-2"><i class="fas fa-archive mr-1"></i> {{ __('tickets.archived') }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-block btn-sm" id="btn-delete">
                        <i class="fas fa-trash mr-1"></i> {{ __('tickets.delete_ticket') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Client info (only for client tickets) --}}
        @if($ticket->client)
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-building mr-1"></i> {{ __('tickets.client') }}</div>
            <div class="card-body py-2">
                <strong>{{ $ticket->client->business_name }}</strong>
                <br><code class="small">{{ $ticket->client->client_identifier }}</code>
                @php $pColors = ['low'=>'secondary','medium'=>'info','high'=>'warning','vip'=>'danger']; @endphp
                <span class="badge badge-{{ $pColors[$ticket->client_priority] ?? 'secondary' }} ml-1">{{ strtoupper($ticket->client_priority) }}</span>
            </div>
        </div>
        @else
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-laptop-code mr-1"></i> {{ __('tickets.source_filter') }}</div>
            <div class="card-body py-2">
                <span class="badge badge-success"><i class="fas fa-laptop-code mr-1"></i>{{ __('tickets.internal_label') }}</span>
            </div>
        </div>
        @endif

        {{-- Priority info --}}
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-exclamation-triangle mr-1"></i> {{ __('tickets.priority') }}</div>
            <div class="card-body py-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">{{ __('tickets.issue') }}</span>
                    <span class="badge badge-priority-{{ $ticket->issue_priority }}">{{ strtoupper($ticket->issue_priority) }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">{{ __('tickets.client') }}</span>
                    <span class="badge badge-{{ $pColors[$ticket->client_priority] ?? 'secondary' }}">{{ strtoupper($ticket->client_priority) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">{{ __('tickets.effective') }}</span>
                    <strong>#{{ $ticket->effective_priority }}</strong>
                </div>
            </div>
        </div>

        {{-- AI Classification --}}
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-robot mr-1"></i> {{ __('tickets.ai_classification') }}</div>
            <div class="card-body py-2">
                @php
                    $incidentTypes = config('support.incident_types');
                    $aiStatusLabels = [
                        'pending'    => ['label' => __('tickets.ai_status_pending'),    'class' => 'secondary', 'icon' => 'fa-spinner fa-spin'],
                        'classified' => ['label' => __('tickets.ai_status_classified'), 'class' => 'success',   'icon' => 'fa-check'],
                        'failed'     => ['label' => __('tickets.ai_status_failed'),     'class' => 'danger',    'icon' => 'fa-exclamation-triangle'],
                        'manual'     => ['label' => __('tickets.ai_status_manual'),     'class' => 'info',      'icon' => 'fa-user'],
                    ];
                    $aiSt = $aiStatusLabels[$ticket->ai_status] ?? $aiStatusLabels['pending'];
                @endphp

                {{-- AI status --}}
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">{{ __('tickets.ai_status') }}</span>
                    <span class="badge badge-{{ $aiSt['class'] }}">
                        <i class="fas {{ $aiSt['icon'] }} mr-1"></i>{{ $aiSt['label'] }}
                    </span>
                </div>

                {{-- Incident type --}}
                @if($ticket->incident_type && isset($incidentTypes[$ticket->incident_type]))
                    @php $inc = $incidentTypes[$ticket->incident_type]; @endphp
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">{{ __('tickets.ai_type') }}</span>
                        <span class="badge badge-{{ $inc['badge'] }}">{{ __('reports.incident_' . $ticket->incident_type) }}</span>
                    </div>
                @endif

                {{-- AI suggestion (only when classified) --}}
                @if($ticket->ai_suggestion)
                    <div class="small mb-2">
                        <span class="text-muted d-block">{{ __('tickets.ai_suggestion') }}</span>
                        <em>{{ $ticket->ai_suggestion }}</em>
                    </div>
                @endif

                @if($ticket->ai_justification)
                    <div class="small mb-2">
                        <span class="text-muted d-block">{{ __('tickets.ai_justification') }}</span>
                        <em>{{ $ticket->ai_justification }}</em>
                    </div>
                @endif

                {{-- Override manual --}}
                <hr class="my-2">
                <div class="small text-muted mb-1">{{ __('tickets.ai_reclassify') }}</div>
                <div class="d-flex">
                    <select id="classify-override-select" class="form-control form-control-sm mr-2">
                        @foreach($incidentTypes as $key => $cfg)
                            <option value="{{ $key }}" {{ $ticket->incident_type === $key ? 'selected' : '' }}>
                                {{ __('reports.incident_' . $key) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-classify-override" title="{{ __('tickets.ai_save') }}">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
                <span id="classify-override-status" class="small text-success mt-1" style="display:none;">{{ __('tickets.ai_saved') }}</span>
            </div>
        </div>

        {{-- Due date (internal tickets only) --}}
        @if($ticket->isInternal())
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-calendar-alt mr-1"></i> {{ __('tickets.due_date') }}</div>
            <div class="card-body py-2">
                <div class="d-flex align-items-center">
                    <input type="date" id="due-date-input" class="form-control form-control-sm" value="{{ $ticket->due_date ? $ticket->due_date->format('Y-m-d') : '' }}" style="max-width:180px;">
                    <span id="due-date-status" class="ml-2 small text-success" style="display:none;">{{ __('app.saved') }}</span>
                    @if($ticket->due_date)
                        @if($ticket->isInAdvancedState())
                            <span class="badge badge-success ml-2" style="font-size:0.8rem;">✅ {{ $ticket->due_date->format('d M Y') }}</span>
                        @elseif($ticket->isOverdue())
                            <span class="badge badge-danger ml-2" style="font-size:0.8rem;"><i class="fas fa-exclamation-circle mr-1"></i>{{ __('tickets.overdue') }} — {{ $ticket->due_date->format('d M Y') }}</span>
                        @elseif($ticket->isDueSoon())
                            <span class="badge badge-warning ml-2" style="font-size:0.8rem;"><i class="fas fa-clock mr-1"></i>{{ __('tickets.due_today') }}</span>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Timestamps --}}
        <div class="card mb-3">
            <div class="card-header small"><i class="far fa-clock mr-1"></i> {{ __('tickets.timestamps') }}</div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">{{ __('tickets.created') }}</span><span>{{ $ticket->created_at->format('M d, Y H:i') }}</span></div>
                @if($ticket->resolved_at)
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">{{ __('tickets.resolved') }}</span><span>{{ $ticket->resolved_at->format('M d, Y H:i') }}</span></div>
                @endif
                @if($ticket->archived_at)
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">{{ __('tickets.archived') }}</span><span>{{ $ticket->archived_at->format('M d, Y H:i') }}</span></div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="card mb-3">
            <div class="card-header small"><i class="fas fa-history mr-1"></i> {{ __('tickets.timeline') }}</div>
            <div class="card-body py-2">
                <div class="timeline">
                    @foreach($ticket->statusHistory as $entry)
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background:{{ $colColors[$entry->new_column] ?? '#6c757d' }};"></div>
                            <div class="timeline-content">
                                <strong class="small">{{ $entry->new_column ? ucfirst(str_replace('_',' ',$entry->new_column)) : '' }}</strong>
                                @if($entry->notes) <span class="small text-muted d-block">{{ $entry->notes }}</span> @endif
                                <span class="small text-muted">
                                    {{ $entry->changedByMember ? $entry->changedByMember->name : __('tickets.system') }}
                                    · {{ $entry->created_at ? $entry->created_at->diffForHumans() : '' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Done modal --}}
<div class="modal fade" id="doneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('tickets.resolve_ticket', ['ticket' => $ticket->ticket_number]) }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('tickets.resolution_message') }}</label>
                    <textarea id="done-message" class="form-control" rows="3" placeholder="{{ __('tickets.resolve_placeholder') }}">{{ $ticket->resolution_message }}</textarea>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="done-generic">
                    <label class="custom-control-label" for="done-generic">{{ __('tickets.use_default_message') }}</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                <button type="button" class="btn btn-success" id="btn-mark-done"><i class="fas fa-check mr-1"></i> {{ __('tickets.mark_as_done') }}</button>
            </div>
        </div>
    </div>
</div>
{{-- Confirmation modal (reusable) --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <p id="confirm-action-text" class="mb-3"></p>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm mr-1" data-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirm-action-btn">{{ __('app.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Archive confirmation modal --}}
@if(!$ticket->archived_at && $ticket->board_column === 'done')
<div class="modal fade" id="archiveTicketModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-archive mr-1"></i> {{ __('tickets.archive_ticket') }}</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('tickets.archive_confirm') }}</p>
                <div class="form-group mb-1">
                    <label class="small">{{ __('tickets.archive_type_confirm') }}</label>
                    <input type="text" id="archive-confirm-input" class="form-control form-control-sm" autocomplete="off" placeholder="archivar">
                </div>
                <p id="archive-confirm-error" class="text-danger small mb-0" style="display:none;">{{ __('tickets.confirm_mismatch') }}</p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                <button type="button" class="btn btn-sm btn-warning" id="archive-confirm-btn" disabled>
                    <i class="fas fa-archive mr-1"></i> {{ __('tickets.archive_ticket') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Delete confirmation modal --}}
@if($ticket->archived_at)
<div class="modal fade" id="deleteTicketModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> {{ __('tickets.delete_ticket') }}</h6>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-danger small"><strong>{{ __('tickets.delete_warning') }}</strong></p>
                <div class="form-group mb-1">
                    <label class="small">{{ __('tickets.delete_type_confirm') }}</label>
                    <input type="text" id="delete-confirm-input" class="form-control form-control-sm" autocomplete="off" placeholder="eliminar">
                </div>
                <p id="delete-confirm-error" class="text-danger small mb-0" style="display:none;">{{ __('tickets.confirm_mismatch') }}</p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                <button type="button" class="btn btn-sm btn-danger" id="delete-confirm-btn" disabled>
                    <i class="fas fa-trash mr-1"></i> {{ __('tickets.delete_ticket') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Success toast --}}
<div id="action-toast" style="display:none;position:fixed;top:20px;right:20px;z-index:9999;background:#27ae60;color:#fff;padding:10px 20px;border-radius:6px;font-size:0.9rem;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-check-circle mr-1"></i> <span id="action-toast-text"></span>
</div>

@endsection

@push('scripts')
<script>
    window.ticketId = {{ $ticket->id }};
    window.i18n = {
        comment_failed: @json(__('tickets.comment_failed')),
        comment_empty: @json(__('tickets.comment_empty')),
        upload_failed: @json(__('tickets.upload_failed')),
        file_too_large: @json(__('tickets.file_too_large')),
        assign_failed: @json(__('tickets.assign_failed')),
        dev_notes_failed: @json(__('tickets.dev_notes_failed')),
        resolve_failed: @json(__('tickets.resolve_failed')),
        move_done_failed: @json(__('tickets.move_done_failed')),
        default_resolution: @json(__('tickets.default_resolution')),
        client_label: @json(__('tickets.client_label')),
        move_column_failed: @json(__('tickets.move_column_failed')),
        archive_confirm: @json(__('tickets.archive_confirm')),
        archive_type_confirm: @json(__('tickets.archive_type_confirm')),
        delete_type_confirm: @json(__('tickets.delete_type_confirm')),
        archive_requires_done: @json(__('tickets.archive_requires_done')),
        delete_confirm: @json(__('tickets.delete_confirm')),
        archive_failed: @json(__('tickets.archive_failed')),
        delete_failed: @json(__('tickets.delete_failed')),
        archive_ticket: @json(__('tickets.archive_ticket')),
        delete_ticket: @json(__('tickets.delete_ticket')),
        confirm_move_column: @json(__('tickets.confirm_move_column')),
        confirm_assign: @json(__('tickets.confirm_assign')),
        column_moved: @json(__('tickets.column_moved')),
        assign_success: @json(__('tickets.assign_success')),
        no_labels: @json(__('tickets.no_labels')),
        label_exists: @json(__('tickets.label_exists')),
        add_label: @json(__('tickets.add_label')),
        show_more: @json(__('tickets.show_more')),
        show_less: @json(__('tickets.show_less')),
        commit_paste_placeholder: @json(__('tickets.commit_paste_placeholder')),
        delete_comment_confirm: @json(__('tickets.delete_comment_confirm'))
    };
    window.ticketLabels = @json($ticket->labels->map(function($l) { return ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]; }));
    @if($advancedState)
    // Move dev notes before comments in advanced states
    $(function() { $('#dev-notes-card').insertBefore('#comments-card'); });
    @endif
</script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script src="{{ asset('js/ticket-detail.js') }}"></script>
<script>
$(document).ready(function () {
    var classifyUrl = '{{ route('tickets.classify-override', $ticket) }}';

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
@endpush
