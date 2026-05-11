@extends('layouts.app')

@section('title', 'Carte Mancanti')

@section('content')
    <main class="py-5">
        <div class="container py-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="text-white fw-bold display-5 mb-2">{{ __('Le tue Carte Mancanti') }}</h1>
                    <p class="text-secondary fs-5 mb-0">{{ __('Hai un totale di') }} <span class="text-white fw-bold">{{ $total }}</span> {{ __('carte mancanti nei tuoi set collezionati.') }}</p>
                </div>
                
                @php
                    $hasActiveFilters = request()->hasAny(['search', 'type', 'stage', 'set', 'serie']);
                @endphp
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-secondary d-flex align-items-center gap-2 {{ $hasActiveFilters ? 'active' : '' }}"
                        type="button" data-bs-toggle="collapse" data-bs-target="#filterDrawer" aria-expanded="false"
                        aria-controls="filterDrawer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-funnel" viewBox="0 0 16 16">
                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .4.8l-4.5 6.5V13.5a.5.5 0 0 1-.8.4L7 10.7l-2.1 3.2a.5.5 0 0 1-.8-.4V8.8L1.6 1.8A.5.5 0 0 1 1.5 1.5z" />
                        </svg>
                        {{ __('Filtri') }}
                    </button>
                </div>
            </div>

            <div class="collapse mb-4 {{ $hasActiveFilters ? 'show' : '' }}" id="filterDrawer">
                <div class="card card-body filter-card border bg-dark border-secondary rounded-4">
                    <form id="filter-form" method="GET" action="{{ route('collezioni.mancanti') }}">
                        <input type="hidden" name="page" value="1" id="filter-page" />
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="search" class="form-label text-secondary small text-uppercase">{{ __('Nome carta') }}</label>
                                <input id="search" name="search" type="text"
                                    class="form-control bg-dark text-white border-secondary" value="{{ request('search') }}"
                                    placeholder="{{ __('Cerca nome carta...') }}" />
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="type" class="form-label text-secondary small text-uppercase">{{ __('Tipo Pokémon') }}</label>
                                <select id="type" name="type" class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutti i tipi') }}</option>
                                    @foreach ($typeOptions as $typeOption)
                                        <option value="{{ $typeOption }}" @selected(request('type') === $typeOption)>{{ $typeOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="stage" class="form-label text-secondary small text-uppercase">{{ __('Stadio evolutivo') }}</label>
                                <select id="stage" name="stage" class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutti gli stadi') }}</option>
                                    @foreach ($stageOptions as $stageOption)
                                        <option value="{{ $stageOption }}" @selected(request('stage') === $stageOption)>{{ $stageOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="serie" class="form-label text-secondary small text-uppercase">{{ __('Serie') }}</label>
                                <select id="serie" name="serie" class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutte le serie') }}</option>
                                    @foreach ($serieOptions as $serieOption)
                                        <option value="{{ $serieOption->id }}" @selected(request('serie') == $serieOption->id)>{{ $serieOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="set" class="form-label text-secondary small text-uppercase">{{ __('Espansione') }}</label>
                                <select id="set" name="set" class="form-select bg-dark text-white border-secondary">
                                    <option value="">{{ __('Tutte le espansioni') }}</option>
                                    @foreach ($setOptions as $setOption)
                                        <option value="{{ $setOption->id }}" @selected(request('set') == $setOption->id)>{{ $setOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('collezioni.mancanti') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                                <button type="submit" class="btn btn-primary px-4 fw-bold">{{ __('Applica Filtri') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="cards-grid">
                @include('collezioni.partials.mancanti-global-grid', ['userCards' => $userCards])
            </div>

            <div class="d-flex justify-content-center mt-5">
                <button id="load-more-btn" type="button" class="btn btn-outline-light px-4 py-2 fw-bold"
                    @if ($userCards->currentPage() >= $userCards->lastPage()) style="display:none;" @endif>
                    {{ __('Carica altre carte') }}
                </button>
            </div>
        </div>
    </main>

    <div id="cards-pagination" data-current-page="{{ $userCards->currentPage() }}"
        data-last-page="{{ $userCards->lastPage() }}"
        data-load-url="{{ route('collezioni.mancanti') }}"></div>

    <script>
        function currentFilterParams() {
            var form = document.getElementById('filter-form');
            if (!form) return new URLSearchParams();
            return new URLSearchParams(new FormData(form));
        }

        function updateLoadMoreButton(page, lastPage) {
            var btn = document.getElementById('load-more-btn');
            if (!btn) return;
            if (page >= lastPage) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'block';
            }
        }

        document.getElementById('load-more-btn')?.addEventListener('click', function() {
            var pagination = document.getElementById('cards-pagination');
            var currentPage = parseInt(pagination.dataset.currentPage);
            var lastPage = parseInt(pagination.dataset.lastPage);

            if (currentPage < lastPage) {
                loadCards(currentPage + 1);
            }
        });

        async function loadCards(nextPage) {
            var pagination = document.getElementById('cards-pagination');
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
    </script>
@endsection
