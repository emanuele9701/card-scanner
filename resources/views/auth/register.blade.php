@extends('layouts.app')

@section('title', 'Registrazione')

@push('styles')
<style>
    .auth-container {
        max-width: 450px;
        margin: 0 auto;
    }

    .auth-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 2.5rem;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        position: relative;
    }

    .auth-logo::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(to bottom, var(--pokemon-red) 45%, #222 45%, #222 55%, #fff 55%);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .auth-logo::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        background: #fff;
        border: 5px solid #222;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .auth-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.5rem;
    }

    .auth-subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.95rem;
    }

    .form-floating {
        margin-bottom: 1rem;
    }

    .form-floating>.form-control {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        color: #fff;
        height: 56px;
        padding: 1rem 1rem;
    }

    .form-floating>.form-control:focus {
        background: rgba(0, 0, 0, 0.4);
        border-color: var(--pokemon-yellow);
        box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.2);
    }

    .form-floating>label {
        color: rgba(255, 255, 255, 0.5);
        padding: 1rem;
    }

    .form-floating>.form-control:focus~label,
    .form-floating>.form-control:not(:placeholder-shown)~label {
        color: var(--pokemon-yellow);
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        z-index: 10;
    }

    .password-toggle:hover {
        color: var(--pokemon-yellow);
    }

    .auth-divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.85rem;
    }

    .auth-divider::before,
    .auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .auth-divider::before {
        margin-right: 1rem;
    }

    .auth-divider::after {
        margin-left: 1rem;
    }

    .auth-link {
        color: var(--pokemon-yellow);
        text-decoration: none;
        font-weight: 500;
    }

    .auth-link:hover {
        color: var(--pokemon-yellow-light);
        text-decoration: underline;
    }

    .invalid-feedback {
        color: var(--pokemon-red-light);
    }
</style>
@endpush

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo"></div>
            <h1 class="auth-title">Crea Account</h1>
            <p class="auth-subtitle">Unisciti ai collezionisti Pokemon!</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-floating">
                <input type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="email@example.com"
                    required
                    autofocus>
                <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating position-relative">
                <input type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    placeholder="Password"
                    required>
                <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="bi bi-eye" id="password-icon"></i>
                </button>
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating position-relative">
                <input type="password"
                    class="form-control"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Conferma Password"
                    required>
                <label for="password_confirmation"><i class="bi bi-lock-fill me-2"></i>Conferma Password</label>
                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                    <i class="bi bi-eye" id="password_confirmation-icon"></i>
                </button>
            </div>

            <div class="form-text text-white-50 mb-3">
                <small><i class="bi bi-info-circle me-1"></i>Minimo 8 caratteri</small>
            </div>

            <button type="submit" class="btn btn-pokemon w-100 py-3">
                <i class="bi bi-person-plus me-2"></i>Registrati
            </button>
        </form>

        <div class="auth-divider">oppure</div>

        <p class="text-center text-white-50 mb-0">
            Hai già un account?
            <a href="{{ route('login') }}" class="auth-link">Accedi</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '-icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endpush