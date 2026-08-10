@extends('layouts.guest')

@section('title', __('Conferma password'))

@section('content')
    <h2 class="auth-title">{{ __('Conferma password') }}</h2>
    <p class="auth-subtitle">{{ __('Questa è un\'area protetta. Per favore conferma la tua password prima di continuare.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        {{-- Password --}}
        <div class="mb-4">
            <label for="password" class="form-label auth-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   class="form-control auth-input" placeholder="••••••••"
                   required autocomplete="current-password">
            @error('password')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="auth-btn-primary">
            {{ __('Conferma') }}
        </button>
    </form>
@endsection
