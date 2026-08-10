{{-- Modale: Segna In Arrivo --}}
<div id="incoming-add-overlay" class="modal-overlay">
    <div onclick="closeIncomingAddModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 600px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">
                <span style="font-size:1.25rem;">🚚</span> {{ __('Segna come In Arrivo') }}
            </h2>
            <button onclick="closeIncomingAddModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="text-secondary small mb-4">{{ __('Stai segnando') }} <strong id="incoming-add-count" class="text-white">0</strong> {{ __('carte come in arrivo.') }}</p>

        <form id="incoming-add-form" onsubmit="event.preventDefault(); submitIncomingAdd();">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Lingua') }}</label>
                    <select id="incoming-add-language" class="form-select bg-dark text-white border-secondary">
                        <option value="it">Italiano (IT)</option>
                        <option value="en">Inglese (EN)</option>
                        <option value="jp">Giapponese (JP)</option>
                        <option value="fr">Francese (FR)</option>
                        <option value="de">Tedesco (DE)</option>
                        <option value="es">Spagnolo (ES)</option>
                        <option value="pt">Portoghese (PT)</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Foil Carta') }}</label>
                    <select id="incoming-add-foil" class="form-select bg-dark text-white border-secondary">
                        <option value="normal">Normale</option>
                        <option value="holo">Holo</option>
                        <option value="reverse">Reverse</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Quantità') }}</label>
                    <input type="number" id="incoming-add-quantity" class="form-control bg-dark text-white border-secondary" value="1" min="1">
                </div>
                <div class="col-12">
                    <label class="form-label text-secondary small">{{ __('Opzioni Extra') }}</label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="incoming-add-first-edition" class="form-check-input mt-0"> 1ª Edizione
                        </label>
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="incoming-add-signed" class="form-check-input mt-0"> Signed
                        </label>
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="incoming-add-altered" class="form-check-input mt-0"> Altered
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label text-secondary small">{{ __('Note (opzionale)') }}</label>
                    <textarea id="incoming-add-notes" class="form-control bg-dark text-white border-secondary" rows="2" 
                        placeholder="{{ __('Es. Ordine CardMarket #12345, venditore Mario...') }}" maxlength="500"></textarea>
                </div>
                <div class="col-12 text-end mt-4">
                    <button type="submit" id="incoming-add-submit-btn" class="btn px-4 fw-bold text-dark" style="background: linear-gradient(135deg, #fb923c, #f59e0b); border: none;">
                        🚚 {{ __('Segna In Arrivo') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modale: Sono Arrivate --}}
<div id="incoming-arrived-overlay" class="modal-overlay">
    <div onclick="closeIncomingArrivedModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 700px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">
                <span style="font-size:1.25rem;">📦</span> {{ __('Carte Arrivate!') }}
            </h2>
            <button onclick="closeIncomingArrivedModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="text-secondary small mb-3">{{ __('Seleziona la condizione e le carte in arrivo verranno aggiunte alla tua collezione.') }}</p>

        <div id="incoming-arrived-loading" class="text-center py-4">
            <div class="spinner-border" style="color: #f59e0b;" role="status"></div>
        </div>

        <div id="incoming-arrived-content" style="display:none;">
            <div class="mb-3">
                <label class="form-label text-secondary small">{{ __('Condizione delle carte ricevute') }}</label>
                <select id="incoming-arrived-condition" class="form-select bg-dark text-white border-secondary" style="max-width: 200px;">
                    <option value="NM">NM (Near Mint)</option>
                    <option value="LP">LP (Lightly Played)</option>
                    <option value="MP">MP (Moderately Played)</option>
                    <option value="HP">HP (Heavily Played)</option>
                    <option value="DMG">DMG (Damaged)</option>
                </select>
            </div>

            <div style="max-height: 40vh; overflow-y: auto;" class="mb-4 pe-2">
                <table class="table table-dark table-hover mb-0" style="font-size: 0.85rem; background-color: transparent;">
                    <thead>
                        <tr>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">
                                <input type="checkbox" id="incoming-arrived-select-all" class="form-check-input" checked onchange="toggleAllIncomingArrived(this)">
                            </th>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">Carta</th>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">Lingua</th>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">Foil</th>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">Qtà</th>
                            <th class="text-secondary border-secondary" style="background-color: transparent;">Note</th>
                        </tr>
                    </thead>
                    <tbody id="incoming-arrived-tbody">
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center" id="incoming-arrived-footer" style="display:none;">
                <button type="button" onclick="removeSelectedIncoming()" class="btn btn-outline-danger btn-sm px-3">
                    {{ __('Annulla ordine') }}
                </button>
                <button type="button" onclick="submitArrivedIncoming()" id="incoming-arrived-submit-btn" class="btn px-4 fw-bold text-dark" style="background: linear-gradient(135deg, #22c55e, #16a34a); border: none;">
                    📦 {{ __('Aggiungi in Collezione') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.selectedCards = window.selectedCards || new Set();

    window.handleMissingCardSelection = function(checkbox) {
        const cardId = parseInt(checkbox.value);
        if (checkbox.checked) {
            window.selectedCards.add(cardId);
        } else {
            window.selectedCards.delete(cardId);
        }
        updateMissingActionBar();
    };

    function updateMissingActionBar() {
        const bar = document.getElementById('missing-action-bar');
        if (!bar) return;
        const count = window.selectedCards.size;

        if (count > 0) {
            bar.style.display = 'flex';
            requestAnimationFrame(() => bar.classList.add('is-open'));
            document.getElementById('missing-selected-count').textContent = count;
        } else {
            bar.classList.remove('is-open');
            setTimeout(() => bar.style.display = 'none', 300);
        }
    }

    window.clearMissingSelection = function() {
        window.selectedCards.clear();
        document.querySelectorAll('.missing-card-checkbox').forEach(cb => cb.checked = false);
        updateMissingActionBar();
    };

    // ─── Incoming Add Modal ─────────────────────────

    window.openIncomingAddModal = function() {
        if (!window.selectedCards || window.selectedCards.size === 0) return;
        document.getElementById('incoming-add-count').textContent = window.selectedCards.size;
        
        var overlay = document.getElementById('incoming-add-overlay');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
    };

    window.closeIncomingAddModal = function() {
        var overlay = document.getElementById('incoming-add-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => overlay.style.display = 'none', 300);
        document.body.style.overflow = '';
    };

    window.submitIncomingAdd = async function() {
        const btn = document.getElementById('incoming-add-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Salvataggio...';

        try {
            const res = await fetch('/collezioni/incoming/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    card_ids: Array.from(window.selectedCards),
                    language: document.getElementById('incoming-add-language').value,
                    foil_type: document.getElementById('incoming-add-foil').value,
                    is_first_edition: document.getElementById('incoming-add-first-edition').checked,
                    is_signed: document.getElementById('incoming-add-signed').checked,
                    is_altered: document.getElementById('incoming-add-altered').checked,
                    quantity: parseInt(document.getElementById('incoming-add-quantity').value) || 1,
                    notes: document.getElementById('incoming-add-notes').value || null
                })
            });

            if (res.ok) {
                closeIncomingAddModal();
                clearMissingSelection();
                if (typeof reloadCardsGrid === 'function') {
                    reloadCardsGrid(document.getElementById('filter-page')?.value || 1);
                } else {
                    location.reload();
                }
            } else {
                alert('Errore durante il salvataggio.');
            }
        } catch(e) {
            console.error(e);
            alert('Errore di rete.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '🚚 Segna In Arrivo';
        }
    };

    // ─── Incoming Arrived Modal ─────────────────────

    window.openIncomingArrivedModal = function() {
        if (!window.selectedCards || window.selectedCards.size === 0) return;

        var overlay = document.getElementById('incoming-arrived-overlay');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('is-open'));
        document.body.style.overflow = 'hidden';

        document.getElementById('incoming-arrived-loading').style.display = 'block';
        document.getElementById('incoming-arrived-content').style.display = 'none';

        fetchIncomingForCards();
    };

    window.closeIncomingArrivedModal = function() {
        var overlay = document.getElementById('incoming-arrived-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => overlay.style.display = 'none', 300);
        document.body.style.overflow = '';
    };

    async function fetchIncomingForCards() {
        try {
            const res = await fetch('/collezioni/incoming/list', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ card_ids: Array.from(window.selectedCards) })
            });
            const data = await res.json();
            renderIncomingArrivedTable(data);
        } catch(e) {
            console.error(e);
            alert('Errore nel caricamento.');
            closeIncomingArrivedModal();
        }
    }

    function renderIncomingArrivedTable(incomingList) {
        const tbody = document.getElementById('incoming-arrived-tbody');
        let html = '';

        if (incomingList.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-secondary py-4">Nessuna carta in arrivo trovata per la selezione.</td></tr>';
            document.getElementById('incoming-arrived-footer').style.display = 'none';
        } else {
            incomingList.forEach(inc => {
                const foilStr = (!inc.foil_type || inc.foil_type === 'normal') ? '-' : inc.foil_type.charAt(0).toUpperCase() + inc.foil_type.slice(1);
                const langStr = inc.language ? inc.language.toUpperCase() : 'EN';
                const notesStr = inc.notes ? inc.notes.substring(0, 40) + (inc.notes.length > 40 ? '...' : '') : '-';
                const cardName = inc.card ? inc.card.name : 'N/D';

                html += `
                    <tr>
                        <td class="align-middle border-secondary" style="background-color: transparent;">
                            <input type="checkbox" class="form-check-input incoming-arrived-cb" value="${inc.id}" checked>
                        </td>
                        <td class="align-middle border-secondary text-white" style="background-color: transparent;">
                            <strong>${cardName}</strong>
                        </td>
                        <td class="align-middle border-secondary text-light" style="background-color: transparent;">
                            <span class="badge bg-secondary">${langStr}</span>
                        </td>
                        <td class="align-middle border-secondary text-light" style="background-color: transparent;">
                            ${foilStr !== '-' ? `<span class="badge bg-info text-dark">${foilStr}</span>` : '<span class="text-secondary">-</span>'}
                        </td>
                        <td class="align-middle border-secondary text-light text-center" style="background-color: transparent;">
                            ${inc.quantity}
                        </td>
                        <td class="align-middle border-secondary text-secondary small" style="background-color: transparent;" title="${inc.notes || ''}">
                            ${notesStr}
                        </td>
                    </tr>
                `;
            });
            document.getElementById('incoming-arrived-footer').style.display = 'flex';
        }

        tbody.innerHTML = html;
        document.getElementById('incoming-arrived-loading').style.display = 'none';
        document.getElementById('incoming-arrived-content').style.display = 'block';
    }

    window.toggleAllIncomingArrived = function(selectAllCb) {
        document.querySelectorAll('.incoming-arrived-cb').forEach(cb => cb.checked = selectAllCb.checked);
    };

    function getSelectedIncomingIds() {
        return Array.from(document.querySelectorAll('.incoming-arrived-cb:checked')).map(cb => parseInt(cb.value));
    }

    window.submitArrivedIncoming = async function() {
        const ids = getSelectedIncomingIds();
        if (ids.length === 0) { alert('Seleziona almeno una carta.'); return; }

        const btn = document.getElementById('incoming-arrived-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Salvataggio...';

        try {
            const res = await fetch('/collezioni/incoming/arrived', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    incoming_ids: ids,
                    condition: document.getElementById('incoming-arrived-condition').value
                })
            });

            if (res.ok) {
                closeIncomingArrivedModal();
                clearMissingSelection();
                if (typeof reloadCardsGrid === 'function') {
                    reloadCardsGrid(document.getElementById('filter-page')?.value || 1);
                } else {
                    location.reload();
                }
            } else {
                alert('Errore durante il salvataggio.');
            }
        } catch(e) {
            console.error(e);
            alert('Errore di rete.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '📦 Aggiungi in Collezione';
        }
    };

    window.removeSelectedIncoming = async function() {
        const ids = getSelectedIncomingIds();
        if (ids.length === 0) { alert('Seleziona almeno una carta.'); return; }
        if (!confirm('Vuoi annullare lo status "In Arrivo" per le carte selezionate?')) return;

        try {
            const res = await fetch('/collezioni/incoming/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ incoming_ids: ids })
            });

            if (res.ok) {
                closeIncomingArrivedModal();
                clearMissingSelection();
                if (typeof reloadCardsGrid === 'function') {
                    reloadCardsGrid(document.getElementById('filter-page')?.value || 1);
                } else {
                    location.reload();
                }
            } else {
                alert('Errore.');
            }
        } catch(e) {
            console.error(e);
        }
    };

    window.cancelIncomingByCardId = async function(cardId, element) {
        if (!confirm('Vuoi annullare lo status "In Arrivo" per questa carta?')) return;
        
        if (element) {
            const originalHtml = element.innerHTML;
            element.disabled = true;
            element.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }

        try {
            const res = await fetch('/collezioni/incoming/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ card_ids: [cardId] })
            });

            if (res.ok) {
                if (typeof reloadCardsGrid === 'function') {
                    reloadCardsGrid(document.getElementById('filter-page')?.value || 1);
                } else {
                    location.reload();
                }
            } else {
                alert('Errore.');
                if (element) {
                    element.disabled = false;
                    element.innerHTML = '❌';
                }
            }
        } catch(e) {
            console.error(e);
            if (element) {
                element.disabled = false;
                element.innerHTML = '❌';
            }
        }
    };
</script>
