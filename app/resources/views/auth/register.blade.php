@extends('layouts.guest')

@section('title', __('Registrati'))
@section('meta_description', __('Crea un nuovo account su Card Scanner per gestire la tua collezione.'))

@section('content')
    <style>
        .login-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: blob 8s infinite ease-in-out;
        }

        @keyframes blob {

            0%,
            100% {
                transform: scale(1) translate(0, 0);
            }

            33% {
                transform: scale(1.1) translate(20px, -10px);
            }

            66% {
                transform: scale(0.95) translate(-15px, 15px);
            }
        }

        .animation-delay-2 {
            animation-delay: 2s;
        }

        .animation-delay-4 {
            animation-delay: 4s;
        }

        .login-card {
            background-color: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .login-input {
            background-color: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 0.75rem !important;
            color: #fff !important;
            padding: 0.75rem 1rem 0.75rem 2.75rem !important;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .login-input::placeholder {
            color: #4b5563;
        }

        .login-input:focus {
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            outline: none;
        }

        .login-input.is-invalid-custom {
            border-color: rgba(239, 68, 68, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }

        .login-input.is-valid-custom {
            border-color: rgba(16, 185, 129, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            z-index: 5;
        }

        .btn-login {
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
        }

        .btn-login:hover {
            color: #fff;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: saturate(0.5);
        }

        .btn-login .shine {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.7s;
        }

        .btn-login:hover .shine {
            transform: translateX(100%);
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }

        .error-box {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem;
        }

        .password-hint {
            font-size: 0.75rem;
            margin-top: 0.375rem;
            transition: color 0.2s;
        }

        .password-strength {
            height: 3px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background-color: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.4s ease, background-color 0.4s ease;
            width: 0%;
        }

        .login-link {
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-link:hover {
            color: #f87171;
            text-decoration: underline;
        }
    </style>

    <div class="position-relative d-flex align-items-center justify-content-center overflow-hidden px-3 py-5 min-vh-100">

        {{-- Animated background blobs --}}
        <div class="position-absolute inset-0 overflow-hidden w-100 h-100" style="pointer-events:none;">
            <div class="login-blob" style="width:384px;height:384px;background:rgba(239,68,68,0.1);top:-8rem;left:-8rem;">
            </div>
            <div class="login-blob animation-delay-2"
                style="width:384px;height:384px;background:rgba(99,102,241,0.1);top:33%;right:-8rem;"></div>
            <div class="login-blob animation-delay-4"
                style="width:384px;height:384px;background:rgba(16,185,129,0.1);bottom:-8rem;left:33%;"></div>
        </div>

        {{-- Register Card --}}
        <div class="position-relative" style="z-index:10; width:100%; max-width:448px;">

            {{-- Logo --}}
            <div class="d-flex flex-column align-items-center mb-4">
                <div class="logo-icon mb-3">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
                <h1 class="text-white fw-bold mb-1" style="font-size:1.5rem; letter-spacing:-0.02em;">Card Scanner</h1>
                <p class="text-secondary mb-0" style="font-size:0.875rem;">{{ __('Crea il tuo account') }}</p>
            </div>

            {{-- Card --}}
            <div class="login-card p-4 p-sm-5">

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div id="register-errors" class="error-box px-3 py-3 mb-4">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0 fw-medium" style="font-size:0.875rem; color:#f87171;">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="register-form">
                    @csrf

                    {{-- Username --}}
                    <div class="mb-3">
                        <label for="name" class="form-label text-light fw-medium"
                            style="font-size:0.875rem;">{{ __('Username') }}</label>
                        <div class="position-relative">
                            <span class="input-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <input type="text" name="name" id="name" required autocomplete="username" autofocus
                                value="{{ old('name') }}" placeholder="{{ __('Il tuo username') }}"
                                class="form-control login-input">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label text-light fw-medium"
                            style="font-size:0.875rem;">{{ __('Password') }}</label>
                        <div class="position-relative">
                            <span class="input-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <input type="password" name="password" id="password" required autocomplete="new-password"
                                placeholder="••••••••" class="form-control login-input">
                        </div>
                        {{-- Strength bar --}}
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                        <p class="password-hint text-secondary" id="password-hint">
                            {{ __('Minimo 8 caratteri') }}
                        </p>
                    </div>

                    {{-- Conferma Password --}}
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label text-light fw-medium"
                            style="font-size:0.875rem;">{{ __('Conferma Password') }}</label>
                        <div class="position-relative">
                            <span class="input-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                autocomplete="new-password" placeholder="••••••••"
                                class="form-control login-input">
                        </div>
                        <p class="password-hint" id="password-match-hint" style="color: #4b5563;">
                            &nbsp;
                        </p>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" id="register-submit"
                        class="btn-login w-100 d-flex align-items-center justify-content-center gap-2">
                        <span class="position-relative" style="z-index:1;">{{ __('Registrati') }}</span>
                        <svg class="position-relative" style="z-index:1;" width="16" height="16" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <div class="shine"></div>
                    </button>
                </form>

                {{-- Link al Login --}}
                <p class="text-center mt-4 mb-0" style="font-size:0.875rem; color:#9ca3af;">
                    {{ __('Hai già un account?') }}
                    <a href="{{ route('login') }}" class="login-link">{{ __('Accedi') }}</a>
                </p>
            </div>

            {{-- Footer --}}
            <p class="text-center mt-4 mb-0" style="font-size:0.75rem; color:#4b5563;">
                &copy; {{ date('Y') }} Card Scanner — {{ __('Tutti i diritti riservati') }}
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const strengthBar = document.getElementById('password-strength-bar');
            const passwordHint = document.getElementById('password-hint');
            const matchHint = document.getElementById('password-match-hint');

            function evaluateStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
                if (/\d/.test(password)) score++;
                if (/[^a-zA-Z0-9]/.test(password)) score++;
                return score;
            }

            passwordInput.addEventListener('input', function () {
                const val = this.value;
                const score = evaluateStrength(val);

                // Strength bar
                const percent = val.length === 0 ? 0 : Math.min((score / 5) * 100, 100);
                strengthBar.style.width = percent + '%';

                if (val.length === 0) {
                    strengthBar.style.backgroundColor = 'transparent';
                    passwordHint.style.color = '#4b5563';
                    passwordHint.textContent = '{{ __("Minimo 8 caratteri") }}';
                    passwordInput.classList.remove('is-valid-custom', 'is-invalid-custom');
                } else if (val.length < 8) {
                    strengthBar.style.backgroundColor = '#ef4444';
                    passwordHint.style.color = '#f87171';
                    passwordHint.textContent = '{{ __("Troppo corta — servono almeno 8 caratteri") }}';
                    passwordInput.classList.add('is-invalid-custom');
                    passwordInput.classList.remove('is-valid-custom');
                } else if (score <= 2) {
                    strengthBar.style.backgroundColor = '#f59e0b';
                    passwordHint.style.color = '#fbbf24';
                    passwordHint.textContent = '{{ __("Password debole") }}';
                    passwordInput.classList.remove('is-invalid-custom', 'is-valid-custom');
                } else if (score <= 3) {
                    strengthBar.style.backgroundColor = '#10b981';
                    passwordHint.style.color = '#34d399';
                    passwordHint.textContent = '{{ __("Password buona") }}';
                    passwordInput.classList.add('is-valid-custom');
                    passwordInput.classList.remove('is-invalid-custom');
                } else {
                    strengthBar.style.backgroundColor = '#06b6d4';
                    passwordHint.style.color = '#22d3ee';
                    passwordHint.textContent = '{{ __("Password forte") }}';
                    passwordInput.classList.add('is-valid-custom');
                    passwordInput.classList.remove('is-invalid-custom');
                }

                // Re-check match
                checkMatch();
            });

            function checkMatch() {
                const pwd = passwordInput.value;
                const confirm = confirmInput.value;

                if (confirm.length === 0) {
                    matchHint.innerHTML = '&nbsp;';
                    matchHint.style.color = '#4b5563';
                    confirmInput.classList.remove('is-valid-custom', 'is-invalid-custom');
                    return;
                }

                if (pwd === confirm) {
                    matchHint.textContent = '{{ __("Le password coincidono") }}';
                    matchHint.style.color = '#34d399';
                    confirmInput.classList.add('is-valid-custom');
                    confirmInput.classList.remove('is-invalid-custom');
                } else {
                    matchHint.textContent = '{{ __("Le password non coincidono") }}';
                    matchHint.style.color = '#f87171';
                    confirmInput.classList.add('is-invalid-custom');
                    confirmInput.classList.remove('is-valid-custom');
                }
            }

            confirmInput.addEventListener('input', checkMatch);
        });
    </script>
@endsection
