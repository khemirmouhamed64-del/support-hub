@extends('layouts.app')

@section('page-title', $member->exists ? __('team.edit_member') : __('team.add_member'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $member->exists ? route('team.update', $member) : route('team.store') }}">
                        @csrf
                        @if($member->exists) @method('PUT') @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('team.full_name') }}</label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $member->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">{{ __('team.email') }}</label>
                                    <input type="email" name="email" id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $member->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @if($member->exists)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">{{ __('team.password') }} <small class="text-muted">({{ __('team.password_keep') }})</small></label>
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @else
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('team.password') }}</label>
                                    <div class="form-control-plaintext text-muted">
                                        <i class="fas fa-key mr-1"></i> {{ __('team.password_auto_generated') }}
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">{{ __('team.role') }}</label>
                                    <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                                        @foreach(['dev' => __('team.role_dev'), 'qa' => __('team.role_qa'), 'lead' => __('team.role_lead'), 'pm' => __('team.role_pm')] as $val => $label)
                                            <option value="{{ $val }}" {{ old('role', $member->role ?? 'dev') === $val ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ __('team.module_expertise') }}</label>
                            <div class="row">
                                @php
                                    $memberModules = $member->exists ? $member->moduleExpertise->pluck('module_name')->toArray() : (old('expertise') ?? []);
                                    $primaryModule = $member->exists ? optional($member->moduleExpertise->where('is_primary', true)->first())->module_name : old('primary_module');
                                @endphp
                                @foreach($modules as $mod)
                                    <div class="col-md-4 col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="exp_{{ $mod }}"
                                                   name="expertise[]" value="{{ $mod }}"
                                                   {{ in_array($mod, $memberModules) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="exp_{{ $mod }}">{{ ucfirst(str_replace('_', ' ', $mod)) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="primary_module">{{ __('team.primary_module') }} <small class="text-muted">({{ __('team.main_expertise') }})</small></label>
                            <select name="primary_module" id="primary_module" class="form-control">
                                <option value="">— {{ __('team.none') }} —</option>
                                @foreach($modules as $mod)
                                    <option value="{{ $mod }}" {{ $primaryModule === $mod ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $mod)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('team.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> {{ __('app.back') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> {{ $member->exists ? __('team.update_member') : __('team.create_member') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
