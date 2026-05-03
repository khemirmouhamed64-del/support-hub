@extends('layouts.app')

@section('page-title', __('auth.my_profile'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            {{-- Profile info --}}
            <div class="card mb-3">
                <div class="card-header"><strong>{{ __('auth.profile_info') }}</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">{{ __('auth.name') }}</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $member->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">{{ __('auth.email') }}</label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $member->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('team.role') }}</label>
                            <input type="text" class="form-control bg-light" value="{{ strtoupper($member->role) }}" readonly>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> {{ __('auth.update_profile') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Change password --}}
            <div class="card">
                <div class="card-header"><strong>{{ __('auth.change_password') }}</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="current_password">{{ __('auth.current_password') }}</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">{{ __('auth.new_password') }}</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">{{ __('auth.confirm_new_password') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key mr-1"></i> {{ __('auth.change_password') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
