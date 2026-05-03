@extends('layouts.app')

@section('page-title', __('tickets.all_tickets'))

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('tickets.tickets_count', ['count' => $tickets->total()]) }}</span>
            <div>
                @php $current = request('status'); @endphp
                <a href="{{ route('tickets.index') }}" class="btn btn-sm {{ !$current ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('tickets.filter_all') }}</a>
                @foreach(['to_do'=>__('tickets.col_to_do'),'in_progress'=>__('tickets.col_in_progress'),'blocked'=>__('tickets.col_blocked'),'done'=>__('tickets.col_done')] as $key => $label)
                    <a href="{{ route('tickets.index', ['status' => $key]) }}"
                       class="btn btn-sm {{ $current === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('tickets.subject') }}</th>
                        <th>{{ __('tickets.client') }}</th>
                        <th>{{ __('tickets.module') }}</th>
                        <th>{{ __('tickets.priority') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('tickets.assigned_to') }}</th>
                        <th>{{ __('tickets.created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr style="cursor:pointer;" onclick="window.location='/tickets/{{ $ticket->id }}'">
                            <td><strong>{{ $ticket->ticket_number }}</strong></td>
                            <td>{{ Str::limit($ticket->subject, 50) }}</td>
                            <td class="small">{{ $ticket->client ? $ticket->client->business_name : __('tickets.internal_label') }}</td>
                            <td class="small">{{ $ticket->module }}</td>
                            <td>
                                <span class="badge badge-priority-{{ $ticket->issue_priority }}">{{ strtoupper($ticket->issue_priority) }}</span>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $ticket->boardColumnLabel() }}</span>
                            </td>
                            <td class="small">{{ $ticket->assignee ? $ticket->assignee->name : '—' }}</td>
                            <td class="small text-muted">{{ $ticket->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">{{ __('tickets.no_tickets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
<style>
    .badge-priority-critical { background: #e74c3c; color: #fff; }
    .badge-priority-high     { background: #f39c12; color: #fff; }
    .badge-priority-medium   { background: #3498db; color: #fff; }
    .badge-priority-low      { background: #95a5a6; color: #fff; }
</style>
@endpush
