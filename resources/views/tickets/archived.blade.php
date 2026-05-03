@extends('layouts.app')

@section('page-title', __('tickets.archived_tickets'))

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-archive mr-1"></i> {{ __('tickets.archived_tickets_count', ['count' => $tickets->total()]) }}</span>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-columns mr-1"></i> {{ __('app.nav_kanban') }}
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('tickets.subject') }}</th>
                        <th>{{ __('tickets.client') }}</th>
                        <th>{{ __('tickets.module') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('tickets.archived') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr style="cursor:pointer;" onclick="window.location='/tickets/{{ $ticket->id }}'">
                            <td><strong>{{ $ticket->ticket_number }}</strong></td>
                            <td>{{ Str::limit($ticket->subject, 50) }}</td>
                            <td class="small">{{ $ticket->client->business_name }}</td>
                            <td class="small">{{ $ticket->module }}</td>
                            <td><span class="badge badge-secondary">{{ $ticket->boardColumnLabel() }}</span></td>
                            <td class="small text-muted">{{ $ticket->archived_at ? $ticket->archived_at->format('M d, Y H:i') : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('tickets.no_archived_tickets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection
