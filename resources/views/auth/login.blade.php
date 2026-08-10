@extends('layouts.guest')

@section('title', __('Accedi'))

@section('content')
    <h2 class="auth-title">{{ __('Bentornato') }}</h2>
    <p class="auth-subtitle">{{ __('Accedi al tuo account PokeStash') }}</p>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label auth-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control auth-input" placeholder="nome@esempio.com"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label auth-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   class="form-control auth-input" placeholder="••••••••"
                   required autocomplete="current-password">
            @error('password')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me + Forgot Password --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" name="remember" class="form-check-input auth-check">
                <label for="remember_me" class="form-check-label auth-check-label">{{ __('Ricordami') }}</label>
            </div>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    {{ __('Password dimenticata?') }}
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="auth-btn-primary">
            {{ __('Accedi') }}
        </button>

        {{-- Register Link --}}
        @if (Route::has('register'))
            <div class="auth-divider"></div>
            <p class="text-center mb-0" style="font-size: 0.875rem; color: #9ca3af;">
                {{ __('Non hai un account?') }}
                <a href="{{ route('register') }}" class="auth-link">{{ __('Registrati') }}</a>
            </p>
        @endif
    </form>
@endsection
