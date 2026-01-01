@extends('layouts.app')

@section('title', 'Collezione Carte')

@push('styles')
<style>
    /* Stats Bar */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-3px);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--pokemon-yellow) 0%, #fff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 0.25rem;
    }

    /* Filter Bar */
    .filter-bar {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
    }

    .filter-bar .form-control,
    .filter-bar .form-select {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        border-radius: 10px;
        font-size: 0.9rem;
    }

    .filter-bar .form-control::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        border-color: var(--pokemon-yellow) !important;
        box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.2) !important;
    }

    .filter-bar label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    /* Card Grid */
    .card-item {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .card-item:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    /* Type-specific glow on hover */
    .card-item[data-type="fuoco"]:hover,
    .card-item[data-type="fire"]:hover {
        border-color: var(--type-fire);
        box-shadow: 0 10px 30px rgba(240, 128, 48, 0.3);
    }

    .card-item[data-type="acqua"]:hover,
    .card-item[data-type="water"]:hover {
        border-color: var(--type-water);
        box-shadow: 0 10px 30px rgba(104, 144, 240, 0.3);
    }

    .card-item[data-type="erba"]:hover,
    .card-item[data-type="grass"]:hover {
        border-color: var(--type-grass);
        box-shadow: 0 10px 30px rgba(120, 200, 80, 0.3);
    }

    .card-item[data-type="elettro"]:hover,
    .card-item[data-type="electric"]:hover {
        border-color: var(--type-electric);
        box-shadow: 0 10px 30px rgba(248, 208, 48, 0.3);
    }

    .card-item[data-type="psico"]:hover,
    .card-item[data-type="psychic"]:hover {
        border-color: var(--type-psychic);
        box-shadow: 0 10px 30px rgba(248, 88, 136, 0.3);
    }

    .card-item[data-type="lotta"]:hover,
    .card-item[data-type="fighting"]:hover {
        border-color: var(--type-fighting);
        box-shadow: 0 10px 30px rgba(192, 48, 40, 0.3);
    }

    .card-item[data-type="buio"]:hover,
    .card-item[data-type="dark"]:hover {
        border-color: var(--type-dark);
        box-shadow: 0 10px 30px rgba(112, 88, 72, 0.3);
    }

    .card-item[data-type="acciaio"]:hover,
    .card-item[data-type="steel"]:hover {
        border-color: var(--type-steel);
        box-shadow: 0 10px 30px rgba(184, 184, 208, 0.3);
    }

    .card-item[data-type="drago"]:hover,
    .card-item[data-type="dragon"]:hover {
        border-color: var(--type-dragon);
        box-shadow: 0 10px 30px rgba(112, 56, 248, 0.3);
    }

    .card-image-container {
        position: relative;
        height: 220px;
        overflow: hidden;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        background: rgba(0, 0, 0, 0.2);
    }

    .card-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
        cursor: pointer;
    }

    .card-item:hover .card-image {
        transform: scale(1.05);
    }

    .card-body-custom {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .pokemon-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        gap: 10px;
    }

    .pokemon-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .pokemon-hp {
        font-size: 0.9rem;
        color: var(--pokemon-red);
        font-weight: 700;
        white-space: nowrap;
    }

    .delete-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: rgba(220, 53, 69, 0.9);
        border: none;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 10;
    }

    .card-item:hover .delete-btn {
        opacity: 1;
    }

    .delete-btn:hover {
        background: #dc3545;
        transform: scale(1.1);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-icon {
        font-size: 5rem;
        color: rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
    }

    /* Modal */
    .modal-content {
        background: rgba(30, 30, 60, 0.98);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
    }

    .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal-body-detail-row {
        display: flex;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 8px 0;
    }

    .modal-body-detail-label {
        width: 120px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 500;
    }

    .modal-body-detail-value {
        color: #fff;
        flex: 1;
    }

    .attack-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        border-left: 4px solid var(--pokemon-yellow);
    }

    .attack-name {
        font-weight: 700;
        color: #fff;
    }

    .attack-damage {
        font-weight: 700;
        color: var(--pokemon-red);
        font-size: 1.2rem;
    }

    .attack-cost {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .attack-effect {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 6px;
    }

    /* Pagination */
    .pagination {
        margin-top: 30px;
    }

    .pagination .page-link {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-link:hover {
        background: rgba(255, 203, 5, 0.2);
        border-color: var(--pokemon-yellow);
    }

    .pagination .page-item.active .page-link {
        background: var(--pokemon-yellow);
        border-color: var(--pokemon-yellow);
        color: #000;
    }

    pre {
        white-space: pre-wrap;
        color: rgba(255, 255, 255, 0.7);
        font-family: inherit;
    }

    /* No results */
    .no-results {
        text-align: center;
        padding: 3rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .no-results i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <div class="mb-3 mb-md-0">
                <h1 class="page-title mb-2">
                    <i class="bi bi-collection-fill me-3"></i>
                    La Mia Collezione
                </h1>
                <p class="page-subtitle mb-0">Gestisci e visualizza tutte le tue carte Pokemon scansionate</p>
            </div>
            <a href="{{ route('ocr.upload') }}" class="btn btn-pokemon">
                <i class="bi bi-plus-circle me-2"></i>Scansiona Nuova Carta
            </a>
        </div>

        @if($cards->total() > 0)
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value">{{ $cards->total() }}</div>
                <div class="stat-label">Totale Carte</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $cards->where('status', 'completed')->count() }}</div>
                <div class="stat-label">Complete</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $cards->whereNotNull('type')->unique('type')->count() }}</div>
                <div class="stat-label">Tipi Diversi</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $cards->where('status', 'pending')->count() + $cards->where('status', 'review')->count() }}</div>
                <div class="stat-label">In Attesa</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label>Cerca per nome</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 text-white-50"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="es. Pikachu, Charizard...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label>Tipo</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Tutti i tipi</option>
                        <option value="erba">🌿 Erba</option>
                        <option value="fuoco">🔥 Fuoco</option>
                        <option value="acqua">💧 Acqua</option>
                        <option value="elettro">⚡ Elettro</option>
                        <option value="psico">🔮 Psico</option>
                        <option value="lotta">👊 Lotta</option>
                        <option value="buio">🌑 Buio</option>
                        <option value="acciaio">⚙️ Acciaio</option>
                        <option value="drago">🐉 Drago</option>
                        <option value="incolore">⚪ Incolore</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Stato</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tutti</option>
                        <option value="completed">✅ Completate</option>
                        <option value="pending">⏳ In attesa</option>
                        <option value="failed">❌ Fallite</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-light w-100" id="resetFilters">
                        <i class="bi bi-x-lg me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="row g-4" id="cardsGrid">
            @foreach($cards as $card)
            <div class="col-md-6 col-lg-4 col-xl-3 card-wrapper"
                data-name="{{ strtolower($card->card_name ?? '') }}"
                data-type="{{ strtolower($card->type ?? '') }}"
                data-status="{{ $card->status }}">
                <div class="card-item" data-type="{{ strtolower($card->type ?? '') }}">
                    <button class="delete-btn" onclick="deleteCard({{ $card->id }})" title="Elimina">
                        <i class="bi bi-trash"></i>
                    </button>

                    <div class="card-image-container" onclick="viewCard({{ $card->id }})" data-bs-toggle="modal" data-bs-target="#cardModal{{ $card->id }}">
                        <img src="{{ Storage::url($card->storage_path) }}" alt="Pokemon Card" class="card-image" loading="lazy">
                    </div>

                    <div class="card-body-custom">
                        @if($card->card_name || $card->hp)
                        <div class="pokemon-header">
                            <h5 class="pokemon-name" title="{{ $card->card_name }}">{{ $card->card_name ?? 'Senza Nome' }}</h5>
                            @if($card->hp)
                            <span class="pokemon-hp">HP {{ $card->hp }}</span>
                            @endif
                        </div>
                        @endif

                        @if($card->type)
                        <div class="mb-2">
                            <span class="type-badge type-{{ strtolower($card->type) }}">{{ $card->type }}</span>
                        </div>
                        @endif

                        @if($card->rarity)
                        <small class="text-white-50 d-block mb-2">{{ $card->rarity }}</small>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <small class="text-white-50">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $card->created_at->format('d/m/Y') }}
                            </small>
                            @if($card->status === 'completed')
                            <i class="bi bi-check-circle-fill text-success" title="Completato"></i>
                            @elseif($card->status === 'pending' || $card->status === 'review')
                            <i class="bi bi-hourglass-split text-warning" title="In attesa"></i>
                            @else
                            <i class="bi bi-x-circle-fill text-danger" title="Fallito"></i>
                            @endif
                        </div>

                        @if(!$card->card_name)
                        <div class="mt-2">
                            <small class="text-white-50 fst-italic">Dati incompleti (OCR grezzo)</small>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Modal for full view -->
                <div class="modal fade" id="cardModal{{ $card->id }}" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title text-white">
                                    <i class="bi bi-card-image me-2"></i>
                                    {{ $card->card_name ?? 'Dettagli Carta' }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white"
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row">
                                    <div class="col-md-5 text-center mb-3 mb-md-0">
                                        <img src="{{ Storage::url($card->storage_path) }}" class="img-fluid rounded shadow-lg"
                                            alt="Pokemon Card" style="max-height: 450px;">
                                    </div>
                                    <div class="col-md-7">
                                        <div class="glass-card p-3 h-100">

                                            <div class="d-flex justify-content-between align-items-end mb-4 border-bottom border-light pb-2">
                                                <div>
                                                    <h2 class="mb-1 text-white fw-bold">{{ $card->card_name ?? 'Nome non rilevato' }}</h2>
                                                    @if($card->type)
                                                    <span class="type-badge type-{{ strtolower($card->type) }}">{{ $card->type }}</span>
                                                    @endif
                                                </div>
                                                @if($card->hp)
                                                <h3 class="mb-0 text-danger fw-bold">HP {{ $card->hp }}</h3>
                                                @endif
                                            </div>

                                            <div class="modal-body-detail-row">
                                                <span class="modal-body-detail-label">Stadio</span>
                                                <span class="modal-body-detail-value">{{ $card->evolution_stage ?? '-' }}</span>
                                            </div>

                                            @if($card->attacks)
                                            <div class="mt-4 mb-3">
                                                <h5 class="text-white border-bottom border-secondary pb-2 mb-3">
                                                    <i class="bi bi-lightning-charge me-2"></i>Attacchi
                                                </h5>
                                                @php
                                                $attacks = is_string($card->attacks) ? json_decode($card->attacks, true) : $card->attacks;
                                                @endphp

                                                @if(is_array($attacks))
                                                @foreach($attacks as $attack)
                                                <div class="attack-card">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="attack-name">{{ $attack['name'] ?? 'Attacco' }}</span>
                                                            <div class="attack-cost">{{ $attack['cost'] ?? '' }}</div>
                                                        </div>
                                                        <span class="attack-damage">{{ $attack['damage'] ?? '' }}</span>
                                                    </div>
                                                    @if(!empty($attack['effect']))
                                                    <div class="attack-effect">{{ $attack['effect'] }}</div>
                                                    @endif
                                                </div>
                                                @endforeach
                                                @else
                                                <pre>{{ $card->attacks }}</pre>
                                                @endif
                                            </div>
                                            @endif

                                            <div class="row mt-4">
                                                <div class="col-md-4">
                                                    <div class="modal-body-detail-row flex-column align-items-start border-0">
                                                        <span class="modal-body-detail-label w-100 mb-1">Debolezza</span>
                                                        <span class="modal-body-detail-value">{{ $card->weakness ?? '-' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="modal-body-detail-row flex-column align-items-start border-0">
                                                        <span class="modal-body-detail-label w-100 mb-1">Resistenza</span>
                                                        <span class="modal-body-detail-value">{{ $card->resistance ?? '-' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="modal-body-detail-row flex-column align-items-start border-0">
                                                        <span class="modal-body-detail-label w-100 mb-1">Ritirata</span>
                                                        <span class="modal-body-detail-value">{{ $card->retreat_cost ?? '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4 pt-3 border-top border-secondary">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <small class="d-block text-white-50">Set: {{ $card->set_number ?? '-' }}</small>
                                                        <small class="d-block text-white-50">Rarità: {{ $card->rarity ?? '-' }}</small>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <small class="d-block text-white-50">Illustratore: {{ $card->illustrator ?? '-' }}</small>
                                                    </div>
                                                </div>
                                                @if($card->flavor_text)
                                                <div class="mt-3 fst-italic small text-white-50">
                                                    "{{ $card->flavor_text }}"
                                                </div>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-light"
                                    data-bs-dismiss="modal">Chiudi</button>
                                <button type="button" class="btn btn-danger-pokemon"
                                    onclick="deleteCard({{ $card->id }})">
                                    <i class="bi bi-trash me-2"></i>Elimina
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- No Results Message -->
        <div class="no-results d-none" id="noResults">
            <i class="bi bi-search"></i>
            <p>Nessuna carta trovata con i filtri selezionati</p>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-5">
            {{ $cards->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="glass-card p-5">
            <div class="empty-state">
                <i class="bi bi-inbox empty-icon"></i>
                <h3 class="text-white mb-3">Nessuna carta ancora</h3>
                <p class="text-white-50 mb-4">Inizia a scansionare le tue carte Pokemon!</p>
                <a href="{{ route('ocr.upload') }}" class="btn btn-pokemon btn-lg">
                    <i class="bi bi-camera-fill me-2"></i>Scansiona la Prima Carta
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Filter functionality
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const cardsGrid = document.getElementById('cardsGrid');
    const noResults = document.getElementById('noResults');

    function filterCards() {
        const searchTerm = searchInput?.value.toLowerCase() || '';
        const typeValue = typeFilter?.value.toLowerCase() || '';
        const statusValue = statusFilter?.value || '';

        const cards = document.querySelectorAll('.card-wrapper');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name || '';
            const type = card.dataset.type || '';
            const status = card.dataset.status || '';

            const matchesSearch = name.includes(searchTerm);
            const matchesType = !typeValue || type === typeValue;
            const matchesStatus = !statusValue || status === statusValue || (statusValue === 'pending' && status === 'review');

            if (matchesSearch && matchesType && matchesStatus) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            if (visibleCount === 0 && cards.length > 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterCards);
    if (typeFilter) typeFilter.addEventListener('change', filterCards);
    if (statusFilter) statusFilter.addEventListener('change', filterCards);

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (typeFilter) typeFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            filterCards();
        });
    }

    // Delete card function
    async function deleteCard(cardId) {
        if (!confirm('Sei sicuro di voler eliminare questa carta?')) {
            return;
        }

        showLoading();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch(`/ocr/cards/${cardId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();
            hideLoading();

            if (data.success) {
                showToast(data.message, 'success');

                // Close modal if open
                const modalEl = document.getElementById(`cardModal${cardId}`);
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                    }
                }

                // Reload page
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Errore durante l\'eliminazione', 'error');
            }
        } catch (error) {
            hideLoading();
            showToast('Errore durante l\'eliminazione: ' + error.message, 'error');
        }
    }

    function viewCard(cardId) {
        // Modal will open automatically via data-bs-toggle
        console.log('Viewing card:', cardId);
    }
</script>
@endpush