@extends('layouts.app')

@section('title', 'Scansiona Carta Pokemon')

@push('styles')
    <link href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.css" rel="stylesheet">
    <style>
        /* Stepper Progress */
        .workflow-stepper {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
            max-width: 140px;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.2);
            z-index: 0;
        }

        .step.completed:not(:last-child)::after {
            background: var(--pokemon-yellow);
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .step.active .step-icon {
            background: var(--pokemon-yellow);
            border-color: var(--pokemon-yellow);
            color: #000;
            box-shadow: 0 0 20px rgba(255, 203, 5, 0.5);
            animation: pulse-step 2s infinite;
        }

        .step.completed .step-icon {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        @keyframes pulse-step {

            0%,
            100% {
                box-shadow: 0 0 10px rgba(255, 203, 5, 0.4);
            }

            50% {
                box-shadow: 0 0 25px rgba(255, 203, 5, 0.7);
            }
        }

        .step-label {
            margin-top: 8px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            font-weight: 500;
        }

        .step.active .step-label,
        .step.completed .step-label {
            color: #fff;
        }

        /* Upload Area */
        .upload-area {
            border: 3px dashed rgba(255, 203, 5, 0.4);
            border-radius: 20px;
            padding: 60px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.03);
            position: relative;
            overflow: hidden;
        }

        .upload-area::before {
            content: '';
            position: absolute;
            inset: -50%;
            background: linear-gradient(45deg, transparent, rgba(255, 203, 5, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .upload-area:hover {
            border-color: var(--pokemon-yellow);
            background: rgba(255, 255, 255, 0.05);
            transform: scale(1.01);
        }

        .upload-area.drag-over {
            border-color: var(--pokemon-yellow);
            background: rgba(255, 203, 5, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--pokemon-yellow);
            margin-bottom: 1rem;
            position: relative;
        }

        .cropper-container-custom {
            max-height: 450px;
            margin: 20px 0;
            display: none;
        }

        .cropper-container-custom.active {
            display: block;
        }

        #cropperImage {
            max-width: 100%;
            display: block;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Recent Scans Table */
        .recent-scans-section {
            margin-top: 3rem;
        }

        .recent-scans-section h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .table-dark {
            --bs-table-bg: transparent;
        }

        .table-dark th {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Card Details Form */
        .form-section {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--pokemon-yellow);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control,
        .form-select {
            background: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #fff !important;
            border-radius: 10px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--pokemon-yellow) !important;
            box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.2) !important;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .status-pending,
        .status-review {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.5);
        }

        .status-failed {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        /* OCR Result Preview */
        .ocr-preview {
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 1rem;
        }

        /* Queue Counter */
        .bg-pokemon {
            background: linear-gradient(135deg, var(--pokemon-yellow), #ffd700) !important;
            color: #000 !important;
            font-weight: 600;
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .gallery-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .gallery-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 203, 5, 0.5);
            box-shadow: 0 10px 30px rgba(255, 203, 5, 0.2);
        }

        .gallery-card.state-pending {
            border-color: rgba(108, 117, 125, 0.5);
        }

        .gallery-card.state-processing {
            border-color: rgba(255, 193, 7, 0.7);
            animation: pulse-border 2s infinite;
        }

        .gallery-card.state-completed {
            border-color: rgba(40, 167, 69, 0.7);
        }

        .gallery-card.state-error {
            border-color: rgba(220, 53, 69, 0.7);
        }

        .gallery-card.state-saved {
            opacity: 0.6;
            pointer-events: none;
        }

        @keyframes pulse-border {

            0%,
            100% {
                border-color: rgba(255, 193, 7, 0.5);
            }

            50% {
                border-color: rgba(255, 193, 7, 1);
            }
        }

        .gallery-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: rgba(0, 0, 0, 0.3);
        }

        .gallery-card-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .badge-pending {
            background: rgba(108, 117, 125, 0.9);
            color: #fff;
        }

        .badge-processing {
            background: rgba(255, 193, 7, 0.9);
            color: #000;
        }

        .badge-completed {
            background: rgba(40, 167, 69, 0.9);
            color: #fff;
        }

        .badge-error {
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
        }

        .badge-saved {
            background: rgba(23, 162, 184, 0.9);
            color: #fff;
        }

        .gallery-card-body {
            padding: 1rem;
        }

        .gallery-card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .gallery-card-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .gallery-card-details {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
        }

        .gallery-card-details.show {
            display: block;
        }

        /* Cropper Modal */
        .cropper-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #0f172a !important;
            /* Force solid background */
            opacity: 1 !important;
            z-index: 10000;
            overflow-y: auto;
            padding: 1rem;
        }

        .cropper-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cropper-modal-content {
            background: #1e293b !important;
            /* Force content background */
            border-radius: 12px;
            padding: 1.5rem;
            max-width: 550px;
            /* Reduced width */
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 203, 5, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            /* Add shadow for depth */
        }

        .cropper-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .cropper-modal-title {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .cropper-modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .cropper-modal-close:hover {
            opacity: 1;
        }

        .cropper-modal-body img {
            max-width: 100%;
            display: block;
        }

        .cropper-modal-footer {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="page-title">
                    <i class="bi bi-camera-fill me-3"></i>
                    Scansiona Carta Pokemon
                </h1>
                <p class="page-subtitle">Carica un'immagine, ritaglia la carta e lascia che l'OCR faccia il resto!</p>
            </div>

            <!-- Workflow Stepper -->
            <div class="workflow-stepper" id="workflowStepper">
                <div class="step active" data-step="1">
                    <div class="step-icon"><i class="bi bi-cloud-upload"></i></div>
                    <span class="step-label">Carica</span>
                </div>
                <div class="step" data-step="2">
                    <div class="step-icon"><i class="bi bi-crop"></i></div>
                    <span class="step-label">Ritaglia</span>
                </div>
                <div class="step" data-step="3">
                    <div class="step-icon"><i class="bi bi-file-text"></i></div>
                    <span class="step-label">OCR</span>
                </div>
                <div class="step" data-step="4">
                    <div class="step-icon"><i class="bi bi-stars"></i></div>
                    <span class="step-label">AI</span>
                </div>
                <div class="step" data-step="5">
                    <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                    <span class="step-label">Salva</span>
                </div>
            </div>

            <!-- Upload Card -->
            <div class="glass-card p-4 mb-4">
                <!-- Upload Area -->
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="fileInput" accept="image/*" multiple class="d-none">
                    <i class="bi bi-cloud-upload upload-icon"></i>
                    <h3 class="text-white mb-2">Carica una o più immagini</h3>
                    <p class="text-white-50">Clicca per selezionare o trascina qui le immagini</p>
                    <small class="text-white-50">Formati supportati: JPG, PNG (max 30MB per file)</small>
                </div>

            </div>

            <!-- Gallery Grid -->
            <div id="gallerySection" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-white mb-0">
                        <i class="bi bi-images me-2"></i>Carte Caricate (<span id="galleryCount">0</span>)
                    </h4>
                    <button type="button" class="btn btn-outline-light btn-sm" id="resetAllBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                </div>
                <div class="gallery-grid" id="galleryGrid"></div>
            </div>

            <!-- Cropper Modal -->
            <div class="cropper-modal" id="cropperModal">
                <div class="cropper-modal-content">
                    <div class="cropper-modal-header">
                        <h5 class="cropper-modal-title" id="cropperModalTitle">Ritaglia Carta</h5>
                        <button type="button" class="cropper-modal-close" id="cropperModalClose">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="cropper-modal-body">
                        <img id="cropperModalImage" alt="Image to crop">
                    </div>
                    <div class="cropper-modal-footer">
                        <button type="button" class="btn btn-outline-light" id="cropperCancelBtn">
                            <i class="bi bi-x me-2"></i>Annulla
                        </button>
                        <button type="button" class="btn btn-pokemon btn-lg" id="cropperProcessBtn">
                            <i class="bi bi-magic me-2"></i>Elabora con OCR
                        </button>
                    </div>
                </div>
            </div>

            <!-- Single Card Result (backward compatibility) -->
            <div class="glass-card p-4 d-none" id="resultCard">
                <h4 class="text-white mb-3">
                    <i class="bi bi-file-text me-2"></i>Testo Estratto (OCR)
                </h4>
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <img id="resultImage" class="img-fluid rounded" alt="Cropped image"
                            style="max-height: 250px; object-fit: contain; width: 100%; background: rgba(0,0,0,0.2);">
                    </div>
                    <div class="col-md-8">
                        <div class="ocr-preview">
                            <pre id="extractedText" class="text-white mb-0" style="white-space: pre-wrap;"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Area (single mode) -->
            <div id="actionButtons" class="text-center mt-4 d-none">
                <div class="action-buttons">
                    <button type="button" class="btn btn-outline-light" id="discardBtn">
                        <i class="bi bi-x-lg me-2"></i>Scarta
                    </button>
                    <button type="button" class="btn btn-success" id="saveOcrBtn">
                        <i class="bi bi-check-lg me-2"></i>Salva così
                    </button>
                    <button type="button" class="btn btn-pokemon" id="enhanceBtn">
                        <i class="bi bi-stars me-2"></i>Migliora con AI
                    </button>
                </div>
            </div>

            <!-- Card Details Form Section (single mode) -->
            <div id="cardDetailsSection" class="glass-card p-4 mt-4 d-none">
                <h4 class="text-white mb-4"><i class="bi bi-card-heading me-2"></i>Dettagli Carta</h4>
                <form id="cardDetailsForm">
                    @csrf
                    <!-- Basic Info Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-info-circle me-1"></i> Informazioni Base
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Carta</label>
                                <input type="text" class="form-control" id="card_name" name="card_name"
                                    placeholder="es. Pikachu">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">HP</label>
                                <input type="text" class="form-control" id="hp" name="hp" placeholder="es. 60">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" id="type" name="type" placeholder="es. Elettro">
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-success btn-lg" id="saveAiBtn">
                            <i class="bi bi-check-circle-fill me-2"></i>Salva Scheda Completa
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Scans -->
            @if($cards->count() > 0)
                <div class="recent-scans-section">
                    <h3 class="text-white mb-3">
                        <i class="bi bi-clock-history me-2"></i>Scansioni Recenti
                    </h3>
                    <div class="glass-card table-responsive p-0">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="ps-4">Anteprima</th>
                                    <th scope="col">Nome/Dettagli</th>
                                    <th scope="col">Data</th>
                                    <th scope="col">Stato</th>
                                    <th scope="col" class="text-end pe-4">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cards as $card)
                                    <tr>
                                        <td class="ps-4">
                                            <img src="{{ Storage::url($card->storage_path) }}" alt="Card"
                                                class="rounded border border-secondary"
                                                style="width: 40px; height: 60px; object-fit: cover;">
                                        </td>
                                        <td>
                                            @if($card->card_name)
                                                <div class="fw-bold text-white">{{ $card->card_name }}</div>
                                                @if($card->type)
                                                    <span class="type-badge type-{{ strtolower($card->type) }}">{{ $card->type }}</span>
                                                @endif
                                            @else
                                                <span class="text-white-50 fst-italic">Scansione senza nome</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-white-50">{{ $card->created_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td>
                                            @if($card->status === 'completed')
                                                <span class="status-badge status-completed">
                                                    <i class="bi bi-check-circle me-1"></i>Completato
                                                </span>
                                            @elseif($card->status === 'pending' || $card->status === 'review')
                                                <span class="status-badge status-pending">
                                                    <i class="bi bi-hourglass-split me-1"></i>In Attesa
                                                </span>
                                            @else
                                                <span class="status-badge status-failed">
                                                    <i class="bi bi-x-circle me-1"></i>Fallito
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('ocr.index') }}" class="btn btn-sm btn-outline-light"
                                                title="Vai alla collezione">
                                                <i class="bi bi-arrow-right-circle me-1"></i>Visualizza
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.js"></script>
    <script>
        // Gallery Mode JavaScript
        // State management
        const galleryCards = new Map(); // id -> { file, state, data, thumbnail, cardId, croppedBlob }
        const selectedCards = new Set(); // Set of fileIds
        let cropper = null;
        let selectedCardId = null;

        // DOM Elements
        const dropZone = document.getElementById('uploadArea'); // Renamed from uploadArea for clarity
        const fileInput = document.getElementById('fileInput');
        const galleryGrid = document.getElementById('galleryGrid');
        const galleryCount = document.getElementById('galleryCount');
        const resetAllBtn = document.getElementById('resetAllBtn');

        const cropperModal = document.getElementById('cropperModal');
        const cropperModalImage = document.getElementById('cropperModalImage'); // Renamed from cropperImage for consistency
        const cropperModalClose = document.getElementById('cropperModalClose');
        const cropperCancelBtn = document.getElementById('cropperCancelBtn');
        const cropperProcessBtn = document.getElementById('cropperProcessBtn'); // Now "Conferma Ritaglio"

        // Batch UI Elements (To be added to HTML)
        let batchActionBar = null;

        // Single mode elements (backward compatibility)
        const resultCard = document.getElementById('resultCard');
        const resultImage = document.getElementById('resultImage');
        const extractedText = document.getElementById('extractedText');
        const actionButtons = document.getElementById('actionButtons');
        const cardDetailsSection = document.getElementById('cardDetailsSection');
        const cardDetailsForm = document.getElementById('cardDetailsForm');

        // Upload area click
        dropZone.addEventListener('click', () => fileInput.click());

        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFiles(Array.from(files));
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFiles(Array.from(e.target.files));
            }
        });

        // Handle multiple files
        function handleFiles(files) {
            // Validate all files first
            const validFiles = files.filter(file => {
                if (!file.type.match('image.*')) {
                    showToast(`${file.name}: Non è un'immagine valida`, 'error');
                    return false;
                }
                if (file.size > 30 * 1024 * 1024) {
                    showToast(`${file.name}: File troppo grande (max 30MB)`, 'error');
                    return false;
                }
                return true;
            });

            if (validFiles.length === 0) return;

            // Gallery mode
            dropZone.style.display = 'none';
            gallerySection.classList.remove('d-none');

            validFiles.forEach(file => {
                const fileId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                createGalleryCard(fileId, file);
            });

            updateGalleryCount();
        }

        // Create gallery card
        function createGalleryCard(fileId, file) {
            // Create thumbnail
            const reader = new FileReader();
            reader.onload = (e) => {
                const thumbnail = e.target.result;

                galleryCards.set(fileId, {
                    file: file,
                    state: 'pending',
                    data: null,
                    thumbnail: thumbnail,
                    cardId: null,
                    croppedBlob: null
                });

                if (galleryCards.size > 0 && !batchActionBar) {
                    createBatchActionBar();
                }
                updateBatchActionBar();

                renderGalleryCard(fileId);
            };
            reader.readAsDataURL(file);
        }

        // Toggle Selection
        window.toggleSelection = function (fileId) {
            if (selectedCards.has(fileId)) {
                selectedCards.delete(fileId);
            } else {
                selectedCards.add(fileId);
            }
            renderGalleryCard(fileId);
            updateBatchActionBar();
        };

        // Render gallery card
        function renderGalleryCard(fileId) {
            const card = galleryCards.get(fileId);
            if (!card) return;

            let existingCard = document.getElementById(`card-${fileId}`);
            if (!existingCard) {
                existingCard = document.createElement('div');
                existingCard.className = 'gallery-card glass-card p-0 fade-in';
                existingCard.id = `card-${fileId}`;
                galleryGrid.appendChild(existingCard);
            }

            const isSelected = selectedCards.has(fileId);

            // Status Badge Logic
            let statusBadge = '<span class="status-badge status-pending">In attesa</span>';
            let actionsHTML = `
                    <button class="btn btn-sm btn-pokemon w-100" onclick="openCropperModal('${fileId}')">
                        <i class="bi bi-crop"></i> Ritaglia
                    </button>
                `;

            if (card.state === 'cropped') {
                statusBadge = '<span class="status-badge bg-info text-white">Pronto</span>';
                actionsHTML = `
                        <button class="btn btn-sm btn-outline-light w-100" onclick="openCropperModal('${fileId}')">
                            <i class="bi bi-crop"></i> Modifica
                        </button>
                    `;
            } else if (card.state === 'processing') {
                statusBadge = '<span class="status-badge status-processing">Elaborazione...</span>';
                actionsHTML = `<div class="text-center text-white"><div class="spinner-border spinner-border-sm" role="status"></div></div>`;
            } else if (card.state === 'completed') {
                statusBadge = '<span class="status-badge status-completed"><i class="bi bi-check-circle"></i> Completato</span>';
                actionsHTML = `
                        <button class="btn btn-sm btn-outline-light flex-1" onclick="toggleCardDetails('${fileId}')">
                            <i class="bi bi-eye"></i> Dettagli
                        </button>
                        <button class="btn btn-sm btn-pokemon flex-1" onclick="enhanceGalleryCard('${fileId}')">
                            <i class="bi bi-stars"></i> AI
                        </button>
                    `;
            } else if (card.state === 'saved') {
                statusBadge = '<span class="status-badge status-saved"><i class="bi bi-save"></i> Salvato</span>';
                actionsHTML = `
                        <div class="text-center text-success small">
                            <i class="bi bi-check-all"></i> Salvato in collezione
                        </div>
                    `;
            } else if (card.state === 'error') {
                statusBadge = '<span class="status-badge status-error">Errore</span>';
                actionsHTML = `
                        <button class="btn btn-sm btn-outline-danger w-100" onclick="openCropperModal('${fileId}')">
                            <i class="bi bi-arrow-clockwise"></i> Riprova
                        </button>
                    `;
            }

            // Thumbnail (use cropped blob if available)
            let imgSrc = card.thumbnail;
            if (card.croppedBlob) {
                imgSrc = URL.createObjectURL(card.croppedBlob);
            }

            existingCard.innerHTML = `
                    <div class="position-relative">
                        <div class="form-check position-absolute top-0 start-0 m-2 z-3">
                            <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleSelection('${fileId}')">
                        </div>
                        <!-- Image Container -->
                        <div style="height: 200px; overflow: hidden; background: #0f172a; display: flex; align-items: center; justify-content: center;">
                            <img src="${imgSrc}" class="img-fluid" style="max-height: 100%; width: auto; object-fit: contain;">
                        </div>
                        ${statusBadge}
                    </div>

                    <div class="p-3">
                        <div class="gallery-card-title text-truncate" title="${card.file.name}">${card.file.name}</div>
                        <div class="gallery-card-actions text-center mt-2">
                            ${actionsHTML}
                        </div>

                        <div class="gallery-card-details" id="details-${fileId}">
                            <!-- Details will be injected here -->
                        </div>
                    </div>
                `;

            // Add details HTML if ready
            if (card.state === 'completed' && card.data) {
                const detailsHTML = `
                        <div class="mt-3 pt-3 border-top border-secondary">
                            <div class="d-flex mb-2">
                                <small class="text-muted flex-1">OCR Testo:</small>
                                <i class="bi bi-clipboard cursor-pointer text-white" onclick="navigator.clipboard.writeText(\`${card.data.extracted_text?.replace(/`/g, '\\`')}\`)"></i>
                            </div>
                            <pre class="text-white small bg-dark p-2 rounded mb-3" style="max-height: 100px; overflow-y: auto;">${card.data.extracted_text || '...'}</pre>

                            <form id="form-${fileId}" class="mt-2">
                                    <input type="text" class="form-control form-control-sm mb-2" name="card_name" placeholder="Nome Carta" value="${card.data.card_name || ''}">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm" name="hp" placeholder="HP" value="${card.data.hp || ''}">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm" name="type" placeholder="Tipo" value="${card.data.type || ''}">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger flex-1" onclick="discardGalleryCard('${fileId}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success flex-1" onclick="saveGalleryCard('${fileId}')">
                                            <i class="bi bi-check"></i> Salva
                                        </button>
                                    </div>
                            </form>
                        </div>
                    `;
                const detailsContainer = existingCard.querySelector(`#details-${fileId}`);
                if (detailsContainer) detailsContainer.innerHTML = detailsHTML;
            }
        }

        // Update gallery count
        function updateGalleryCount() {
            galleryCount.textContent = galleryCards.size;
        }

        // Open cropper modal
        window.openCropperModal = function (fileId) {
            const card = galleryCards.get(fileId);
            if (!card) return;

            selectedCardId = fileId;
            cropperModalImage.src = card.thumbnail;
            cropperModal.classList.add('active');

            // Initialize cropper
            if (cropper) {
                cropper.destroy();
            }

            setTimeout(() => {
                cropper = new Cropper(cropperModalImage, {
                    viewMode: 1,
                    aspectRatio: NaN,
                    autoCropArea: 0.8,
                    responsive: true,
                    background: false,
                    guides: true,
                    highlight: true,
                    cropBoxResizable: true,
                    cropBoxMovable: true,
                });
            }, 100);
        };

        // Close cropper modal
        function closeCropperModal() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropperModal.classList.remove('active');
            selectedCardId = null;
        }

        cropperModalClose.addEventListener('click', closeCropperModal);
        cropperCancelBtn.addEventListener('click', closeCropperModal);

        // Process card crop (Offline: Store Blob)
        cropperProcessBtn.addEventListener('click', async () => {
            if (!cropper || !selectedCardId) return;
            const card = galleryCards.get(selectedCardId);
            if (!card) return;

            cropperProcessBtn.disabled = true;

            try {
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 4096,
                    maxHeight: 4096,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (!canvas) throw new Error('Errore canvas');

                const cardIdToProcess = selectedCardId;

                canvas.toBlob((blob) => {
                    card.croppedBlob = blob;
                    card.state = 'cropped'; // New state: Ready to be processed

                    // Auto-select the cropped card
                    selectedCards.add(cardIdToProcess);

                    renderGalleryCard(cardIdToProcess);
                    closeCropperModal();
                    updateBatchActionBar();

                    showToast('Ritaglio salvato. Seleziona per elaborare.', 'success');
                    cropperProcessBtn.disabled = false;
                }, 'image/jpeg', 0.95);

            } catch (e) {
                showToast('Errore ritaglio: ' + e.message, 'error');
                cropperProcessBtn.disabled = false;
            }
        });

        // Batch Processing Functions
        function createBatchActionBar() {
            batchActionBar = document.createElement('div');
            batchActionBar.className = 'batch-action-bar glass-card p-3 position-fixed bottom-0 start-50 translate-middle-x mb-4 d-flex align-items-center gap-3 z-3';
            batchActionBar.style.width = 'fit-content';
            batchActionBar.style.minWidth = '300px';
            batchActionBar.innerHTML = `
                    <div class="fw-bold text-white"><span id="selectedCount">0</span> Selezionati</div>
                    <div class="vr bg-secondary mx-2"></div>
                    <div class="d-flex gap-2">
                         <button class="btn btn-sm btn-primary" id="batchOcrBtn" onclick="runBatchOCR()">
                            <i class="bi bi-cpu"></i> Elabora (OCR)
                         </button>
                         <button class="btn btn-sm btn-pokemon" id="batchAiBtn" onclick="runBatchAI()">
                            <i class="bi bi-stars"></i> Run AI
                         </button>
                    </div>
                 `;
            document.body.appendChild(batchActionBar);
        }

        function updateBatchActionBar() {
            if (!batchActionBar) return;
            document.getElementById('selectedCount').textContent = selectedCards.size;
            if (selectedCards.size > 0) {
                batchActionBar.classList.remove('d-none');
            } else {
                batchActionBar.classList.add('d-none');
            }
        }

        window.runBatchOCR = async function () {
            const cardsToProcess = Array.from(selectedCards).filter(id => {
                const c = galleryCards.get(id);
                return c && c.state === 'cropped' && c.croppedBlob;
            });

            if (cardsToProcess.length === 0) {
                showToast('Nessuna carta pronta per OCR selezionata', 'warning');
                return;
            }

            for (const fileId of cardsToProcess) {
                await processSingleCardOCR(fileId);
            }
        };

        window.runBatchAI = async function () {
            const cardsToProcess = Array.from(selectedCards).filter(id => {
                const c = galleryCards.get(id);
                return c && c.state === 'completed'; // Must be OCR completed
            });

            if (cardsToProcess.length === 0) {
                showToast('Nessuna carta con OCR completato selezionata', 'warning');
                return;
            }

            // Parallel execution for AI? Or sequential? Sequential is safer for rate limits.
            for (const fileId of cardsToProcess) {
                await enhanceGalleryCard(fileId);
            }
        };

        // Extracted Single Card Process Logic
        async function processSingleCardOCR(fileId) {
            const card = galleryCards.get(fileId);
            if (!card || !card.croppedBlob) return;

            card.state = 'processing';
            renderGalleryCard(fileId);

            const formData = new FormData();
            formData.append('cropped_image', card.croppedBlob, 'pokemon-card.jpg');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('{{ route("ocr.process") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData,
                });
                const data = await response.json();

                if (data.success) {
                    card.state = 'completed';
                    card.data = data.data;
                    card.cardId = data.data.id;
                    showToast(`${card.file.name}: OCR Completato`, 'success');
                } else {
                    card.state = 'error';
                    showToast(`${card.file.name}: Errore OCR`, 'error');
                }
            } catch (e) {
                card.state = 'error';
                showToast(`${card.file.name}: Errore Rete`, 'error');
            }
            renderGalleryCard(fileId);
        }

        // Toggle card details
        window.toggleCardDetails = function (fileId) {
            const details = document.getElementById(`details-${fileId}`);
            if (details) {
                details.classList.toggle('show');
            }
        };

        // Enhance card with AI
        // Enhance card with AI
        window.enhanceGalleryCard = async function (fileId) {
            const card = galleryCards.get(fileId);
            if (!card || !card.cardId) return;

            card.state = 'processing';
            renderGalleryCard(fileId);

            showLoading();
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('{{ route("ocr.enhance") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ card_id: card.cardId })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    // Update card data with AI results
                    card.data = { ...card.data, ...data.data };
                    card.state = 'completed';
                    renderGalleryCard(fileId);
                    showToast('Dati AI caricati!', 'success');
                } else {
                    card.state = 'completed';
                    renderGalleryCard(fileId);
                    showToast('Errore AI: ' + data.message, 'error');
                }
            } catch (error) {
                hideLoading();
                card.state = 'completed';
                renderGalleryCard(fileId);
                showToast('Errore: ' + error.message, 'error');
            }
        };

        // Save gallery card
        window.saveGalleryCard = async function (fileId) {
            const card = galleryCards.get(fileId);
            if (!card || !card.cardId) return;

            showLoading();
            try {
                const form = document.getElementById(`form-${fileId}`);
                const formData = new FormData(form);
                const formValues = Object.fromEntries(formData.entries());

                // Create payload by merging all known card data with form values
                // sending card.data ensures fields not in the form (like attacks, rarity, etc.) are saved
                const payload = {
                    ...card.data,
                    ...formValues,
                    card_id: card.cardId
                };

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('{{ route("ocr.confirm") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    card.state = 'saved';
                    renderGalleryCard(fileId);
                    showToast('Carta salvata!', 'success');
                } else {
                    showToast('Errore: ' + data.message, 'error');
                }
            } catch (error) {
                hideLoading();
                showToast('Errore: ' + error.message, 'error');
            }
        };

        // Discard gallery card
        window.discardGalleryCard = async function (fileId) {
            const card = galleryCards.get(fileId);
            if (!card) return;

            if (!confirm('Eliminare questa carta?')) return;

            if (card.cardId) {
                showLoading();
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch('{{ route("ocr.discard") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ card_id: card.cardId })
                    });

                    hideLoading();
                    const data = await response.json();
                    if (data.success) {
                        removeGalleryCard(fileId);
                        showToast('Carta eliminata', 'info');
                    }
                } catch (error) {
                    hideLoading();
                    showToast('Errore: ' + error.message, 'error');
                }
            } else {
                removeGalleryCard(fileId);
            }
        };

        // Remove gallery card
        function removeGalleryCard(fileId) {
            const cardElement = document.getElementById(`gallery-card-${fileId}`);
            if (cardElement) {
                cardElement.remove();
            }
            galleryCards.delete(fileId);
            updateGalleryCount();

            if (galleryCards.size === 0) {
                resetAll();
            }
        }

        // Reset all
        function resetAll() {
            galleryCards.clear();
            galleryGrid.innerHTML = '';
            gallerySection.classList.add('d-none');
            uploadArea.style.display = 'block';
            fileInput.value = '';
            updateGalleryCount();
        }

        resetAllBtn.addEventListener('click', () => {
            if (confirm('Reset tutto e ricaricare nuove immagini?')) {
                resetAll();
            }
        });
    </script>
@endpush