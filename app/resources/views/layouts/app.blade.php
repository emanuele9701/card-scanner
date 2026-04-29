<!DOCTYPE html>
<html lang="it" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Card Scanner') — Card Scanner</title>
    <meta name="description" content="@yield('meta_description', 'Gestisci la tua collezione di carte collezionabili.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #030712;
            font-family: 'Inter', sans-serif;
        }

        /* Navbar */
        #main-navbar {
            background-color: rgba(3, 7, 18, 0.8) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        }

        .navbar-brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            flex-shrink: 0;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #9ca3af;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-link-custom:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        .nav-link-custom.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* Dropdown */
        .nav-dropdown-menu {
            background-color: rgba(17, 24, 39, 0.97);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            padding: 0.375rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            min-width: 220px;
        }

        .nav-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.15s;
        }

        .nav-dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .nav-dropdown-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dropdown-chevron {
            transition: transform 0.2s;
        }

        .dropdown-chevron.rotated {
            transform: rotate(180deg);
        }
    </style>
    @yield('custom_style')
</head>

<body class="min-vh-100 d-flex flex-column">

    {{-- ─── Navbar ─────────────────────────────────────────────────────── --}}
    <nav id="main-navbar" class="navbar sticky-top border-bottom">
        <div class="container" style="max-width: 1280px;">
            <div class="d-flex w-100 align-items-center justify-content-between" style="height: 64px;">

                {{-- Logo / Brand --}}
                <a href="{{ route('dashboard') }}"
                    class="d-flex align-items-center gap-2 text-decoration-none text-white fw-bold"
                    style="font-size: 1.1rem; letter-spacing: -0.01em; transition: opacity 0.2s;"
                    onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                    <div class="navbar-brand-icon">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white"
                            stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    Card Scanner
                </a>

                {{-- Navigation Links --}}
                <div class="d-flex align-items-center gap-1">

                    {{-- Dashboard --}}
                    <a href="{{ route('dashboard') }}"
                        class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
                        </svg>
                        Dashboard
                    </a>

                    {{-- Collezioni Dropdown --}}
                    <div class="position-relative" id="nav-collezioni-dropdown">
                        <button type="button" id="nav-collezioni-btn"
                            class="nav-link-custom border-0 bg-transparent {{ request()->routeIs('collezioni.*') ? 'active' : '' }}"
                            onclick="toggleDropdown()">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Collezioni
                            <svg class="dropdown-chevron" id="dropdown-chevron" width="12" height="12"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div id="dropdown-menu" class="nav-dropdown-menu position-absolute end-0 mt-2"
                            style="display: none; top: 100%;">
                            <a href="{{ route('collezioni.mie') }}" class="nav-dropdown-item">
                                <div class="nav-dropdown-icon" style="background-color: rgba(99,102,241,0.1);">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                        stroke="#818cf8" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                </div>
                                Le mie collezioni
                            </a>
                            <a href="{{ route('collezioni.disponibili') }}" class="nav-dropdown-item">
                                <div class="nav-dropdown-icon" style="background-color: rgba(16,185,129,0.1);">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                        stroke="#34d399" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                Collezioni disponibili
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </nav>

    {{-- ─── Main Content ──────────────────────────────────────────────── --}}
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            const isHidden = menu.style.display === 'none' || menu.style.display === '';
            menu.style.display = isHidden ? 'block' : 'none';
            chevron.classList.toggle('rotated', isHidden);
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('nav-collezioni-dropdown');
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            if (dropdown && !dropdown.contains(e.target)) {
                menu.style.display = 'none';
                chevron.classList.remove('rotated');
            }
        });
    </script>
</body>

</html>
