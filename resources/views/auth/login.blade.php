@extends('layouts.guest')

@section('title', __('auth.sign_in'))

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">{{ __('auth.email') }}</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus placeholder="{{ __('auth.email_placeholder') }}">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">{{ __('auth.password_label') }}</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                   required placeholder="{{ __('auth.password_placeholder') }}">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                <label class="custom-control-label" for="remember">{{ __('auth.remember_me') }}</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-sign-in-alt mr-1"></i> {{ __('auth.sign_in') }}
        </button>

        <div class="text-center mt-3">
            <small class="text-muted">{{ __('auth.internal_access') }}</small>
        </div>
    </form>
@endsection
