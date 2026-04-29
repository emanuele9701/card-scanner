@extends('layouts.app')
@section('title', $set->name)
@section('meta_description', 'Dettaglio del set ' . $set->name)

@section('content')
    <style>
        .breadcrumb-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .breadcrumb-link:hover {
            color: #fff;
        }

        .set-header-icon {
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background-color: rgba(255, 255, 255, 0.04);
        }

        .selection-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(251, 180, 0, 0.3);
            background-color: rgba(5, 20, 36, 0.9);
            padding: 0.75rem 1.25rem;
            box-shadow: 0 0 32px rgba(251, 180, 0, 0.2);
            backdrop-filter: blur(16px);
        }

        .selection-bar-divider {
            width: 1px;
            height: 16px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-add-selection {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 9999px;
            background-color: #fbb400;
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #422c00;
            border: none;
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .btn-add-selection:hover {
            background-color: #ffd795;
        }

        .btn-clear-selection {
            border-radius: 9999px;
            padding: 0.375rem;
            color: rgba(212, 228, 250, 0.4);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-clear-selection:hover {
            color: #d4e4fa;
        }
    </style>

    {{-- Rotte esposte come variabili JS globali --}}
    @php $cardShowRoute = route('card.show', ':card'); @endphp
    @php $addCardToCollection = route('collezioni.cards.addToCollection', ':card'); @endphp

    <script>
        window._cardDetailRoute = '{{ $cardShowRoute }}';
        window._addCardRoute = '{{ $addCardToCollection }}';
    </script>

    <div class="position-relative container py-5" style="max-width:1280px;">

        {{-- Breadcrumb --}}
        <nav class="d-flex align-items-center gap-2 mb-4" style="font-size:0.875rem;">
            <a href="{{ route('collezioni.disponibili') }}" class="breadcrumb-link">Collezioni disponibili</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            @if ($set->serie)
                <span class="text-secondary">{{ $set->serie->name }}</span>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
            <span class="text-white">{{ $set->name }}</span>
        </nav>

        {{-- Page Header --}}
        <div class="d-flex align-items-center gap-3 mb-5">
            @if ($set->symbol)
                <div class="set-header-icon">
                    <img src="{{ $set->symbol }}.png" alt="Simbolo {{ $set->name }}"
                        style="width:32px;height:32px;object-fit:contain;">
                </div>
            @endif
            <div>
                <h1 class="text-white fw-bold mb-0" style="font-size:1.875rem; letter-spacing:-0.02em;">
                    {{ $set->name }}
                </h1>
                @if ($set->serie)
                    <p class="text-secondary mb-0 mt-1" style="font-size:0.875rem;">{{ $set->serie->name }}</p>
                @endif
            </div>
        </div>

        {{-- Grid carte --}}
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
            @foreach ($set->cards as $card)
                @include('collezioni.singles.cards', ['card' => $card])
            @endforeach
        </div>

        {{-- Barra flottante selezione multipla --}}
        <div id="selection-bar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4"
            style="z-index:1040; display:none; opacity:0; transform:translateY(16px);
                    transition: opacity 0.3s ease, transform 0.3s ease; pointer-events:none;">
            <div class="selection-bar">
                <span style="font-size:0.875rem; font-weight:600; color:#ffd795;">
                    <span id="selection-count">0</span> carte selezionate
                </span>
                <div class="selection-bar-divider"></div>
                <button onclick="addToCollection([..._selected])" class="btn-add-selection">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Aggiungi alla collezione
                </button>
                <div class="selection-bar-divider"></div>
                <button onclick="clearSelection()" class="btn-clear-selection" title="Annulla selezione">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    <script>
        var _selected = [];

        function cardToggleSelect(cardId) {
            var cb = document.getElementById('card-cb-' + cardId);
            var idx = _selected.indexOf(cardId);
            if (idx >= 0) {
                _selected.splice(idx, 1);
                if (cb) cb.dataset.selected = 'false';
            } else {
                _selected.push(cardId);
                if (cb) cb.dataset.selected = 'true';
            }
            _updateSelectionBar();
        }

        function _updateSelectionBar() {
            var bar = document.getElementById('selection-bar');
            var countEl = document.getElementById('selection-count');
            if (!bar) return;
            if (_selected.length > 0) {
                if (countEl) countEl.textContent = _selected.length;
                bar.style.display = '';
                bar.style.pointerEvents = 'auto';
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        bar.style.opacity = '1';
                        bar.style.transform = 'translateY(0)';
                    });
                });
            } else {
                bar.style.opacity = '0';
                bar.style.transform = 'translateY(16px)';
                bar.style.pointerEvents = 'none';
                setTimeout(function() {
                    if (_selected.length === 0) bar.style.display = 'none';
                }, 300);
            }
        }

        function clearSelection() {
            _selected.forEach(function(id) {
                var cb = document.getElementById('card-cb-' + id);
                if (cb) cb.dataset.selected = 'false';
            });
            _selected = [];
            _updateSelectionBar();
        }

        async function addToCollection(cardIds) {
            const uri = window._addCardRoute.replace(':card', cardIds);
            await fetch(uri, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    card_ids: cardIds
                }),
            });
            clearSelection();
        }
    </script>

@endsection
