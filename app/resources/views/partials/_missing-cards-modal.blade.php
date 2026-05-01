<div id="missing-cards-overlay" class="modal-overlay">
    <div onclick="closeMissingCardsModal()" class="position-absolute inset-0 w-100 h-100"></div>
    <div class="card-modal-content p-4 position-relative" style="z-index:10; max-width: 800px; width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-shrink-0">
            <h2 class="fw-bold mb-0" style="font-size:1.25rem; color:#d4e4fa;">Aggiungi Carte Mancanti</h2>
            <button onclick="closeMissingCardsModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="mcm-missing-loading" class="text-center py-5 flex-grow-1">
            <div class="spinner-border text-warning" role="status"></div>
        </div>

        <div id="mcm-missing-content" class="flex-grow-1" style="display:none; overflow-y: auto; padding-right: 8px;">
            <div class="row g-3" id="missing-cards-grid">
                <!-- Cards injected here -->
            </div>
            <div id="no-missing-cards" style="display:none;" class="text-center text-secondary py-5">
                Hai completato questo set! Non ci sono carte mancanti.
            </div>
        </div>
        
        <div class="mt-4 text-end flex-shrink-0">
            <button class="btn btn-secondary" onclick="closeMissingCardsModal()">Chiudi e Aggiorna</button>
        </div>
    </div>
</div>

<script>
    let missingCardsNeedsReload = false;

    window.openMissingCardsModal = function() {
        missingCardsNeedsReload = false;
        var overlay = document.getElementById('missing-cards-overlay');
        overlay.style.display = 'flex';
        
        document.getElementById('mcm-missing-loading').style.display = 'block';
        document.getElementById('mcm-missing-content').style.display = 'none';
        
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
        });
        
        document.body.style.overflow = 'hidden';
        
        fetchMissingCards();
    };

    window.closeMissingCardsModal = function() {
        var overlay = document.getElementById('missing-cards-overlay');
        overlay.classList.remove('is-open');
        setTimeout(() => {
            overlay.style.display = 'none';
            if (missingCardsNeedsReload) {
                window.location.reload();
            }
        }, 300);
        document.body.style.overflow = '';
    };

    async function fetchMissingCards() {
        try {
            const res = await fetch(`{{ route('collezioni.set.missing', ['set' => $set->id]) }}`);
            const data = await res.json();
            renderMissingCards(data);
        } catch(e) {
            console.error(e);
        } finally {
            document.getElementById('mcm-missing-loading').style.display = 'none';
            document.getElementById('mcm-missing-content').style.display = 'block';
        }
    }

    function renderMissingCards(cards) {
        const grid = document.getElementById('missing-cards-grid');
        const empty = document.getElementById('no-missing-cards');
        
        if (cards.length === 0) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        
        empty.style.display = 'none';
        let html = '';
        cards.forEach(card => {
            const img = card.url_image ? `<img src="${card.url_image}/low.png" style="width: 50px; height: 70px; object-fit: contain; border-radius: 4px;" loading="lazy">` : `<div style="width: 50px; height: 70px; background: rgba(255,255,255,0.1); border-radius: 4px;"></div>`;
            
            html += `
                <div class="col-12 col-md-6 col-lg-4 missing-card-item" id="missing-card-${card.id}">
                    <div class="d-flex align-items-center gap-2 p-2" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem;">
                        ${img}
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-bold text-truncate text-white" style="font-size: 0.85rem;" title="${card.name}">${card.name}</div>
                            <div class="text-secondary text-truncate" style="font-size: 0.75rem;">#${card.dexId} · ${card.rarity || 'Common'}</div>
                            <button class="btn btn-sm btn-warning fw-bold py-0 px-2 mt-1 w-100 text-dark" style="font-size: 0.7rem;" onclick="addMissingCard(${card.id}, this)">
                                Aggiungi
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;
    }

    window.addMissingCard = async function(cardId, btnEl) {
        btnEl.disabled = true;
        btnEl.textContent = '...';
        
        try {
            const res = await fetch(`/collezioni/cards/${cardId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            
            if (data.esito) {
                missingCardsNeedsReload = true;
                const item = document.getElementById(`missing-card-${cardId}`);
                if (item) item.remove();
            } else {
                btnEl.disabled = false;
                btnEl.textContent = 'Aggiungi';
            }
        } catch(e) {
            console.error(e);
            btnEl.disabled = false;
            btnEl.textContent = 'Aggiungi';
        }
    }
</script>
