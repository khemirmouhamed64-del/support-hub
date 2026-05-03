@extends('layouts.app')

@section('page-title', __('clients.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted">{{ __('clients.clients_registered', ['count' => $clients->count()]) }}</span>
        <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> {{ __('clients.add_client') }}
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ __('clients.client') }}</th>
                        <th>{{ __('clients.identifier') }}</th>
                        <th>{{ __('clients.priority_level') }}</th>
                        <th>{{ __('clients.api_key') }}</th>
                        <th>{{ __('clients.callback_url') }}</th>
                        <th class="text-center">{{ __('clients.tickets') }}</th>
                        <th class="text-center">{{ __('app.status') }}</th>
                        <th class="text-right">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr class="{{ $client->is_active ? '' : 'text-muted' }}">
                            <td><strong>{{ $client->business_name }}</strong></td>
                            <td><code>{{ $client->client_identifier }}</code></td>
                            <td>
                                @php
                                    $colors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'vip' => 'danger'];
                                @endphp
                                <span class="badge badge-{{ $colors[$client->priority_level] ?? 'secondary' }}">
                                    {{ strtoupper($client->priority_level) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <code class="small" style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="{{ $client->api_key }}">
                                        {{ $client->api_key }}
                                    </code>
                                    {{-- Copy API key --}}
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm py-0 px-1 ml-1 btn-copy-key"
                                            data-key="{{ $client->api_key }}"
                                            title="{{ __('clients.key_copied') }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    {{-- Regenerate key --}}
                                    <form method="POST" action="{{ route('clients.regenerate-key', $client) }}" class="ml-1" onsubmit="return confirm('{{ __('clients.regenerate_confirm') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-1" title="{{ __('clients.regenerate_key') }}">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="small">{{ $client->api_callback_url ?: '—' }}</td>
                            <td class="text-center">{{ $client->tickets_count }}</td>
                            <td class="text-center">
                                @if($client->is_active)
                                    <span class="badge badge-success">{{ __('app.active') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('app.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                {{-- Test connection --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline-info btn-test-conn"
                                        data-url="{{ route('clients.test-connection', $client) }}"
                                        data-client="{{ $client->business_name }}"
                                        title="{{ __('clients.test_connection') }}">
                                    <i class="fas fa-plug"></i>
                                </button>
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary" title="{{ __('app.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('clients.toggle-active', $client) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $client->is_active ? 'warning' : 'success' }}" title="{{ $client->is_active ? __('app.deactivate') : __('app.activate') }}">
                                        <i class="fas fa-{{ $client->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">{{ __('clients.no_clients') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Test connection result modal --}}
    <div class="modal fade" id="testConnModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header py-2" id="testConnModalHeader">
                    <h6 class="modal-title mb-0" id="testConnModalTitle">{{ __('clients.test_connection') }}</h6>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center py-4" id="testConnModalBody">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Copy toast --}}
    <div id="copy-toast" style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999;"
         class="alert alert-success py-2 px-3 shadow">
        <i class="fas fa-check mr-1"></i> {{ __('clients.key_copied') }}
    </div>
@endsection

@push('scripts')
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
    $title.text('{{ __("clients.test_connection") }} — ' + clientName);
    $body.html('<i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted small">{{ __("clients.testing") }}</p>');
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
                    '<p class="mb-0 font-weight-bold">{{ __("clients.test_ok") }}</p>'
                );
            } else {
                $header.addClass('bg-danger text-white');
                $body.html(
                    '<i class="fas fa-times-circle fa-2x text-white mb-2"></i>' +
                    '<p class="mb-1 font-weight-bold">{{ __("clients.test_unreachable") }}</p>' +
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
@endpush
