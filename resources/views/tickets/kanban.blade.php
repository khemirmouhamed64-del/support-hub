@extends('layouts.app')

@section('page-title', __('tickets.kanban_board'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kanban.css') }}">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
@endpush

@section('content')
    {{-- Filters --}}
    <div class="kanban-filters mb-3">
        <div class="d-flex flex-wrap align-items-center">
            <select id="filter-client" class="form-control form-control-sm mr-2 mb-1" style="width: 160px;">
                <option value="">{{ __('tickets.all_clients') }}</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->business_name }}</option>
                @endforeach
            </select>
            <select id="filter-assignee" class="form-control form-control-sm mr-2 mb-1" style="width: 160px;">
                <option value="">{{ __('tickets.all_members') }}</option>
                @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
            <select id="filter-priority" class="form-control form-control-sm mr-2 mb-1" style="width: 160px;">
                <option value="">{{ __('tickets.all_incident_types') }}</option>
                @foreach(config('support.incident_types') as $key => $type)
                    <option value="{{ $key }}">{{ __('reports.incident_' . $key) }}</option>
                @endforeach
            </select>
            <select id="filter-module" class="form-control form-control-sm mr-2 mb-1" style="width: 150px;">
                <option value="">{{ __('tickets.all_modules') }}</option>
                @foreach(['ventas','pos','compras','inventario','contabilidad','bancos','rrhh','crm','activo_fijo','reportes','configuracion'] as $mod)
                    <option value="{{ $mod }}">{{ ucfirst(str_replace('_',' ',$mod)) }}</option>
                @endforeach
            </select>
            <select id="filter-source" class="form-control form-control-sm mr-2 mb-1" style="width: 130px;">
                <option value="">{{ __('tickets.all_sources') }}</option>
                <option value="client">{{ __('tickets.source_client') }}</option>
                <option value="internal">{{ __('tickets.source_internal') }}</option>
            </select>
            <button id="btn-clear-filters" class="btn btn-sm btn-outline-secondary mb-1" title="{{ __('tickets.clear_filters') }}">
                <i class="fas fa-times"></i>
            </button>
            <div class="ml-auto mb-1 d-flex align-items-center">
                <span id="ticket-count" class="text-muted small mr-2"></span>
                <button type="button" class="btn btn-sm btn-success mr-2" data-toggle="modal" data-target="#createInternalModal">
                    <i class="fas fa-plus mr-1"></i>{{ __('tickets.new_internal_ticket') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#priorityGuideModal" title="{{ __('tickets.priority_guide') }}">
                    <i class="fas fa-question-circle mr-1"></i>{{ __('tickets.priority_guide') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Board --}}
    <div class="kanban-board" id="kanban-board">
        @php
            $columns = [
                'to_do'             => ['label' => __('tickets.col_to_do'),             'color' => '#3498db'],
                'in_progress'       => ['label' => __('tickets.col_in_progress'),       'color' => '#f39c12'],
                'blocked'           => ['label' => __('tickets.col_blocked'),            'color' => '#e74c3c'],
                'code_review'       => ['label' => __('tickets.col_code_review'),        'color' => '#9b59b6'],
                'qa_testing'        => ['label' => __('tickets.col_qa_testing'),         'color' => '#1abc9c'],
                'ready_for_release' => ['label' => __('tickets.col_ready_for_release'),  'color' => '#2ecc71'],
                'done'              => ['label' => __('tickets.col_done'),               'color' => '#27ae60'],
            ];
        @endphp

        @foreach($columns as $key => $col)
            <div class="kanban-column" data-column="{{ $key }}">
                <div class="kanban-column-header" style="border-top: 3px solid {{ $col['color'] }};">
                    <span class="kanban-column-title">{{ $col['label'] }}</span>
                    <span class="kanban-column-count badge badge-light" id="count-{{ $key }}">0</span>
                </div>
                <div class="kanban-column-body" id="column-{{ $key }}">
                    {{-- Cards injected via JS --}}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Priority guide modal --}}
    <div class="modal fade" id="priorityGuideModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:#1a2035; color:#fff;">
                    <h5 class="modal-title">
                        <i class="fas fa-map-signs mr-2"></i>{{ __('tickets.pg_title') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="font-size:0.9rem;">

                    {{-- Step 1: effective priority number --}}
                    <h6 class="font-weight-bold mb-2">
                        <span class="badge badge-dark mr-1">1</span>
                        {!! __('tickets.pg_step1_heading') !!}
                    </h6>
                    <p class="text-muted mb-1">{!! __('tickets.pg_step1_desc') !!}</p>
                    <p class="mb-0">{{ __('tickets.pg_step1_formula_label') }}</p>
                    <div class="alert alert-secondary py-2 px-3 mb-3" style="font-family:monospace; font-size:0.88rem;">
                        {{ __('tickets.pg_step1_formula') }}
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="font-weight-bold mb-1">{{ __('tickets.pg_client_weight') }}</p>
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light"><tr><th>{{ __('tickets.pg_type') }}</th><th>{{ __('tickets.pg_weight') }}</th></tr></thead>
                                <tbody>
                                    <tr><td><span class="badge badge-danger">VIP</span></td><td><strong>1</strong> {{ __('tickets.pg_most_urgent') }}</td></tr>
                                    <tr><td><span class="badge badge-warning text-dark">High</span></td><td>2</td></tr>
                                    <tr><td><span class="badge badge-info">Medium</span></td><td>3</td></tr>
                                    <tr><td><span class="badge badge-secondary">Low</span></td><td>4</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <p class="font-weight-bold mb-1">{{ __('tickets.pg_incident_weight') }}</p>
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light"><tr><th>{{ __('tickets.pg_type') }}</th><th>{{ __('tickets.pg_weight') }}</th></tr></thead>
                                <tbody>
                                    <tr><td><span class="badge badge-danger">{{ __('reports.incident_operacion_bloqueada') }}</span></td><td><strong>1</strong></td></tr>
                                    <tr><td><span class="badge badge-warning text-dark">{{ __('reports.incident_funcionalidad_critica') }}</span></td><td>2</td></tr>
                                    <tr><td><span class="badge badge-info">{{ __('reports.incident_funcionalidad_menor') }}</span></td><td>3</td></tr>
                                    <tr><td><span class="badge badge-secondary">{{ __('reports.incident_configuracion') }}</span></td><td>4</td></tr>
                                    <tr><td><span class="badge badge-secondary">{{ __('reports.incident_consulta') }}</span></td><td>5</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.85rem;">
                        <strong>{{ __('tickets.pg_example_heading') }}</strong> {!! __('tickets.pg_example_text') !!}
                    </div>

                    <hr>

                    {{-- Step 2: incident types --}}
                    <h6 class="font-weight-bold mb-2">
                        <span class="badge badge-dark mr-1">2</span>
                        {{ __('tickets.pg_step2_heading') }}
                    </h6>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="thead-light">
                            <tr><th>{{ __('tickets.pg_badge') }}</th><th>{{ __('tickets.pg_when') }}</th><th>{{ __('tickets.pg_examples') }}</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-danger">{{ __('reports.incident_operacion_bloqueada') }}</span></td>
                                <td>{{ __('tickets.pg_blocked_when') }}</td>
                                <td>{{ __('tickets.pg_blocked_ex') }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning text-dark">{{ __('reports.incident_funcionalidad_critica') }}</span></td>
                                <td>{{ __('tickets.pg_critical_when') }}</td>
                                <td>{{ __('tickets.pg_critical_ex') }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info">{{ __('reports.incident_funcionalidad_menor') }}</span></td>
                                <td>{{ __('tickets.pg_minor_when') }}</td>
                                <td>{{ __('tickets.pg_minor_ex') }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">{{ __('reports.incident_configuracion') }}</span></td>
                                <td>{{ __('tickets.pg_config_when') }}</td>
                                <td>{{ __('tickets.pg_config_ex') }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-secondary">{{ __('reports.incident_consulta') }}</span></td>
                                <td>{{ __('tickets.pg_inquiry_when') }}</td>
                                <td>{{ __('tickets.pg_inquiry_ex') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr>

                    {{-- Step 3: AI failed --}}
                    <h6 class="font-weight-bold mb-2">
                        <span class="badge badge-dark mr-1">3</span>
                        {{ __('tickets.pg_step3_heading') }}
                    </h6>
                    <p class="mb-1">{!! __('tickets.pg_step3_desc') !!}</p>
                    <ol class="mb-0" style="padding-left:1.2rem;">
                        <li>{!! __('tickets.pg_step3_li1') !!}</li>
                        <li>{!! __('tickets.pg_step3_li2') !!}</li>
                        <li>{!! __('tickets.pg_step3_li3') !!}</li>
                        <li>{!! __('tickets.pg_step3_li4') !!}</li>
                    </ol>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('tickets.pg_close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ERP connection error modal --}}
    <div class="modal fade" id="erpErrorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title erp-error-title"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle text-danger mr-3 mt-1" style="font-size:1.4rem;"></i>
                        <p class="erp-error-message mb-0"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('app.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Done message modal --}}
    <div class="modal fade" id="doneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('tickets.resolve_ticket') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('tickets.resolve_note') }}</p>
                    <input type="hidden" id="done-ticket-id">
                    <div class="form-group">
                        <label>{{ __('tickets.resolution_message') }}</label>
                        <textarea id="done-message" class="form-control" rows="3" placeholder="{{ __('tickets.resolution_placeholder') }}"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="done-generic" checked>
                        <label class="custom-control-label" for="done-generic">{{ __('tickets.use_default_message') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="button" class="btn btn-success" id="btn-confirm-done">
                        <i class="fas fa-check mr-1"></i> {{ __('tickets.mark_as_done') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- Create internal ticket modal --}}
    <div class="modal fade" id="createInternalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:#1a2035; color:#fff;">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>{{ __('tickets.create_internal_ticket') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="internal-ticket-errors" class="alert alert-danger" style="display:none;"></div>
                    <form id="form-create-internal">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>{{ __('tickets.subject') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" class="form-control" placeholder="{{ __('tickets.subject_placeholder') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.type') }} <span class="text-danger">*</span></label>
                                    <select name="ticket_type" class="form-control" required>
                                        <option value="">{{ __('tickets.select_type') }}</option>
                                        <option value="bug">{{ __('tickets.ticket_type_bug') }}</option>
                                        <option value="feature_request">{{ __('tickets.ticket_type_feature') }}</option>
                                        <option value="task">{{ __('tickets.ticket_type_task') }}</option>
                                        <option value="configuration">{{ __('tickets.ticket_type_config') }}</option>
                                        <option value="question">{{ __('tickets.ticket_type_question') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.module') }}</label>
                                    <select name="module" class="form-control">
                                        <option value="">{{ __('tickets.all_modules') }}</option>
                                        @foreach($modules as $mod)
                                            <option value="{{ $mod }}">{{ ucfirst(str_replace('_',' ',$mod)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.priority') }} <span class="text-danger">*</span></label>
                                    <select name="issue_priority" class="form-control" required>
                                        <option value="">{{ __('tickets.select_priority') }}</option>
                                        <option value="critical">{{ __('tickets.priority_critical') }}</option>
                                        <option value="high">{{ __('tickets.priority_high') }}</option>
                                        <option value="medium" selected>{{ __('tickets.priority_medium') }}</option>
                                        <option value="low">{{ __('tickets.priority_low') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.assigned_to') }}</label>
                                    <select name="assigned_to" class="form-control">
                                        <option value="">{{ __('tickets.unassigned') }}</option>
                                        @foreach($members as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('tickets.description') }} <span class="text-danger">*</span></label>
                            <div id="modal-description-editor"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.due_date') }}</label>
                                    <input type="date" name="due_date" class="form-control" min="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.board_column') }}</label>
                                    <select name="board_column" class="form-control">
                                        @foreach($columns as $key => $col)
                                            <option value="{{ $key }}" {{ $key === 'to_do' ? 'selected' : '' }}>{{ $col['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('tickets.labels') }}</label>
                                    <div id="modal-labels-selected" class="d-flex flex-wrap mb-1"></div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="modal-label-dropdown" data-toggle="dropdown">
                                            <i class="fas fa-tags mr-1"></i>{{ __('tickets.add_label') }}
                                        </button>
                                        <div class="dropdown-menu p-2 label-dropdown" style="width:240px;" id="modal-label-menu">
                                            <input type="text" class="form-control form-control-sm mb-2" id="modal-label-search" placeholder="{{ __('tickets.search_labels') }}">
                                            <div id="modal-label-options" style="max-height:120px;overflow-y:auto;"></div>
                                            <hr class="my-1">
                                            <div class="d-flex align-items-center">
                                                <input type="text" class="form-control form-control-sm mr-1" id="modal-new-label-name" placeholder="{{ __('tickets.label_name_placeholder') }}" maxlength="50" style="flex:1;">
                                                <input type="color" id="modal-new-label-color" value="#3498db" class="mr-1" style="width:26px;height:26px;padding:0;border:1px solid #ced4da;border-radius:3px;cursor:pointer;">
                                                <button type="button" class="btn btn-sm btn-success" id="modal-btn-create-label"><i class="fas fa-check"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="label_ids" id="modal-label-ids" value="">
                                </div>
                            </div>
                        </div>
                        {{-- Attachments --}}
                        <div class="form-group">
                            <label>{{ __('tickets.attachments') }}</label>
                            <div id="modal-attachments-list" class="mb-2"></div>
                            <div class="d-flex align-items-center">
                                <label class="btn btn-sm btn-outline-secondary mb-0 mr-2">
                                    <i class="fas fa-paperclip mr-1"></i>{{ __('tickets.attach_files') }}
                                    <input type="file" id="modal-file-input" multiple style="display:none;">
                                </label>
                                <div id="modal-paste-zone" class="form-control form-control-sm text-muted flex-grow-1" contenteditable="true" style="min-height:32px;max-height:32px;overflow:hidden;font-size:0.8rem;" data-placeholder="{{ __('tickets.paste_image_hint') }}"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="button" class="btn btn-success" id="btn-create-internal">
                        <i class="fas fa-check mr-1"></i>{{ __('tickets.create_internal_ticket') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window.incidentTypes = @json(config('support.incident_types'));
    window.i18n = {
        no_tickets_col: @json(__('tickets.no_tickets_col')),
        move_failed: @json(__('tickets.move_failed')),
        resolve_save_failed: @json(__('tickets.resolve_save_failed')),
        default_resolution: @json(__('tickets.default_resolution')),
        unassigned: @json(__('tickets.unassigned_label')),
        effective_priority: @json(__('tickets.effective_priority')),
        ticket_word: @json(__('tickets.ticket_word')),
        tickets_word: @json(__('tickets.tickets_word')),
        unread_client_replies: @json(__('tickets.unread_client_replies')),
        erp_connection_error: @json(__('tickets.erp_connection_error')),
        erp_move_blocked: @json(__('tickets.erp_move_blocked')),
        internal_label: @json(__('tickets.internal_label')),
        internal_ticket_created: @json(__('tickets.internal_ticket_created')),
        description_placeholder: @json(__('tickets.description_placeholder')),
        description_required: @json(__('tickets.description_required')),
        due_today: @json(__('tickets.due_today')),
        overdue: @json(__('tickets.overdue'))
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script src="{{ asset('js/kanban.js') }}"></script>
@endpush
