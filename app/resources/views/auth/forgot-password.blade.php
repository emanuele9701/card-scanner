@extends('layouts.guest')

@section('title', __('Password dimenticata'))

@section('content')
    <h2 class="auth-title">{{ __('Password dimenticata') }}</h2>
    <p class="auth-subtitle">{{ __('Inserisci la tua email e ti invieremo un link per reimpostare la password.') }}</p>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="form-label auth-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control auth-input" placeholder="nome@esempio.com"
                   required autofocus>
            @error('email')
                <div class="auth-error">{{ $message }}</div>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="auth-btn-primary">
            {{ __('Invia link di reset') }}
        </button>

        {{-- Back to Login --}}
        <div class="auth-divider"></div>
        <p class="text-center mb-0" style="font-size: 0.875rem; color: #9ca3af;">
            <a href="{{ route('login') }}" class="auth-link">{{ __('Torna al login') }}</a>
        </p>
    </form>
@endsection
