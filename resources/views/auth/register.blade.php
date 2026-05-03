@extends('layouts.guest')

@section('title', __('auth.create_account'))

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name">{{ __('auth.full_name') }}</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus placeholder="{{ __('auth.name_placeholder') }}">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">{{ __('auth.email') }}</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required placeholder="{{ __('auth.email_placeholder') }}">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">{{ __('auth.password_new') }}</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                   required placeholder="{{ __('auth.password_min') }}">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">{{ __('auth.confirm_password') }}</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                   required placeholder="{{ __('auth.repeat_password') }}">
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-user-plus mr-1"></i> {{ __('auth.create_account') }}
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-muted small">{{ __('auth.login_link') }}</a>
        </div>
    </form>
@endsection
