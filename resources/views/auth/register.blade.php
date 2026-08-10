@extends('layouts.guest')

@section('title', __('Registrati'))

@section('content')
    <h2 class="auth-title">{{ __('Crea il tuo account') }}</h2>
    <p class="auth-subtitle">{{ __('Unisciti a PokeStash e inizia a collezionare') }}</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-3">
            <label for="name" class="form-label auth-label">{{ __('Nome') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="form-control auth-input" placeholder="{{ __('Il tuo nome') }}"
                   required autofocus autocomplete="name">
            @error('name')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label auth-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control auth-input" placeholder="nome@esempio.com"
                   required autocomplete="username">
            @error('email')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label auth-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   class="form-control auth-input" placeholder="••••••••"
                   required autocomplete="new-password">
            @error('password')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label auth-label">{{ __('Conferma Password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control auth-input" placeholder="••••••••"
                   required autocomplete="new-password">
            @error('password_confirmation')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="auth-btn-primary">
            {{ __('Registrati') }}
        </button>

        {{-- Login Link --}}
        <div class="auth-divider"></div>
        <p class="text-center mb-0" style="font-size: 0.875rem; color: #9ca3af;">
            {{ __('Hai già un account?') }}
            <a href="{{ route('login') }}" class="auth-link">{{ __('Accedi') }}</a>
        </p>
    </form>
@endsection
