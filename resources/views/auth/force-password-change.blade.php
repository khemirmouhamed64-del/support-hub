@extends('layouts.guest')

@section('title', __('auth.change_password'))

@section('content')
    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle mr-1"></i> {{ __('auth.must_change_password') }}
    </div>

    <form method="POST" action="{{ route('password.force-change') }}">
        @csrf

        <div class="form-group">
            <label for="password">{{ __('auth.new_password') }}</label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autofocus placeholder="{{ __('auth.password_min') }}">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">{{ __('auth.confirm_new_password') }}</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="form-control" required placeholder="{{ __('auth.repeat_password') }}">
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-key mr-1"></i> {{ __('auth.change_password') }}
        </button>

        <div class="text-center mt-3">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link text-muted small p-0">
                    {{ __('auth.logout') }}
                </button>
            </form>
        </div>
    </form>
@endsection
