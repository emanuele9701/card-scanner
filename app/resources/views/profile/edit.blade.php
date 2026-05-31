@extends('layouts.app')

@section('title', __('Profilo'))
@section('meta_description', __('Gestisci le informazioni del tuo profilo.'))

@section('content')
<style>
    .profile-shell {
        background: radial-gradient(circle at top, rgba(63, 82, 110, 0.12), transparent 28%), #050c16;
        min-height: calc(100vh - 80px);
        padding-top: 3rem;
        padding-bottom: 4rem;
    }

    .profile-card {
        background: rgba(14, 24, 44, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
    }

    .profile-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.25rem;
    }

    .profile-card-desc {
        font-size: 0.8125rem;
        color: #9ca3af;
        margin-bottom: 1.5rem;
    }

    .profile-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #d1d5db;
    }

    .profile-input {
        background-color: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.625rem !important;
        color: #ffffff !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.9375rem !important;
    }

    .profile-input:focus {
        background-color: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(99, 102, 241, 0.5) !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
    }

    .btn-profile-save {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border: none;
        border-radius: 0.5rem;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.5rem 1.25rem;
        transition: all 0.2s;
    }

    .btn-profile-save:hover {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        color: #ffffff;
    }

    .btn-danger-profile {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.5rem;
        color: #f87171;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.5rem 1.25rem;
        transition: all 0.2s;
    }

    .btn-danger-profile:hover {
        background: rgba(239, 68, 68, 0.25);
        color: #fca5a5;
    }

    .profile-error {
        color: #f87171;
        font-size: 0.8125rem;
        margin-top: 0.25rem;
    }

    .profile-success {
        color: #34d399;
        font-size: 0.8125rem;
    }
</style>

<main class="profile-shell">
    <div class="container" style="max-width: 720px;">
        <h1 class="h3 text-white fw-bold mb-4">{{ __('Profilo') }}</h1>

        {{-- Update Profile Information --}}
        <div class="profile-card">
            <h2 class="profile-card-title">{{ __('Informazioni Profilo') }}</h2>
            <p class="profile-card-desc">{{ __('Aggiorna il nome e l\'indirizzo email del tuo account.') }}</p>

            <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="mb-3">
                    <label for="name" class="form-label profile-label">{{ __('Nome') }}</label>
                    <input id="name" name="name" type="text" class="form-control profile-input"
                           value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="profile-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label profile-label">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="form-control profile-input"
                           value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email')
                        <div class="profile-error">{{ $message }}</div>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2">
                            <p class="text-secondary" style="font-size: 0.8125rem;">
                                {{ __('Il tuo indirizzo email non è stato verificato.') }}
                                <button form="send-verification" class="btn btn-link auth-link p-0" style="font-size: 0.8125rem;">
                                    {{ __('Clicca qui per reinviare l\'email di verifica.') }}
                                </button>
                            </p>
                            @if (session('status') === 'verification-link-sent')
                                <p class="profile-success mt-1">
                                    {{ __('Un nuovo link di verifica è stato inviato al tuo indirizzo email.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-profile-save">{{ __('Salva') }}</button>
                    @if (session('status') === 'profile-updated')
                        <span class="profile-success">{{ __('Salvato.') }}</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Update Password --}}
        <div class="profile-card">
            <h2 class="profile-card-title">{{ __('Aggiorna Password') }}</h2>
            <p class="profile-card-desc">{{ __('Assicurati di usare una password lunga e sicura per proteggere il tuo account.') }}</p>

            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label for="update_password_current_password" class="form-label profile-label">{{ __('Password Attuale') }}</label>
                    <input id="update_password_current_password" name="current_password" type="password"
                           class="form-control profile-input" autocomplete="current-password">
                    @error('current_password', 'updatePassword')
                        <div class="profile-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="update_password_password" class="form-label profile-label">{{ __('Nuova Password') }}</label>
                    <input id="update_password_password" name="password" type="password"
                           class="form-control profile-input" autocomplete="new-password">
                    @error('password', 'updatePassword')
                        <div class="profile-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="update_password_password_confirmation" class="form-label profile-label">{{ __('Conferma Password') }}</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                           class="form-control profile-input" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword')
                        <div class="profile-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-profile-save">{{ __('Salva') }}</button>
                    @if (session('status') === 'password-updated')
                        <span class="profile-success">{{ __('Salvato.') }}</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Delete Account --}}
        <div class="profile-card" style="border-color: rgba(239, 68, 68, 0.2);">
            <h2 class="profile-card-title" style="color: #f87171;">{{ __('Elimina Account') }}</h2>
            <p class="profile-card-desc">{{ __('Una volta eliminato il tuo account, tutte le risorse e i dati saranno permanentemente cancellati. Scarica tutti i dati che desideri conservare prima di eliminare il tuo account.') }}</p>

            <button type="button" class="btn btn-danger-profile" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                {{ __('Elimina Account') }}
            </button>

            {{-- Delete Account Modal --}}
            <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="background: rgba(14, 24, 44, 0.98); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 1rem;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title text-white fw-bold" id="deleteAccountModalLabel">{{ __('Sei sicuro di voler eliminare il tuo account?') }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary" style="font-size: 0.875rem;">{{ __('Una volta eliminato il tuo account, tutte le risorse e i dati saranno permanentemente cancellati. Inserisci la tua password per confermare.') }}</p>
                            <form id="delete-account-form" method="post" action="{{ route('profile.destroy') }}">
                                @csrf
                                @method('delete')
                                <div class="mb-3">
                                    <label for="delete_password" class="form-label profile-label">{{ __('Password') }}</label>
                                    <input id="delete_password" name="password" type="password"
                                           class="form-control profile-input" placeholder="{{ __('Password') }}">
                                    @error('password', 'userDeletion')
                                        <div class="profile-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                            <button type="submit" form="delete-account-form" class="btn btn-danger rounded-pill px-3">{{ __('Elimina Account') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@if($errors->userDeletion->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
        modal.show();
    });
</script>
@endif
@endsection
