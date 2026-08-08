{{--
    Partial: _missing-cards-modal.blade.php
    Bulk Add Missing Cards Modal — replaces the old single-add modal.
    Included in mie-set-detail.blade.php via @include('partials._missing-cards-modal')
--}}

<style>
    /* ─── Bulk Add Modal ──────────────────────────────────────────── */
    .bulk-modal-content {
        background-color: #0e182c;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        width: 90vw;
        max-width: 1600px;
        height: 90vh;
        display: flex;
        flex-direction: column;
        transform: scale(0.96);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
        overflow: hidden;
    }

    .modal-overlay.is-open .bulk-modal-content {
        transform: scale(1);
        opacity: 1;
    }

    /* ─── Header ──────────────────────────────────────────────────── */
    .bulk-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }

    .bulk-search-input {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #f4f7fb;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.8rem;
        outline: none;
        transition: border-color 0.2s;
        width: 220px;
    }

    .bulk-search-input:focus {
        border-color: #2B6FFF;
    }

    .bulk-search-input::placeholder {
        color: #94a3b8;
    }

    /* ─── Toolbar ─────────────────────────────────────────────────── */
    .bulk-toolbar {
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.02);
    }

    .bulk-toolbar-btn {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8;
        padding: 0.3rem 0.65rem;
        border-radius: 0.4rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }

    .bulk-toolbar-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #f4f7fb;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .bulk-toolbar-select {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #f4f7fb;
        padding: 0.3rem 0.5rem;
        border-radius: 0.4rem;
        font-size: 0.75rem;
        outline: none;
        cursor: pointer;
    }

    .bulk-toolbar-divider {
        width: 1px;
        height: 24px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0 0.25rem;
    }

    /* ─── Table ───────────────────────────────────────────────────── */
    .bulk-table-wrap {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        padding: 0;
    }

    .bulk-table-wrap::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .bulk-table-wrap::-webkit-scrollbar-track {
        background: transparent;
    }

    .bulk-table-wrap::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.12);
        border-radius: 3px;
    }

    .bulk-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .bulk-table thead {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #0a1220;
    }

    .bulk-table th {
        padding: 0.6rem 0.75rem;
        color: rgba(212, 228, 250, 0.4);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 0.05em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        white-space: nowrap;
        text-align: left;
        user-select: none;
    }

    .bulk-table td {
        padding: 0.5rem 0.75rem;
        color: #d4e4fa;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        white-space: nowrap;
        vertical-align: middle;
    }

    .bulk-table tbody tr {
        transition: background-color 0.1s;
        cursor: default;
    }

    .bulk-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }

    .bulk-table tbody tr.bulk-row-selected {
        background-color: rgba(43, 111, 255, 0.08);
    }

    .bulk-table tbody tr.bulk-row-active {
        background-color: rgba(255, 200, 0, 0.06);
        border-left: 3px solid #fbb400;
    }

    .bulk-table tbody tr.bulk-row-error {
        background-color: rgba(239, 68, 68, 0.1);
    }

    /* ─── Inline inputs ──────────────────────────────────────────── */
    .bulk-qty-input {
        width: 58px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #f4f7fb;
        text-align: center;
        padding: 0.25rem 0.35rem;
        border-radius: 0.35rem;
        font-size: 0.82rem;
        outline: none;
        transition: border-color 0.15s;
    }

    .bulk-qty-input:focus {
        border-color: #2B6FFF;
        background: rgba(43, 111, 255, 0.08);
    }

    .bulk-qty-input.has-value {
        border-color: rgba(255, 200, 0, 0.5);
        background: rgba(255, 200, 0, 0.06);
        color: #fbb400;
        font-weight: 600;
    }

    .bulk-inline-select {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #d4e4fa;
        padding: 0.25rem 0.35rem;
        border-radius: 0.35rem;
        font-size: 0.78rem;
        outline: none;
        cursor: pointer;
        transition: border-color 0.15s;
    }

    .bulk-inline-select:focus {
        border-color: #2B6FFF;
    }

    /* ─── Attribute toggles ──────────────────────────────────────── */
    .bulk-attr-group {
        display: flex;
        gap: 0.3rem;
    }

    .bulk-attr-toggle {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8;
        padding: 0.15rem 0.4rem;
        border-radius: 0.3rem;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
        line-height: 1.3;
    }

    .bulk-attr-toggle:hover {
        border-color: rgba(255, 255, 255, 0.25);
        color: #d4e4fa;
    }

    .bulk-attr-toggle.active {
        background: rgba(255, 200, 0, 0.15);
        border-color: rgba(255, 200, 0, 0.5);
        color: #fbb400;
    }

    /* ─── Rarity badge ───────────────────────────────────────────── */
    .bulk-rarity-badge {
        display: inline-block;
        padding: 0.15rem 0.45rem;
        border-radius: 0.3rem;
        font-size: 0.7rem;
        font-weight: 500;
        background: rgba(59, 130, 246, 0.12);
        color: #93c5fd;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    /* ─── Footer ──────────────────────────────────────────────────── */
    .bulk-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.02);
    }

    .bulk-submit-btn {
        background-color: #fbb400;
        color: #1a1a1a;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 2rem;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bulk-submit-btn:hover:not(:disabled) {
        background-color: #e5a600;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(251, 180, 0, 0.3);
    }

    .bulk-submit-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .bulk-cancel-btn {
        background: none;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #94a3b8;
        padding: 0.5rem 1.25rem;
        border-radius: 2rem;
        font-weight: 500;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .bulk-cancel-btn:hover {
        border-color: rgba(255, 255, 255, 0.3);
        color: #f4f7fb;
    }

    .bulk-count-badge {
        background: rgba(251, 180, 0, 0.15);
        color: #fbb400;
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ─── Responsive ─────────────────────────────────────────────── */
    @media (max-width: 1024px) {
        .bulk-modal-content {
            width: 98vw;
            height: 95vh;
            border-radius: 0.75rem;
        }

        .bulk-toolbar {
            padding: 0.5rem 1rem;
        }

        .bulk-header {
            padding: 1rem;
        }

        .bulk-footer {
            padding: 0.75rem 1rem;
        }
    }

    .bulk-state-panel {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div id="missing-cards-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="bulk-add-title">
    <div onclick="bulkTryClose()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="bulk-modal-content position-relative" style="z-index:10;">

        {{-- ─── Header ──────────────────────────────────────────────── --}}
        <div class="bulk-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <h2 id="bulk-add-title" class="fw-bold mb-0" style="font-size:1.15rem; color:#d4e4fa;">
                        {{ __('Aggiungi Carte Mancanti') }}
                    </h2>
                    <span id="bulk-total-count" class="text-secondary" style="font-size: 0.8rem;"></span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <input type="text" id="bulk-search" class="bulk-search-input" placeholder="{{ __('Cerca carta...') }}" autocomplete="off">
                    <button onclick="bulkTryClose()" class="btn-modal-close" aria-label="{{ __('Chiudi') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Toolbar ─────────────────────────────────────────────── --}}
        <div class="bulk-toolbar">
            <button type="button" class="bulk-toolbar-btn" onclick="bulkSelectAllVisible()" title="{{ __('Imposta qty=1 a tutte le carte visibili') }}">
                {{ __('Seleziona visibili') }}
            </button>
            <button type="button" class="bulk-toolbar-btn" onclick="bulkDeselectAll()" title="{{ __('Rimuovi tutte le selezioni') }}">
                {{ __('Deseleziona') }}
            </button>

            <div class="bulk-toolbar-divider"></div>

            <span class="text-secondary" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.04em;">{{ __('Applica a selezionate:') }}</span>

            <select id="bulk-toolbar-lang" class="bulk-toolbar-select" title="{{ __('Lingua') }}">
                <option value="it">IT</option>
                <option value="en">EN</option>
                <option value="jp">JP</option>
                <option value="fr">FR</option>
                <option value="de">DE</option>
                <option value="es">ES</option>
                <option value="pt">PT</option>
            </select>
            <button type="button" class="bulk-toolbar-btn" onclick="bulkApplyLang()">{{ __('Lingua') }}</button>

            <select id="bulk-toolbar-cond" class="bulk-toolbar-select" title="{{ __('Condizione') }}">
                <option value="NM">NM</option>
                <option value="LP">LP</option>
                <option value="MP">MP</option>
                <option value="HP">HP</option>
                <option value="DMG">DMG</option>
            </select>
            <button type="button" class="bulk-toolbar-btn" onclick="bulkApplyCond()">{{ __('Condizione') }}</button>

            <select id="bulk-toolbar-foil" class="bulk-toolbar-select" title="{{ __('Foil') }}">
                <option value="normal">Normal</option>
                <option value="holo">Holo</option>
                <option value="reverse">Reverse</option>
            </select>
            <button type="button" class="bulk-toolbar-btn" onclick="bulkApplyFoil()">{{ __('Foil') }}</button>

            <div class="bulk-toolbar-divider"></div>

            <button type="button" class="bulk-toolbar-btn" onclick="bulkResetAll()" style="color: #ef4444;">
                {{ __('Reset') }}
            </button>
        </div>

        {{-- ─── Loading ─────────────────────────────────────────────── --}}
        <div id="bulk-loading" class="flex-grow-1 bulk-state-panel">
            <div class="spinner-border text-warning" role="status"></div>
        </div>

        {{-- ─── Empty state ─────────────────────────────────────────── --}}
        <div id="bulk-empty" class="flex-grow-1 bulk-state-panel" style="display:none;">
            <div class="text-center text-secondary py-5">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3" style="opacity:0.3;"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="mb-0 fw-bold" style="font-size: 1rem;">{{ __('Hai completato questo set!') }}</p>
                <p class="small mb-0">{{ __('Non ci sono carte mancanti.') }}</p>
            </div>
        </div>

        {{-- ─── Table ───────────────────────────────────────────────── --}}
        <div id="bulk-table-wrap" class="bulk-table-wrap" style="display:none;">
            <table class="bulk-table">
                <thead>
                    <tr>
                        <th style="width: 55px;">#</th>
                        <th>{{ __('Nome Carta') }}</th>
                        <th>{{ __('Rarità') }}</th>
                        <th style="width: 70px; text-align: center;">{{ __('Qtà') }}</th>
                        <th style="width: 75px;">{{ __('Lingua') }}</th>
                        <th style="width: 80px;">{{ __('Cond.') }}</th>
                        <th style="width: 95px;">{{ __('Foil') }}</th>
                        <th style="width: 140px;">{{ __('Attributi') }}</th>
                    </tr>
                </thead>
                <tbody id="bulk-tbody">
                    {{-- Rows injected by JS --}}
                </tbody>
            </table>
        </div>

        {{-- ─── Footer ──────────────────────────────────────────────── --}}
        <div class="bulk-footer">
            <button type="button" class="bulk-cancel-btn" onclick="bulkTryClose()">
                {{ __('Annulla') }}
            </button>
            <div class="d-flex align-items-center gap-3">
                <span id="bulk-selection-info" class="bulk-count-badge" style="display:none;"></span>
                <button type="button" id="bulk-submit-btn" class="bulk-submit-btn" disabled onclick="bulkSubmit()">
                    {{ __('Nessuna carta selezionata') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ─── State ──────────────────────────────────────────────────────
    let bulkRows = [];
    let searchFilter = '';
    let isSubmitting = false;
    const defaultLang = '{{ Auth::user()->language ?? "it" }}';
    const defaultCond = 'NM';
    const defaultFoil = 'normal';

    // ─── DOM refs (cached on first open) ────────────────────────────
    let $overlay, $loading, $empty, $tableWrap, $tbody, $submitBtn, $selInfo, $totalCount, $search;

    function cacheDom() {
        $overlay = document.getElementById('missing-cards-overlay');
        $loading = document.getElementById('bulk-loading');
        $empty = document.getElementById('bulk-empty');
        $tableWrap = document.getElementById('bulk-table-wrap');
        $tbody = document.getElementById('bulk-tbody');
        $submitBtn = document.getElementById('bulk-submit-btn');
        $selInfo = document.getElementById('bulk-selection-info');
        $totalCount = document.getElementById('bulk-total-count');
        $search = document.getElementById('bulk-search');
    }

    // ─── Open / Close ───────────────────────────────────────────────
    window.openMissingCardsModal = function() {
        cacheDom();
        bulkRows = [];
        searchFilter = '';
        isSubmitting = false;
        if ($search) $search.value = '';

        $overlay.style.display = 'flex';
        $loading.style.display = 'flex';
        $empty.style.display = 'none';
        $tableWrap.style.display = 'none';

        requestAnimationFrame(() => $overlay.classList.add('is-open'));
        document.body.style.overflow = 'hidden';

        fetchMissingCards();
    };

    window.closeMissingCardsModal = function(force) {
        if (!force && hasChanges() && !isSubmitting) {
            if (!confirm('{{ __("Hai modifiche non salvate. Vuoi davvero chiudere?") }}')) return;
        }
        $overlay.classList.remove('is-open');
        setTimeout(() => {
            $overlay.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    };

    window.bulkTryClose = function() {
        closeMissingCardsModal(false);
    };

    // Esc to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && $overlay && $overlay.classList.contains('is-open')) {
            bulkTryClose();
        }
    });

    // ─── Fetch data ─────────────────────────────────────────────────
    async function fetchMissingCards() {
        try {
            const res = await fetch(`{{ route('collezioni.set.missing', ['set' => $set->id]) }}`);
            const data = await res.json();
            initRows(data);
        } catch(e) {
            console.error('Error fetching missing cards:', e);
            $loading.style.display = 'none';
        }
    }

    function initRows(cards) {
        bulkRows = cards.map(card => ({
            cardId: card.id,
            cardNumber: card.dexId || '',
            name: card.name || '',
            rarity: card.rarity || '',
            quantity: 0,
            language: defaultLang,
            condition: defaultCond,
            foilType: defaultFoil,
            firstEdition: false,
            signed: false,
            altered: false,
            _error: false,
        }));

        $loading.style.display = 'none';

        if (bulkRows.length === 0) {
            $empty.style.display = 'flex';
            $tableWrap.style.display = 'none';
        } else {
            $empty.style.display = 'none';
            $tableWrap.style.display = 'block';
            $totalCount.textContent = bulkRows.length + ' {{ __("carte mancanti") }}';
            renderTable();
        }

        updateFooter();
    }

    // ─── Render ─────────────────────────────────────────────────────
    function renderTable() {
        const filter = searchFilter.toLowerCase().trim();
        let html = '';

        for (let i = 0; i < bulkRows.length; i++) {
            const row = bulkRows[i];

            // Search filter
            if (filter && !row.name.toLowerCase().includes(filter) && !row.cardNumber.toLowerCase().includes(filter)) {
                continue;
            }

            const rowClass = row._error ? 'bulk-row-error' : (row.quantity > 0 ? 'bulk-row-active' : '');
            const qtyClass = row.quantity > 0 ? 'has-value' : '';

            html += `<tr class="${rowClass}" data-idx="${i}" onclick="bulkToggleRow(event, ${i})">
                <td style="color: #94a3b8; font-size: 0.78rem;">#${escHtml(row.cardNumber)}</td>
                <td>
                    <span class="fw-medium">${escHtml(row.name)}</span>
                </td>
                <td><span class="bulk-rarity-badge">${escHtml(row.rarity || 'Common')}</span></td>
                <td style="text-align: center;">
                    <input type="number" class="bulk-qty-input ${qtyClass}" value="${row.quantity}" min="0" max="999"
                        data-idx="${i}" onchange="bulkSetQty(${i}, this.value)" onfocus="this.select()"
                        aria-label="{{ __('Quantità') }}">
                </td>
                <td>
                    <select class="bulk-inline-select" data-idx="${i}" onchange="bulkSetField(${i}, 'language', this.value)"
                        aria-label="{{ __('Lingua') }}">
                        <option value="it" ${row.language === 'it' ? 'selected' : ''}>IT</option>
                        <option value="en" ${row.language === 'en' ? 'selected' : ''}>EN</option>
                        <option value="jp" ${row.language === 'jp' ? 'selected' : ''}>JP</option>
                        <option value="fr" ${row.language === 'fr' ? 'selected' : ''}>FR</option>
                        <option value="de" ${row.language === 'de' ? 'selected' : ''}>DE</option>
                        <option value="es" ${row.language === 'es' ? 'selected' : ''}>ES</option>
                        <option value="pt" ${row.language === 'pt' ? 'selected' : ''}>PT</option>
                    </select>
                </td>
                <td>
                    <select class="bulk-inline-select" data-idx="${i}" onchange="bulkSetField(${i}, 'condition', this.value)"
                        aria-label="{{ __('Condizione') }}">
                        <option value="NM" ${row.condition === 'NM' ? 'selected' : ''}>NM</option>
                        <option value="LP" ${row.condition === 'LP' ? 'selected' : ''}>LP</option>
                        <option value="MP" ${row.condition === 'MP' ? 'selected' : ''}>MP</option>
                        <option value="HP" ${row.condition === 'HP' ? 'selected' : ''}>HP</option>
                        <option value="DMG" ${row.condition === 'DMG' ? 'selected' : ''}>DMG</option>
                    </select>
                </td>
                <td>
                    <select class="bulk-inline-select" data-idx="${i}" onchange="bulkSetField(${i}, 'foilType', this.value)"
                        aria-label="{{ __('Foil') }}">
                        <option value="normal" ${row.foilType === 'normal' ? 'selected' : ''}>Normal</option>
                        <option value="holo" ${row.foilType === 'holo' ? 'selected' : ''}>Holo</option>
                        <option value="reverse" ${row.foilType === 'reverse' ? 'selected' : ''}>Reverse</option>
                    </select>
                </td>
                <td>
                    <div class="bulk-attr-group">
                        <button type="button" class="bulk-attr-toggle ${row.firstEdition ? 'active' : ''}"
                            onclick="bulkToggleAttr(${i}, 'firstEdition')" aria-pressed="${row.firstEdition}"
                            title="1ª Edizione">1E</button>
                        <button type="button" class="bulk-attr-toggle ${row.signed ? 'active' : ''}"
                            onclick="bulkToggleAttr(${i}, 'signed')" aria-pressed="${row.signed}"
                            title="Signed">SG</button>
                        <button type="button" class="bulk-attr-toggle ${row.altered ? 'active' : ''}"
                            onclick="bulkToggleAttr(${i}, 'altered')" aria-pressed="${row.altered}"
                            title="Altered">AL</button>
                    </div>
                </td>
            </tr>`;
        }

        $tbody.innerHTML = html;
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Row interactions ───────────────────────────────────────────
    window.bulkToggleRow = function(event, idx) {
        // Prevent toggle if clicking on interactive elements
        const tagName = event.target.tagName.toLowerCase();
        if (tagName === 'input' || tagName === 'select' || tagName === 'button') {
            return;
        }

        const row = bulkRows[idx];
        row.quantity = row.quantity > 0 ? 0 : 1;
        renderTable();
        updateFooter();
    };

    window.bulkSetQty = function(idx, val) {
        let v = parseInt(val, 10);
        if (isNaN(v) || v < 0) v = 0;
        if (v > 999) v = 999;
        bulkRows[idx].quantity = v;
        // Update just this input's class and footer, avoid full re-render for performance
        const input = $tbody.querySelector(`input[data-idx="${idx}"]`);
        if (input) {
            input.value = v;
            input.classList.toggle('has-value', v > 0);
            const tr = input.closest('tr');
            if (tr) {
                tr.classList.toggle('bulk-row-active', v > 0);
                tr.classList.remove('bulk-row-error');
            }
        }
        updateFooter();
    };

    window.bulkSetField = function(idx, field, value) {
        bulkRows[idx][field] = value;
    };

    window.bulkToggleAttr = function(idx, attr) {
        bulkRows[idx][attr] = !bulkRows[idx][attr];
        const row = bulkRows[idx];
        // Update button visually without full re-render
        const tr = $tbody.querySelector(`tr[data-idx="${idx}"]`);
        if (tr) {
            const btns = tr.querySelectorAll('.bulk-attr-toggle');
            btns.forEach(btn => {
                const t = btn.title;
                let key = null;
                if (t === '1ª Edizione') key = 'firstEdition';
                else if (t === 'Signed') key = 'signed';
                else if (t === 'Altered') key = 'altered';
                if (key) {
                    btn.classList.toggle('active', row[key]);
                    btn.setAttribute('aria-pressed', row[key]);
                }
            });
        }
    };

    // ─── Toolbar actions ────────────────────────────────────────────
    window.bulkSelectAllVisible = function() {
        const filter = searchFilter.toLowerCase().trim();
        for (let i = 0; i < bulkRows.length; i++) {
            const row = bulkRows[i];
            if (filter && !row.name.toLowerCase().includes(filter) && !row.cardNumber.toLowerCase().includes(filter)) continue;
            if (row.quantity === 0) row.quantity = 1;
        }
        renderTable();
        updateFooter();
    };

    window.bulkDeselectAll = function() {
        for (let i = 0; i < bulkRows.length; i++) {
            bulkRows[i].quantity = 0;
        }
        renderTable();
        updateFooter();
    };

    window.bulkApplyLang = function() {
        const lang = document.getElementById('bulk-toolbar-lang').value;
        applyToSelected('language', lang);
    };

    window.bulkApplyCond = function() {
        const cond = document.getElementById('bulk-toolbar-cond').value;
        applyToSelected('condition', cond);
    };

    window.bulkApplyFoil = function() {
        const foil = document.getElementById('bulk-toolbar-foil').value;
        applyToSelected('foilType', foil);
    };

    function applyToSelected(field, value) {
        let changed = 0;
        for (let i = 0; i < bulkRows.length; i++) {
            if (bulkRows[i].quantity > 0) {
                bulkRows[i][field] = value;
                changed++;
            }
        }
        if (changed === 0) {
            if (window.showToast) window.showToast('{{ __("Nessuna carta selezionata (qty > 0)") }}', 'warning');
            return;
        }
        renderTable();
        if (window.showToast) window.showToast(changed + ' {{ __("carte aggiornate") }}', 'success');
    }

    window.bulkResetAll = function() {
        for (let i = 0; i < bulkRows.length; i++) {
            bulkRows[i].quantity = 0;
            bulkRows[i].language = defaultLang;
            bulkRows[i].condition = defaultCond;
            bulkRows[i].foilType = defaultFoil;
            bulkRows[i].firstEdition = false;
            bulkRows[i].signed = false;
            bulkRows[i].altered = false;
            bulkRows[i]._error = false;
        }
        renderTable();
        updateFooter();
    };

    // ─── Search ─────────────────────────────────────────────────────
    let searchTimeout;
    document.addEventListener('DOMContentLoaded', function() {
        const searchEl = document.getElementById('bulk-search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchFilter = this.value;
                    renderTable();
                }, 200);
            });
        }
    });

    // ─── Footer update ──────────────────────────────────────────────
    function updateFooter() {
        const count = getSelectedCount();
        const totalQty = getTotalQuantity();

        if (count > 0) {
            $submitBtn.disabled = false;
            const label = totalQty === 1 ? '{{ __("Aggiungi 1 carta") }}' : '{{ __("Aggiungi") }} ' + totalQty + ' {{ __("carte") }}';
            $submitBtn.textContent = label;
            $selInfo.style.display = 'inline-block';
            $selInfo.textContent = count + ' {{ __("righe") }}';
        } else {
            $submitBtn.disabled = true;
            $submitBtn.textContent = '{{ __("Nessuna carta selezionata") }}';
            $selInfo.style.display = 'none';
        }
    }

    function getSelectedCount() {
        return bulkRows.filter(r => r.quantity > 0).length;
    }

    function getTotalQuantity() {
        return bulkRows.reduce((sum, r) => sum + (r.quantity > 0 ? r.quantity : 0), 0);
    }

    function hasChanges() {
        return bulkRows.some(r => r.quantity > 0);
    }

    // ─── Submit ─────────────────────────────────────────────────────
    window.bulkSubmit = async function() {
        if (isSubmitting) return;
        const selected = bulkRows.filter(r => r.quantity > 0);
        if (selected.length === 0) return;

        isSubmitting = true;
        $submitBtn.disabled = true;
        $submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __("Aggiungendo...") }}';

        const payload = {
            cards: selected.map(r => ({
                card_id: r.cardId,
                quantity: r.quantity,
                language: r.language,
                condition: r.condition,
                foil_type: r.foilType,
                is_first_edition: r.firstEdition,
                is_signed: r.signed,
                is_altered: r.altered,
            })),
        };

        try {
            const res = await fetch(`{{ route('collezioni.set.bulkAdd', ['set' => $set->id]) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                if (data.failed > 0) {
                    // Partial error
                    if (window.showToast) {
                        window.showToast(data.added + ' {{ __("carte aggiunte") }}, ' + data.failed + ' {{ __("non inserite") }}', 'warning');
                    }
                    // Mark failed rows
                    if (data.errors && data.errors.length > 0) {
                        const failedIds = new Set(data.errors.map(e => e.card_id));
                        for (let i = 0; i < bulkRows.length; i++) {
                            if (failedIds.has(bulkRows[i].cardId)) {
                                bulkRows[i]._error = true;
                            } else if (bulkRows[i].quantity > 0) {
                                bulkRows[i].quantity = 0; // Clear successfully added
                            }
                        }
                        renderTable();
                        updateFooter();
                    }
                    isSubmitting = false;
                } else {
                    // Full success
                    if (window.showToast) {
                        window.showToast(data.added + ' {{ __("carte aggiunte alla collezione!") }}', 'success');
                    }
                    isSubmitting = true; // Keep true to skip close confirmation
                    closeMissingCardsModal(true);
                    // Reload the grid
                    if (typeof reloadCardsGrid === 'function') {
                        reloadCardsGrid(1);
                    } else {
                        window.location.reload();
                    }
                }
            } else {
                if (window.showToast) window.showToast(data.message || '{{ __("Errore durante il salvataggio.") }}', 'danger');
                isSubmitting = false;
                updateFooter();
            }
        } catch(e) {
            console.error('Bulk add error:', e);
            if (window.showToast) window.showToast('{{ __("Errore di rete. Riprova.") }}', 'danger');
            isSubmitting = false;
            updateFooter();
        }
    };

})();
</script>
