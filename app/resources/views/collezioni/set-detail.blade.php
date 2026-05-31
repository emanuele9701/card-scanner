@extends('layouts.app')
@section('title', $set->name)
@section('meta_description', __('Dettaglio del set') . ' ' . $set->name)

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

        .fast-search-wrap {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .fast-search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .fast-search-input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .fast-search-input:focus {
            border-color: rgba(251, 180, 0, 0.5);
            box-shadow: 0 0 0 3px rgba(251, 180, 0, 0.1);
        }

        .fast-search-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.35);
            pointer-events: none;
        }

        .fast-search-count {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .fast-search-clear {
            position: absolute;
            right: 5.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            padding: 4px;
            display: none;
            line-height: 1;
        }

        .fast-search-clear:hover {
            color: #fff;
        }

        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.18);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .no-results-msg {
            text-align: center;
            padding: 3rem 1rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .no-results-msg svg {
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* Card Component Styles */
        .card-item {
            width: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 1.5rem;
            cursor: pointer;
            aspect-ratio: 1.05 / 1.35;
            max-height: 380px;
            background: linear-gradient(135deg, #142135 0%, #0b1522 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
        }

        .card-item:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28), 0 6px 22px rgba(36, 56, 86, 0.28);
            border-color: rgba(255, 255, 255, 0.16);
        }

        .card-glass-highlight {
            pointer-events: none;
            position: absolute;
            inset: 0;
            z-index: 10;
            border-radius: 1.5rem;
            background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.08), transparent 28%);
        }

        .card-image-area {
            position: relative;
            min-height: 140px;
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(15, 28, 47, 0.9) 0%, rgba(6, 15, 28, 0.98) 100%);
        }

        .card-symbol-img {
            width: 100%;
            height: auto;
            max-height: 240px;
            object-fit: contain;
            opacity: 0.95;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .card-item:hover .card-symbol-img {
            transform: scale(1.05);
            opacity: 1;
        }

        .card-number-badge,
        .card-collected-badge {
            position: absolute;
            z-index: 20;
            border-radius: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(5, 20, 36, 0.72);
            padding: 4px 8px;
            font-size: 10px;
            color: rgba(212, 228, 250, 0.8);
            backdrop-filter: blur(10px);
        }

        .card-number-badge {
            left: 12px;
            top: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
            letter-spacing: 0.12em;
        }

        .card-collected-badge {
            right: 12px;
            top: 12px;
            width: 26px;
            height: 26px;
            padding: 0;
            display: grid;
            place-items: center;
            background-color: rgba(251, 180, 0, 0.18);
        }

        .card-hover-overlay {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 16px;
            gap: 10px;
            background: linear-gradient(180deg, transparent 45%, rgba(5, 20, 36, 0.84) 100%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .card-item:hover .card-hover-overlay {
            opacity: 1;
        }

        .btn-card-detail,
        .btn-card-add {
            width: 100%;
            border-radius: 0.95rem;
            padding: 10px 0;
            font-size: 0.77rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-card-detail {
            color: #d4e4fa;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .btn-card-detail:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .btn-card-add {
            background: #fbb400;
            color: #1b1100;
            border-color: rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
        }

        .btn-card-add:hover {
            background: #ffd795;
        }

        .btn-card-add.loading {
            pointer-events: none;
            opacity: 0.75;
        }

        .btn-card-loader {
            display: inline-block;
            font-size: 0.8rem;
            line-height: 1;
            margin-left: 0.5rem;
        }

        .btn-card-loader.visually-hidden {
            display: none;
        }

        .card-footer-area {
            position: relative;
            z-index: 20;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background-color: rgba(5, 20, 36, 0.86);
            padding: 16px 14px;
            backdrop-filter: blur(16px);
        }

        .card-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #edf2ff;
            margin-bottom: 0.6rem;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-rarity-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.67rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(12px);
        }

        .card-rarity-dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .card-type-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(212, 228, 250, 0.72);
        }
    </style>

    <main class="set-detail-shell">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a class="text-decoration-none text-secondary" href="{{ route('collezioni.disponibili') }}">{{ __('Collezioni') }}</a></li>
                    @if ($set->serie)
                        <li class="breadcrumb-item"><a class="text-decoration-none text-secondary" href="#">{{ $set->serie->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $set->name }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 text-white mb-2">{{ $set->name }}</h1>
                    <p class="text-secondary mb-0">{{ __('Serie') }} {{ $set->serie?->name ?? 'Senza serie' }} • <span id="total-cards-label">{{ count($allCardsJson) }}</span> {{ __('carte') }}</p>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-12 col-xl-8">
                    {{-- Fast Search --}}
                    <div class="fast-search-wrap">
                        <svg class="fast-search-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="fast-search" class="fast-search-input" placeholder="{{ __('Cerca nome carta...') }}" autocomplete="off" />
                        <button type="button" id="fast-search-clear" class="fast-search-clear" title="Clear">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <span id="fast-search-count" class="fast-search-count"></span>
                    </div>

                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="cards-grid"></div>

                    <div id="no-results" class="no-results-msg" style="display:none;">
                        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-white fw-semibold mb-1">{{ __('Nessuna carta trovata') }}</p>
                        <p class="text-secondary mb-0" style="font-size:0.85rem;">{{ __('Prova a modificare i filtri o la ricerca per visualizzare altre carte.') }}</p>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        <button id="load-more-btn" type="button" class="btn btn-outline-light px-4" style="display:none;">
                            {{ __('Carica altre carte') }} <span id="load-more-remaining" class="badge bg-secondary ms-2"></span>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card summary-card h-100">
                        <div class="card-body">
                            <h2 class="h5 text-white">{{ __('Riepilogo set') }}</h2>
                            <p class="text-secondary">{{ __('Controlla rapidamente il set e applica filtri sulla griglia.') }}</p>
                            <div class="list-group list-group-flush mt-4">
                                <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">{{ __('Carte totali') }}</span>
                                    <span class="summary-value">{{ strtoupper($set->card_total ?? '—') }}</span>
                                </div>
                                <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="summary-label">{{ __('Lingua') }}</span>
                                    <span class="summary-value">{{ strtoupper($set->language) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    (function() {
        'use strict';

        var CARDS_PER_PAGE = 18;
        var allCards = @json($allCardsJson);
        var filteredCards = allCards.slice();
        var visibleCount = 0;

        var grid = document.getElementById('cards-grid');
        var loadMoreBtn = document.getElementById('load-more-btn');
        var loadMoreRemaining = document.getElementById('load-more-remaining');
        var noResults = document.getElementById('no-results');
        var searchInput = document.getElementById('fast-search');
        var searchClear = document.getElementById('fast-search-clear');
        var searchCount = document.getElementById('fast-search-count');

        var addUrl = '{{ route("collezioni.cards.addToCollection", ["card" => ":card"]) }}';

        function getRarityConfig(rarity) {
            var r = (rarity || '').toLowerCase();
            if (r.includes('ultra') || r.includes('secret')) {
                return { label: rarity || 'Ultra Rare', chip: 'background-color:rgba(255,179,177,0.08); border:1px solid rgba(255,179,177,0.3); color:#ffb3b1;', dot: 'background-color:#ffb3b1;' };
            }
            if (r.includes('rare')) {
                return { label: rarity, chip: 'background-color:rgba(255,215,149,0.08); border:1px solid rgba(255,215,149,0.3); color:#ffd795;', dot: 'background-color:#ffd795;' };
            }
            if (r === 'uncommon') {
                return { label: 'Uncommon', chip: 'background-color:rgba(105,212,244,0.08); border:1px solid rgba(105,212,244,0.25); color:#69d4f4;', dot: 'background-color:#69d4f4;' };
            }
            return { label: rarity || 'Common', chip: 'background-color:rgba(212,228,250,0.06); border:1px solid rgba(212,228,250,0.15); color:#a0b4cc;', dot: 'background-color:#a0b4cc;' };
        }

        function getFlagSvg(lang) {
            if (!lang) return '';
            var l = lang.toLowerCase();
            if (l === 'it') return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" width="16" height="12" style="border-radius:2px; flex-shrink:0;"><path fill="#009246" d="M0 0h1v2H0z"/><path fill="#fff" d="M1 0h1v2H1z"/><path fill="#ce2b37" d="M2 0h1v2H2z"/></svg>';
            if (l === 'en') return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="16" height="12" style="border-radius:2px; flex-shrink:0;"><clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg>';
            if (l === 'jp' || l === 'ja') return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="16" height="12" style="border-radius:2px; flex-shrink:0;"><rect width="900" height="600" fill="#fff"/><circle cx="450" cy="300" r="180" fill="#bc002d"/></svg>';
            return '';
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function padDexId(dexId) {
            var s = String(dexId || 0);
            while (s.length < 3) s = '0' + s;
            return s;
        }

        function renderCard(card) {
            var rc = getRarityConfig(card.rarity);
            var dex = padDexId(card.dexId);
            var escapedName = escapeHtml(card.name);
            var flag = getFlagSvg(card.language);
            var imgHtml = card.url_image
                ? '<img src="' + card.url_image + '/low.png" alt="' + escapedName + '" class="card-symbol-img" loading="lazy">'
                : '<div class="d-flex align-items-center justify-content-center h-100"><svg width="56" height="56" viewBox="0 0 80 80" fill="none" style="opacity:0.15; color:#d4e4fa;"><circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="2" /><circle cx="40" cy="40" r="18" fill="currentColor" opacity="0.4" /><rect x="4" y="38" width="72" height="4" fill="currentColor" opacity="0.5" /></svg></div>';

            var collectedBadge = card.isCollected
                ? '<div class="card-collected-badge"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="#ffd795" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg></div>'
                : '';

            var addBtn = !card.isCollected
                ? '<button type="button" class="btn-card-add w-100" data-card-add-id="' + card.id + '" data-card-name="' + escapedName.replace(/"/g, '&quot;') + '" data-card-dex="' + dex + '"><span class="btn-card-add-text">' + @json(__('+ Aggiungi')) + '</span><span class="btn-card-loader visually-hidden">' + @json(__('Caricamento...')) + '</span></button>'
                : '';

            var evolveText = card.evolve_from ? @json(__('Evolve da')) + ' ' + escapeHtml(card.evolve_from) : @json(__('Base'));
            var priceText = '€ ' + Number(card.price).toFixed(2).replace('.', ',');

            var setLine = '';
            if (card.set_name) {
                var symbolImg = card.set_symbol ? '<img src="' + card.set_symbol + '/low.png" alt="Set Symbol" style="height:12px; width:auto; margin-right:6px; filter:drop-shadow(0px 1px 1px rgba(0,0,0,0.5));" loading="lazy" onerror="this.style.display=\'none\'">' : '';
                var abbrText = card.set_abbr ? '<span style="opacity:0.5; margin-left:4px; flex-shrink:0;">(' + escapeHtml(card.set_abbr) + ')</span>' : '';
                setLine = symbolImg + '<span class="text-truncate">' + escapeHtml(card.set_name) + '</span>' + abbrText;
            }

            return '<div class="col" data-card-col-id="' + card.id + '">' +
                '<div data-card-id="' + card.id + '" class="card-item">' +
                    '<div class="card-glass-highlight"></div>' +
                    '<div class="card-image-area">' +
                        imgHtml +
                        '<div class="card-number-badge">#' + dex + '</div>' +
                        collectedBadge +
                        '<div class="card-hover-overlay">' +
                            '<button type="button" class="btn-card-detail d-inline-flex justify-content-center align-items-center text-decoration-none" data-card-modal-id="' + card.id + '" data-card-modal-name="' + escapedName.replace(/"/g, '&quot;') + '" data-card-modal-image="' + (card.url_image || '') + '">' + @json(__('Vedi dettagli')) + '</button>' +
                            addBtn +
                        '</div>' +
                    '</div>' +
                    '<div class="card-footer-area">' +
                        '<p class="card-name d-flex align-items-center gap-2 mb-1" title="' + escapedName + '">' +
                            flag +
                            '<span class="text-truncate">' + escapedName + '</span>' +
                        '</p>' +
                        '<p class="text-secondary d-flex align-items-center text-truncate mb-2" style="font-size:0.75rem;" title="' + escapeHtml(card.set_name) + '">' +
                            setLine +
                        '</p>' +
                        '<div class="d-flex align-items-center justify-content-between gap-1 mb-2 mt-1">' +
                            '<span class="card-rarity-chip" style="' + rc.chip + '"><span class="card-rarity-dot" style="' + rc.dot + '"></span>' + escapeHtml(rc.label) + '</span>' +
                            (card.type ? '<span class="card-type-label">' + escapeHtml(card.type) + '</span>' : '') +
                        '</div>' +
                        '<div style="display:flex; justify-content:space-between; gap:10px; font-size:12px; color:#8c909f;">' +
                            '<span>' + evolveText + '</span>' +
                            '<span>' + priceText + '</span>' +
                        '</div>' +
                        '<div style="margin-top:10px; font-size:11px; color:#8c909f;">Illus. ' + escapeHtml(card.illustrator || '—') + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function renderBatch(startIdx, count) {
            var end = Math.min(startIdx + count, filteredCards.length);
            var html = '';
            for (var i = startIdx; i < end; i++) {
                html += renderCard(filteredCards[i]);
            }
            grid.insertAdjacentHTML('beforeend', html);
            visibleCount = end;
            updateLoadMore();
        }

        function updateLoadMore() {
            var remaining = filteredCards.length - visibleCount;
            if (remaining > 0) {
                loadMoreBtn.style.display = 'inline-flex';
                loadMoreRemaining.textContent = remaining;
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }

        function fullRender() {
            grid.innerHTML = '';
            visibleCount = 0;
            if (filteredCards.length === 0) {
                noResults.style.display = 'block';
                loadMoreBtn.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                renderBatch(0, CARDS_PER_PAGE);
            }
            updateSearchCount();
        }

        function updateSearchCount() {
            var q = searchInput.value.trim();
            if (q.length > 0) {
                searchCount.textContent = filteredCards.length + ' / ' + allCards.length;
            } else {
                searchCount.textContent = allCards.length + ' {{ __("carte") }}';
            }
        }

        function applySearch() {
            var q = searchInput.value.trim().toLowerCase();
            searchClear.style.display = q.length > 0 ? 'block' : 'none';
            if (q.length === 0) {
                filteredCards = allCards.slice();
            } else {
                filteredCards = allCards.filter(function(c) {
                    return c.name.toLowerCase().indexOf(q) !== -1;
                });
            }
            fullRender();
        }

        // Event: search input
        var searchDebounce;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(applySearch, 150);
        });

        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            applySearch();
            searchInput.focus();
        });

        // Event: load more
        loadMoreBtn.addEventListener('click', function() {
            renderBatch(visibleCount, CARDS_PER_PAGE);
        });

        // Event delegation: card detail modal + add to collection
        grid.addEventListener('click', function(e) {
            // Modal
            var modalBtn = e.target.closest('[data-card-modal-id]');
            if (modalBtn) {
                e.stopPropagation();
                var data = {
                    id: parseInt(modalBtn.dataset.cardModalId),
                    name: modalBtn.dataset.cardModalName,
                    image: modalBtn.dataset.cardModalImage || null
                };
                if (typeof openModal === 'function') openModal(data);
                return;
            }

            // Add to collection
            var addBtn = e.target.closest('[data-card-add-id]');
            if (addBtn) {
                e.stopPropagation();
                var cardId = addBtn.dataset.cardAddId;
                var cardName = addBtn.dataset.cardName;
                var cardDex = addBtn.dataset.cardDex;
                window.addToCollection(addBtn, cardId, cardName, cardDex);
                return;
            }
        });

        // addToCollection function
        window.addToCollection = async function(button, cardId, cardName, cardDexId) {
            if (!button) return;
            var textEl = button.querySelector('.btn-card-add-text');
            var loaderEl = button.querySelector('.btn-card-loader');
            var originalText = textEl ? textEl.textContent : button.textContent;

            button.classList.add('loading');
            if (textEl) textEl.textContent = window.__trans ? window.__trans.sending : 'Invio...';
            if (loaderEl) loaderEl.classList.remove('visually-hidden');

            var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
            var url = addUrl.replace(':card', cardId);

            try {
                var response = await fetch(url, {
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
                    throw new Error(data.message || (window.__trans ? window.__trans.add_error : 'Errore'));
                }

                if (textEl) textEl.textContent = window.__trans ? window.__trans.added : 'Aggiunta!';
                button.classList.add('btn-success');
                button.classList.remove('btn-card-add');
                button.disabled = true;

                // Update the card in the pool so re-renders are correct
                for (var i = 0; i < allCards.length; i++) {
                    if (String(allCards[i].id) === String(cardId)) {
                        allCards[i].isCollected = true;
                        break;
                    }
                }

                if (window.showToast) {
                    var msg = window.__trans && window.__trans.pokemon_added
                        ? window.__trans.pokemon_added.replace(':name', cardName || '').replace(':number', cardDexId || '')
                        : (cardName || '') + ' #' + (cardDexId || '') + ' aggiunto';
                    window.showToast(msg, 'success');
                }
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

        // Initial render
        fullRender();
    })();
    </script>
@endsection
