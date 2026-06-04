@extends('layouts.app')
@section('title', __('La mia collezione:') . ' ' . $set->name)
@section('meta_description', __('Dettaglio della tua collezione nel set') . ' ' . $set->name)

@section('content')
    <style>
        .set-detail-shell {
            background: radial-gradient(circle at top, rgba(63, 82, 110, 0.12), transparent 28%), #050c16;
            min-height: calc(100vh - 80px);
            padding-top: 3rem;
            padding-bottom: 4rem;
        }

        .summary-card {
            background: rgba(14, 24, 44, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.15);
        }

        .summary-card .card-body {
            padding: 1.5rem;
        }

        .summary-label {
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(209, 213, 219, 0.55);
        }

        .summary-value {
            color: #ffffff;
            font-weight: 700;
        }

        .filter-card {
            background: rgba(12, 19, 33, 0.96);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.18);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.08);
        }
    </style>

    <main class="set-detail-shell">
        @php
            $hasActiveFilters = request()->filled('search') || request()->filled('type') || request()->filled('stage') || (request()->has('sort') && request('sort') !== 'dex_asc');
        @endphp

        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a class="text-decoration-none text-secondary"
                            href="{{ route('collezioni.mie') }}">{{ __('Le mie Collezioni') }}</a></li>
                    @if ($set->serie)
                        <li class="breadcrumb-item"><a class="text-decoration-none text-secondary"
                                href="#">{{ $set->serie->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $set->name }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 text-white mb-2">{{ $set->name }}</h1>
                    <p class="text-secondary mb-0">{{ __('La tua collezione') }} • {{ $userCards->total() }} {{ __('carte salvate in questo set') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-warning fw-bold text-dark px-3" type="button" onclick="openMissingCardsModal()">
                        {{ __('+ Aggiungi carte') }}
                    </button>
                    <button class="btn btn-outline-light {{ $hasActiveFilters ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                        data-bs-target="#filterDrawer" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="filterDrawer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-funnel" viewBox="0 0 16 16">
                            <path
                                d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .4.8l-4.5 6.5V13.5a.5.5 0 0 1-.8.4L7 10.7l-2.1 3.2a.5.5 0 0 1-.8-.4V8.8L1.6 1.8A.5.5 0 0 1 1.5 1.5z" />
                        </svg>
                        {{ __('Filtri') }}
                    </button>
                    <button class="btn btn-outline-secondary disabled" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-sort-down" viewBox="0 0 16 16">
                            <path
                                d="M3.5 2.5a.5.5 0 0 1 .5.5v8.793l1.146-1.147a.5.5 0 1 1 .708.708l-2 2a.498.498 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L3 11.793V3a.5.5 0 0 1 .5-.5z" />
                            <path
                                d="M5 12.5a.5.5 0 0 0 .5.5h7.793l-1.147 1.146a.5.5 0 0 0 .708.708l2-2a.498.498 0 0 0 0-.708l-2-2a.5.5 0 0 0-.708.708L13.293 13H5.5a.5.5 0 0 0-.5.5z" />
                        </svg>
                        {{ __('Ordina') }}
                    </button>
                </div>
            </div>

            <div class="collapse mb-4 {{ $hasActiveFilters ? 'show' : '' }}" id="filterDrawer">
                <div class="card card-body filter-card border">
                    <form id="filter-form" method="GET" action="{{ route('collezioni.mie.set', ['set' => $set->id]) }}">
                        <input type="hidden" name="page" value="1" id="filter-page" />
                        <input type="hidden" name="tab" value="{{ $tab ?? 'owned' }}" />
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="search" class="form-label text-secondary small text-uppercase">{{ __('Nome carta') }}
                                    </label>
                                <input id="search" name="search" type="text"
                                    class="form-control bg-dark text-white border-secondary" value="{{ request('search') }}"
                                    placeholder="{{ __('Cerca nome carta...') }}" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label text-secondary small text-uppercase">{{ __('Tipo Pokémon') }}
                                    </label>
                                <select id="type" name="type"
                                    class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutti i tipi') }}</option>
                                    @foreach ($typeOptions as $typeOption)
                                        <option value="{{ $typeOption }}" @selected(request('type') === $typeOption)>{{ $typeOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="stage" class="form-label text-secondary small text-uppercase">{{ __('Stadio evolutivo') }}
                                    </label>
                                <select id="stage" name="stage"
                                    class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutti gli stadi') }}</option>
                                    @foreach ($stageOptions as $stageOption)
                                        <option value="{{ $stageOption }}" @selected(request('stage') === $stageOption)>
                                            {{ $stageOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sort" class="form-label text-secondary small text-uppercase">{{ __('Ordina per') }}
                                    </label>
                                <select id="sort" name="sort"
                                    class="form-select bg-dark text-white border-secondary">
                                    <option value="dex_asc" @selected(request('sort') === 'dex_asc')>{{ __('Numero carta') }}</option>
                                    <option value="dex_desc" @selected(request('sort') === 'dex_desc')>{{ __('Numero carta (desc)') }}</option>
                                    <option value="name_asc" @selected(request('sort') === 'name_asc')>{{ __('Nome A → Z') }}</option>
                                    <option value="name_desc" @selected(request('sort') === 'name_desc')>{{ __('Nome Z → A') }}</option>
                                    <option value="rarity_asc" @selected(request('sort') === 'rarity_asc')>{{ __('Rarità ascendente') }}</option>
                                    <option value="rarity_desc" @selected(request('sort') === 'rarity_desc')>{{ __('Rarità discendente') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="per_page" class="form-label text-secondary small text-uppercase">{{ __('Carte per pagina') }}
                                    </label>
                                <select id="per_page" name="per_page"
                                    class="form-select bg-dark text-white border-secondary">
                                    <option value="100" @selected(request('per_page', 100) == 100)>100</option>
                                    <option value="200" @selected(request('per_page') == 200)>200</option>
                                    <option value="300" @selected(request('per_page') == 300)>300</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">{{ __('Applica') }}</button>
                                <span class="badge rounded-pill bg-secondary text-white">{{ $userCards->total() }}
                                    {{ __('risultati') }}</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-12 col-xl-8">
                    <ul class="nav nav-pills mb-4" id="collection-tabs">
                        <li class="nav-item">
                            <a class="nav-link collection-tab-link {{ ($tab ?? 'owned') === 'owned' ? 'active bg-primary text-white' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'owned', 'page' => 1]) }}" data-tab="owned">
                                {{ __('Le mie carte') }} <span class="badge {{ ($tab ?? 'owned') === 'owned' ? 'bg-white text-primary' : 'bg-secondary text-white' }} ms-2">{{ $ownedTotal ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link collection-tab-link {{ ($tab ?? 'owned') === 'missing' ? 'active bg-warning text-dark' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'missing', 'page' => 1]) }}" data-tab="missing">
                                {{ __('Carte Mancanti') }} <span class="badge {{ ($tab ?? 'owned') === 'missing' ? 'bg-dark text-warning' : 'bg-secondary text-white' }} ms-2">{{ $missingTotal ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link collection-tab-link {{ ($tab ?? 'owned') === 'doppie' ? 'active bg-success text-white' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'doppie', 'page' => 1]) }}" data-tab="doppie">
                                {{ __('Doppie') }} <span class="badge {{ ($tab ?? 'owned') === 'doppie' ? 'bg-white text-success' : 'bg-secondary text-white' }} ms-2">{{ $doppieTotal ?? 0 }}</span>
                            </a>
                        </li>
                    </ul>

                        <div class="d-flex justify-content-end mb-3" id="selectAllContainer" style="{{ ($tab ?? 'owned') === 'owned' ? '' : 'display: none !important;' }}">
                            <div class="form-check form-switch d-flex align-items-center gap-2 bg-dark p-2 px-3 rounded-pill border border-secondary shadow-sm">
                                <input class="form-check-input mt-0" type="checkbox" id="selectAllVisible" onchange="toggleSelectAllVisible(this)" style="cursor: pointer;">
                                <label class="form-check-label text-white small fw-bold text-uppercase" for="selectAllVisible" style="cursor: pointer;">{{ __('Seleziona Tutte Visibili') }}</label>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="cards-grid">
                            @include('collezioni.partials.my-cards-grid', ['userCards' => $userCards, 'tab' => $tab ?? 'owned'])
                        </div>

                    <div class="d-flex justify-content-center mt-4">
                        @php
                            $isPaginator = $userCards instanceof \Illuminate\Pagination\LengthAwarePaginator;
                        @endphp
                        <button id="load-more-btn" type="button" class="btn btn-outline-light px-4"
                            @if (!$isPaginator || ($isPaginator && $userCards->currentPage() >= $userCards->lastPage())) style="display:none;" @endif>
                            {{ __('Carica altro') }}
                        </button>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card summary-card h-100">
                        <div class="card-body">
                            <h2 class="h5 text-white">{{ __('Riepilogo collezione nel set') }}</h2>
                            <p class="text-secondary">{{ __('Informazioni sulla tua collezione per questo specifico set.') }}</p>
                            <div class="list-group list-group-flush mt-4">
                                <div
                                    class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">{{ __('Carte possedute (Uniche)') }}</span>
                                    <span class="summary-value">{{ $ownedTotal ?? 0 }} /
                                        {{ $set->card_total ?? '?' }}</span>
                                </div>
                                @php
                                    $uniqueOwnedQty = \App\Models\UserCardCollection::where('user_id', auth()->id())
                                        ->where('set_id', $set->id)
                                        ->distinct('card_id')
                                        ->count('card_id');
                                    $cardTotal = $set->card_official ?? ($set->card_total ?? 0);
                                    $progress =
                                        $cardTotal > 0 ? min(100, round(($uniqueOwnedQty / $cardTotal) * 100)) : 0;
                                @endphp
                                <div
                                    class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">{{ __('Completamento') }}</span>
                                    <span class="summary-value">{{ $progress }}%</span>
                                </div>
                                <div
                                    class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">{{ __('Lingua set') }}</span>
                                    <span class="summary-value">{{ strtoupper($set->language) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Mass Action Bar -->
    <div id="mass-action-bar" class="fixed-bottom p-3 d-flex justify-content-center" style="pointer-events: none; z-index: 1040; transition: transform 0.3s ease; transform: translateY(100%);">
        @if(($tab ?? 'owned') !== 'missing')
            <div class="card bg-dark text-white shadow-lg border border-secondary" style="pointer-events: auto; border-radius: 2rem;">
                <div class="card-body d-flex align-items-center justify-content-between py-2 px-4">
                    <span class="fw-bold me-3 text-nowrap"><span id="mass-selected-count" class="text-warning">0</span> {{ __('carte') }}</span>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button class="btn btn-outline-light btn-sm fw-bold rounded-pill d-flex align-items-center gap-1" onclick="openMassEditModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            {{ __('Modifica') }}
                        </button>
                        
                        <button class="btn btn-sm fw-bold rounded-pill text-dark d-flex align-items-center gap-1" style="background-color: #fbb400; border: none;" onclick="openMassAddModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            {{ __('Aggiungi Copie') }}
                        </button>
                        
                        <button class="btn btn-outline-danger btn-sm fw-bold rounded-pill d-flex align-items-center gap-1" onclick="massRemoveSelected()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            {{ __('Elimina') }}
                        </button>
                        
                        <div class="vr bg-secondary mx-1"></div>
                        
                        <button class="btn btn-sm text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" onclick="clearSelection()" title="{{ __('Annulla Selezione') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div id="cards-pagination" data-current-page="{{ $userCards->currentPage() }}"
        data-last-page="{{ $userCards->lastPage() }}"
        data-load-url="{{ route('collezioni.mie.set', ['set' => $set->id]) }}"></div>

    @include('partials._missing-cards-modal')
    @include('partials._mass-action-modals')

    <script>
        function currentFilterParams() {
            var form = document.getElementById('filter-form');
            return new URLSearchParams(new FormData(form));
        }

        function updateLoadMoreButton(page, lastPage) {
            var btn = document.getElementById('load-more-btn');
            if (!btn) return;
            btn.style.display = page >= lastPage ? 'none' : 'inline-flex';
        }

        async function loadMoreCards() {
            var pagination = document.getElementById('cards-pagination');
            var currentPage = parseInt(pagination.dataset.currentPage, 10);
            var lastPage = parseInt(pagination.dataset.lastPage, 10);
            var nextPage = currentPage + 1;
            if (nextPage > lastPage) return;

            var params = currentFilterParams();
            params.set('page', nextPage);
            params.set('ajax', 1);

            var url = pagination.dataset.loadUrl + '?' + params.toString();
            var response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await response.json();
            if (data.html) {
                var grid = document.getElementById('cards-grid');
                grid.insertAdjacentHTML('beforeend', data.html);
                pagination.dataset.currentPage = data.current_page;
                pagination.dataset.lastPage = data.last_page;
                updateLoadMoreButton(data.current_page, data.last_page);
                var pageInput = document.getElementById('filter-page');
                if (pageInput) pageInput.value = data.current_page;
            }
        }

        async function reloadCardsGrid(page = 1) {
            document.getElementById('cards-grid').innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-secondary mt-3">' + (window.__trans ? window.__trans.loading : 'Caricamento...') + '</p></div>';
            
            var params = currentFilterParams();
            params.set('page', page);
            params.set('ajax', 1);

            var pagination = document.getElementById('cards-pagination');
            var url = pagination.dataset.loadUrl + '?' + params.toString();
            
            try {
                var response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await response.json();
                if (data.html !== undefined) {
                    document.getElementById('cards-grid').innerHTML = data.html;
                    pagination.dataset.currentPage = data.current_page || 1;
                    pagination.dataset.lastPage = data.last_page || 1;
                    updateLoadMoreButton(data.current_page || 1, data.last_page || 1);
                    var pageInput = document.getElementById('filter-page');
                    if (pageInput) pageInput.value = data.current_page || 1;
                    clearSelection();
                }
            } catch (e) {
                console.error("AJAX Error:", e);
                // Fallback, reload page if ajax fails
                window.location.reload();
            }
        }

        document.querySelectorAll('.collection-tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update visuals
                document.querySelectorAll('.collection-tab-link').forEach(l => {
                    l.classList.remove('active', 'bg-primary', 'bg-warning', 'bg-success', 'text-white', 'text-dark');
                    l.classList.add('text-secondary');
                    l.querySelector('.badge').className = 'badge bg-secondary text-white ms-2';
                });
                
                let tabValue = this.dataset.tab;
                this.classList.remove('text-secondary');
                
                if(tabValue === 'owned') {
                    this.classList.add('active', 'bg-primary', 'text-white');
                    this.querySelector('.badge').className = 'badge bg-white text-primary ms-2';
                } else if(tabValue === 'missing') {
                    this.classList.add('active', 'bg-warning', 'text-dark');
                    this.querySelector('.badge').className = 'badge bg-dark text-warning ms-2';
                } else if(tabValue === 'doppie') {
                    this.classList.add('active', 'bg-success', 'text-white');
                    this.querySelector('.badge').className = 'badge bg-white text-success ms-2';
                }

                // Update hidden input & reload
                document.querySelector('input[name="tab"]').value = tabValue;
                
                let selectAllBtn = document.getElementById('selectAllContainer');
                if (selectAllBtn) {
                    if (tabValue === 'owned') {
                        selectAllBtn.style.setProperty('display', 'flex', 'important');
                    } else {
                        selectAllBtn.style.setProperty('display', 'none', 'important');
                    }
                }
                
                reloadCardsGrid(1);
            });
        });

        document.getElementById('filter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            reloadCardsGrid(1);
        });

        window._cardDetailRoute = "{{ route('cards.show', ['card' => ':card']) }}";

        window.removeEntireCard = async function(cardId, btnEl) {
            if (!confirm(window.__trans ? window.__trans.confirm_remove_card : 'Vuoi rimuovere completamente questa carta dalla tua collezione?')) return;
            
            try {
                const res = await fetch(`/collezioni/cards/${cardId}/collection`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (res.ok) {
                    const cardCol = btnEl.closest('.col');
                    if (cardCol) cardCol.remove();
                }
            } catch (e) {
                console.error(e);
            }
        };

        window.selectedCards = window.selectedCards || new Set();

        window.handleCardSelection = function(checkbox) {
            if (checkbox.checked) {
                window.selectedCards.add(checkbox.value);
                checkbox.closest('.card-item').style.borderColor = '#ef4444';
            } else {
                window.selectedCards.delete(checkbox.value);
                checkbox.closest('.card-item').style.borderColor = 'rgba(255, 255, 255, 0.08)';
            }
            updateMassActionBar();
        };

        window.updateMassActionBar = function() {
            const bar = document.getElementById('mass-action-bar');
            const countEl = document.getElementById('mass-selected-count');
            countEl.textContent = window.selectedCards.size;
            bar.style.transform = window.selectedCards.size > 0 ? 'translateY(0)' : 'translateY(100%)';
        };

        window.clearSelection = function() {
            window.selectedCards.clear();
            const checkboxes = document.querySelectorAll('.mass-select-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = false;
                const cardItem = cb.closest('.card-item');
                if(cardItem) cardItem.style.borderColor = 'rgba(255, 255, 255, 0.08)';
            });
            updateMassActionBar();
            var selectAllSwitch = document.getElementById('selectAllVisible');
            if(selectAllSwitch) selectAllSwitch.checked = false;
        };

        window.toggleSelectAllVisible = function(checkbox) {
            const cardCheckboxes = document.querySelectorAll('.mass-select-checkbox');
            cardCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
                if (checkbox.checked) {
                    window.selectedCards.add(cb.value);
                    cb.closest('.card-item').style.borderColor = '#ef4444';
                } else {
                    window.selectedCards.delete(cb.value);
                    cb.closest('.card-item').style.borderColor = 'rgba(255, 255, 255, 0.08)';
                }
            });
            updateMassActionBar();
        };

        window.massRemoveSelected = async function() {
            if (window.selectedCards.size === 0) return;
            if (!confirm(window.__trans ? window.__trans.confirm_remove_mass.replace(':count', window.selectedCards.size) : `Vuoi rimuovere ${window.selectedCards.size} carte dalla tua collezione?`)) return;
            
            try {
                const res = await fetch(`/collezioni/cards/mass-remove`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ card_ids: Array.from(window.selectedCards).map(Number) })
                });
                
                if (res.ok) {
                    window.selectedCards.forEach(id => {
                        const el = document.querySelector(`.mass-select-checkbox[value="${id}"]`);
                        if (el) {
                            const col = el.closest('.col');
                            if (col) col.remove();
                        }
                    });
                    clearSelection();
                }
            } catch (e) {
                console.error(e);
            }
        };

        document.getElementById('load-more-btn')?.addEventListener('click', loadMoreCards);
    </script>
@endsection
