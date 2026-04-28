<!DOCTYPE html>
<html lang="it" class="dark">

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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('custom_style')
</head>

<body class="bg-gray-950 text-gray-100 font-sans min-h-screen flex flex-col antialiased">

    {{-- ─── Navbar ─────────────────────────────────────────────────────── --}}
    <nav id="main-navbar"
        class="sticky top-0 z-50 w-full border-b border-white/[0.06] bg-gray-950/80 backdrop-blur-xl supports-[backdrop-filter]:bg-gray-950/60">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            {{-- Logo / Brand --}}
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-2 text-lg font-bold tracking-tight text-white transition hover:opacity-80">
                {{-- Pokéball-inspired icon --}}
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-rose-600 shadow-lg shadow-red-500/25">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
                Card Scanner
            </a>

            {{-- Navigation Links --}}
            <div class="flex items-center gap-1">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}" id="nav-dashboard"
                    class="nav-link group flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/[0.06] hover:text-white' }}">
                    <svg class="h-4 w-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
                    </svg>
                    Dashboard
                </a>

                {{-- Collezioni Dropdown --}}
                <div class="relative" id="nav-collezioni-dropdown">
                    <button type="button" id="nav-collezioni-btn"
                        class="nav-link group flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200
                                   {{ request()->routeIs('collezioni.*') ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/[0.06] hover:text-white' }}"
                        onclick="toggleDropdown()">
                        <svg class="h-4 w-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Collezioni
                        <svg class="h-3 w-3 transition-transform duration-200" id="dropdown-chevron" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div id="dropdown-menu"
                        class="absolute right-0 top-full mt-2 hidden w-56 origin-top-right animate-dropdown rounded-xl border border-white/[0.08] bg-gray-900/95 p-1.5 shadow-2xl shadow-black/40 backdrop-blur-xl">
                        <a href="{{ route('collezioni.mie') }}" id="nav-mie-collezioni"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 transition-all duration-150 hover:bg-white/[0.08] hover:text-white">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </span>
                            Le mie collezioni
                        </a>
                        <a href="{{ route('collezioni.disponibili') }}" id="nav-collezioni-disponibili"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 transition-all duration-150 hover:bg-white/[0.08] hover:text-white">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            Collezioni disponibili
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- ─── Main Content ──────────────────────────────────────────────── --}}
    <main class="flex-1">
        @yield('content')
    </main>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- ─── Dropdown Script ───────────────────────────────────────────── --}}
    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('nav-collezioni-dropdown');
            const menu = document.getElementById('dropdown-menu');
            const chevron = document.getElementById('dropdown-chevron');
            if (dropdown && !dropdown.contains(e.target)) {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        });
    </script>
</body>

</html>
