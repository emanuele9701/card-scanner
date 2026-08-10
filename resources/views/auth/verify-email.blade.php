@extends('layouts.guest')

@section('title', __('Verifica email'))

@section('content')
    <h2 class="auth-title">{{ __('Verifica la tua email') }}</h2>
    <p class="auth-subtitle">{{ __('Grazie per esserti registrato! Prima di iniziare, verifica il tuo indirizzo email cliccando sul link che ti abbiamo appena inviato. Se non hai ricevuto l\'email, te ne invieremo volentieri un\'altra.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="auth-status">
            {{ __('Un nuovo link di verifica è stato inviato al tuo indirizzo email.') }}
        </div>
    @endif

    <div class="d-flex flex-column gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-btn-primary">
                {{ __('Reinvia email di verifica') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link auth-link w-100 text-center" style="text-decoration: none;">
                {{ __('Esci') }}
            </button>
        </form>
    </div>
@endsection
