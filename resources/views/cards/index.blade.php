@extends('layouts.app')

@section('title', 'My Cards')

@push('styles')
<style>
    .text-pokemon-yellow {
        color: var(--pokemon-yellow);
    }

    .set-section {
        margin-bottom: 3rem;
    }

    .set-header {
        background: linear-gradient(135deg, rgba(255, 203, 5, 0.1) 0%, rgba(30, 35, 60, 0.8) 100%);
        border: 1px solid rgba(255, 203, 5, 0.3);
        border-radius: 12px;
        padding: 15px 25px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .set-header:hover {
        border-color: var(--pokemon-yellow);
        box-shadow: 0 4px 15px rgba(255, 203, 5, 0.2);
    }

    .set-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--pokemon-yellow);
    }

    .set-badge {
        background: var(--pokemon-yellow);
        color: #000;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.875rem;
    }

    /* Smaller card size */
    .card-small {
        transition: transform 0.2s ease;
    }

    .card-small:hover {
        transform: translateY(-5px);
    }

    .card-small .glass-card {
        padding: 10px !important;
        height: 100%;
    }

    .card-small .card-image {
        aspect-ratio: 0.70;
        object-fit: cover;
        border-radius: 8px;
        width: 100%;
        cursor: zoom-in;
    }

    .card-small .card-name {
        font-size: 0.875rem;
        font-weight: 600;
        margin: 8px 0 4px;
        color: var(--pokemon-yellow);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .card-small .card-meta {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .card-actions {
        display: flex;
        gap: 4px;
        margin-top: 8px;
    }

    .card-actions .btn {
        flex: 1;
        padding: 4px 8px;
        font-size: 0.75rem;
    }

    .noset-section {
        background: rgba(220, 53, 69, 0.05);
        border: 1px dashed rgba(220, 53, 69, 0.3);
        border-radius: 12px;
        padding: 20px;
    }

    .noset-section h3 {
        color: #dc3545;
    }

    /* Set collapse animation */
    .set-cards {
        max-height: 3000px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .set-cards.collapsed {
        max-height: 0;
    }

    /* Collapse indicator */
    .collapse-icon {
        transition: transform 0.3s ease;
    }

    .collapsed .collapse-icon {
        transform: rotate(-90deg);
    }

    /* Bulk Selection */
    .card-selector {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 10;
    }

    .card-selector input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--pokemon-yellow);
    }

    .glass-card {
        position: relative;
    }

    .glass-card.selected {
        border: 2px solid var(--pokemon-yellow);
        box-shadow: 0 0 15px rgba(255, 203, 5, 0.5);
    }

    /* Floating Action Bar */
    .floating-bar {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #1e233c 0%, #2a3050 100%);
        border: 1px solid var(--pokemon-yellow);
        border-radius: 50px;
        padding: 12px 25px;
        display: none;
        align-items: center;
        gap: 15px;
        z-index: 8000;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .floating-bar.active {
        display: flex;
    }

    .floating-bar .count {
        color: var(--pokemon-yellow);
        font-weight: bold;
    }

    /* Loader Overlay */
    .loader-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(30, 35, 60, 0.95);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 100;
        border-radius: 12px;
    }

    .loader-overlay.active {
        display: flex;
    }

    .loader-card {
        width: 80px;
        height: 110px;
        background: linear-gradient(135deg, #ffcb05 0%, #ffd84d 100%);
        border-radius: 8px;
        position: relative;
        animation: cardScan 1.5s ease-in-out infinite;
        box-shadow: 0 8px 30px rgba(255, 203, 5, 0.3);
    }

    .loader-card::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b4cca 0%, #7886d7 100%);
        border: 3px solid #fff;
    }

    .loader-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(to bottom, transparent 0%, rgba(255, 255, 255, 0.5) 50%, transparent 100%);
        animation: scanLine 1.5s ease-in-out infinite;
    }

    @keyframes cardScan {

        0%,
        100% {
            transform: scale(1) rotate(0deg);
        }

        50% {
            transform: scale(1.05) rotate(2deg);
        }
    }

    @keyframes scanLine {
        0% {
            transform: translateY(-100%);
        }

        100% {
            transform: translateY(100%);
        }
    }

    .loader-text {
        margin-top: 15px;
        color: var(--pokemon-yellow);
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="text-center mb-5">
        <h1 class="page-title">La Mia Collezione</h1>
        <p class="page-subtitle">Tutte le tue carte Pokemon organizzate per set</p>
    </div>

    @if($cardsBySet->count() > 0 || $cardsWithoutSet->count() > 0)

    {{-- Cards organized by set --}}
    @foreach($cardsBySet as $setName => $cards)
    <div class="set-section">
        <div class="set-header" onclick="toggleSet(this)">
            <h3>
                <i class="bi bi-collection"></i> {{ $setName }}
            </h3>
            <div class="d-flex align-items-center gap-3">
                <span class="set-badge">{{ $cards->count() }} {{ $cards->count() == 1 ? 'carta' : 'carte' }}</span>
                <i class="bi bi-chevron-down collapse-icon"></i>
            </div>
        </div>
        <div class="set-cards">
            <div class="row g-3">
                @foreach($cards as $card)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 card-small">
                    <div class="glass-card" data-card-id="{{ $card->id }}">
                        <div class="card-selector">
                            <input type="checkbox" onchange="toggleCardSelection({{ $card->id }}, this)">
                        </div>
                        <img src="{{ $card->image_url }}"
                            alt="{{ $card->card_name ?? 'Pokemon Card' }}"
                            class="card-image"
                            onclick="openFullscreenCard('{{ $card->image_url }}')">

                        <div class="card-name" title="{{ $card->card_name ?? 'Sconosciuta' }}">
                            {{ $card->card_name ?? 'Sconosciuta' }}
                        </div>

                        @if($card->hp || $card->type)
                        <div class="card-meta">
                            {{ $card->hp ? 'HP ' . $card->hp : '' }}
                            {{ $card->hp && $card->type ? ' • ' : '' }}
                            {{ $card->type ?? '' }}
                        </div>
                        @endif

                        <div class="card-actions">
                            <button class="btn btn-sm btn-info" onclick="viewEditCard({{ $card->id }}, false)" title="Visualizza">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="viewEditCard({{ $card->id }}, true)" title="Modifica">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCard({{ $card->id }})" title="Elimina">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    {{-- Cards without set --}}
    @if($cardsWithoutSet->count() > 0)
    <div class="noset-section">
        <div class="set-header" onclick="toggleSet(this)" style="background: transparent; border: none;">
            <h3>
                <i class="bi bi-question-circle"></i> Senza Set
            </h3>
            <div class="d-flex align-items-center gap-3">
                <span class="set-badge" style="background: #dc3545;">{{ $cardsWithoutSet->count() }}</span>
                <i class="bi bi-chevron-down collapse-icon"></i>
            </div>
        </div>
        <div class="set-cards">
            <div class="row g-3">
                @foreach($cardsWithoutSet as $card)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 card-small">
                    <div class="glass-card" data-card-id="{{ $card->id }}">
                        <div class="card-selector">
                            <input type="checkbox" onchange="toggleCardSelection({{ $card->id }}, this)">
                        </div>
                        <img src="{{ $card->image_url }}"
                            alt="{{ $card->card_name ?? 'Pokemon Card' }}"
                            class="card-image"
                            onclick="openFullscreenCard('{{ $card->image_url }}')">

                        <div class="card-name" title="{{ $card->card_name ?? 'Sconosciuta' }}">
                            {{ $card->card_name ?? 'Sconosciuta' }}
                        </div>

                        @if($card->hp || $card->type)
                        <div class="card-meta">
                            {{ $card->hp ? 'HP ' . $card->hp : '' }}
                            {{ $card->hp && $card->type ? ' • ' : '' }}
                            {{ $card->type ?? '' }}
                        </div>
                        @endif

                        <div class="card-actions">
                            <button class="btn btn-sm btn-info" onclick="viewEditCard({{ $card->id }}, false)" title="Visualizza">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="viewEditCard({{ $card->id }}, true)" title="Modifica">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCard({{ $card->id }})" title="Elimina">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @else
    {{-- Empty state --}}
    <div class="glass-card p-5 text-center">
        <i class="bi bi-inbox" style="font-size: 64px; color: rgba(255, 255, 255, 0.3);"></i>
        <h3 class="mt-3">Nessuna Carta Trovata</h3>
        <p class="text-white-50">Inizia a scansionare le tue carte!</p>
        <a href="{{ route('cards.upload') }}" class="btn btn-pokemon mt-3">
            <i class="bi bi-camera-fill"></i> Carica Carte
        </a>
    </div>
    @endif
</div>

{{-- Floating Action Bar for Bulk Selection --}}
<div class="floating-bar" id="floatingBar">
    <span class="count"><span id="selectedCount">0</span> carte selezionate</span>
    <button class="btn btn-warning btn-sm" onclick="openBulkSetModal()">
        <i class="bi bi-collection"></i> Assegna Set
    </button>
    <button class="btn btn-outline-light btn-sm" onclick="clearSelection()">
        <i class="bi bi-x-lg"></i> Annulla
    </button>
</div>

{{-- Bulk Set Assignment Modal --}}
<div class="modal-overlay" id="bulkSetModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header-custom">
            <h3>Assegna Set a Carte Selezionate</h3>
            <button type="button" class="btn-close-modal" onclick="closeBulkSetModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p class="text-white-50 mb-3">Seleziona il set da assegnare a <strong id="bulkCardCount">0</strong> carte:</p>
            <select class="form-select bg-dark text-white border-secondary" id="bulkSetSelect">
                <option value="">Seleziona un set...</option>
                <!-- Populated via JS -->
            </select>
        </div>
        <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeBulkSetModal()">Annulla</button>
            <button type="button" class="btn btn-success" onclick="saveBulkSet()">
                <i class="bi bi-check-lg"></i> Assegna Set
            </button>
        </div>
    </div>
</div>

{{-- Fullscreen Image Viewer --}}
<div id="fullscreenViewer" onclick="closeFullscreenCard()">
    <img id="fullscreenImage" src="" alt="Fullscreen Card">
</div>

{{-- View/Edit Card Modal --}}
<div class="modal-overlay" id="cardModal">
    <div class="modal-container" style="max-width: 1100px; width: 95%; position: relative;">
        {{-- Loader --}}
        <div class="loader-overlay" id="cardModalLoader">
            <div class="loader-card"></div>
            <div class="loader-text">Caricamento carta...</div>
        </div>
        <div class="modal-header-custom">
            <h3 id="cardModalTitle">Dettagli Carta</h3>
            <button type="button" class="btn-close-modal" onclick="closeCardModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body modal-body-grid" style="display: flex; gap: 20px; padding: 20px;">
            {{-- Card Image --}}
            <div style="flex: 0 0 300px;">
                <img id="modalCardImage" src="" alt="Card" style="width: 100%; border-radius: 10px; cursor: zoom-in;" onclick="openFullscreenCard(this.src)">
            </div>
            {{-- Card Details --}}
            <div style="flex: 1; overflow-y: auto; max-height: 500px;">
                <div id="cardDetailsView">
                    <table class="table table-dark" style="font-size: 0.9rem;">
                        <tbody id="cardDetailsBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="cardEditForm" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label text-warning">Nome Carta</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardName">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning">HP</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardHp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Tipo</label>
                            <select class="form-select bg-dark text-white border-secondary" id="editCardType">
                                <option value="">Seleziona...</option>
                                <option value="Erba">Erba</option>
                                <option value="Fuoco">Fuoco</option>
                                <option value="Acqua">Acqua</option>
                                <option value="Elettro">Elettro</option>
                                <option value="Psico">Psico</option>
                                <option value="Lotta">Lotta</option>
                                <option value="Oscurità">Oscurità</option>
                                <option value="Metallo">Metallo</option>
                                <option value="Drago">Drago</option>
                                <option value="Fata">Fata</option>
                                <option value="Incolore">Incolore</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Stadio Evoluzione</label>
                            <select class="form-select bg-dark text-white border-secondary" id="editCardEvolution">
                                <option value="">Seleziona...</option>
                                <option value="Base">Base</option>
                                <option value="Fase 1">Fase 1</option>
                                <option value="Fase 2">Fase 2</option>
                                <option value="VMAX">VMAX</option>
                                <option value="VSTAR">VSTAR</option>
                                <option value="ex">ex</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning">Debolezza</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardWeakness">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning">Resistenza</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardResistance">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-warning">Costo Ritirata</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardRetreat">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Set</label>
                            <select class="form-select bg-dark text-white border-secondary" id="editCardSet">
                                <option value="">Nessun Set</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Numero Set</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardSetNumber" placeholder="es. 002/094">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Rarità</label>
                            <select class="form-select bg-dark text-white border-secondary" id="editCardRarity">
                                <option value="">Seleziona...</option>
                                <option value="Comune">Comune</option>
                                <option value="Non Comune">Non Comune</option>
                                <option value="Rara">Rara</option>
                                <option value="Rara Holo">Rara Holo</option>
                                <option value="Ultra Rara">Ultra Rara</option>
                                <option value="Segreta">Segreta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Illustratore</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="editCardIllustrator">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeCardModal()">Chiudi</button>
            <button type="button" class="btn btn-warning" id="editModeBtn" onclick="toggleEditMode()">
                <i class="bi bi-pencil"></i> Modifica
            </button>
            <button type="button" class="btn btn-success" id="saveCardBtn" style="display: none;" onclick="saveCardChanges()">
                <i class="bi bi-check-lg"></i> Salva
            </button>
        </div>
    </div>
</div>

<style>
    /* Fullscreen Viewer & Animation */
    #fullscreenViewer {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.92);
        z-index: 10000;
        visibility: hidden;
        opacity: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        perspective: 1500px;
        backdrop-filter: blur(10px);
        transition: opacity 0.3s ease, visibility 0.3s ease;
        cursor: zoom-out;
    }

    #fullscreenViewer.active {
        visibility: visible;
        opacity: 1;
    }

    #fullscreenImage {
        max-height: 90vh;
        max-width: 90vw;
        border-radius: 20px;
        box-shadow: 0 0 60px rgba(0, 0, 0, 0.8);
        cursor: zoom-out;
        transform-style: preserve-3d;
        opacity: 0;
    }

    #fullscreenImage.animate {
        animation: drawFromDeck 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes drawFromDeck {
        0% {
            opacity: 0;
            transform: translateY(100vh) rotateX(45deg) scale(0.5);
        }

        100% {
            opacity: 1;
            transform: translateY(0) rotateX(0) scale(1);
        }
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-container {
        background: #1e233c;
        border: 1px solid rgba(255, 203, 5, 0.3);
        border-radius: 12px;
        max-height: 90vh;
        overflow: hidden;
    }

    .modal-header-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal-header-custom h3 {
        margin: 0;
        color: var(--pokemon-yellow);
    }

    .btn-close-modal {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.25rem;
        cursor: pointer;
        opacity: 0.7;
    }

    .btn-close-modal:hover {
        opacity: 1;
    }

    #cardDetailsBody tr td:first-child {
        color: var(--pokemon-yellow);
        font-weight: 500;
        width: 40%;
    }

    /* Responsive Modal Styles */
    @media (max-width: 992px) {
        .modal-container {
            max-width: 95% !important;
            width: 95% !important;
            max-height: 95vh;
        }

        .modal-body-grid {
            flex-direction: column !important;
        }

        .modal-body-grid>div:first-child {
            max-width: 100% !important;
            flex: none !important;
        }

        .modal-body-grid>div:last-child {
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .modal-container {
            border-radius: 8px;
        }

        .modal-header-custom {
            padding: 12px 15px;
        }

        .modal-header-custom h3 {
            font-size: 1rem;
        }

        .modal-body-grid {
            padding: 15px !important;
            gap: 15px !important;
            flex-direction: column !important;
        }

        .modal-body-grid>div:first-child {
            flex: none !important;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-body-grid>div:first-child img {
            max-height: 180px !important;
            width: auto !important;
            margin: 0 auto;
        }

        .modal-body-grid>div:last-child {
            max-height: 300px;
            overflow-y: auto;
        }

        #cardDetailsBody tr td {
            font-size: 0.85rem;
            padding: 6px 8px !important;
        }

        #cardDetailsBody tr td:first-child {
            width: 45%;
        }

        /* Collection cards smaller on mobile */
        .card-small {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        .card-small .glass-card {
            padding: 8px;
        }

        .card-small .card-image {
            max-height: 140px !important;
            object-fit: contain;
        }

        .card-small .card-name {
            font-size: 0.75rem;
            margin-top: 5px;
        }

        .card-small .card-meta {
            font-size: 0.65rem;
        }

        .card-small .card-actions {
            margin-top: 5px;
        }

        .card-small .card-actions .btn {
            padding: 3px 6px;
            font-size: 0.7rem;
        }

        /* Edit form responsive */
        .row.g-3>div {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .form-control,
        .form-select {
            font-size: 0.9rem;
            padding: 8px 12px;
        }

        .modal-footer {
            flex-wrap: wrap;
            gap: 8px !important;
            padding: 12px 15px !important;
        }

        .modal-footer .btn {
            flex: 1;
            min-width: 100px;
        }
    }

    @media (max-width: 576px) {
        .floating-bar {
            left: 10px;
            right: 10px;
            transform: none;
            border-radius: 12px;
            padding: 10px 15px;
            gap: 10px;
        }

        .floating-bar .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .floating-bar .count {
            font-size: 0.9rem;
        }

        /* Bulk Set Modal */
        #bulkSetModal .modal-container {
            max-width: 95% !important;
            margin: 10px;
        }

        #bulkSetModal .modal-body {
            padding: 15px !important;
        }

        #bulkSetModal .form-select {
            font-size: 1rem;
        }
    }

    /* Extra small screens - iPhone SE, etc */
    @media (max-width: 480px) {
        .card-small {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            padding: 5px !important;
        }

        .card-small .glass-card {
            display: flex;
            flex-direction: column;
            padding: 15px;
        }

        .card-small .card-image {
            max-height: 200px !important;
            width: auto;
            margin: 0 auto;
        }

        .card-small .card-name {
            font-size: 1rem;
            margin-top: 10px;
            text-align: center;
        }

        .card-small .card-meta {
            font-size: 0.85rem;
            text-align: center;
        }

        .card-small .card-actions {
            margin-top: 10px;
            justify-content: center;
        }

        .card-small .card-actions .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
    }
</style>

<script>
    function toggleSet(header) {
        const setCards = header.nextElementSibling;
        setCards.classList.toggle('collapsed');
        header.classList.toggle('collapsed');
    }

    function openFullscreenCard(src) {
        const viewer = document.getElementById('fullscreenViewer');
        const img = document.getElementById('fullscreenImage');

        // Reset state
        img.src = '';
        img.classList.remove('animate');

        // Show viewer (fade in backdrop)
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Load image
        const newImg = new Image();
        newImg.src = src;
        newImg.onload = () => {
            img.src = src;
            // Force reflow
            void img.offsetWidth;
            // Start animation after a brief delay
            setTimeout(() => {
                img.classList.add('animate');
            }, 50);
        };
    }

    function closeFullscreenCard() {
        const viewer = document.getElementById('fullscreenViewer');
        const img = document.getElementById('fullscreenImage');

        viewer.classList.remove('active');
        img.classList.remove('animate');
        document.body.style.overflow = '';

        setTimeout(() => {
            img.src = '';
        }, 300);
    }

    // Store current card data globally
    let currentCardData = null;
    let cardSetsLoaded = false;

    // Load card sets on page load
    async function loadCardSets() {
        if (cardSetsLoaded) return;
        try {
            const response = await fetch('/cards/api/card-sets');
            const data = await response.json();
            if (data.success) {
                const select = document.getElementById('editCardSet');
                data.data.forEach(set => {
                    const option = document.createElement('option');
                    option.value = set.id;
                    option.textContent = set.name + (set.abbreviation ? ` (${set.abbreviation})` : '');
                    select.appendChild(option);
                });
                cardSetsLoaded = true;
            }
        } catch (error) {
            console.error('Error loading sets:', error);
        }
    }

    async function viewEditCard(cardId, isEdit) {
        // Open modal with loader
        document.getElementById('cardModal').classList.add('active');
        document.getElementById('cardModalLoader').classList.add('active');
        document.body.style.overflow = 'hidden';

        try {
            // Load sets first
            await loadCardSets();

            // Fetch card data from server
            const response = await fetch(`/cards/${cardId}/data`);
            const data = await response.json();

            // Hide loader
            document.getElementById('cardModalLoader').classList.remove('active');

            if (!data.success) {
                showToast('Errore: impossibile caricare i dati della carta', 'error');
                closeCardModal();
                return;
            }

            currentCardData = data.card;
            currentCardData.id = cardId;

            // Set modal title
            document.getElementById('cardModalTitle').textContent = isEdit ? 'Modifica Carta' : (currentCardData.card_name || 'Dettagli Carta');

            // Set card image
            document.getElementById('modalCardImage').src = currentCardData.image_url || '';

            if (isEdit) {
                // EDIT MODE: Show form, hide view
                document.getElementById('cardDetailsView').style.display = 'none';
                document.getElementById('cardEditForm').style.display = 'block';
                document.getElementById('editModeBtn').style.display = 'none';
                document.getElementById('saveCardBtn').style.display = 'inline-flex';

                // Populate form fields
                document.getElementById('editCardName').value = currentCardData.card_name || '';
                document.getElementById('editCardHp').value = currentCardData.hp || '';
                document.getElementById('editCardType').value = currentCardData.type || '';
                document.getElementById('editCardEvolution').value = currentCardData.evolution_stage || '';
                document.getElementById('editCardWeakness').value = currentCardData.weakness || '';
                document.getElementById('editCardResistance').value = currentCardData.resistance || '';
                document.getElementById('editCardRetreat').value = currentCardData.retreat_cost || '';
                document.getElementById('editCardSet').value = currentCardData.card_set_id || '';
                document.getElementById('editCardSetNumber').value = currentCardData.set_number || '';
                document.getElementById('editCardRarity').value = currentCardData.rarity || '';
                document.getElementById('editCardIllustrator').value = currentCardData.illustrator || '';
            } else {
                // VIEW MODE: Show view, hide form
                document.getElementById('cardDetailsView').style.display = 'block';
                document.getElementById('cardEditForm').style.display = 'none';
                document.getElementById('editModeBtn').style.display = 'inline-flex';
                document.getElementById('saveCardBtn').style.display = 'none';

                // Build details table
                const tbody = document.getElementById('cardDetailsBody');
                tbody.innerHTML = `
                    <tr><td>Nome</td><td>${currentCardData.card_name || 'N/A'}</td></tr>
                    <tr><td>HP</td><td>${currentCardData.hp || 'N/A'}</td></tr>
                    <tr><td>Tipo</td><td>${currentCardData.type || 'N/A'}</td></tr>
                    <tr><td>Stadio Evoluzione</td><td>${currentCardData.evolution_stage || 'N/A'}</td></tr>
                    <tr><td>Debolezza</td><td>${currentCardData.weakness || 'N/A'}</td></tr>
                    <tr><td>Resistenza</td><td>${currentCardData.resistance || 'N/A'}</td></tr>
                    <tr><td>Costo Ritirata</td><td>${currentCardData.retreat_cost || 'N/A'}</td></tr>
                    <tr><td>Set</td><td>${currentCardData.card_set?.name || 'Nessun Set'}</td></tr>
                    <tr><td>Numero Set</td><td>${currentCardData.set_number || 'N/A'}</td></tr>
                    <tr><td>Rarità</td><td>${currentCardData.rarity || 'N/A'}</td></tr>
                    <tr><td>Illustratore</td><td>${currentCardData.illustrator || 'N/A'}</td></tr>
                    ${currentCardData.estimated_value ? `<tr><td><strong>💰 Valore Stimato</strong></td><td><strong style="color: #22c55e">${currentCardData.estimated_value}</strong></td></tr>` : ''}
                    ${currentCardData.acquisition_price ? `<tr><td>Prezzo Acquisto</td><td>€${parseFloat(currentCardData.acquisition_price).toFixed(2)}</td></tr>` : ''}
                    ${currentCardData.condition ? `<tr><td>Condizione</td><td>${currentCardData.condition}</td></tr>` : ''}
                `;
            }

            // Store card ID
            document.getElementById('cardModal').dataset.cardId = cardId;

        } catch (error) {
            console.error('Error loading card:', error);
            document.getElementById('cardModalLoader').classList.remove('active');
            showToast('Errore di rete: impossibile caricare la carta', 'error');
            closeCardModal();
        }
    }

    function closeCardModal() {
        document.getElementById('cardModal').classList.remove('active');
        document.body.style.overflow = '';
        // Reset to view mode for next open
        document.getElementById('cardDetailsView').style.display = 'block';
        document.getElementById('cardEditForm').style.display = 'none';
        document.getElementById('editModeBtn').style.display = 'inline-flex';
        document.getElementById('saveCardBtn').style.display = 'none';
    }

    function toggleEditMode() {
        const cardId = document.getElementById('cardModal').dataset.cardId;
        viewEditCard(cardId, true);
    }

    async function saveCardChanges() {
        const cardId = document.getElementById('cardModal').dataset.cardId;

        const formData = {
            card_name: document.getElementById('editCardName').value,
            hp: document.getElementById('editCardHp').value,
            type: document.getElementById('editCardType').value,
            evolution_stage: document.getElementById('editCardEvolution').value,
            weakness: document.getElementById('editCardWeakness').value,
            resistance: document.getElementById('editCardResistance').value,
            retreat_cost: document.getElementById('editCardRetreat').value,
            card_set_id: document.getElementById('editCardSet').value || null,
            set_number: document.getElementById('editCardSetNumber').value,
            rarity: document.getElementById('editCardRarity').value,
            illustrator: document.getElementById('editCardIllustrator').value,
        };

        try {
            const response = await fetch(`/cards/${cardId}/update`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            if (result.success) {
                showToast('Carta aggiornata con successo!', 'success');
                closeCardModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Errore durante il salvataggio', 'error');
            }
        } catch (error) {
            console.error('Error saving card:', error);
            showToast('Errore durante il salvataggio', 'error');
        }
    }

    async function deleteCard(cardId) {
        if (!confirm('Sei sicuro di voler eliminare questa carta?')) return;

        try {
            const response = await fetch(`/cards/${cardId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            if (result.success) {
                showToast('Carta eliminata con successo!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Errore durante l eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore durante l eliminazione', 'error');
        }
    }

    // Bulk Selection Functions
    let selectedCards = new Set();

    function toggleCardSelection(cardId, checkbox) {
        const card = document.querySelector(`.glass-card[data-card-id="${cardId}"]`);
        if (checkbox.checked) {
            selectedCards.add(cardId);
            card.classList.add('selected');
        } else {
            selectedCards.delete(cardId);
            card.classList.remove('selected');
        }
        updateFloatingBar();
    }

    function updateFloatingBar() {
        const bar = document.getElementById('floatingBar');
        const count = selectedCards.size;
        document.getElementById('selectedCount').textContent = count;
        if (count > 0) {
            bar.classList.add('active');
        } else {
            bar.classList.remove('active');
        }
    }

    function clearSelection() {
        selectedCards.forEach(cardId => {
            const card = document.querySelector(`.glass-card[data-card-id="${cardId}"]`);
            if (card) {
                card.classList.remove('selected');
                const checkbox = card.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            }
        });
        selectedCards.clear();
        updateFloatingBar();
    }

    async function openBulkSetModal() {
        // Load sets if not loaded
        await loadCardSets();

        // Copy sets to bulk modal
        const sourceSelect = document.getElementById('editCardSet');
        const bulkSelect = document.getElementById('bulkSetSelect');
        bulkSelect.innerHTML = '<option value="">Nessun Set (rimuovi)</option>';
        Array.from(sourceSelect.options).forEach(opt => {
            if (opt.value) {
                bulkSelect.appendChild(opt.cloneNode(true));
            }
        });

        document.getElementById('bulkCardCount').textContent = selectedCards.size;
        document.getElementById('bulkSetModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeBulkSetModal() {
        document.getElementById('bulkSetModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    async function saveBulkSet() {
        const setId = document.getElementById('bulkSetSelect').value;
        const cardIds = Array.from(selectedCards);

        if (cardIds.length === 0) {
            showToast('Nessuna carta selezionata', 'error');
            return;
        }

        try {
            const response = await fetch('/cards/assign-set', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    card_ids: cardIds,
                    card_set_id: setId || null
                })
            });

            const result = await response.json();
            if (result.success) {
                showToast(`Set assegnato a ${cardIds.length} carte!`, 'success');
                closeBulkSetModal();
                clearSelection();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Errore durante l assegnazione', 'error');
            }
        } catch (error) {
            console.error('Error assigning set:', error);
            showToast('Errore durante l assegnazione', 'error');
        }
    }
</script>
@endsection