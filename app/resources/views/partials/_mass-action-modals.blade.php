<div id="mam-add-overlay" class="modal-overlay">
    <div onclick="closeMassAddModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 600px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">{{ __('Aggiungi Copie Massive') }}</h2>
            <button onclick="closeMassAddModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="text-secondary small mb-4">{{ __('Stai per aggiungere la stessa copia a ') }} <strong id="mam-add-count" class="text-white">0</strong> {{ __(' carte.') }}</p>

        <form id="mam-add-form" onsubmit="event.preventDefault(); submitMassAdd();">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Condizione') }}</label>
                    <select id="mam-add-condition" class="form-select bg-dark text-white border-secondary" required>
                        <option value="NM">NM</option>
                        <option value="LP">LP</option>
                        <option value="MP">MP</option>
                        <option value="HP">HP</option>
                        <option value="DMG">DMG</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Lingua') }}</label>
                    <select id="mam-add-language" class="form-select bg-dark text-white border-secondary" required>
                        <option value="it">Italiano (IT)</option>
                        <option value="en">Inglese (EN)</option>
                        <option value="jp">Giapponese (JP)</option>
                        <option value="fr">Francese (FR)</option>
                        <option value="de">Tedesco (DE)</option>
                        <option value="es">Spagnolo (ES)</option>
                        <option value="pt">Portoghese (PT)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label text-secondary small">{{ __('Quantità') }}</label>
                    <input type="number" id="mam-add-quantity" class="form-control bg-dark text-white border-secondary" value="1" min="1" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-secondary small">{{ __('Foil Carta') }}</label>
                    <select id="mam-add-foil" class="form-select bg-dark text-white border-secondary">
                        <option value="normal">Normale</option>
                        <option value="holo">Holo</option>
                        <option value="reverse">Reverse</option>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label text-secondary small">{{ __('Opzioni Extra') }}</label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="mam-add-first-edition" class="form-check-input mt-0"> 1ª Edizione
                        </label>
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="mam-add-signed" class="form-check-input mt-0"> Signed
                        </label>
                        <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                            <input type="checkbox" id="mam-add-altered" class="form-check-input mt-0"> Altered
                        </label>
                    </div>
                </div>
                <div class="col-12 text-end mt-4">
                    <button type="submit" id="mam-add-submit-btn" class="btn px-4 fw-bold text-dark" style="background-color: #fbb400; border: none;">
                        {{ __('Aggiungi Copie') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="mam-edit-overlay" class="modal-overlay">
    <div onclick="closeMassEditModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 800px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">{{ __('Modifica Quantità') }}</h2>
            <button onclick="closeMassEditModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="text-secondary small mb-4">{{ __('Modifica le quantità delle copie che già possiedi. Imposta a 0 per eliminare una copia.') }}</p>

        <div id="mam-edit-loading" class="text-center py-5">
            <div class="spinner-border" style="color: #f59e0b;" role="status"></div>
        </div>

        <div id="mam-edit-content" style="display:none; max-height: 50vh; overflow-y: auto;" class="mb-4 pe-2">
            <table class="table table-dark table-hover mb-0" style="font-size: 0.85rem; background-color: transparent;">
                <thead>
                    <tr>
                        <th class="text-secondary border-secondary" style="background-color: transparent;">Carta</th>
                        <th class="text-secondary border-secondary" style="background-color: transparent;">Condizione</th>
                        <th class="text-secondary border-secondary" style="background-color: transparent;">Lingua</th>
                        <th class="text-secondary border-secondary" style="background-color: transparent;">Foil</th>
                        <th class="text-secondary border-secondary" style="background-color: transparent;">Extra</th>
                        <th class="text-secondary border-secondary text-center" style="background-color: transparent; width: 100px;">Qtà</th>
                    </tr>
                </thead>
                <tbody id="mam-edit-tbody">
                    <!-- Riempiuto da JS -->
                </tbody>
            </table>
        </div>

        <div class="text-end" id="mam-edit-footer" style="display:none;">
            <button type="button" onclick="submitMassEdit()" class="btn px-4 fw-bold text-dark" style="background-color: #fbb400; border: none;">
                {{ __('Salva Modifiche') }}
            </button>
        </div>
    </div>
</div>

<script>
    window.openMassAddModal = function() {
        if (!window.selectedCards || window.selectedCards.size === 0) return;
        document.getElementById('mam-add-count').textContent = window.selectedCards.size;
        
        var overlay = document.getElementById('mam-add-overlay');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
    };

    window.closeMassAddModal = function() {
        var overlay = document.getElementById('mam-add-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => overlay.style.display = 'none', 300);
        document.body.style.overflow = '';
    };

    window.openMassEditModal = function() {
        if (!window.selectedCards || window.selectedCards.size === 0) return;
        
        var overlay = document.getElementById('mam-edit-overlay');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
        
        document.getElementById('mam-edit-loading').style.display = 'block';
        document.getElementById('mam-edit-content').style.display = 'none';
        document.getElementById('mam-edit-footer').style.display = 'none';

        fetchMassCopies();
    };

    window.closeMassEditModal = function() {
        var overlay = document.getElementById('mam-edit-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => overlay.style.display = 'none', 300);
        document.body.style.overflow = '';
    };

    async function fetchMassCopies() {
        try {
            const res = await fetch('/collezioni/cards/mass-copies', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ card_ids: Array.from(window.selectedCards).map(Number) })
            });
            const data = await res.json();
            renderMassEditTable(data);
        } catch (e) {
            console.error('Error fetching mass copies:', e);
            alert('Errore nel caricamento delle copie.');
            closeMassEditModal();
        }
    }

    function renderMassEditTable(groupedData) {
        const tbody = document.getElementById('mam-edit-tbody');
        let html = '';
        
        if (groupedData.length === 0) {
            html = '<tr><td colspan="4" class="text-center text-secondary py-4">Nessuna copia trovata per le carte selezionate.</td></tr>';
        } else {
            groupedData.forEach(group => {
                group.copies.forEach((copy) => {
                    const langStr = copy.language ? copy.language.toUpperCase() : 'EN';
                    const foilStr = !copy.foil_type || copy.foil_type === 'normal' ? '-' : copy.foil_type.charAt(0).toUpperCase() + copy.foil_type.slice(1);
                    
                    let extraArr = [];
                    if (copy.is_first_edition) extraArr.push('1ª Ed');
                    if (copy.is_signed) extraArr.push('Signed');
                    if (copy.is_altered) extraArr.push('Altered');
                    const extraStr = extraArr.length > 0 ? extraArr.join(', ') : '-';
                    
                    html += `
                        <tr>
                            <td class="align-middle border-secondary text-white" style="background-color: transparent;">
                                <strong>${group.card.name}</strong><br><small class="text-secondary">${group.card.set ? group.card.set.name : ''}</small>
                            </td>
                            <td class="align-middle border-secondary" style="background-color: transparent;">
                                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3);">${copy.condition}</span>
                            </td>
                            <td class="align-middle border-secondary text-light" style="background-color: transparent;">
                                <span class="badge bg-secondary">${langStr}</span>
                            </td>
                            <td class="align-middle border-secondary text-light" style="background-color: transparent;">
                                ${foilStr !== '-' ? `<span class="badge bg-info text-dark">${foilStr}</span>` : '<span class="text-secondary">-</span>'}
                            </td>
                            <td class="align-middle border-secondary text-light" style="background-color: transparent;">
                                ${extraStr !== '-' ? `<span class="badge bg-warning text-dark">${extraStr}</span>` : '<span class="text-secondary">-</span>'}
                            </td>
                            <td class="align-middle border-secondary text-center" style="background-color: transparent;">
                                <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary text-center mam-qty-input" 
                                    data-copy-id="${copy.id}" value="${copy.quantity}" min="0">
                            </td>
                        </tr>
                    `;
                });
            });
        }
        
        tbody.innerHTML = html;
        document.getElementById('mam-edit-loading').style.display = 'none';
        document.getElementById('mam-edit-content').style.display = 'block';
        if (groupedData.length > 0) {
            document.getElementById('mam-edit-footer').style.display = 'block';
        }
    }

    window.submitMassAdd = async function() {
        const btn = document.getElementById('mam-add-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aggiungendo...';
        
        const condition = document.getElementById('mam-add-condition').value;
        const language = document.getElementById('mam-add-language').value;
        const quantity = document.getElementById('mam-add-quantity').value;
        const foilType = document.getElementById('mam-add-foil').value;
        
        const isFirstEdition = document.getElementById('mam-add-first-edition').checked;
        const isSigned = document.getElementById('mam-add-signed').checked;
        const isAltered = document.getElementById('mam-add-altered').checked;

        try {
            const res = await fetch('/collezioni/cards/mass-add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    card_ids: Array.from(window.selectedCards).map(Number),
                    condition: condition,
                    quantity: quantity,
                    language: language,
                    foil_type: foilType,
                    is_first_edition: isFirstEdition,
                    is_signed: isSigned,
                    is_altered: isAltered
                })
            });
            
            if(res.ok) {
                closeMassAddModal();
                if(typeof clearSelection === 'function') clearSelection();
                if(typeof reloadCardsGrid === 'function') reloadCardsGrid(1);
            } else {
                alert('Errore durante l\'aggiunta.');
            }
        } catch(e) {
            console.error(e);
            alert('Errore di rete.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Aggiungi Copie';
        }
    };

    window.submitMassEdit = async function() {
        const inputs = document.querySelectorAll('.mam-qty-input');
        const updates = {};
        let hasChanges = false;
        
        inputs.forEach(input => {
            const originalVal = input.getAttribute('value');
            const newVal = input.value;
            if (originalVal !== newVal) {
                updates[input.getAttribute('data-copy-id')] = parseInt(newVal, 10);
                hasChanges = true;
            }
        });
        
        if (!hasChanges) {
            closeMassEditModal();
            return;
        }

        try {
            const res = await fetch('/collezioni/copies/mass-update', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ updates: updates })
            });
            
            if(res.ok) {
                closeMassEditModal();
                if(typeof clearSelection === 'function') clearSelection();
                if(typeof reloadCardsGrid === 'function') reloadCardsGrid(1);
            } else {
                alert('Errore durante il salvataggio.');
            }
        } catch(e) {
            console.error(e);
            alert('Errore di rete.');
        }
    };
</script>
