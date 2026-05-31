<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PokeStash') — PokeStash</title>
    <meta name="description" content="@yield('meta_description', __('Gestisci la tua collezione di carte collezionabili.'))">
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

        /* Fix Tailwind CSS conflict with Bootstrap's collapse */
        .collapse.show {
            visibility: visible !important;
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

        .nav-user-avatar {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #ef4444, #e11d48);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
    </style>
    @yield('custom_style')
</head>

<body class="min-vh-100 d-flex flex-column">
    @include('partials._card–modal')
    @include('partials._manage-collection-modal')

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
                    PokeStash
                </a>

                {{-- Search Bar --}}
                <form action="{{ route('cards.search') }}" method="GET" class="d-none d-md-flex mx-4 position-relative" style="flex-grow: 1; max-width: 800px;" id="nav-search-form">
                    <div class="input-group input-group-sm position-relative">
                        <span class="input-group-text border-0" style="background-color: rgba(255, 255, 255, 0.05); color: #9ca3af; border-radius: 0.5rem 0 0 0.5rem;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="nav-search-input" name="q" class="form-control border-0 text-white shadow-none" style="background-color: rgba(255, 255, 255, 0.05); border-radius: 0 0.5rem 0.5rem 0;" placeholder="{{ __('Cerca carte...') }}" value="{{ request('q') }}" autocomplete="off">
                        
                        <!-- Autocomplete Dropdown -->
                        <div id="nav-search-dropdown" class="position-absolute w-100 bg-dark rounded-3 shadow-lg d-none" style="top: 100%; left: 0; z-index: 1050; max-height: 400px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); margin-top: 8px;">
                            <div id="nav-search-results" class="list-group list-group-flush rounded-3"></div>
                            <div id="nav-search-loading" class="p-3 text-center text-secondary d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <small>{{ __('Caricamento...') }}</small>
                            </div>
                        </div>
                    </div>
                </form>

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
                        {{ __('Dashboard') }}
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
                            {{ __('Collezioni') }}
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
                                {{ __('Le mie collezioni') }}
                            </a>

                            <a href="{{ route('collezioni.disponibili') }}" class="nav-dropdown-item">
                                <div class="nav-dropdown-icon" style="background-color: rgba(16,185,129,0.1);">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                        stroke="#34d399" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                {{ __('Collezioni disponibili') }}
                            </a>
                        </div>
                    </div>

                    {{-- User Dropdown --}}
                    <div class="position-relative" id="nav-user-dropdown">
                        <button type="button" id="nav-user-btn"
                            class="nav-link-custom border-0 bg-transparent {{ request()->routeIs('profile.*', 'settings.*') ? 'active' : '' }}"
                            onclick="toggleUserDropdown()">
                            <div class="nav-user-avatar">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="d-none d-sm-inline">{{ Auth::user()->name }}</span>
                            <svg class="dropdown-chevron" id="user-dropdown-chevron" width="12" height="12"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- User Dropdown Menu --}}
                        <div id="user-dropdown-menu" class="nav-dropdown-menu position-absolute end-0 mt-2"
                            style="display: none; top: 100%;">

                            {{-- Header utente --}}
                            <div class="px-3 py-2 mb-1">
                                <p class="mb-0 text-white fw-semibold" style="font-size:0.875rem;">{{ Auth::user()->name }}</p>
                                <p class="mb-0" style="font-size:0.75rem; color:#6b7280;">{{ __('Gestisci il tuo account') }}</p>
                            </div>

                            <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.25rem 0;"></div>

                            {{-- Profilo --}}
                            <a href="{{ route('profile.edit') }}" class="nav-dropdown-item">
                                <div class="nav-dropdown-icon" style="background-color: rgba(99,102,241,0.1);">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                        stroke="#818cf8" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                {{ __('Profilo') }}
                            </a>

                            {{-- Impostazioni --}}
                            <a href="{{ route('settings.index') }}" class="nav-dropdown-item">
                                <div class="nav-dropdown-icon" style="background-color: rgba(245,158,11,0.1);">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                        stroke="#f59e0b" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 008.5 4.6 1.65 1.65 0 0010 3.09V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 8.5a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
                                    </svg>
                                </div>
                                {{ __('Impostazioni') }}
                            </a>

                            <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.25rem 0;"></div>

                            {{-- Logout --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="nav-dropdown-item w-100 border-0 bg-transparent text-start" style="cursor:pointer;">
                                    <div class="nav-dropdown-icon" style="background-color: rgba(239,68,68,0.1);">
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                            stroke="#ef4444" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </div>
                                    {{ __('Esci') }}
                                </button>
                            </form>
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
    <script>
        // ── Collezioni Dropdown ──
        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            const isHidden = menu.style.display === 'none' || menu.style.display === '';
            menu.style.display = isHidden ? 'block' : 'none';
            chevron.classList.toggle('rotated', isHidden);

            // Chiudi l'altro dropdown
            closeUserDropdown();
        }

        // ── User Dropdown ──
        function toggleUserDropdown() {
            const menu = document.getElementById('user-dropdown-menu');
            const chevron = document.getElementById('user-dropdown-chevron');
            const isHidden = menu.style.display === 'none' || menu.style.display === '';
            menu.style.display = isHidden ? 'block' : 'none';
            chevron.classList.toggle('rotated', isHidden);

            // Chiudi l'altro dropdown
            closeCollezioniDropdown();
        }

        function closeUserDropdown() {
            const menu = document.getElementById('user-dropdown-menu');
            const chevron = document.getElementById('user-dropdown-chevron');
            if (menu) {
                menu.style.display = 'none';
                chevron.classList.remove('rotated');
            }
        }

        function closeCollezioniDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            if (menu) {
                menu.style.display = 'none';
                chevron.classList.remove('rotated');
            }
        }

        document.addEventListener('click', function(e) {
            const collezioniDropdown = document.getElementById('nav-collezioni-dropdown');
            const userDropdown = document.getElementById('nav-user-dropdown');

            if (collezioniDropdown && !collezioniDropdown.contains(e.target)) {
                closeCollezioniDropdown();
            }
            if (userDropdown && !userDropdown.contains(e.target)) {
                closeUserDropdown();
            }
        });
    </script>

    <script>
        window.__trans = {
            loading: @json(__('Caricamento...')),
            sending: @json(__('Invio...')),
            added: @json(__('Aggiunta!')),
            error: @json(__('Errore')),
            add: @json(__('Aggiungi')),
            remove: @json(__('Rimuovi')),
            no_abilities: @json(__('Nessuna abilità')),
            no_price: @json(__('Nessun prezzo disponibile')),
            no_copies: @json(__('Nessuna copia in collezione.')),
            confirm_remove_copy: @json(__('Vuoi rimuovere questa copia?')),
            confirm_remove_card: @json(__('Vuoi rimuovere completamente questa carta dalla tua collezione?')),
            confirm_remove_mass: @json(__('Vuoi rimuovere :count carte dalla tua collezione?')),
            save_error: @json(__('Errore durante il salvataggio.')),
            add_error: @json(__('Errore durante l\'aggiunta')),
            pokemon_added: @json(__(':name #:number aggiunto'))
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('nav-search-input');
            const searchDropdown = document.getElementById('nav-search-dropdown');
            const searchResults = document.getElementById('nav-search-results');
            const searchLoading = document.getElementById('nav-search-loading');
            let debounceTimer;

            if (!searchInput) return;

            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                
                clearTimeout(debounceTimer);
                
                if (query.length < 2) {
                    searchDropdown.classList.add('d-none');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    searchDropdown.classList.remove('d-none');
                    searchResults.innerHTML = '';
                    searchLoading.classList.remove('d-none');

                    fetch(`{{ route('cards.autocomplete') }}?q=${encodeURIComponent(query)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        searchLoading.classList.add('d-none');
                        searchResults.innerHTML = '';
                        
                        if (data.length === 0) {
                            searchResults.innerHTML = `<div class="p-3 text-center text-secondary small">{{ __('Nessun risultato trovato') }}</div>`;
                            return;
                        }

                        data.forEach(card => {
                            const rarityStyle = card.rarity.toLowerCase().includes('ultra') || card.rarity.toLowerCase().includes('secret') 
                                ? 'color:#ffb3b1;' : (card.rarity.toLowerCase().includes('rare') ? 'color:#ffd795;' : 'color:#a0b4cc;');
                            
                            const setImg = card.set_symbol 
                                ? `<img src="${card.set_symbol}" alt="" style="height:12px; margin-right:4px; filter: drop-shadow(0px 1px 1px rgba(0,0,0,0.5));">`
                                : '';
                                
                            let flagSvg = '';
                            if (card.language === 'it') flagSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" width="12" height="9" style="border-radius:1px; flex-shrink:0;"><path fill="#009246" d="M0 0h1v2H0z"/><path fill="#fff" d="M1 0h1v2H1z"/><path fill="#ce2b37" d="M2 0h1v2H2z"/></svg>`;
                            else if (card.language === 'en') flagSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="12" height="9" style="border-radius:1px; flex-shrink:0;"><clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg>`;
                            else if (card.language === 'jp') flagSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="12" height="9" style="border-radius:1px; flex-shrink:0;"><rect width="900" height="600" fill="#fff"/><circle cx="450" cy="300" r="180" fill="#bc002d"/></svg>`;

                            const a = document.createElement('a');
                            a.href = `{{ route('cards.search') }}?q=${encodeURIComponent(card.name + ' ' + card.dexId)}`; // Navigate to card specifically
                            // If they just click, they will search for this exact card. Or we can open the modal. For now, doing a specific search is fine.
                            a.className = 'list-group-item list-group-item-action bg-transparent text-white border-0 border-bottom d-flex align-items-center gap-3 py-2';
                            a.style.borderColor = 'rgba(255,255,255,0.05) !important';
                            
                            // Let's open the modal directly! It's much cooler.
                            a.onclick = (ev) => {
                                ev.preventDefault();
                                searchDropdown.classList.add('d-none');
                                openModal({ id: card.id, name: card.name, image: card.image ? card.image.replace('/low.png', '') : null });
                            };

                            a.innerHTML = `
                                <div style="width: 35px; height: 48px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; flex-shrink: 0;" class="d-flex align-items-center justify-content-center">
                                    ${card.image ? `<img src="${card.image}" style="width:100%; height:100%; object-fit:contain;" loading="lazy" onerror="this.style.display='none'">` : '<span style="font-size:10px; color:#666;">{{ __("No img") }}</span>'}
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        ${flagSvg}
                                        <div class="fw-bold text-truncate" style="font-size: 0.85rem;">${card.name}</div>
                                        <div class="ms-auto font-monospace text-secondary" style="font-size:0.7rem;">#${card.dexId}</div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-secondary text-truncate d-flex align-items-center" style="font-size: 0.7rem;">
                                            ${setImg} ${card.set_name || '{{ __("Sconosciuto") }}'}
                                        </div>
                                        <div style="font-size: 0.7rem; font-weight: 600; ${rarityStyle}">
                                            ${card.rarity}
                                        </div>
                                    </div>
                                </div>
                            `;
                            searchResults.appendChild(a);
                        });
                        
                        // Add "See all results" link
                        const seeAll = document.createElement('a');
                        seeAll.href = `{{ route('cards.search') }}?q=${encodeURIComponent(query)}`;
                        seeAll.className = 'list-group-item list-group-item-action bg-transparent text-center text-primary border-0 py-2 py-2 fw-semibold';
                        seeAll.style.fontSize = '0.8rem';
                        seeAll.innerHTML = `{{ __('Vedi tutti i risultati') }} &rarr;`;
                        searchResults.appendChild(seeAll);
                    })
                    .catch(err => {
                        searchLoading.classList.add('d-none');
                        searchResults.innerHTML = `<div class="p-3 text-center text-danger small">{{ __('Errore di connessione') }}</div>`;
                    });
                }, 300);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.classList.add('d-none');
                }
            });
            
            // Show dropdown when clicking input if there's a value
            searchInput.addEventListener('focus', function(e) {
                if (e.target.value.trim().length >= 2 && searchResults.innerHTML !== '') {
                    searchDropdown.classList.remove('d-none');
                }
            });
        });
    </script>
    <!-- Global Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3 mt-5" id="global-toast-container" style="z-index: 1080;"></div>

    <script>
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('global-toast-container');
            if (!container) return;

            const bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
            const icon = type === 'success' 
                ? '<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>'
                : '<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';

            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2 fw-medium" style="font-size: 0.95rem;">
                        ${icon}
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            container.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        };
    </script>
</body>

</html>
