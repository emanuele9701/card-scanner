@extends('layouts.app')
@section('title', __('Risultati Ricerca'))

@section('content')
    <style>
        .search-results-shell {
            background: radial-gradient(circle at top, rgba(63, 82, 110, 0.12), transparent 28%), #050c16;
            min-height: calc(100vh - 80px);
            padding-top: 3rem;
            padding-bottom: 4rem;
        }
        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.18);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.08);
        }
    </style>

    <main class="search-results-shell">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 text-white mb-2">{{ __('Risultati per') }}: "{{ $q }}"</h1>
                    <p class="text-secondary mb-0">{{ $cards->total() }} {{ __('carte trovate') }}</p>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-12">
                    @if($cards->isEmpty())
                        <div class="text-center py-5">
                            <h3 class="text-white">{{ __('Nessun risultato trovato') }}</h3>
                            <p class="text-secondary">{{ __('Prova a cercare con un altro nome.') }}</p>
                        </div>
                    @else
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="cards-grid">
                            @include('collezioni.partials.cards-grid', ['cards' => $cards])
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button id="load-more-btn" type="button" class="btn btn-outline-light px-4" @if($cards->currentPage() >= $cards->lastPage()) style="display:none;" @endif>
                                {{ __('Carica altre carte') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <div id="cards-pagination" data-current-page="{{ $cards->currentPage() }}" data-last-page="{{ $cards->lastPage() }}" data-load-url="{{ route('cards.search', ['q' => $q]) }}"></div>

    <script>
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

            var url = pagination.dataset.loadUrl + '&page=' + nextPage + '&ajax=1';
            var response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            var data = await response.json();
            if (data.html) {
                var grid = document.getElementById('cards-grid');
                grid.insertAdjacentHTML('beforeend', data.html);
                pagination.dataset.currentPage = data.current_page;
                pagination.dataset.lastPage = data.last_page;
                updateLoadMoreButton(data.current_page, data.last_page);
            }
        }

        document.getElementById('load-more-btn')?.addEventListener('click', loadMoreCards);
        
        window.addToCollection = async function(button, cardId) {
            if (!button) return;
            var textEl = button.querySelector('.btn-card-add-text');
            var loaderEl = button.querySelector('.btn-card-loader');
            var originalText = textEl ? textEl.textContent : button.textContent;

            button.classList.add('loading');
            if (textEl) textEl.textContent = window.__trans ? window.__trans.sending : 'Invio...';
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
                    throw new Error(data.message || (window.__trans ? window.__trans.add_error : 'Errore durante l\'aggiunta'));
                }

                if (textEl) textEl.textContent = window.__trans ? window.__trans.added : 'Aggiunta!';
                button.classList.add('btn-success');
                button.classList.remove('btn-card-add');
                button.disabled = true;
            } catch (error) {
                if (textEl) textEl.textContent = window.__trans ? window.__trans.error : 'Errore';
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
    </script>
@endsection
