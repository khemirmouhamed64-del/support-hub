@extends('layouts.app')

@section('page-title', __('team.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted">{{ __('team.members_count', ['count' => $members->count()]) }}</span>
        <a href="{{ route('team.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> {{ __('team.add_member') }}
        </a>
    </div>

    <div class="row">
        @forelse($members as $member)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 {{ $member->is_active ? '' : 'border-secondary' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-0 {{ $member->is_active ? '' : 'text-muted' }}">
                                    {{ $member->name }}
                                </h5>
                                <small class="text-muted">{{ $member->email }}</small>
                            </div>
                            @php
                                $roleColors = ['dev' => 'primary', 'qa' => 'info', 'lead' => 'warning', 'pm' => 'danger'];
                            @endphp
                            <span class="badge badge-{{ $roleColors[$member->role] ?? 'secondary' }}">
                                {{ strtoupper($member->role) }}
                            </span>
                        </div>

                        @if($member->moduleExpertise->isNotEmpty())
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">{{ __('team.expertise') }}:</small>
                                @foreach($member->moduleExpertise as $exp)
                                    <span class="badge badge-{{ $exp->is_primary ? 'dark' : 'light' }} border mr-1">
                                        {{ $exp->module_name }}
                                        @if($exp->is_primary) <i class="fas fa-star text-warning" style="font-size: 0.6rem;"></i> @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-ticket-alt mr-1"></i> {{ $member->assigned_tickets_count }} {{ __('team.tickets') }}
                            </small>
                            <div>
                                <a href="{{ route('team.edit', $member) }}" class="btn btn-sm btn-outline-primary" title="{{ __('app.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('team.toggle-active', $member) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $member->is_active ? 'warning' : 'success' }}"
                                            title="{{ $member->is_active ? __('app.deactivate') : __('app.activate') }}">
                                        <i class="fas fa-{{ $member->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if(!$member->is_active)
                            <div class="mt-2"><span class="badge badge-secondary">{{ __('app.inactive') }}</span></div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">{{ __('team.no_members') }}</div>
            </div>
        @endforelse
    </div>
    @if(session('generated_password'))
    <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-key mr-1"></i> {{ __('team.credentials_title') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ __('team.credentials_for', ['name' => session('generated_for')]) }}</p>
                    <div class="form-group">
                        <label class="small text-muted mb-1">{{ __('team.temporary_password') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-weight-bold" id="generatedPassword"
                                   value="{{ session('generated_password') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copyPasswordBtn"
                                        title="{{ __('team.copy_password') }}">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('team.password_warning') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('app.close') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@if(session('generated_password'))
@push('scripts')
<script>
$(function() {
    $('#passwordModal').modal('show');
    $('#copyPasswordBtn').on('click', function() {
        var input = document.getElementById('generatedPassword');
        input.select();
        document.execCommand('copy');
        var btn = $(this);
        btn.html('<i class="fas fa-check"></i>');
        setTimeout(function() { btn.html('<i class="fas fa-copy"></i>'); }, 2000);
    });
});
</script>
@endpush
@endif
