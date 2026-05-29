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
                            <a class="nav-link {{ ($tab ?? 'owned') === 'owned' ? 'active bg-primary text-white' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'owned', 'page' => 1]) }}">
                                {{ __('Le mie carte') }} <span class="badge {{ ($tab ?? 'owned') === 'owned' ? 'bg-white text-primary' : 'bg-secondary text-white' }} ms-2">{{ $ownedTotal ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link {{ ($tab ?? 'owned') === 'missing' ? 'active bg-warning text-dark' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'missing', 'page' => 1]) }}">
                                {{ __('Carte Mancanti') }} <span class="badge {{ ($tab ?? 'owned') === 'missing' ? 'bg-dark text-warning' : 'bg-secondary text-white' }} ms-2">{{ $missingTotal ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link {{ ($tab ?? 'owned') === 'doppie' ? 'active bg-success text-white' : 'text-secondary' }} px-4 rounded-pill fw-medium" href="{{ request()->fullUrlWithQuery(['tab' => 'doppie', 'page' => 1]) }}">
                                {{ __('Doppie') }} <span class="badge {{ ($tab ?? 'owned') === 'doppie' ? 'bg-white text-success' : 'bg-secondary text-white' }} ms-2">{{ $doppieTotal ?? 0 }}</span>
                            </a>
                        </li>
                    </ul>

                        <div class="d-flex justify-content-end mb-3">
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
                                    <span class="summary-label">{{ __('Carte possedute') }}</span>
                                    <span class="summary-value">{{ $userCards->total() }} /
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
            <div class="card bg-danger text-white shadow-lg border-0" style="pointer-events: auto; max-width: 400px; border-radius: 2rem;">
                <div class="card-body d-flex align-items-center gap-3 py-2 px-4">
                    <span class="fw-bold"><span id="mass-selected-count">0</span> {{ __('carte selezionate') }}</span>
                    <button class="btn btn-light btn-sm text-danger fw-bold rounded-pill" onclick="massRemoveSelected()">{{ __('Elimina') }}</button>
                    <button class="btn btn-sm text-white text-opacity-75" onclick="clearSelection()">{{ __('Annulla') }}</button>
                </div>
            </div>
        @endif
    </div>

    <div id="cards-pagination" data-current-page="{{ $userCards->currentPage() }}"
        data-last-page="{{ $userCards->lastPage() }}"
        data-load-url="{{ route('collezioni.mie.set', ['set' => $set->id]) }}"></div>

    @include('partials._missing-cards-modal')

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
                if (pageInput) pageInput.value = 1;
            }
        }

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
                    if (cardCol) {
                        cardCol.remove();
                    }
                }
            } catch (e) {
                console.error(e);
            }
        };

        let selectedCards = new Set();

        window.handleCardSelection = function(checkbox) {
            if (checkbox.checked) {
                selectedCards.add(checkbox.value);
                checkbox.closest('.card-item').style.borderColor = '#ef4444';
            } else {
                selectedCards.delete(checkbox.value);
                checkbox.closest('.card-item').style.borderColor = 'rgba(255, 255, 255, 0.08)';
            }
            updateMassActionBar();
        };

        window.updateMassActionBar = function() {
            const bar = document.getElementById('mass-action-bar');
            const countEl = document.getElementById('mass-selected-count');
            countEl.textContent = selectedCards.size;
            
            if (selectedCards.size > 0) {
                bar.style.transform = 'translateY(0)';
            } else {
                bar.style.transform = 'translateY(100%)';
            }
        };

        window.clearSelection = function() {
            selectedCards.clear();
            document.querySelectorAll('.mass-select-checkbox').forEach(cb => {
                cb.checked = false;
                const cardItem = cb.closest('.card-item');
                if(cardItem) cardItem.style.borderColor = 'rgba(255, 255, 255, 0.08)';
            });
            const selectAllToggle = document.getElementById('selectAllVisible');
            if(selectAllToggle) selectAllToggle.checked = false;
            updateMassActionBar();
        };

        window.toggleSelectAllVisible = function(checkbox) {
            const isChecked = checkbox.checked;
            document.querySelectorAll('.mass-select-checkbox').forEach(cb => {
                if (cb.checked !== isChecked) {
                    cb.checked = isChecked;
                    handleCardSelection(cb);
                }
            });
        };



        window.massRemoveSelected = async function() {
            if (!confirm(window.__trans ? window.__trans.confirm_remove_mass.replace(':count', selectedCards.size) : `Vuoi rimuovere ${selectedCards.size} carte dalla tua collezione?`)) return;
            
            try {
                const res = await fetch(`/collezioni/cards/mass-remove`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ card_ids: Array.from(selectedCards) })
                });
                
                if (res.ok) {
                    selectedCards.forEach(id => {
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
