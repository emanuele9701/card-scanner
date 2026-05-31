<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Accedi')) — PokeStash</title>
    <meta name="description" content="@yield('meta_description', __('Accedi alla tua collezione di carte collezionabili.'))">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #030712;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.08), transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(168, 85, 247, 0.06), transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(59, 130, 246, 0.05), transparent 50%),
                #030712;
            padding: 2rem 1rem;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: rgba(14, 24, 44, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            backdrop-filter: blur(20px);
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.03) inset;
            padding: 2.5rem;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .auth-logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(168, 85, 247, 0.2));
            border: 1px solid rgba(129, 140, 248, 0.3);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .auth-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            color: #9ca3af;
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #d1d5db;
            margin-bottom: 0.375rem;
        }

        .auth-input {
            background-color: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.625rem !important;
            color: #ffffff !important;
            padding: 0.625rem 0.875rem !important;
            font-size: 0.9375rem !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
        }

        .auth-input:focus {
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(99, 102, 241, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
            outline: none !important;
        }

        .auth-input::placeholder {
            color: #6b7280;
        }

        .auth-btn-primary {
            width: 100%;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 0.625rem;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s;
            cursor: pointer;
        }

        .auth-btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }

        .auth-btn-primary:active {
            transform: translateY(0);
        }

        .auth-link {
            color: #818cf8;
            text-decoration: none;
            font-size: 0.8125rem;
            transition: color 0.2s;
        }

        .auth-link:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }

        .auth-check {
            accent-color: #6366f1;
        }

        .auth-check-label {
            font-size: 0.8125rem;
            color: #9ca3af;
        }

        .auth-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin: 1.25rem 0;
        }

        .auth-error {
            color: #f87171;
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .auth-status {
            color: #34d399;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: rgba(52, 211, 153, 0.1);
            border-radius: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="auth-shell">
        <div class="auth-card">
            <a href="/" class="auth-logo">
                <div class="auth-logo-icon">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M2 12h20" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
                <span class="auth-logo-text">PokeStash</span>
            </a>

            @yield('content')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
