@extends('layouts.app')

@section('page-title', $client->exists ? __('clients.edit_client') : __('clients.add_client'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
                        @csrf
                        @if($client->exists) @method('PUT') @endif

                        <div class="form-group">
                            <label for="client_identifier">{{ __('clients.identifier') }} <small class="text-muted">{{ __('clients.identifier_note') }}</small></label>
                            <input type="text" name="client_identifier" id="client_identifier"
                                   class="form-control @error('client_identifier') is-invalid @enderror"
                                   value="{{ old('client_identifier', $client->client_identifier) }}" required
                                   placeholder="{{ __('clients.identifier_placeholder') }}">
                            @error('client_identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="business_name">{{ __('clients.business_name') }}</label>
                            <input type="text" name="business_name" id="business_name"
                                   class="form-control @error('business_name') is-invalid @enderror"
                                   value="{{ old('business_name', $client->business_name) }}" required
                                   placeholder="{{ __('clients.name_placeholder') }}">
                            @error('business_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="api_callback_url">{{ __('clients.callback_url') }} <small class="text-muted">{{ __('clients.callback_note') }}</small></label>
                            <input type="url" name="api_callback_url" id="api_callback_url"
                                   class="form-control @error('api_callback_url') is-invalid @enderror"
                                   value="{{ old('api_callback_url', $client->api_callback_url) }}"
                                   placeholder="{{ __('clients.callback_placeholder') }}">
                            @error('api_callback_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="priority_level">{{ __('clients.priority_level') }}</label>
                            <select name="priority_level" id="priority_level" class="form-control @error('priority_level') is-invalid @enderror" required>
                                @foreach(['low' => __('clients.priority_low'), 'medium' => __('clients.priority_medium'), 'high' => __('clients.priority_high'), 'vip' => __('clients.priority_vip')] as $val => $label)
                                    <option value="{{ $val }}" {{ old('priority_level', $client->priority_level ?? 'medium') === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('priority_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($client->exists)
                            <div class="form-group">
                                <label>{{ __('clients.api_key') }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" value="{{ $client->api_key }}" readonly id="apiKeyField">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="copyApiKey()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">{{ __('clients.key_help') }}</small>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('clients.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> {{ __('app.back') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> {{ $client->exists ? __('clients.update_client') : __('clients.create_client') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@if($client->exists)
@push('scripts')
<script>
function copyApiKey() {
    var field = document.getElementById('apiKeyField');
    field.select();
    document.execCommand('copy');
    alert('{{ __('clients.key_copied') }}');
}
</script>
@endpush
@endif
