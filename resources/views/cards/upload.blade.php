@extends('layouts.app')

@section('title', 'Upload Card')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
<style>
    .upload-zone {
        border: 3px dashed rgba(255, 203, 5, 0.4);
        border-radius: 20px;
        padding: 60px 20px;
        text-align: center;
        background: rgba(255, 255, 255, 0.03);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .upload-zone:hover,
    .upload-zone.drag-over {
        border-color: var(--pokemon-yellow);
        background: rgba(255, 203, 5, 0.08);
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .card-item {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 15px;
        padding: 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .card-image {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 10px;
    }

    .custom-cropper-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 2000;
        padding: 20px;
    }

    .custom-cropper-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cropper-container-wrapper {
        max-width: 800px;
        width: 100%;
    }

    #cropperImage {
        max-height: 70vh;
    }

    /* Card Edit Modal Styles */
    .card-edit-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        z-index: 2000;
        padding: 20px;
        overflow-y: auto;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .card-edit-modal.active {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        opacity: 1;
    }

    .card-edit-container {
        background: linear-gradient(145deg, rgba(30, 35, 60, 0.95), rgba(20, 25, 45, 0.98));
        border: 1px solid rgba(255, 203, 5, 0.3);
        border-radius: 24px;
        max-width: 700px;
        width: 100%;
        margin: 40px auto;
        box-shadow:
            0 25px 80px rgba(0, 0, 0, 0.5),
            0 0 40px rgba(255, 203, 5, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
        transform: translateY(20px);
        animation: slideUp 0.4s ease forwards;
    }

    @keyframes slideUp {
        to {
            transform: translateY(0);
        }
    }

    .modal-header-custom {
        background: linear-gradient(135deg, rgba(255, 203, 5, 0.15), rgba(255, 203, 5, 0.05));
        border-bottom: 1px solid rgba(255, 203, 5, 0.2);
        padding: 20px 25px;
        border-radius: 24px 24px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header-custom h4 {
        margin: 0;
        color: var(--pokemon-yellow);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-header-custom h4 i {
        font-size: 1.4rem;
    }

    .modal-close-btn {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .modal-close-btn:hover {
        background: rgba(255, 100, 100, 0.3);
        transform: rotate(90deg);
    }

    .modal-body-custom {
        padding: 25px;
    }

    .modal-preview-section {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }

    .modal-card-preview {
        width: 140px;
        flex-shrink: 0;
    }

    .modal-card-preview img {
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    }

    .modal-preview-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .modal-preview-info p {
        margin: 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
    }

    .form-section-title {
        color: var(--pokemon-yellow);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255, 203, 5, 0.2);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .form-grid.single-col {
        grid-template-columns: 1fr;
    }

    .form-group-custom {
        margin-bottom: 0;
    }

    .form-group-custom label {
        display: block;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
        margin-bottom: 6px;
        font-weight: 500;
    }

    .form-group-custom input,
    .form-group-custom select,
    .form-group-custom textarea {
        width: 100%;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        padding: 12px 15px;
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-group-custom input:focus,
    .form-group-custom select:focus,
    .form-group-custom textarea:focus {
        outline: none;
        border-color: var(--pokemon-yellow);
        background: rgba(255, 203, 5, 0.08);
        box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.1);
    }

    .form-group-custom input::placeholder,
    .form-group-custom textarea::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .form-group-custom select option {
        background: #1a1f35;
        color: #fff;
    }

    .form-group-custom.full-width {
        grid-column: span 2;
    }

    .modal-footer-custom {
        padding: 20px 25px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0 0 24px 24px;
    }

    .btn-cancel {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .btn-save-modal {
        background: linear-gradient(135deg, var(--pokemon-yellow), #f5a623);
        border: none;
        color: #1a1f35;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-save-modal:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 203, 5, 0.3);
    }

    .btn-save-modal:active {
        transform: translateY(0);
    }

    @media (max-width: 576px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-group-custom.full-width {
            grid-column: span 1;
        }

        .modal-preview-section {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
    }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0;
        overflow-x: auto;
    }

    .tab-item {
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
        font-weight: 500;
        position: relative;
        white-space: nowrap;
    }

    .tab-item:hover {
        color: #fff;
    }

    .tab-item.active {
        color: var(--pokemon-yellow);
        border-bottom-color: var(--pokemon-yellow);
    }

    .tab-badge {
        background: rgba(255, 255, 255, 0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 8px;
        transition: all 0.3s ease;
    }

    .tab-item.active .tab-badge {
        background: var(--pokemon-yellow);
        color: #000;
    }

    /* Selection Checkbox */
    .card-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
        width: 24px;
        height: 24px;
        background: rgba(0, 0, 0, 0.6);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .card-checkbox.checked {
        background: var(--pokemon-yellow);
        border-color: var(--pokemon-yellow);
    }

    .card-checkbox i {
        display: none;
        color: #000;
        font-size: 16px;
    }

    .card-checkbox.checked i {
        display: block;
    }

    .card-item {
        position: relative;
        /* For absolute positioning of checkbox */
    }

    /* Floating Action Bar */
    .floating-action-bar {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(150px);
        background: #1e233c;
        border: 1px solid rgba(255, 203, 5, 0.3);
        padding: 15px 30px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        gap: 20px;
        z-index: 1000;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .floating-action-bar.visible {
        transform: translateX(-50%) translateY(0);
    }

    .fab-count {
        background: var(--pokemon-yellow);
        color: #000;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

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
        /* Always flex but hidden */
        align-items: center;
        justify-content: center;
        perspective: 1500px;
        backdrop-filter: blur(10px);
        transition: opacity 0.3s ease, visibility 0.3s ease;
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
        /* Hidden initially until animation starts */
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

    /* Modal Layout Improvements */
    .card-edit-container {
        width: 90%;
        max-width: 1200px;
        height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .modal-body-custom {
        flex: 1;
        display: flex;
        gap: 0;
        overflow: hidden;
        padding: 0;
    }

    .modal-preview-section {
        flex: 1;
        /* Take available space */
        min-width: 300px;
        background: rgba(0, 0, 0, 0.3);
        padding: 20px;
        display: flex;
        align-items: center;
        /* Center vertically */
        justify-content: center;
        /* Center horizontally */
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        overflow: hidden;
        /* Contain huge images */
    }

    .modal-card-preview {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-card-preview img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        /* Natural width */
        height: auto;
        /* Natural height */
        object-fit: contain;
        /* Ensure it fits */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border-radius: 10px;
        cursor: zoom-in;
        transition: transform 0.2s ease;
    }

    .modal-card-preview img:hover {
        transform: scale(1.02);
    }

    #cardEditForm {
        flex: 0 0 500px;
        /* Fixed width for form */
        max-width: 50%;
        overflow-y: auto;
        padding: 30px;
        background: rgba(30, 35, 60, 0.5);
    }

    /* Mobile Responsive Fixes */
    @media (max-width: 991px) {
        .card-edit-container {
            height: auto;
            max-height: 95vh;
            width: 95%;
        }

        .modal-body-custom {
            flex-direction: column;
            overflow-y: auto;
        }

        .modal-preview-section {
            flex: 0 0 auto;
            height: 300px;
            /* Fixed height for preview on mobile */
            width: 100%;
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
        }

        #cardEditForm {
            flex: 1;
            max-width: 100%;
            overflow-y: visible;
            padding: 20px;
        }
    }

    /* Zoom Overlay */
    .card-image-wrapper {
        position: relative;
        cursor: pointer;
        overflow: hidden;
        border-radius: 10px;
    }

    .zoom-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .card-image-wrapper:hover .zoom-overlay {
        opacity: 1;
    }

    .zoom-overlay i {
        color: white;
        font-size: 2rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
    }
</style>
@endpush

@section('content')
<!-- Fullscreen Viewer -->
<div id="fullscreenViewer" onclick="closeFullscreen()">
    <img id="fullscreenImage" src="" alt="Fullscreen Card">
</div>


@section('content')
<div class="container">
    <div class="text-center mb-5">
        <h1 class="page-title">Carica le Tue Carte</h1>
        <p class="page-subtitle">Scansiona le carte Pokemon con intelligenza artificiale o inserisci i dati manualmente
        </p>
    </div>

    <!-- Upload Area -->
    <div class="glass-card p-4 mb-4">
        <div class="upload-zone" id="uploadZone">
            <i class="bi bi-cloud-upload" style="font-size: 48px; color: var(--pokemon-yellow);"></i>
            <h3 class="mt-3">Trascina le immagini qui</h3>
            <p class="text-muted">oppure clicca per selezionare</p>
            <input type="file" id="fileInput" accept="image/*" multiple class="d-none">
        </div>
    </div>

    <!-- Gallery -->
    <div id="gallerySection" style="display: none;">
        <!-- Tabs -->
        <div class="tabs-nav">
            <div class="tab-item active" onclick="switchTab('pending')" id="tab-pending">
                Da Ritagliare <span class="tab-badge" id="badge-pending">0</span>
            </div>
            <div class="tab-item" onclick="switchTab('processing')" id="tab-processing">
                Da Analizzare <span class="tab-badge" id="badge-processing">0</span>
            </div>
            <div class="tab-item" onclick="switchTab('completed')" id="tab-completed">
                Completate <span class="tab-badge" id="badge-completed">0</span>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check" id="selectAllContainer">
                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                <label class="form-check-label text-white-50" for="selectAll">Seleziona Tutti</label>
            </div>
            <button class="btn btn-sm btn-danger-pokemon" onclick="resetGallery()">
                <i class="bi bi-trash"></i> Svuota Tutto
            </button>
        </div>

        <div class="gallery-grid" id="galleryGrid"></div>
    </div>

    <!-- Floating Action Bar -->
    <div class="floating-action-bar" id="floatingBar">
        <div class="fab-count" id="selectedCount">0</div>
        <div class="d-flex gap-2" id="fabActions">
            <!-- Actions injected via JS -->
        </div>
    </div>

    <!-- Cropper Modal -->
    <div class="custom-cropper-modal" id="cropperModal">
        <div class="cropper-container-wrapper">
            <div class="text-center mb-3">
                <h4 class="text-white">Ritaglia la Carta</h4>
            </div>
            <img id="cropperImage" src="" alt="Crop">
            <div class="text-center mt-3">
                <button class="btn btn-pokemon me-2" onclick="confirmCrop()">
                    <i class="bi bi-check-lg"></i> Conferma Ritaglio
                </button>
                <button class="btn btn-secondary" onclick="closeCropper()">
                    <i class="bi bi-x-lg"></i> Annulla
                </button>
            </div>
        </div>
    </div>

    <!-- Card Edit Modal -->
    <div class="card-edit-modal" id="cardEditModal">
        <div class="card-edit-container">
            <div class="modal-header-custom">
                <h4>
                    <i class="bi bi-pencil-square"></i>
                    <span id="modalTitle">Inserimento Manuale</span>
                </h4>
                <button class="modal-close-btn" onclick="closeEditModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body-custom">
                <!-- Preview Section -->
                <div class="modal-preview-section">
                    <div class="modal-card-preview">
                        <img id="editModalPreview" src="" alt="Card Preview">
                    </div>
                </div>

                <form id="cardEditForm">
                    <input type="hidden" id="editFileId" value="">

                    <!-- Instructions -->
                    <div class="alert alert-info mb-3" style="background: rgba(13, 110, 253, 0.1); border: 1px solid rgba(13, 110, 253, 0.3); color: #9ec5fe; border-radius: 8px; padding: 12px;">
                        <i class="bi bi-info-circle"></i> Compila i campi con le informazioni della carta. I campi contrassegnati con * sono obbligatori.
                    </div>

                    <!-- Basic Info Section -->
                    <div class="form-section-title">
                        <i class="bi bi-card-text"></i> Informazioni Base
                    </div>
                    <div class="form-grid">
                        <div class="form-group-custom">
                            <label for="editCardName">Nome Carta *</label>
                            <input type="text" id="editCardName" placeholder="es. Pikachu" required>
                        </div>
                        <div class="form-group-custom">
                            <label for="editHp">HP</label>
                            <input type="text" id="editHp" placeholder="es. 60">
                        </div>
                        <div class="form-group-custom">
                            <label for="editType">Tipo</label>
                            <select id="editType">
                                <option value="">Seleziona tipo...</option>
                                <option value="Colorless">Incolore</option>
                                <option value="Darkness">Oscurità</option>
                                <option value="Dragon">Drago</option>
                                <option value="Fairy">Folletto</option>
                                <option value="Fighting">Lotta</option>
                                <option value="Fire">Fuoco</option>
                                <option value="Grass">Erba</option>
                                <option value="Lightning">Elettro</option>
                                <option value="Metal">Metallo</option>
                                <option value="Psychic">Psico</option>
                                <option value="Water">Acqua</option>
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label for="editEvolutionStage">Stadio Evoluzione</label>
                            <select id="editEvolutionStage">
                                <option value="">Seleziona stadio...</option>
                                <option value="Basic">Base</option>
                                <option value="Stage 1">Stadio 1</option>
                                <option value="Stage 2">Stadio 2</option>
                                <option value="VMAX">VMAX</option>
                                <option value="VSTAR">VSTAR</option>
                                <option value="V">V</option>
                                <option value="GX">GX</option>
                                <option value="EX">EX</option>
                                <option value="BREAK">BREAK</option>
                                <option value="Mega">Mega</option>
                            </select>
                        </div>
                    </div>

                    <!-- Combat Info Section -->
                    <div class="form-section-title mt-4">
                        <i class="bi bi-lightning-charge"></i> Statistiche di Combattimento
                    </div>
                    <div class="form-grid">
                        <div class="form-group-custom">
                            <label for="editWeakness">Debolezza</label>
                            <input type="text" id="editWeakness" placeholder="es. Fire x2">
                        </div>
                        <div class="form-group-custom">
                            <label for="editResistance">Resistenza</label>
                            <input type="text" id="editResistance" placeholder="es. Fighting -30">
                        </div>
                        <div class="form-group-custom">
                            <label for="editRetreatCost">Costo Ritirata</label>
                            <input type="text" id="editRetreatCost" placeholder="es. 1 o 2">
                        </div>
                    </div>

                    <!-- Set Info Section -->
                    <div class="form-section-title mt-4">
                        <i class="bi bi-collection"></i> Informazioni Set
                    </div>
                    <div class="form-grid">
                        <div class="form-group-custom">
                            <label for="editCardSet">Card Set</label>
                            <select id="editCardSet">
                                <option value="">Nessun Set...</option>
                                <!-- Populated via JS from API -->
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label for="editSetNumber">Numero Set</label>
                            <input type="text" id="editSetNumber" placeholder="es. 025/198">
                        </div>
                        <div class="form-group-custom">
                            <label for="editRarity">Rarità</label>
                            <select id="editRarity">
                                <option value="">Seleziona rarità...</option>
                                <option value="Common">Comune</option>
                                <option value="Uncommon">Non Comune</option>
                                <option value="Rare">Rara</option>
                                <option value="Rare Holo">Rara Holo</option>
                                <option value="Rare Holo V">Rara Holo V</option>
                                <option value="Rare Holo VMAX">Rara Holo VMAX</option>
                                <option value="Rare Holo VSTAR">Rara Holo VSTAR</option>
                                <option value="Rare Ultra">Ultra Rara</option>
                                <option value="Rare Secret">Segreta</option>
                                <option value="Rare Rainbow">Arcobaleno</option>
                                <option value="Rare Shiny">Shiny</option>
                                <option value="Illustration Rare">Illustration Rare</option>
                                <option value="Special Art Rare">Special Art Rare</option>
                                <option value="Promo">Promo</option>
                            </select>
                        </div>
                        <div class="form-group-custom full-width">
                            <label for="editIllustrator">Illustratore</label>
                            <input type="text" id="editIllustrator" placeholder="es. Ken Sugimori">
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="form-section-title mt-4">
                        <i class="bi bi-chat-left-text"></i> Informazioni Aggiuntive
                    </div>
                    <div class="form-grid single-col">
                        <div class="form-group-custom">
                            <label for="editFlavorText">Testo Descrittivo</label>
                            <textarea id="editFlavorText" rows="3" placeholder="Descrizione o testo della carta..."></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer-custom">
                <button class="btn-cancel" onclick="closeEditModal()">
                    <i class="bi bi-x-lg"></i> Annulla
                </button>
                <button class="btn-save-modal" onclick="saveEditModal()">
                    <i class="bi bi-check-lg"></i> Conferma
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
    let galleryCards = new Map();
    let cropper = null;
    let currentFileId = null;
    let currentTab = 'pending'; // pending, processing, completed
    let selectedCards = new Set();

    // Stats for badges
    let stats = {
        pending: 0,
        processing: 0,
        completed: 0
    };

    // Upload Zone Events
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    uploadZone.addEventListener('click', () => fileInput.click());
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    async function handleFiles(files) {
        // Switch to pending tab immediately
        switchTab('pending');

        const fileArray = Array.from(files).filter(file => file.type.startsWith('image/'));

        for (const file of fileArray) {
            const formData = new FormData();
            formData.append('image', file);

            // Create temporary ID for UI feedback immediately
            const tempId = Date.now() + '-' + Math.random();

            // Preview immediately
            const reader = new FileReader();
            reader.onload = (e) => {
                galleryCards.set(tempId, {
                    tempId: tempId,
                    cardId: null,
                    thumbnail: e.target.result,
                    state: 'uploading', // New transient state
                    data: null
                });
                updateStats();
                renderGallery();
            };
            reader.readAsDataURL(file);

            // Upload Raw
            try {
                const response = await fetch('{{ route("cards.upload-image") }}', { // Points to uploadRawImage
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    const card = galleryCards.get(tempId);
                    // Replace entry with real ID as key if possible, but Map keys are static.
                    // Better to just update the value.
                    // Or re-key. Let's keep tempId as UI key but store db id inside.
                    if (card) {
                        card.cardId = result.data.id;
                        card.state = 'pending';
                        card.thumbnail = result.data.image_url; // Use server URL
                        updateStats();
                        renderGallery();
                    }
                }
            } catch (error) {
                console.error("Upload failed", error);
                galleryCards.delete(tempId);
                updateStats();
                renderGallery();
                showToast('Errore upload: ' + file.name, 'error');
            }
        }
    }

    function switchTab(tab) {
        currentTab = tab;
        selectedCards.clear();
        updateFloatingBar();
        renderGallery();
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        document.getElementById(`tab-${tab}`).classList.add('active');
        document.getElementById('selectAll').checked = false;
    }

    function updateStats() {
        stats.pending = 0;
        stats.processing = 0;
        stats.completed = 0;

        galleryCards.forEach(card => {
            if (card.state === 'pending' || card.state === 'uploading') stats.pending++;
            else if (['cropped', 'processing', 'ready'].includes(card.state)) stats.processing++;
            else if (card.state === 'completed') stats.completed++;
        });

        document.getElementById('badge-pending').textContent = stats.pending;
        document.getElementById('badge-processing').textContent = stats.processing;
        document.getElementById('badge-completed').textContent = stats.completed;
    }

    function renderGallery() {
        const grid = document.getElementById('galleryGrid');
        const section = document.getElementById('gallerySection');

        if (galleryCards.size === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        grid.innerHTML = '';

        galleryCards.forEach((card, fileId) => {
            // Filter by Tab
            let show = false;
            if (currentTab === 'pending' && (card.state === 'pending' || card.state === 'uploading')) show = true;
            if (currentTab === 'processing' && ['cropped', 'processing', 'ready'].includes(card.state)) show = true;
            if (currentTab === 'completed' && card.state === 'completed') show = true;

            if (!show) return;

            const isSelected = selectedCards.has(fileId);
            const cardEl = document.createElement('div');
            cardEl.className = 'card-item';

            // Checkbox logic
            const checkboxHtml = (card.state !== 'uploading' && card.state !== 'processing') ? `
                <div class="card-checkbox ${isSelected ? 'checked' : ''}" onclick="toggleSelection('${fileId}')">
                    <i class="bi bi-check"></i>
                </div>
            ` : '';

            cardEl.innerHTML = `
                ${checkboxHtml}
                <div class="card-image-wrapper" onclick="openFullscreen('${card.thumbnail}')">
                    <img src="${card.thumbnail}" class="card-image" alt="Card">
                    <div class="zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${getActionButtons(fileId, card)}
                </div>
                ${getCardInfoHtml(card)}
            `;
            grid.appendChild(cardEl);
        });

        updateFloatingBar();
    }

    // --- Fullscreen Viewer ---
    // --- Fullscreen Viewer ---
    function openFullscreen(src) {
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

    function closeFullscreen() {
        const viewer = document.getElementById('fullscreenViewer');
        const img = document.getElementById('fullscreenImage');

        viewer.classList.remove('active');
        img.classList.remove('animate');
        document.body.style.overflow = '';

        setTimeout(() => {
            img.src = '';
        }, 300);
    }

    // Add click listener to edit modal image
    document.addEventListener('DOMContentLoaded', () => {
        const editPreview = document.getElementById('editModalPreview');
        if (editPreview) {
            editPreview.addEventListener('click', function() {
                if (this.src && this.src !== window.location.href) openFullscreen(this.src);
            });
        }
    });

    function getActionButtons(fileId, card) {
        if (card.state === 'uploading') {
            return `<div class="text-center text-white-50"><small>Upload in corso...</small></div>`;
        }
        if (card.state === 'pending') {
            return `
                <button class="btn btn-sm btn-pokemon" onclick="openCropper('${fileId}')">
                    <i class="bi bi-crop"></i> Ritaglia
                </button>
                <button class="btn btn-sm btn-secondary" onclick="skipCrop('${fileId}')">
                     <i class="bi bi-skip-forward"></i> Salta
                </button>
            `;
        }
        if (card.state === 'cropped') {
            return `
                <button class="btn btn-sm btn-success" onclick="recognizeWithAI('${fileId}')">
                    <i class="bi bi-robot"></i> Analizza
                </button>
                <button class="btn btn-sm btn-warning" onclick="manualEntry('${fileId}')">
                    <i class="bi bi-pencil"></i> Manuale
                </button>
            `;
        }
        if (card.state === 'processing') {
            return `
                 <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    <small class="d-block mt-1">AI in corso...</small>
                </div>
            `;
        }
        if (card.state === 'ready') {
            return `
                <button class="btn btn-sm btn-success" onclick="saveCard('${fileId}')">
                    <i class="bi bi-save"></i> Salva
                </button>
                <button class="btn btn-sm btn-info" onclick="editCard('${fileId}')">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
            `;
        }
        if (card.state === 'completed') {
            return `
                <div class="alert alert-success p-1 text-center mb-0"><small>Completata</small></div>
                <button class="btn btn-sm btn-secondary" onclick="deleteCard('${fileId}')">
                    <i class="bi bi-trash"></i> Rimuovi
                </button>
            `;
        }
        return '';
    }

    function getCardInfoHtml(card) {
        if (!card.data) return '';
        return `
            <div class="mt-2 small text-white-50">
                <strong>${card.data.card_name || 'Sconosciuta'}</strong><br>
                ${card.data.hp ? `HP: ${card.data.hp} |` : ''} ${card.data.type || ''}
            </div>
        `;
    }

    // --- Bulk Actions ---

    function toggleSelection(fileId) {
        const card = galleryCards.get(fileId);
        if (!card) return;
        // Prevent selecting items that are processing
        if (card.state === 'uploading' || card.state === 'processing') return;

        if (selectedCards.has(fileId)) {
            selectedCards.delete(fileId);
        } else {
            selectedCards.add(fileId);
        }
        renderGallery();
    }

    function toggleSelectAll() {
        const isChecked = document.getElementById('selectAll').checked;
        selectedCards.clear();

        if (isChecked) {
            galleryCards.forEach((card, fileId) => {
                // Select only visible cards in current tab
                let visible = false;
                if (currentTab === 'pending' && card.state === 'pending') visible = true;
                if (currentTab === 'processing' && ['cropped', 'ready'].includes(card.state)) visible = true;
                if (currentTab === 'completed' && card.state === 'completed') visible = true;

                if (visible) selectedCards.add(fileId);
            });
        }
        renderGallery();
    }

    function updateFloatingBar() {
        const fab = document.getElementById('floatingBar');
        const countEl = document.getElementById('selectedCount');
        const actionsEl = document.getElementById('fabActions');

        if (selectedCards.size > 0) {
            fab.classList.add('visible');
            countEl.textContent = selectedCards.size;

            // Build actions based on tab
            let html = '';
            if (currentTab === 'pending') {
                html = `
                    <button class="btn btn-sm btn-secondary" onclick="bulkSkipCrop()">
                        <i class="bi bi-skip-forward"></i> Salta Ritaglio (${selectedCards.size})
                    </button>
                `;
            } else if (currentTab === 'processing') {
                const hasReady = Array.from(selectedCards).some(id => galleryCards.get(id).state === 'ready');
                const hasCropped = Array.from(selectedCards).some(id => galleryCards.get(id).state === 'cropped');

                if (hasCropped) {
                    html += `
                        <button class="btn btn-sm btn-success" onclick="bulkAnalyze()">
                            <i class="bi bi-robot"></i> Analizza (${selectedCards.size})
                        </button>
                    `;
                }
                if (hasReady) {
                    html += `
                        <button class="btn btn-sm btn-primary" onclick="bulkSave()">
                            <i class="bi bi-save"></i> Salva (${selectedCards.size})
                        </button>
                    `;
                }
            } else if (currentTab === 'completed') {
                html = `
                    <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        <i class="bi bi-trash"></i> Elimina
                    </button>
                `;
            }
            actionsEl.innerHTML = html;

        } else {
            fab.classList.remove('visible');
        }
    }

    // --- Bulk Operations ---

    async function bulkSkipCrop() {
        if (!confirm(`Saltare il ritaglio per ${selectedCards.size} carte?`)) return;

        const ids = Array.from(selectedCards);
        selectedCards.clear(); // Clear immediately to update UI
        updateFloatingBar();

        for (const fileId of ids) {
            await skipCrop(fileId, false); // false = no toast per single item
        }
        showToast('Ritaglio saltato per le carte selezionate', 'success');
    }

    async function bulkAnalyze() {
        const ids = Array.from(selectedCards);
        selectedCards.clear();
        updateFloatingBar();

        for (const fileId of ids) {
            const card = galleryCards.get(fileId);
            if (card.state === 'cropped') {
                recognizeWithAI(fileId, false);
            }
        }
        showToast('Analisi avviata in background...', 'info');
    }

    async function bulkSave() {
        const ids = Array.from(selectedCards);
        selectedCards.clear();
        updateFloatingBar();

        for (const fileId of ids) {
            const card = galleryCards.get(fileId);
            if (card.state === 'ready') {
                await saveCard(fileId, false);
            }
        }
        showToast('Carte salvate', 'success');
    }

    // --- Single Actions ---

    function openCropper(fileId) {
        currentFileId = fileId;
        const card = galleryCards.get(fileId);
        document.getElementById('cropperImage').src = card.thumbnail;
        document.getElementById('cropperModal').classList.add('active');
        setTimeout(() => {
            if (cropper) cropper.destroy();
            cropper = new Cropper(document.getElementById('cropperImage'), {
                aspectRatio: NaN,
                viewMode: 1,
                autoCropArea: 1
            });
        }, 100);
    }

    function closeCropper() {
        document.getElementById('cropperModal').classList.remove('active');
        if (cropper) cropper.destroy();
    }

    function confirmCrop() {
        cropper.getCroppedCanvas().toBlob(async (blob) => {
            const formData = new FormData();
            const card = galleryCards.get(currentFileId);
            formData.append('cropped_image', blob, 'card_crop.jpg');
            formData.append('card_id', card.cardId);

            try {
                const response = await fetch('{{ route("cards.save-crop") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    card.state = 'cropped';
                    card.thumbnail = result.data.image_url; // Update thumb with crop
                    updateStats();
                    renderGallery();
                    showToast('Ritaglio salvato!', 'success');
                }
            } catch (error) {
                showToast('Errore salvataggio ritaglio', 'error');
            }
            closeCropper();
        });
    }

    async function skipCrop(fileId, showNotification = true) {
        const card = galleryCards.get(fileId);
        try {
            const response = await fetch('{{ route("cards.skip-crop") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_id: card.cardId
                })
            });
            const result = await response.json();
            if (result.success) {
                card.state = 'cropped'; // Treated as cropped (ready for AI)
                updateStats();
                renderGallery();
                if (showNotification) showToast('Ritaglio saltato!', 'success');
            }
        } catch (error) {
            if (showNotification) showToast('Errore durante lo skip', 'error');
        }
    }

    async function recognizeWithAI(fileId, showNotification = true) {
        const card = galleryCards.get(fileId);
        card.state = 'processing';
        updateStats(); // Force re-render to remove checkbox and show spinner
        renderGallery();

        try {
            const response = await fetch('{{ route("cards.enhance") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_id: card.cardId
                })
            });

            const result = await response.json();
            if (result.success) {
                card.data = result.data;
                card.state = 'ready';
                updateStats();
                renderGallery();
                if (showNotification) showToast('Analisi completata!', 'success');
            }
        } catch (error) {
            card.state = 'cropped'; // Revert state
            updateStats();
            renderGallery();
            if (showNotification) showToast('Errore AI', 'error');
        }
    }

    // Modal Functions (Keep existing logic mostly)
    function manualEntry(fileId) {
        openEditModal(fileId, false);
    }

    function editCard(fileId) {
        openEditModal(fileId, true);
    }

    function openEditModal(fileId, isEdit = false) {
        const card = galleryCards.get(fileId);
        if (!card) return;
        document.getElementById('editFileId').value = fileId;
        document.getElementById('modalTitle').textContent = isEdit ? 'Modifica Carta' : 'Inserimento Manuale';
        document.getElementById('editModalPreview').src = card.thumbnail;

        if (isEdit && card.data) {
            document.getElementById('editCardName').value = card.data.card_name || '';
            document.getElementById('editHp').value = card.data.hp || '';
            document.getElementById('editType').value = card.data.type || '';
            document.getElementById('editEvolutionStage').value = card.data.evolution_stage || '';
            document.getElementById('editWeakness').value = card.data.weakness || '';
            document.getElementById('editResistance').value = card.data.resistance || '';
            document.getElementById('editRetreatCost').value = card.data.retreat_cost || '';
            document.getElementById('editSetNumber').value = card.data.set_number || '';
            document.getElementById('editRarity').value = card.data.rarity || '';
            document.getElementById('editIllustrator').value = card.data.illustrator || '';
            document.getElementById('editFlavorText').value = card.data.flavor_text || '';
            document.getElementById('editCardSet').value = card.data.card_set_id || '';
        } else {
            document.getElementById('cardEditForm').reset();
        }

        // Load card sets if not already loaded
        loadCardSets();

        document.getElementById('cardEditModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            document.getElementById('editCardName').focus();
        }, 300);
    }

    // Load card sets from API
    let cardSetsLoaded = false;
    async function loadCardSets() {
        if (cardSetsLoaded) return;

        try {
            const response = await fetch('{{ route("api.card-sets") }}');
            const result = await response.json();

            if (result.success) {
                const select = document.getElementById('editCardSet');
                result.data.forEach(set => {
                    const option = document.createElement('option');
                    option.value = set.id;
                    option.textContent = set.name + (set.abbreviation ? ` (${set.abbreviation})` : '');
                    select.appendChild(option);
                });
                cardSetsLoaded = true;
            }
        } catch (error) {
            console.error('Error loading card sets:', error);
        }
    }

    function closeEditModal() {
        document.getElementById('cardEditModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    function saveEditModal() {
        const fileId = document.getElementById('editFileId').value;
        const card = galleryCards.get(fileId);
        if (!card) return;
        const cardName = document.getElementById('editCardName').value.trim();
        if (!cardName) {
            showToast('Nome obbligatorio', 'error');
            return;
        }

        card.data = {
            card_name: cardName,
            hp: document.getElementById('editHp').value.trim() || null,
            type: document.getElementById('editType').value || null,
            evolution_stage: document.getElementById('editEvolutionStage').value || null,
            weakness: document.getElementById('editWeakness').value.trim() || null,
            resistance: document.getElementById('editResistance').value.trim() || null,
            retreat_cost: document.getElementById('editRetreatCost').value.trim() || null,
            set_number: document.getElementById('editSetNumber').value.trim() || null,
            rarity: document.getElementById('editRarity').value || null,
            illustrator: document.getElementById('editIllustrator').value.trim() || null,
            flavor_text: document.getElementById('editFlavorText').value.trim() || null,
            card_set_id: document.getElementById('editCardSet').value || null,
        };
        card.state = 'ready';
        updateStats();
        renderGallery();
        closeEditModal();
        showToast('Dati salvati localmente', 'success');
    }

    async function saveCard(fileId, showNotification = true) {
        const card = galleryCards.get(fileId);
        try {
            const response = await fetch('{{ route("cards.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_id: card.cardId,
                    ...card.data
                })
            });
            const result = await response.json();
            if (result.success) {
                card.state = 'completed'; // Move to completed tab
                updateStats();
                renderGallery();
                if (showNotification) showToast('Carta salvata!', 'success');
            }
        } catch (error) {
            if (showNotification) showToast('Errore salvataggio', 'error');
        }
    }

    async function deleteCard(fileId) {
        if (!confirm('Eliminare?')) return;
        const card = galleryCards.get(fileId);
        if (card.cardId) {
            await fetch('{{ route("cards.discard") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_id: card.cardId
                })
            });
        }
        galleryCards.delete(fileId);
        selectedCards.delete(fileId);
        updateStats();
        renderGallery();
    }

    // Bulk Delete
    async function bulkDelete() {
        if (!confirm(`Eliminare ${selectedCards.size} carte?`)) return;
        const ids = Array.from(selectedCards);
        selectedCards.clear();
        updateFloatingBar();
        for (const id of ids) await deleteCard(id); // Use existing delete logic which includes API call
    }

    function resetGallery() {
        if (!confirm('Eliminare tutto?')) return;
        // In theory should call API to delete temp files for all pending cards but lets just clear UI for now
        galleryCards.clear();
        selectedCards.clear();
        updateStats();
        renderGallery();
    }

    // Modal listeners
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (document.getElementById('cardEditModal').classList.contains('active')) closeEditModal();
            if (document.getElementById('cropperModal').classList.contains('active')) closeCropper();
        }
    });

    document.getElementById('cardEditModal').addEventListener('click', (e) => {
        if (e.target.id === 'cardEditModal') closeEditModal();
    });
</script>
@endpush