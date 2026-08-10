@extends('layouts.app')

@section('title', __('Profilo'))
@section('meta_description', __('Modifica il tuo username e la tua password.'))

@section('custom_style')
    <style>
        .profile-card {
            background-color: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-input {
            background-color: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 0.75rem !important;
            color: #fff !important;
            padding: 0.75rem 1rem 0.75rem 2.75rem !important;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .profile-input::placeholder {
            color: #4b5563;
        }

        .profile-input:focus {
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            outline: none;
        }

        .profile-input.is-invalid-custom {
            border-color: rgba(239, 68, 68, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }

        .profile-input.is-valid-custom {
            border-color: rgba(16, 185, 129, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        .profile-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            z-index: 5;
        }

        .btn-profile {
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border: none;
            border-radius: 0.75rem;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
        }

        .btn-profile:hover {
            color: #fff;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
            filter: brightness(1.1);
        }

        .btn-profile:active {
            transform: scale(0.98);
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .password-strength-profile {
            height: 3px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background-color: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .password-strength-bar-profile {
            height: 100%;
            border-radius: 2px;
            transition: width 0.4s ease, background-color 0.4s ease;
            width: 0%;
        }

        .password-hint-profile {
            font-size: 0.75rem;
            margin-top: 0.375rem;
            transition: color 0.2s;
        }

        .success-toast {
            animation: slideIn 0.3s ease-out, fadeOut 0.3s ease-in 3.7s forwards;
        }

        @keyframes slideIn {
            from { transform: translateY(-1rem); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeOut {
            to { opacity: 0; }
        }

        .divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin: 2rem 0;
        }

        .avatar-large {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }
    </style>
@endsection

@section('content')
    <div class="container py-5" style="max-width: 640px;">

        {{-- Success Messages --}}
        @if (session('success'))
            <div class="success-toast mb-4 px-4 py-3 rounded-3" id="profile-success"
                 style="background-color: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);">
                <p class="mb-0 fw-medium" style="font-size:0.875rem; color:#34d399;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         class="d-inline-block me-1" style="vertical-align: -2px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </p>
            </div>
        @endif

        {{-- User Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="avatar-large">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-white fw-bold mb-1" style="font-size:1.5rem; letter-spacing:-0.02em;">
                    {{ __('Il tuo profilo') }}
                </h1>
                <p class="text-secondary mb-0" style="font-size:0.875rem;">
                    {{ __('Gestisci il tuo account e la sicurezza') }}
                </p>
            </div>
        </div>

        {{-- ═══ Cambio Username ═══ --}}
        <div class="profile-card p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon" style="background-color: rgba(99,102,241,0.1);">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-white fw-semibold mb-0" style="font-size:1rem;">{{ __('Username') }}</h2>
                    <p class="text-secondary mb-0" style="font-size:0.75rem;">{{ __('Cambia il tuo nome utente') }}</p>
                </div>
            </div>

            @if ($errors->username->any())
                <div class="mb-3 px-3 py-3 rounded-3"
                     style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);">
                    @foreach ($errors->username->all() as $error)
                        <p class="mb-0 fw-medium" style="font-size:0.875rem; color:#f87171;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('profile.updateUsername') }}" id="username-form">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="profile-name" class="form-label text-light fw-medium"
                        style="font-size:0.875rem;">{{ __('Nuovo username') }}</label>
                    <div class="position-relative">
                        <span class="profile-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input type="text" name="name" id="profile-name" required
                            value="{{ old('name', Auth::user()->name) }}"
                            placeholder="{{ __('Il tuo username') }}"
                            class="form-control profile-input">
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-profile" id="username-submit">
                        {{ __('Aggiorna username') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══ Cambio Password ═══ --}}
        <div class="profile-card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon" style="background-color: rgba(239,68,68,0.1);">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-white fw-semibold mb-0" style="font-size:1rem;">{{ __('Password') }}</h2>
                    <p class="text-secondary mb-0" style="font-size:0.75rem;">{{ __('Aggiorna la tua password') }}</p>
                </div>
            </div>

            @if ($errors->password->any())
                <div class="mb-3 px-3 py-3 rounded-3"
                     style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);">
                    @foreach ($errors->password->all() as $error)
                        <p class="mb-0 fw-medium" style="font-size:0.875rem; color:#f87171;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('profile.updatePassword') }}" id="password-form">
                @csrf
                @method('PUT')

                {{-- Password attuale --}}
                <div class="mb-3">
                    <label for="current_password" class="form-label text-light fw-medium"
                        style="font-size:0.875rem;">{{ __('Password attuale') }}</label>
                    <div class="position-relative">
                        <span class="profile-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </span>
                        <input type="password" name="current_password" id="current_password" required
                            autocomplete="current-password" placeholder="••••••••"
                            class="form-control profile-input">
                    </div>
                </div>

                {{-- Nuova password --}}
                <div class="mb-3">
                    <label for="new_password" class="form-label text-light fw-medium"
                        style="font-size:0.875rem;">{{ __('Nuova password') }}</label>
                    <div class="position-relative">
                        <span class="profile-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password" name="new_password" id="new_password" required
                            autocomplete="new-password" placeholder="••••••••"
                            class="form-control profile-input">
                    </div>
                    <div class="password-strength-profile">
                        <div class="password-strength-bar-profile" id="profile-strength-bar"></div>
                    </div>
                    <p class="password-hint-profile text-secondary" id="profile-password-hint">
                        {{ __('Minimo 8 caratteri') }}
                    </p>
                </div>

                {{-- Conferma nuova password --}}
                <div class="mb-4">
                    <label for="new_password_confirmation" class="form-label text-light fw-medium"
                        style="font-size:0.875rem;">{{ __('Conferma nuova password') }}</label>
                    <div class="position-relative">
                        <span class="profile-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" required
                            autocomplete="new-password" placeholder="••••••••"
                            class="form-control profile-input">
                    </div>
                    <p class="password-hint-profile" id="profile-match-hint" style="color: #4b5563;">&nbsp;</p>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-profile" id="password-submit">
                        {{ __('Aggiorna password') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══ Le Mie Watchlist ═══ --}}
        <div class="profile-card p-4 mt-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="section-icon" style="background-color: rgba(16,185,129,0.1);">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-white fw-semibold mb-0" style="font-size:1rem;">{{ __('Watchlist Prezzi') }}</h2>
                    <p class="text-secondary mb-0" style="font-size:0.75rem;">{{ __('Carte ed espansioni monitorate') }}</p>
                </div>
            </div>
            
            <div id="watchlist-container">
                <div class="text-center text-secondary py-3">{{ __('Caricamento...') }}</div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pwdInput = document.getElementById('new_password');
            const confirmInput = document.getElementById('new_password_confirmation');
            const strengthBar = document.getElementById('profile-strength-bar');
            const pwdHint = document.getElementById('profile-password-hint');
            const matchHint = document.getElementById('profile-match-hint');

            function evaluateStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
                if (/\d/.test(password)) score++;
                if (/[^a-zA-Z0-9]/.test(password)) score++;
                return score;
            }

            pwdInput.addEventListener('input', function () {
                const val = this.value;
                const score = evaluateStrength(val);
                const percent = val.length === 0 ? 0 : Math.min((score / 5) * 100, 100);
                strengthBar.style.width = percent + '%';

                if (val.length === 0) {
                    strengthBar.style.backgroundColor = 'transparent';
                    pwdHint.style.color = '#4b5563';
                    pwdHint.textContent = '{{ __("Minimo 8 caratteri") }}';
                    pwdInput.classList.remove('is-valid-custom', 'is-invalid-custom');
                } else if (val.length < 8) {
                    strengthBar.style.backgroundColor = '#ef4444';
                    pwdHint.style.color = '#f87171';
                    pwdHint.textContent = '{{ __("Troppo corta — servono almeno 8 caratteri") }}';
                    pwdInput.classList.add('is-invalid-custom');
                    pwdInput.classList.remove('is-valid-custom');
                } else if (score <= 2) {
                    strengthBar.style.backgroundColor = '#f59e0b';
                    pwdHint.style.color = '#fbbf24';
                    pwdHint.textContent = '{{ __("Password debole") }}';
                    pwdInput.classList.remove('is-invalid-custom', 'is-valid-custom');
                } else if (score <= 3) {
                    strengthBar.style.backgroundColor = '#10b981';
                    pwdHint.style.color = '#34d399';
                    pwdHint.textContent = '{{ __("Password buona") }}';
                    pwdInput.classList.add('is-valid-custom');
                    pwdInput.classList.remove('is-invalid-custom');
                } else {
                    strengthBar.style.backgroundColor = '#06b6d4';
                    pwdHint.style.color = '#22d3ee';
                    pwdHint.textContent = '{{ __("Password forte") }}';
                    pwdInput.classList.add('is-valid-custom');
                    pwdInput.classList.remove('is-invalid-custom');
                }

                checkMatch();
            });

            function checkMatch() {
                const pwd = pwdInput.value;
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

            // Auto-hide success toast
            const toast = document.getElementById('profile-success');
            if (toast) {
                setTimeout(() => toast.remove(), 4000);
            }

            // Load Watchlists
            function loadWatchlists() {
                fetch('/watchlist', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('watchlist-container');
                    let html = '';
                    
                    if (data.cards && data.cards.length > 0) {
                        html += '<h6 class="text-light mt-2 mb-3">Carte Monitorate</h6><ul class="list-group mb-4" style="background:transparent;">';
                        data.cards.forEach(item => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:0.5rem; margin-bottom:0.5rem; color:#fff;">
                                <span>${item.card ? item.card.name : 'Carta #' + item.card_id}</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeWatchlist('card', ${item.card_id})">Rimuovi</button>
                            </li>`;
                        });
                        html += '</ul>';
                    }

                    if (data.sets && data.sets.length > 0) {
                        html += '<h6 class="text-light mt-2 mb-3">Espansioni Monitorate</h6><ul class="list-group mb-4" style="background:transparent;">';
                        data.sets.forEach(item => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:0.5rem; margin-bottom:0.5rem; color:#fff;">
                                <span>${item.set ? item.set.name : 'Set #' + item.set_id}</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeWatchlist('set', ${item.set_id})">Rimuovi</button>
                            </li>`;
                        });
                        html += '</ul>';
                    }

                    if (html === '') {
                        html = `<div class="text-center text-secondary py-3">${'{{ __("Nessun elemento monitorato attualmente.") }}'}</div>`;
                    }
                    container.innerHTML = html;
                });
            }

            window.removeWatchlist = function(type, id) {
                fetch('/watchlist/' + type + '/' + id, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' 
                    },
                    credentials: 'same-origin'
                }).then(() => loadWatchlists());
            };

            loadWatchlists();
        });
    </script>
@endsection
