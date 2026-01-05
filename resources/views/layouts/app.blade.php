<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Card Scanner')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --pokemon-yellow: #FFCB05;
            --pokemon-yellow-light: #FFE066;
            --pokemon-blue: #3D7DCA;
            --pokemon-dark-blue: #003A70;
            --pokemon-red: #CC0000;
            --pokemon-red-light: #FF4444;
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;

            /* Pokemon Type Colors */
            --type-fire: #F08030;
            --type-water: #6890F0;
            --type-grass: #78C850;
            --type-electric: #F8D030;
            --type-psychic: #F85888;
            --type-fighting: #C03028;
            --type-dark: #705848;
            --type-steel: #B8B8D0;
            --type-fairy: #EE99AC;
            --type-dragon: #7038F8;
            --type-normal: #A8A878;
            --type-colorless: #A8A8A8;
        }

        /* Demo Banner */
        .demo-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(90deg, #ff9966, #ff5e62);
            color: white;
            z-index: 1001;
            font-size: 0.85rem;
            font-weight: 600;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .banner-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
            line-height: 1.2;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-mid) 50%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Improvements */
        .navbar {
            background: rgba(0, 0, 0, 0.4) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 0;
            top: 40px;
            /* Offset for banner */
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--pokemon-yellow) !important;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.02);
        }

        .scanner-logo {
            width: 32px;
            height: 40px;
            position: relative;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            border: 2px solid var(--pokemon-yellow);
            overflow: hidden;
            box-shadow: 0 0 10px rgba(255, 203, 5, 0.2);
        }

        .scanner-logo::before {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            bottom: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 2px;
        }

        .scanner-logo::after {
            content: '';
            position: absolute;
            width: 120%;
            height: 2px;
            background: var(--pokemon-red);
            box-shadow: 0 0 8px var(--pokemon-red);
            top: 0;
            left: -10%;
            animation: scan-vertical 2s linear infinite;
        }

        @keyframes scan-vertical {
            0% {
                top: 0;
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                top: 100%;
                opacity: 0;
            }
        }



        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            border-radius: 10px;
            margin: 0 0.25rem;
        }

        .nav-link:hover {
            color: var(--pokemon-yellow) !important;
            background: rgba(255, 203, 5, 0.1);
        }

        .nav-link.active {
            color: var(--pokemon-yellow) !important;
            background: rgba(255, 203, 5, 0.15);
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4);
        }

        /* Buttons */
        .btn-pokemon {
            background: linear-gradient(135deg, var(--pokemon-yellow) 0%, #f39c12 100%);
            border: none;
            color: #000;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 203, 5, 0.3);
        }

        .btn-pokemon:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 25px rgba(255, 203, 5, 0.5);
            color: #000;
        }

        .btn-pokemon:disabled {
            opacity: 0.6;
            transform: none;
        }

        .btn-danger-pokemon {
            background: linear-gradient(135deg, var(--pokemon-red) 0%, #ff4444 100%);
            border: none;
            color: #fff;
        }

        .btn-danger-pokemon:hover {
            color: #fff;
            box-shadow: 0 6px 25px rgba(204, 0, 0, 0.5);
        }

        /* Main Container */
        .main-container {
            padding-top: 130px;
            padding-bottom: 80px;
            flex: 1;
        }

        /* Page Titles */
        .page-title {
            font-weight: 800;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--pokemon-yellow) 0%, #fff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
        }

        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .pokeball-loader {
            width: 80px;
            height: 80px;
            position: relative;
            animation: bounce 0.6s infinite alternate;
        }

        @keyframes bounce {
            from {
                transform: translateY(0) rotate(0deg);
            }

            to {
                transform: translateY(-20px) rotate(20deg);
            }
        }

        .pokeball-loader::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(to bottom, var(--pokemon-red) 45%, #000 45%, #000 55%, #fff 55%);
            box-shadow: 0 0 30px rgba(255, 203, 5, 0.5);
        }

        .pokeball-loader::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: #fff;
            border: 4px solid #000;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(255, 203, 5, 0.7);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(255, 203, 5, 0);
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9998;
        }

        .toast {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
        }

        .toast-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .toast-body {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Footer */
        .app-footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 0;
            margin-top: auto;
        }

        .app-footer p {
            margin: 0;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }

        .app-footer a {
            color: var(--pokemon-yellow);
            text-decoration: none;
            transition: color 0.3s;
        }

        .app-footer a:hover {
            color: var(--pokemon-yellow-light);
        }

        .footer-version {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.3);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Page Transitions */
        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Hamburger */
        .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.3);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 203, 5, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Type Badges */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .type-fire {
            background: var(--type-fire);
        }

        .type-water {
            background: var(--type-water);
        }

        .type-grass,
        .type-erba {
            background: var(--type-grass);
        }

        .type-electric,
        .type-elettro {
            background: var(--type-electric);
        }

        .type-psychic,
        .type-psico {
            background: var(--type-psychic);
        }

        .type-fighting,
        .type-lotta {
            background: var(--type-fighting);
        }

        .type-dark,
        .type-buio {
            background: var(--type-dark);
        }

        .type-steel,
        .type-acciaio {
            background: var(--type-steel);
        }

        .type-fairy {
            background: var(--type-fairy);
        }

        .type-dragon,
        .type-drago {
            background: var(--type-dragon);
        }

        .type-normal,
        .type-normale {
            background: var(--type-normal);
        }

        .type-colorless,
        .type-incolore {
            background: var(--type-colorless);
        }

        .type-fuoco {
            background: var(--type-fire);
        }

        .type-acqua {
            background: var(--type-water);
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- Demo Warning Banner -->
    <div class="demo-banner">
        <div class="container banner-content">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>ATTENZIONE: Ogni giorno a mezzanotte viene eseguita la pulizia del DB e dello storage (Ambiente
                Demo).</span>
        </div>
    </div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('cards.upload') }}">
                <span class="scanner-logo"></span>
                Card Scanner
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cards.upload') ? 'active' : '' }}"
                                href="{{ route('cards.upload') }}">
                                <i class="bi bi-camera-fill me-1"></i> Scansiona
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cards.index') ? 'active' : '' }}"
                                href="{{ route('cards.index') }}">
                                <i class="bi bi-collection-fill me-1"></i> Collezione
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('collection.*') ? 'active' : '' }}"
                                href="{{ route('collection.value') }}">
                                <i class="bi bi-currency-dollar me-1"></i> Valore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('matching.*') ? 'active' : '' }}"
                                href="{{ route('matching.index') }}">
                                <i class="bi bi-link-45deg me-1"></i> Matching
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('market-data.*') ? 'active' : '' }}"
                                href="{{ route('market-data.index') }}">
                                <i class="bi bi-cloud-upload me-1"></i> Market Data
                            </a>
                        </li>
                    @endauth
                </ul>

                <ul class="navbar-nav">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                                href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Accedi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-pokemon btn-sm ms-2" href="{{ route('register') }}">
                                <i class="bi bi-person-plus me-1"></i> Registrati
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @if(Auth::user()->avatar)
                                    <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="rounded-circle me-2"
                                        style="width: 28px; height: 28px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center me-2"
                                        style="width: 28px; height: 28px;">
                                        <i class="bi bi-person-fill text-dark small"></i>
                                    </div>
                                @endif
                                {{ Auth::user()->display_name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.show') }}">
                                        <i class="bi bi-person me-2"></i>Il Mio Profilo
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Esci
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="pokeball-loader"></div>
            <p class="mt-4 text-white fw-medium">Analizzando la carta...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Main Content -->
    <main class="main-container fade-in">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <p>
                    <i class="bi bi-stars me-1"></i>
                    <strong>Card Scanner</strong>
                </p>
                <p class="footer-version mt-2 mt-md-0">
                    v1.0.0 &bull; Made with <i class="bi bi-heart-fill text-danger"></i> for collectors
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global utility functions
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();

            const iconClass = type === 'success' ? 'bi-check-circle-fill text-success' :
                type === 'warning' ? 'bi-exclamation-triangle-fill text-warning' :
                    type === 'info' ? 'bi-info-circle-fill text-info' :
                        'bi-exclamation-circle-fill text-danger';
            const title = type === 'success' ? 'Successo' :
                type === 'warning' ? 'Attenzione' :
                    type === 'info' ? 'Info' : 'Errore';

            const toastHtml = `
                <div id="${toastId}" class="toast" role="alert">
                    <div class="toast-header">
                        <i class="bi ${iconClass} me-2"></i>
                        <strong class="me-auto">${title}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHtml);

            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, {
                delay: 5000
            });
            toast.show();

            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        // Get type class from type name
        function getTypeClass(type) {
            if (!type) return 'type-colorless';
            const typeMap = {
                'fuoco': 'type-fuoco',
                'fire': 'type-fire',
                'acqua': 'type-acqua',
                'water': 'type-water',
                'erba': 'type-erba',
                'grass': 'type-grass',
                'elettro': 'type-elettro',
                'electric': 'type-electric',
                'psico': 'type-psico',
                'psychic': 'type-psychic',
                'lotta': 'type-lotta',
                'fighting': 'type-fighting',
                'buio': 'type-buio',
                'dark': 'type-dark',
                'acciaio': 'type-acciaio',
                'steel': 'type-steel',
                'drago': 'type-drago',
                'dragon': 'type-dragon',
                'normale': 'type-normale',
                'normal': 'type-normal',
                'incolore': 'type-incolore',
                'colorless': 'type-colorless',
                'fairy': 'type-fairy'
            };
            return typeMap[type.toLowerCase()] || 'type-colorless';
        }
    </script>

    @stack('scripts')
</body>

</html>