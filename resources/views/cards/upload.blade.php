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

        .cropper-modal {
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

        .cropper-modal.active {
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
    </style>
@endpush

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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Carte Caricate (<span id="cardCount">0</span>)</h3>
                <button class="btn btn-sm btn-danger-pokemon" onclick="resetGallery()">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </button>
            </div>
            <div class="gallery-grid" id="galleryGrid"></div>
        </div>

        <!-- Cropper Modal -->
        <div class="cropper-modal" id="cropperModal">
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
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let galleryCards = new Map();
        let cropper = null;
        let currentFileId = null;

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

        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) return;

                const fileId = Date.now() + '-' + Math.random();
                const reader = new FileReader();

                reader.onload = (e) => {
                    galleryCards.set(fileId, {
                        file: file,
                        thumbnail: e.target.result,
                        state: 'pending',
                        data: null
                    });
                    renderGallery();
                };

                reader.readAsDataURL(file);
            });
        }

        function renderGallery() {
            const grid = document.getElementById('galleryGrid');
            const section = document.getElementById('gallerySection');
            const count = document.getElementById('cardCount');

            if (galleryCards.size === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';
            count.textContent = galleryCards.size;
            grid.innerHTML = '';

            galleryCards.forEach((card, fileId) => {
                const cardEl = document.createElement('div');
                cardEl.className = 'card-item';
                cardEl.innerHTML = `
                <img src="${card.thumbnail}" class="card-image" alt="Card">
                <div class="d-flex flex-column gap-2">
                    ${card.state === 'pending' ? `
                        <button class="btn btn-sm btn-pokemon" onclick="openCropper('${fileId}')">
                            <i class="bi bi-crop"></i> Ritaglia
                        </button>
                    ` : card.state === 'cropped' ? `
                       <button class="btn btn-sm btn-success" onclick="recognizeWithAI('${fileId}')">
                            <i class="bi bi-robot"></i> Riconosci con AI
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="manualEntry('${fileId}')">
                            <i class="bi bi-pencil"></i> Inserimento Manuale
                        </button>
                    ` : card.state === 'processing' ? `
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                            <small class="d-block mt-1">Elaborazione...</small>
                        </div>
                    ` : card.state === 'ready' ? `
                        <button class="btn btn-sm btn-success" onclick="saveCard('${fileId}')">
                            <i class="bi bi-save"></i> Salva
                        </button>
                        <button class="btn btn-sm btn-info" onclick="editCard('${fileId}')">
                            <i class="bi bi-pencil"></i> Modifica
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteCard('${fileId}')">
                        <i class="bi bi-trash"></i> Elimina
                    </button>
                </div>
                ${card.data ? `
                    <div class="mt-2 small text-white-50">
                        <strong>${card.data.card_name || 'Sconosciuta'}</strong><br>
                        HP: ${card.data.hp || 'N/A'} | ${card.data.type || 'N/A'}
                    </div>
                ` : ''}
            `;
                grid.appendChild(cardEl);
            });
        }

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
                formData.append('cropped_image', blob, 'card.jpg');

                try {
                    const response = await fetch('{{ route("cards.upload-image") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        const card = galleryCards.get(currentFileId);
                        card.state = 'cropped';
                        card.cardId = result.data.id;
                        card.thumbnail = result.data.image_url;
                        renderGallery();
                        showToast('Immagine caricata con successo!', 'success');
                    }
                } catch (error) {
                    showToast('Errore durante il caricamento', 'error');
                }

                closeCropper();
            });
        }

        async function recognizeWithAI(fileId) {
            const card = galleryCards.get(fileId);
            card.state = 'processing';
            renderGallery();

            try {
                const response = await fetch('{{ route("cards.enhance") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ card_id: card.cardId })
                });

                const result = await response.json();
                if (result.success) {
                    card.data = result.data;
                    card.state = 'ready';
                    showToast('Riconoscimento AI completato!', 'success');
                }
            } catch (error) {
                card.state = 'cropped';
                showToast('Errore nel riconoscimento AI', 'error');
            }

            renderGallery();
        }

        function manualEntry(fileId) {
            // For simplicity, prompting for manual entry (can be enhanced with a modal form)
            const card = galleryCards.get(fileId);
            const name = prompt('Nome Carta:');
            const hp = prompt('HP:');
            const type = prompt('Tipo:');

            if (name) {
                card.data = { card_name: name, hp: hp, type: type };
                card.state = 'ready';
                renderGallery();
            }
        }

        async function saveCard(fileId) {
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
                    galleryCards.delete(fileId);
                    renderGallery();
                    showToast('Carta salvata con successo!', 'success');
                }
            } catch (error) {
                showToast('Errore durante il salvataggio', 'error');
            }
        }

        function editCard(fileId) {
            manualEntry(fileId);
        }

        async function deleteCard(fileId) {
            if (!confirm('Eliminare questa carta?')) return;

            const card = galleryCards.get(fileId);
            if (card.cardId) {
                await fetch('{{ route("cards.discard") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ card_id: card.cardId })
                });
            }

            galleryCards.delete(fileId);
            renderGallery();
        }

        function resetGallery() {
            if (!confirm('Eliminare tutte le carte in lavorazione?')) return;
            galleryCards.clear();
            renderGallery();
        }
    </script>
@endpush