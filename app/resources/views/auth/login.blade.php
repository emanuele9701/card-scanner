@extends('layouts.guest')

@section('title', __('Accedi'))
@section('meta_description', __('Accedi al tuo account Card Scanner per gestire la tua collezione.'))

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

        {{-- Login Card --}}
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
                <p class="text-secondary mb-0" style="font-size:0.875rem;">{{ __('Accedi alla tua collezione') }}</p>
            </div>

            {{-- Card --}}
            <div class="login-card p-4 p-sm-5">

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div id="login-errors" class="error-box px-3 py-3 mb-4">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0 fw-medium" style="font-size:0.875rem; color:#f87171;">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label text-light fw-medium"
                            style="font-size:0.875rem;">{{ __('Email') }}</label>
                        <div class="position-relative">
                            <span class="input-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </span>
                            <input type="email" name="email" id="email" required autocomplete="email" autofocus
                                value="{{ old('email') }}" placeholder="nome@esempio.com" class="form-control login-input">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
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
                            <input type="password" name="password" id="password" required autocomplete="current-password"
                                placeholder="••••••••" class="form-control login-input">
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" id="login-submit"
                        class="btn-login w-100 d-flex align-items-center justify-content-center gap-2">
                        <span class="position-relative" style="z-index:1;">{{ __('Accedi') }}</span>
                        <svg class="position-relative" style="z-index:1;" width="16" height="16" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <div class="shine"></div>
                    </button>
                </form>

                {{-- Link alla Registrazione --}}
                <p class="text-center mt-4 mb-0" style="font-size:0.875rem; color:#9ca3af;">
                    {{ __('Non hai un account?') }}
                    <a href="{{ route('register') }}" style="color:#ef4444; text-decoration:none; font-weight:500; transition:color 0.2s;"
                       onmouseover="this.style.color='#f87171'; this.style.textDecoration='underline';"
                       onmouseout="this.style.color='#ef4444'; this.style.textDecoration='none';">{{ __('Registrati') }}</a>
                </p>
            </div>

            {{-- Footer --}}
            <p class="text-center mt-4 mb-0" style="font-size:0.75rem; color:#4b5563;">
                &copy; {{ date('Y') }} Card Scanner — {{ __('Tutti i diritti riservati') }}
            </p>
        </div>
    </div>
@endsection
