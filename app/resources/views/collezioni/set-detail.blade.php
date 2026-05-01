@extends('layouts.app')
@section('title', $set->name)
@section('meta_description', 'Dettaglio del set ' . $set->name)

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
            $hasActiveFilters = request()->filled('search') || request()->filled('type') || request()->filled('stage') || (request()->has('sort') && request('sort') !== 'dex_asc') || (request()->has('per_page') && request('per_page') != 10);
        @endphp

        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a class="text-decoration-none text-secondary" href="{{ route('collezioni.disponibili') }}">Collezioni</a></li>
                    @if ($set->serie)
                        <li class="breadcrumb-item"><a class="text-decoration-none text-secondary" href="#">{{ $set->serie->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $set->name }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 text-white mb-2">{{ $set->name }}</h1>
                    <p class="text-secondary mb-0">Serie {{ $set->serie?->name ?? 'Senza serie' }} • {{ $cards->total() }} carte</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-light {{ $hasActiveFilters ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#filterDrawer" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="filterDrawer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .4.8l-4.5 6.5V13.5a.5.5 0 0 1-.8.4L7 10.7l-2.1 3.2a.5.5 0 0 1-.8-.4V8.8L1.6 1.8A.5.5 0 0 1 1.5 1.5z"/>
                        </svg>
                        Filtri
                    </button>
                    <button class="btn btn-outline-secondary disabled" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sort-down" viewBox="0 0 16 16">
                            <path d="M3.5 2.5a.5.5 0 0 1 .5.5v8.793l1.146-1.147a.5.5 0 1 1 .708.708l-2 2a.498.498 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L3 11.793V3a.5.5 0 0 1 .5-.5z"/>
                            <path d="M5 12.5a.5.5 0 0 0 .5.5h7.793l-1.147 1.146a.5.5 0 0 0 .708.708l2-2a.498.498 0 0 0 0-.708l-2-2a.5.5 0 0 0-.708.708L13.293 13H5.5a.5.5 0 0 0-.5.5z"/>
                        </svg>
                        Ordina
                    </button>
                </div>
            </div>

            <div class="collapse mb-4 {{ $hasActiveFilters ? 'show' : '' }}" id="filterDrawer">
                <div class="card card-body filter-card border">
                    <form id="filter-form" method="GET" action="{{ route('collezioni.set', ['set' => $set->id]) }}">
                        <input type="hidden" name="page" value="1" id="filter-page" />
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="search" class="form-label text-secondary small text-uppercase">Nome carta</label>
                                <input id="search" name="search" type="text" class="form-control bg-dark text-white border-secondary" value="{{ request('search') }}" placeholder="Cerca nome carta..." />
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label text-secondary small text-uppercase">Tipo Pokémon</label>
                                <select id="type" name="type" class="form-select bg-dark text-white border-secondary">
                                    <option value="">Tutti i tipi</option>
                                    @foreach ($typeOptions as $typeOption)
                                        <option value="{{ $typeOption }}" @selected(request('type') === $typeOption)>{{ $typeOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="stage" class="form-label text-secondary small text-uppercase">Stadio evolutivo</label>
                                <select id="stage" name="stage" class="form-select bg-dark text-white border-secondary">
                                    <option value="">Tutti gli stadi</option>
                                    @foreach ($stageOptions as $stageOption)
                                        <option value="{{ $stageOption }}" @selected(request('stage') === $stageOption)>{{ $stageOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sort" class="form-label text-secondary small text-uppercase">Ordina per</label>
                                <select id="sort" name="sort" class="form-select bg-dark text-white border-secondary">
                                    <option value="dex_asc" @selected(request('sort') === 'dex_asc')>Numero carta</option>
                                    <option value="dex_desc" @selected(request('sort') === 'dex_desc')>Numero carta (desc)</option>
                                    <option value="name_asc" @selected(request('sort') === 'name_asc')>Nome A → Z</option>
                                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Nome Z → A</option>
                                    <option value="rarity_asc" @selected(request('sort') === 'rarity_asc')>Rarità ascendente</option>
                                    <option value="rarity_desc" @selected(request('sort') === 'rarity_desc')>Rarità discendente</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="per_page" class="form-label text-secondary small text-uppercase">Carte per pagina</label>
                                <select id="per_page" name="per_page" class="form-select bg-dark text-white border-secondary">
                                    <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                                    <option value="15" @selected(request('per_page') == 15)>15</option>
                                    <option value="20" @selected(request('per_page') == 20)>20</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">Applica</button>
                                <span class="badge rounded-pill bg-secondary text-white">{{ $cards->total() }} risultati</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-12 col-xl-8">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="cards-grid">
                        @include('collezioni.partials.cards-grid', ['cards' => $cards])
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        <button id="load-more-btn" type="button" class="btn btn-outline-light px-4" @if($cards->currentPage() >= $cards->lastPage()) style="display:none;" @endif>
                            Carica altre carte
                        </button>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card summary-card h-100">
                        <div class="card-body">
                            <h2 class="h5 text-white">Riepilogo set</h2>
                            <p class="text-secondary">Controlla rapidamente il set e applica filtri sulla griglia.</p>
                            <div class="list-group list-group-flush mt-4">
                                <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">Carte totali</span>
                                    <span class="summary-value">{{ strtoupper($set->card_total ?? '—') }}</span>
                                </div>
                                <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">Lingua</span>
                                    <span class="summary-value">{{ strtoupper($set->language) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="cards-pagination" data-current-page="{{ $cards->currentPage() }}" data-last-page="{{ $cards->lastPage() }}" data-load-url="{{ route('collezioni.set', ['set' => $set->id]) }}"></div>

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
            var response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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

        window.addToCollection = async function(button, cardId) {
            if (!button) return;
            var textEl = button.querySelector('.btn-card-add-text');
            var loaderEl = button.querySelector('.btn-card-loader');
            var originalText = textEl ? textEl.textContent : button.textContent;

            button.classList.add('loading');
            if (textEl) textEl.textContent = 'Invio...';
            if (loaderEl) loaderEl.classList.remove('visually-hidden');

            var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
            var addUrl = '{{ route('collezioni.cards.addToCollection', ['card' => ':card']) }}'.replace(':card', cardId);

            try {
                var response = await fetch(addUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });

                var data = await response.json();
                if (!response.ok || !data.esito) {
                    throw new Error(data.message || 'Errore durante l\'aggiunta');
                }

                if (textEl) textEl.textContent = 'Aggiunta!';
                button.classList.add('btn-success');
                button.classList.remove('btn-card-add');
                button.disabled = true;
            } catch (error) {
                if (textEl) textEl.textContent = 'Errore';
                console.error('addToCollection:', error);
                setTimeout(function() {
                    if (textEl) textEl.textContent = originalText;
                }, 2000);
            } finally {
                if (loaderEl) loaderEl.classList.add('visually-hidden');
                button.classList.remove('loading');
            }
        };

        window._cardDetailRoute = "{{ route('cards.show', ['card' => ':card']) }}";

        document.getElementById('load-more-btn')?.addEventListener('click', loadMoreCards);
    </script>
@endsection
