@extends('layouts.guest')

@section('title', __('Reimposta password'))

@section('content')
    <h2 class="auth-title">{{ __('Reimposta password') }}</h2>
    <p class="auth-subtitle">{{ __('Scegli la tua nuova password') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        {{-- Password Reset Token --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label auth-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   class="form-control auth-input"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label auth-label">{{ __('Nuova Password') }}</label>
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
            {{ __('Reimposta password') }}
        </button>
    </form>
@endsection
