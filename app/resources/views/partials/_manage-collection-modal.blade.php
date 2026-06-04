<div id="mcm-overlay" class="modal-overlay">
    <div onclick="closeManageModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 600px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">{{ __('Gestisci Copie') }}: <span id="mcm-card-name"></span></h2>
            <button onclick="closeManageModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="mcm-loading" class="text-center py-4">
            <div class="spinner-border" style="color: #f59e0b;" role="status"></div>
        </div>

        <div id="mcm-content" style="display:none;">
            <!-- Existing copies -->
            <div class="mb-4">
                <h6 class="text-secondary text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">{{ __('Copie in possesso') }}</h6>
                <div id="mcm-copies-list" class="d-flex flex-column gap-2">
                    <!-- Copies injected via JS -->
                </div>
            </div>

            <!-- Add new copy form -->
            <div class="p-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem;">
                <h6 class="text-white mb-3" style="font-size: 0.85rem;">{{ __('Aggiungi nuova copia') }}</h6>
                <form id="mcm-add-form" onsubmit="event.preventDefault(); submitAddCopy();">
                    <input type="hidden" id="mcm-card-id" value="">
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary small">{{ __('Condizione') }}</label>
                            <select id="mcm-condition" class="form-select bg-dark text-white border-secondary" required>
                                <option value="NM">NM (Near Mint)</option>
                                <option value="LP">LP (Lightly Played)</option>
                                <option value="MP">MP (Moderately Played)</option>
                                <option value="HP">HP (Heavily Played)</option>
                                <option value="DMG">DMG (Damaged)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary small">{{ __('Lingua') }}</label>
                            <select id="mcm-language" class="form-select bg-dark text-white border-secondary" required>
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
                            <input type="number" id="mcm-quantity" class="form-control bg-dark text-white border-secondary" value="1" min="1" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-secondary small">{{ __('Foil Carta') }}</label>
                            <select id="mcm-foil" class="form-select bg-dark text-white border-secondary">
                                <option value="normal">Normale</option>
                                <option value="holo">Holo</option>
                                <option value="reverse">Reverse</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label text-secondary small">{{ __('Opzioni Extra') }}</label>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                                    <input type="checkbox" id="mcm-first-edition" class="form-check-input mt-0"> 1ª Edizione
                                </label>
                                <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                                    <input type="checkbox" id="mcm-signed" class="form-check-input mt-0"> Signed
                                </label>
                                <label class="d-flex align-items-center gap-1 text-white" style="font-size: 0.85rem;">
                                    <input type="checkbox" id="mcm-altered" class="form-check-input mt-0"> Altered
                                </label>
                            </div>
                        </div>
                        <div class="col-12 text-end mt-3">
                            <button type="submit" class="btn btn-sm px-4 fw-bold text-dark" style="background-color: #fbb400; border: none;">{{ __('Aggiungi') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.openManageModal = function(cardId, cardName) {
        document.getElementById('mcm-card-name').textContent = cardName;
        document.getElementById('mcm-card-id').value = cardId;
        
        var overlay = document.getElementById('mcm-overlay');
        overlay.style.display = 'flex';
        
        document.getElementById('mcm-loading').style.display = 'block';
        document.getElementById('mcm-content').style.display = 'none';
        
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
        });
        
        document.body.style.overflow = 'hidden';
        
        loadCopies(cardId);
    };

    window.closeManageModal = function() {
        var overlay = document.getElementById('mcm-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
        document.body.style.overflow = '';
    };

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    async function loadCopies(cardId) {
        try {
            const res = await fetch(`/collezioni/cards/${cardId}/copies`);
            const data = await res.json();
            renderCopies(data);
        } catch(e) {
            console.error('Error loading copies', e);
        } finally {
            document.getElementById('mcm-loading').style.display = 'none';
            document.getElementById('mcm-content').style.display = 'block';
        }
    }

    function renderCopies(copies) {
        const list = document.getElementById('mcm-copies-list');
        if (!copies || copies.length === 0) {
            list.innerHTML = '<div class="text-secondary small fst-italic">' + (window.__trans ? window.__trans.no_copies : 'Nessuna copia in collezione.') + '</div>';
            return;
        }
        
        let html = '';
        copies.forEach(copy => {
            const langStr = copy.language ? copy.language.toUpperCase() : 'EN';
            const foilStr = !copy.foil_type || copy.foil_type === 'normal' ? '' : `<span class="badge bg-info text-dark ms-1">${copy.foil_type.charAt(0).toUpperCase() + copy.foil_type.slice(1)}</span>`;
            
            let extraArr = [];
            if (copy.is_first_edition) extraArr.push('1ª Ed');
            if (copy.is_signed) extraArr.push('Signed');
            if (copy.is_altered) extraArr.push('Altered');
            const extraStr = extraArr.length > 0 ? `<span class="badge bg-warning text-dark ms-1">${extraArr.join(', ')}</span>` : '';
            
            html += `
                <div class="d-flex align-items-center justify-content-between p-2" style="background: rgba(255,255,255,0.05); border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                    <div>
                        <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3);">${copy.condition}</span>
                        <span class="badge bg-secondary ms-1">${langStr}</span>
                        ${foilStr}
                        ${extraStr}
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQty(${copy.id}, ${copy.quantity - 1})">-</button>
                            <input type="text" class="form-control text-center bg-dark text-white" value="${copy.quantity}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQty(${copy.id}, ${copy.quantity + 1})">+</button>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCopy(${copy.id})" title="{{ __('Rimuovi') }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    }

    window.updateQty = async function(copyId, newQty) {
        if (newQty < 0) return;
        
        try {
            const res = await fetch(`/collezioni/copies/${copyId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ quantity: newQty })
            });
            
            if(res.ok) {
                // reload
                loadCopies(document.getElementById('mcm-card-id').value);
            }
        } catch(e) {
            console.error('Update err', e);
        }
    };

    window.deleteCopy = async function(copyId) {
        if(!confirm(window.__trans ? window.__trans.confirm_remove_copy : 'Vuoi rimuovere questa copia?')) return;
        
        try {
            const res = await fetch(`/collezioni/copies/${copyId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            
            if(res.ok) {
                loadCopies(document.getElementById('mcm-card-id').value);
            }
        } catch(e) {
            console.error('Delete err', e);
        }
    };

    window.submitAddCopy = async function() {
        const cardId = document.getElementById('mcm-card-id').value;
        const condition = document.getElementById('mcm-condition').value;
        const quantity = document.getElementById('mcm-quantity').value;
        const language = document.getElementById('mcm-language').value;
        const foilType = document.getElementById('mcm-foil').value;
        
        const isFirstEdition = document.getElementById('mcm-first-edition').checked;
        const isSigned = document.getElementById('mcm-signed').checked;
        const isAltered = document.getElementById('mcm-altered').checked;
        
        try {
            const res = await fetch(`/collezioni/cards/${cardId}/copies`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
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
                // reset form
                document.getElementById('mcm-add-form').reset();
                loadCopies(cardId);
            } else {
                alert(window.__trans ? window.__trans.save_error : 'Errore durante il salvataggio.');
            }
        } catch(e) {
            console.error('Save err', e);
        }
    };
</script>
