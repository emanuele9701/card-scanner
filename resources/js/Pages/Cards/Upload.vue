<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useModal } from '@/composables/useModal';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import axios from 'axios';

const { showConfirm, showAlert } = useModal();

const props = defineProps({
    initialCards: Array,
    cardsBySet: Object,
    cardsWithoutSet: Array,
});

// State
const cards = ref([]); // Local state of cards being processed
const currentTab = ref('pending'); // pending, processing, completed
const selectedCardIds = ref(new Set());
const isDragging = ref(false);
const fileInput = ref(null);
const toasts = ref([]); // Toast state

// Loading states
const isSkipping = ref(false);
const isCropping = ref(false);
const isUploading = ref(false);
const isEnhancing = ref(new Set()); // Set of card IDs being enhanced
const isSaving = ref(new Set()); // Set of card IDs being saved
const isBulkEnhancing = ref(false);
const isBulkSaving = ref(false);

// Edit Modal State
const showEditModal = ref(false);
const editingCardId = ref(null);
const editForm = reactive({
    card_name: '',
    hp: '',
    type: '',
    evolution_stage: '',
    weakness: '',
    resistance: '',
    retreat_cost: '',
    set_number: '',
    rarity: '',
    illustrator: '',
    flavor_text: '',
    card_set_id: '',
    game: '',
});
const cardSets = ref([]);
const availableGames = ref([]);
const validationErrors = ref({});

// Cropper State
const showCropperModal = ref(false);
const cropperImageSrc = ref('');
const croppingCardId = ref(null);
let cropperInstance = null;

// Fullscreen Viewer State
const showFullscreen = ref(false);
const fullscreenImageSrc = ref('');

// Computed Stats
const stats = computed(() => {
    let pending = 0;
    let processing = 0;
    let completed = 0;

    cards.value.forEach(card => {
        if (card.state === 'pending' || card.state === 'uploading') pending++;
        else if (['cropped', 'processing', 'ready', 'failed'].includes(card.state)) processing++;
        else if (card.state === 'completed') completed++;
    });

    return { pending, processing, completed };
});

const filteredCards = computed(() => {
    return cards.value.filter(card => {
        if (currentTab.value === 'pending') return card.state === 'pending' || card.state === 'uploading';
        if (currentTab.value === 'processing') return ['cropped', 'processing', 'ready', 'failed'].includes(card.state);
        if (currentTab.value === 'completed') return card.state === 'completed';
        return false;
    });
});

// Actions
const triggerFileInput = () => fileInput.value.click();

const handleDrop = (e) => {
    isDragging.value = false;
    const files = e.dataTransfer.files;
    handleFiles(files);
};

const handleFileSelect = (e) => {
    handleFiles(e.target.files);
    e.target.value = ''; // Allow re-selecting the same file
};

/**
 * Resize image client-side if it exceeds 1920x1080 (1080p)
 * @param {File} file - The image file to resize
 * @returns {Promise<File>} - The resized file or original if within limits
 */
const resizeImageIfNeeded = (file) => {
    return new Promise((resolve, reject) => {
        const MAX_WIDTH = 1920;
        const MAX_HEIGHT = 1080;
        const QUALITY = 0.85; // 85% quality for JPEG

        // Create image object
        const img = new Image();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        img.onload = () => {
            const width = img.width;
            const height = img.height;

            console.log(`[CLIENT RESIZE] Original: ${width}x${height}, File size: ${(file.size / 1024 / 1024).toFixed(2)}MB`);

            // Check if resize is needed
            if (width <= MAX_WIDTH && height <= MAX_HEIGHT) {
                console.log('[CLIENT RESIZE] No resize needed - within limits');
                resolve(file);
                return;
            }

            // Calculate new dimensions maintaining aspect ratio
            const ratio = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height);
            const newWidth = Math.floor(width * ratio);
            const newHeight = Math.floor(height * ratio);

            console.log(`[CLIENT RESIZE] Resizing to: ${newWidth}x${newHeight}, Ratio: ${ratio.toFixed(4)}`);

            // Set canvas dimensions
            canvas.width = newWidth;
            canvas.height = newHeight;

            // Draw resized image
            ctx.drawImage(img, 0, 0, newWidth, newHeight);

            // Convert canvas to blob
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        reject(new Error('Failed to create blob'));
                        return;
                    }

                    console.log(`[CLIENT RESIZE] New file size: ${(blob.size / 1024 / 1024).toFixed(2)}MB`);

                    // Create new File object from blob
                    const resizedFile = new File(
                        [blob],
                        file.name,
                        {
                            type: file.type || 'image/jpeg',
                            lastModified: Date.now()
                        }
                    );

                    resolve(resizedFile);
                },
                file.type || 'image/jpeg',
                QUALITY
            );
        };

        img.onerror = () => {
            reject(new Error('Failed to load image'));
        };

        // Load image
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };
        reader.readAsDataURL(file);
    });
};

const handleFiles = async (files) => {
    currentTab.value = 'pending';
    const fileArray = Array.from(files).filter(file => file.type.startsWith('image/'));

    for (const originalFile of fileArray) {
        const tempId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        try {
            // Resize image if needed BEFORE upload
            console.log(`[UPLOAD] Processing file: ${originalFile.name}`);
            const file = await resizeImageIfNeeded(originalFile);
            
            // Add to local state immediately
            const reader = new FileReader();
            reader.onload = (e) => {
                cards.value.push({
                    tempId: tempId,
                    id: null, // Server ID
                    thumbnail: e.target.result,
                    state: 'uploading',
                    data: null,
                    error: null
                });
            };
            reader.readAsDataURL(file);

            // Upload resized file
            const formData = new FormData();
            formData.append('image', file);

            const response = await axios.post('/cards/upload-image', formData);
            const cardIndex = cards.value.findIndex(c => c.tempId === tempId);
            if (cardIndex !== -1) {
                cards.value[cardIndex].id = response.data.data.id;
                cards.value[cardIndex].state = 'pending';
                cards.value[cardIndex].thumbnail = response.data.data.image_url;
            }
        } catch (error) {
            console.error('[UPLOAD ERROR]', error);
            const cardIndex = cards.value.findIndex(c => c.tempId === tempId);
            if (cardIndex !== -1) {
                 // Remove failed uploads for now or show error state?
                 // Let's remove to match blade logic roughly or show error
                 cards.value.splice(cardIndex, 1);
            }
            showToast('Errore upload: ' + originalFile.name, 'error');
        }
    }
};

// Selection Logic
const toggleSelection = (cardId) => {
     // Vue 3 Set reactivity needs new Set trigger or assume setup handles it
     // For safety we can mutate the Set.
    if (selectedCardIds.value.has(cardId)) {
        selectedCardIds.value.delete(cardId);
    } else {
        selectedCardIds.value.add(cardId);
    }
};

const toggleSelectAll = (e) => {
    selectedCardIds.value.clear();
    if (e.target.checked) {
        filteredCards.value.forEach(card => {
             // Logic from blade: prevent selecting processing/uploading if strict
             if(card.state !== 'uploading' && card.state !== 'processing') {
                 selectedCardIds.value.add(card.id || card.tempId); // Use best ID
             }
        });
    }
};

// Cropper Logic
const openCropper = (card) => {
    croppingCardId.value = card.tempId; // Use tempId for local lookup
    cropperImageSrc.value = card.thumbnail;
    showCropperModal.value = true;
    
    nextTick(() => {
        const image = document.getElementById('cropperImage');
        if (cropperInstance) cropperInstance.destroy();
        cropperInstance = new Cropper(image, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 1
        });
    });
};

const closeCropper = () => {
    showCropperModal.value = false;
    if (cropperInstance) cropperInstance.destroy();
    cropperInstance = null;
    cropperImageSrc.value = '';
};

const confirmCrop = () => {
    if (!cropperInstance) return;
    
    cropperInstance.getCroppedCanvas().toBlob(async (blob) => {
        const cardIndex = cards.value.findIndex(c => c.tempId === croppingCardId.value);
        if (cardIndex === -1) return;
        const card = cards.value[cardIndex];

        if (!card.id) return; // Need server ID

        const formData = new FormData();
        formData.append('cropped_image', blob, 'card_crop.jpg');
        formData.append('card_id', card.id);

        try {
            const response = await axios.post('/cards/save-crop', formData);
            cards.value[cardIndex].state = 'cropped';
            cards.value[cardIndex].thumbnail = response.data.data.image_url;
            showToast('Ritaglio salvato!', 'success');
        } catch (error) {
            showToast('Errore salvataggio ritaglio', 'error');
        }
        closeCropper();
    });
};

const skipCrop = async (card, notify = true) => {
    try {
        await axios.post('/cards/skip-crop', { card_id: card.id });
        card.state = 'cropped';
        if (notify) showToast('Ritaglio saltato!', 'success');
    } catch (error) {
        if (notify) showToast('Errore durante lo skip', 'error');
    }
};

// AI Enhance
const recognizeWithAI = async (card, notify = true) => {
    if (isEnhancing.value.has(card.id || card.tempId)) return;
    
    const cardKey = card.id || card.tempId;
    isEnhancing.value.add(cardKey);
    card.state = 'processing';
    card.error = null;

    try {
        const response = await axios.post('/cards/enhance', { card_id: card.id });
        card.data = response.data.data;
        card.state = 'ready';
        if (notify) showToast('Analisi completata!', 'success');
    } catch (error) {
        card.state = 'failed';
        card.error = error.response?.data?.message || error.message || 'Errore AI';
        if (notify) showToast(card.error, 'error');
    } finally {
        isEnhancing.value.delete(cardKey);
    }
};

// Edit Modal
const openEditModal = async (card) => {
    editingCardId.value = card.tempId;
    
    // Reset form and validation errors
    Object.keys(editForm).forEach(key => editForm[key] = '');
    validationErrors.value = {};

    if (card.data) {
        Object.keys(editForm).forEach(key => {
            if (card.data[key]) editForm[key] = card.data[key];
        });
    }

    if (cardSets.value.length === 0) {
        try {
            const res = await axios.get('/cards/api/card-sets');
            cardSets.value = res.data.data;
        } catch (e) {
            console.error(e);
        }
    }
    
    // Load available games if not already loaded
    if (availableGames.value.length === 0) {
        try {
            const res = await axios.get('/cards/api/available-games');
            availableGames.value = res.data.data;
        } catch (e) {
            console.error(e);
        }
    }
    
    // If AI detected a game that's NOT in the available list, clear it
    // This forces the user to manually select a valid game
    if (card.data && card.data.game && !availableGames.value.includes(card.data.game)) {
        console.warn(`AI detected game "${card.data.game}" is not in available games list. User must select manually.`);
        editForm.game = '';
        validationErrors.value.game = `Il game "${card.data.game}" rilevato dall'AI non è valido. Seleziona manualmente.`;
    }
    
    showEditModal.value = true;
};

const saveEdit = async () => {
    // Clear previous errors
    validationErrors.value = {};
    
    // Validate required fields
    if (!editForm.card_name || editForm.card_name.trim() === '') {
        validationErrors.value.card_name = 'Il nome della carta è obbligatorio';
    }
    
    if (!editForm.game || editForm.game.trim() === '') {
        validationErrors.value.game = 'Il game è obbligatorio';
    }
    
    // If there are errors, show toast and return
    if (Object.keys(validationErrors.value).length > 0) {
        showToast('Compila tutti i campi obbligatori', 'error');
        return;
    }

    const cardIndex = cards.value.findIndex(c => c.tempId === editingCardId.value);
    if (cardIndex === -1) return;
    const card = cards.value[cardIndex];

    // Local update
    card.data = { ...editForm };
    card.state = 'ready';
    
    
    showEditModal.value = false;
    showToast('Dati salvati localmente', 'success');
};

const saveCard = async (card, notify = true) => {
    const cardKey = card.id || card.tempId;
    if (isSaving.value.has(cardKey)) return;
    
    isSaving.value.add(cardKey);
    
    try {
        await axios.post('/cards/save', {
            card_id: card.id,
            ...card.data
        });
        card.state = 'completed';
        if (notify) showToast('Carta salvata!', 'success');
    } catch (error) {
        if (notify) showToast('Errore salvataggio', 'error');
    } finally {
        isSaving.value.delete(cardKey);
    }
};

const deleteCard = async (card, skipConfirm = false) => {
    if (!skipConfirm) {
        const confirmed = await showConfirm(
            'Sei sicuro di voler eliminare questa carta?',
            'Conferma Eliminazione',
            { confirmText: 'Elimina', cancelText: 'Annulla' }
        );
        if (!confirmed) return;
    }
    try {
        if (card.id) {
            await axios.delete(`/cards/${card.id}`); 
        }
        // Blade used /cards/discard POST. Let's use discard to be safe with existing logic
        // await axios.post('/cards/discard', { card_id: card.id });
         // Checking web.php: Route::delete('/{card}', [CardUploadController::class, 'destroy']) exists.
         // But blade used discard. I'll use destroy if possible, or discard.
         // Let's stick to blade logic if unsure, but standard DELETE is better if routes exist.
         // Blade JS: await fetch('{{ route("cards.discard") }}'...
        
         // Actually I'll use DELETE /cards/{id}
         const cardIndex = cards.value.findIndex(c => c.tempId === card.tempId);
         if (cardIndex !== -1) cards.value.splice(cardIndex, 1);
         selectedCardIds.value.delete(card.id || card.tempId);
         
         // Fire and forget server delete to be snappy? Or await?
         await axios.delete(`/cards/${card.id}`);
    } catch (e) {
        console.error(e);
    }
};

// Bulk Actions
const bulkSkipCrop = async () => {
    const confirmed = await showConfirm(
        `Saltare il ritaglio per ${selectedCardIds.value.size} carte?`,
        'Conferma Azione',
        { confirmText: 'Salta', cancelText: 'Annulla' }
    );
    if (!confirmed) return;
    const ids = Array.from(selectedCardIds.value);
    selectedCardIds.value.clear();
    
    for (const id of ids) {
         // Find card by ID or tempId? logic above used tempId for lookup but Set probably stores ID
         // I should store objects or look up carefully.
         const card = cards.value.find(c => c.id === id || c.tempId === id);
         if (card) await skipCrop(card, false);
    }
    showToast('Ritaglio saltato per le carte selezionate', 'success');
};

const bulkAnalyze = async () => {
     const ids = Array.from(selectedCardIds.value);
     selectedCardIds.value.clear();
     showToast('Analisi avviata...', 'info');
     for (const id of ids) {
          const card = cards.value.find(c => c.id === id || c.tempId === id);
          if (card && card.state === 'cropped') recognizeWithAI(card, false);
     }
};

const bulkSave = async () => {
     const ids = Array.from(selectedCardIds.value);
     selectedCardIds.value.clear();
     for (const id of ids) {
          const card = cards.value.find(c => c.id === id || c.tempId === id);
          if (card && card.state === 'ready') await saveCard(card, false);
     }
     showToast('Carte salvate', 'success');
};

const bulkDelete = async () => {
    const confirmed = await showConfirm(
        `Eliminare ${selectedCardIds.value.size} carte?`,
        'Conferma Eliminazione Multipla',
        { confirmText: 'Elimina', cancelText: 'Annulla' }
    );
    if (!confirmed) return;
    const ids = Array.from(selectedCardIds.value);
    selectedCardIds.value.clear();
    for (const id of ids) {
         const card = cards.value.find(c => c.id === id || c.tempId === id);
         if (card) await deleteCard(card, true);
    }
};

const resetGallery = async () => {
    const confirmed = await showConfirm(
        'Sei sicuro di voler eliminare tutto?\nLe carte salvate o caricate verranno eliminate DEFINITIVAMENTE dal database e dallo storage.',
        'Reset Galleria',
        { confirmText: 'Elimina Tutto', cancelText: 'Annulla' }
    );
    if (!confirmed) return;
    
    // Create a copy of cards to delete to avoid iteration issues while modifying
    const cardsToDelete = [...cards.value];
    let deletedCount = 0;
    
    showToast('Eliminazione in corso...', 'info');

    for (const card of cardsToDelete) {
        if (card.id) {
            try {
                // Delete from server (using logic similar to deleteCard but without reloading UI/confirm)
                await axios.delete(`/cards/${card.id}`);
                deletedCount++;
            } catch (e) {
                console.error(`Errore eliminazione carta ${card.id}:`, e);
            }
        }
    }
    
    cards.value = [];
    selectedCardIds.value.clear();
    if (fileInput.value) fileInput.value.value = '';
    
    if (deletedCount > 0) {
        showToast(`${deletedCount} carte eliminate definitivamente`, 'success');
    } else {
        showToast('Galleria svuotata', 'success');
    }
};


// Utils
const showToast = (message, type = 'success') => {
    const id = Date.now();
    toasts.value.push({ id, message, type });
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(id);
    }, 5000);
};

const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

// Fullscreen
const openFullscreen = (src) => {
    fullscreenImageSrc.value = src;
    showFullscreen.value = true;
};
</script>

<template>
    <AppLayout>
        <Head title="Card Scanner - Carica Carta" />
        
        <div class="container h-custom-padding">
            <div class="text-center mb-5">
                <h1 class="page-title">Carica le Tue Carte</h1>
                <p class="page-subtitle">Scansiona le tue Carte con intelligenza artificiale o inserisci i dati manualmente</p>
            </div>

            <!-- Upload Area -->
            <div class="glass-card p-4 mb-4">
                <div 
                    class="upload-zone" 
                    :class="{ 'drag-over': isDragging }"
                    @click="triggerFileInput"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <i class="bi bi-cloud-upload" style="font-size: 48px; color: #FFCB05;"></i>
                    <h3 class="mt-3">Trascina le immagini qui</h3>
                    <p class="text-muted">oppure clicca per selezionare</p>
                    <input 
                        ref="fileInput" 
                        type="file" 
                        accept="image/*" 
                        multiple 
                        class="d-none" 
                        @change="handleFileSelect"
                    >
                </div>
            </div>

            <!-- Gallery -->
            <div v-if="cards.length > 0" id="gallerySection">
                <!-- Tabs -->
                <div class="tabs-nav">
                    <div class="tab-item" :class="{ active: currentTab === 'pending' }" @click="currentTab = 'pending'">
                        Da Ritagliare <span class="tab-badge" :class="{ 'active-badge': currentTab === 'pending' }">{{ stats.pending }}</span>
                    </div>
                    <div class="tab-item" :class="{ active: currentTab === 'processing' }" @click="currentTab = 'processing'">
                        Da Analizzare <span class="tab-badge" :class="{ 'active-badge': currentTab === 'processing' }">{{ stats.processing }}</span>
                    </div>
                    <div class="tab-item" :class="{ active: currentTab === 'completed' }" @click="currentTab = 'completed'">
                        Completate <span class="tab-badge" :class="{ 'active-badge': currentTab === 'completed' }">{{ stats.completed }}</span>
                    </div>
                </div>

                <!-- Controls -->
                 <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll" @change="toggleSelectAll">
                        <label class="form-check-label text-white-50 ms-2" for="selectAll">Seleziona Tutti</label>
                    </div>
                    <button class="btn btn-sm btn-danger-pokemon" @click="resetGallery">
                        <i class="bi bi-trash"></i> Svuota Tutto
                    </button>
                </div>

                <!-- Grid -->
                <div class="gallery-grid">
                    <div v-for="card in filteredCards" :key="card.tempId" class="card-item">
                        <!-- Checkbox -->
                        <div 
                            v-if="card.state !== 'uploading' && card.state !== 'processing'" 
                            class="card-checkbox" 
                            :class="{ checked: selectedCardIds.has(card.id || card.tempId) }"
                            @click="toggleSelection(card.id || card.tempId)"
                        >
                            <i class="bi bi-check"></i>
                        </div>

                        <!-- Image -->
                        <div class="card-image-wrapper" @click="openFullscreen(card.thumbnail)">
                            <img :src="card.thumbnail" class="card-image" alt="Card">
                            <div class="zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex flex-column gap-2">
                             <div v-if="card.state === 'uploading'" class="text-center text-white-50"><small>Upload in corso...</small></div>
                             
                             <template v-else-if="card.state === 'pending'">
                                <button class="btn btn-sm btn-pokemon" @click="openCropper(card)">
                                    <i class="bi bi-crop"></i> Ritaglia
                                </button>
                                <button class="btn btn-sm btn-secondary" @click="skipCrop(card)">
                                    <i class="bi bi-skip-forward"></i> Salta
                                </button>
                                <button class="btn btn-sm btn-danger" @click="deleteCard(card)">
                                    <i class="bi bi-trash"></i> Elimina
                                </button>
                             </template>

                             <template v-else-if="card.state === 'cropped'">
                                <button class="btn btn-sm btn-success" @click="recognizeWithAI(card)">
                                    <i class="bi bi-robot"></i> Analizza
                                </button>
                                <button class="btn btn-sm btn-warning" @click="openEditModal(card)">
                                    <i class="bi bi-pencil"></i> Manuale
                                </button>
                                <button class="btn btn-sm btn-danger" @click="deleteCard(card)">
                                    <i class="bi bi-trash"></i> Elimina
                                </button>
                             </template>

                             <template v-else-if="card.state === 'processing'">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                                    <small class="d-block mt-1">AI in corso...</small>
                                </div>
                             </template>

                             <template v-else-if="card.state === 'ready'">
                                <button class="btn btn-sm btn-success" @click="saveCard(card)">
                                    <i class="bi bi-save"></i> Salva
                                </button>
                                <button class="btn btn-sm btn-info" @click="openEditModal(card)">
                                    <i class="bi bi-pencil"></i> Modifica
                                </button>
                                <button class="btn btn-sm btn-danger" @click="deleteCard(card)">
                                    <i class="bi bi-trash"></i> Elimina
                                </button>
                             </template>

                             <template v-else-if="card.state === 'completed'">
                                <div class="alert alert-success p-1 text-center mb-0"><small>Completata</small></div>
                                <button class="btn btn-sm btn-secondary" @click="deleteCard(card)">
                                    <i class="bi bi-trash"></i> Rimuovi
                                </button>
                             </template>
                             
                             <template v-else-if="card.state === 'failed'">
                                <div class="alert alert-danger p-1 text-center mb-1">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <small class="d-block text-truncate" style="max-width: 100%;">{{ card.error }}</small>
                                </div>
                                <button class="btn btn-sm btn-success" @click="recognizeWithAI(card)">
                                    <i class="bi bi-arrow-clockwise"></i> Riprova
                                </button>
                                <button class="btn btn-sm btn-warning" @click="openEditModal(card)">
                                    <i class="bi bi-pencil"></i> Manuale
                                </button>
                                <button class="btn btn-sm btn-secondary" @click="deleteCard(card)">
                                    <i class="bi bi-trash"></i> Rimuovi
                                </button>
                             </template>
                        </div>
                        
                        <!-- Info -->
                         <div v-if="card.data" class="mt-2 small text-white-50">
                            <strong>{{ card.data.card_name || 'Sconosciuta' }}</strong><br>
                            {{ card.data.type }}
                         </div>
                    </div>
                </div>
            </div>
            
            <!-- Floating Action Bar -->
            <div class="floating-action-bar" :class="{ visible: selectedCardIds.size > 0 }">
                <div class="fab-count">{{ selectedCardIds.size }}</div>
                <div class="d-flex gap-2">
                     <template v-if="currentTab === 'pending'">
                        <button class="btn btn-sm btn-secondary" @click="bulkSkipCrop">
                            <i class="bi bi-skip-forward"></i> Salta Ritaglio
                        </button>
                    </template>
                    <template v-if="currentTab === 'processing'">
                        <button class="btn btn-sm btn-success" @click="bulkAnalyze">
                            <i class="bi bi-robot"></i> Analizza
                        </button>
                        <button class="btn btn-sm btn-primary" @click="bulkSave">
                            <i class="bi bi-save"></i> Salva
                        </button>
                    </template>
                     <template v-if="currentTab === 'completed'">
                        <button class="btn btn-sm btn-danger" @click="bulkDelete">
                            <i class="bi bi-trash"></i> Elimina
                        </button>
                    </template>
                </div>
            </div>

            <!-- Cropper Modal -->
            <div v-if="showCropperModal" class="custom-modal-overlay">
                <div class="cropper-container-wrapper">
                    <h4 class="text-white text-center mb-3">Ritaglia la Carta</h4>
                    <div style="max-height: 60vh; overflow:hidden;">
                        <img id="cropperImage" :src="cropperImageSrc" style="max-width: 100%;">
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-pokemon me-2" @click="confirmCrop">
                            <i class="bi bi-check-lg"></i> Conferma Ritaglio
                        </button>
                        <button class="btn btn-secondary" @click="closeCropper">
                            <i class="bi bi-x-lg"></i> Annulla
                        </button>
                    </div>
                </div>
            </div>

             <!-- Edit Modal -->
            <div v-if="showEditModal" class="custom-modal-overlay">
                <div class="card-edit-container">
                    <div class="modal-header-custom">
                         <h4>
                            <i class="bi bi-pencil-square"></i>
                            <span>{{ editingCardId ? 'Modifica Carta' : 'Inserimento Manuale' }}</span>
                        </h4>
                        <button class="modal-close-btn" @click="showEditModal = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body-custom">
                         <!-- Form here (simplified for brevity, should map editForm) -->
                         <!-- I'll add the form fields properly -->
                         <div style="padding: 20px; overflow-y: auto; max-height: 70vh; width: 100%;">
                             <div class="form-grid">
                                <div class="form-group-custom">
                                    <label>Nome Carta *</label>
                                    <input 
                                        v-model="editForm.card_name" 
                                        type="text"
                                        :class="{ 'border-error': validationErrors.card_name }"
                                    >
                                    <span v-if="validationErrors.card_name" class="error-message">
                                        {{ validationErrors.card_name }}
                                    </span>
                                </div>
                                <div class="form-group-custom">
                                    <label>HP</label>
                                    <input v-model="editForm.hp" type="text">
                                </div>
                                <div class="form-group-custom">
                                    <label>Tipo</label>
                                    <select v-model="editForm.type">
                                        <option value="">Seleziona...</option>
                                        <option value="Grass">Erba</option>
                                        <option value="Fire">Fuoco</option>
                                        <option value="Water">Acqua</option>
                                        <option value="Lightning">Elettro</option>
                                        <option value="Psychic">Psico</option>
                                        <option value="Fighting">Lotta</option>
                                        <option value="Darkness">Oscurità</option>
                                        <option value="Metal">Metallo</option>
                                        <option value="Fairy">Folletto</option>
                                        <option value="Dragon">Drago</option>
                                        <option value="Colorless">Incolore</option>
                                    </select>
                                </div>
                                
                                <div class="form-group-custom">
                                    <label>Stadio Evolutivo</label>
                                    <input v-model="editForm.evolution_stage" type="text" placeholder="es. Base, Fase 1">
                                </div>

                                <div class="d-flex gap-2">
                                    <div class="form-group-custom w-50">
                                        <label>Debolezza</label>
                                        <input v-model="editForm.weakness" type="text">
                                    </div>
                                    <div class="form-group-custom w-50">
                                        <label>Resistenza</label>
                                        <input v-model="editForm.resistance" type="text">
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label>Costo Ritirata</label>
                                    <input v-model="editForm.retreat_cost" type="text">
                                </div>

                                <div class="form-group-custom">
                                    <label>Set</label>
                                    <select v-model="editForm.card_set_id">
                                        <option value="">Seleziona Set...</option>
                                        <option v-for="set in cardSets" :key="set.id" :value="set.id">
                                            {{ set.name }} ({{ set.abbreviation || set.series }})
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="form-group-custom">
                                    <label>Game *</label>
                                    <select 
                                        v-model="editForm.game" 
                                        required
                                        :class="{ 'border-error': validationErrors.game }"
                                    >
                                        <option value="">Seleziona Game...</option>
                                        <option v-for="game in availableGames" :key="game" :value="game">
                                            {{ game }}
                                        </option>
                                    </select>
                                    <span v-if="validationErrors.game" class="error-message">
                                        {{ validationErrors.game }}
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                     <div class="form-group-custom w-50">
                                        <label>Numero Set</label>
                                        <input v-model="editForm.set_number" type="text">
                                    </div>
                                    <div class="form-group-custom w-50">
                                        <label>Rarità</label>
                                        <select v-model="editForm.rarity">
                                            <option value="">Seleziona...</option>
                                            <option value="Common">Comune</option>
                                            <option value="Uncommon">Non Comune</option>
                                            <option value="Rare">Rara</option>
                                            <option value="Rare Holo">Rara Holo</option>
                                            <option value="Ultra Rare">Ultra Rara</option>
                                            <option value="Secret Rare">Segreta</option>
                                            <option value="Promo">Promo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label>Illustratore</label>
                                    <input v-model="editForm.illustrator" type="text">
                                </div>

                                <div class="form-group-custom">
                                    <label>Testo del Gusto (Flavor)</label>
                                    <textarea v-model="editForm.flavor_text" rows="2" style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); padding: 8px 12px; color: white; border-radius: 8px;"></textarea>
                                </div>
                             </div>
                              <div class="mt-3 text-end">
                                <button class="btn btn-secondary me-2" @click="showEditModal = false">Annulla</button>
                                <button class="btn btn-pokemon" @click="saveEdit">Salva</button>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
            
            <!-- Fullscreen Viewer -->
            <div v-if="showFullscreen" class="fullscreen-viewer" @click="showFullscreen = false">
                <img :src="fullscreenImageSrc" class="fullscreen-image">
            </div>

            <!-- Toast Container -->
            <div class="toast-container-custom">
                <div 
                    v-for="toast in toasts" 
                    :key="toast.id" 
                    class="toast-custom" 
                    :class="toast.type"
                >
                    <div class="d-flex align-items-center justify-content-between">
                         <div class="d-flex align-items-center gap-2">
                            <i class="bi" :class="{
                                'bi-check-circle-fill text-success': toast.type === 'success',
                                'bi-exclamation-triangle-fill text-warning': toast.type === 'warning',
                                'bi-exclamation-circle-fill text-danger': toast.type === 'error',
                                'bi-info-circle-fill text-info': toast.type === 'info'
                            }"></i>
                            <span>{{ toast.message }}</span>
                         </div>
                         <button type="button" class="btn-close btn-close-white ms-3" @click="removeToast(toast.id)"></button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Confirm Modal -->
        <ConfirmModal />
    </AppLayout>
</template>

<style scoped>
/* Ported CSS */
.h-custom-padding {
    padding-top: 2rem; /* Adjusted because main-container handles top padding */
}

.upload-zone {
    border: 3px dashed rgba(255, 203, 5, 0.4);
    border-radius: 20px;
    padding: 60px 20px;
    text-align: center;
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-zone:hover, .upload-zone.drag-over {
    border-color: #FFCB05;
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
    position: relative;
}

.card-image-wrapper {
    position: relative;
    cursor: pointer;
    overflow: hidden;
    border-radius: 10px;
    margin-bottom: 10px;
}

.card-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.zoom-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.3s ease;
}

.card-image-wrapper:hover .zoom-overlay { opacity: 1; }
.zoom-overlay i { color: white; font-size: 2rem; }

/* Tabs */
.tabs-nav {
    display: flex; gap: 15px; margin-bottom: 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tab-item {
    padding: 12px 20px; color: rgba(255, 255, 255, 0.6);
    cursor: pointer; border-bottom: 3px solid transparent;
    font-weight: 500;
}

.tab-item:hover { color: #fff; }
.tab-item.active { color: #FFCB05; border-bottom-color: #FFCB05; }

.tab-badge {
    background: rgba(255, 255, 255, 0.1);
    padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 8px;
}
.active-badge { background: #FFCB05; color: #000; }

/* Checkbox */
.card-checkbox {
    position: absolute; top: 10px; left: 10px; z-index: 10;
    width: 24px; height: 24px;
    background: rgba(0, 0, 0, 0.6);
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-radius: 6px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.card-checkbox.checked { background: #FFCB05; border-color: #FFCB05; }
.card-checkbox i { display: none; color: #000; }
.card-checkbox.checked i { display: block; }

/* FAB */
.floating-action-bar {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(150px);
    background: #1e233c; border: 1px solid rgba(255, 203, 5, 0.3);
    padding: 15px 30px; border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    display: flex; align-items: center; gap: 20px; z-index: 1000;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.floating-action-bar.visible { transform: translateX(-50%) translateY(0); }
.fab-count {
    background: #FFCB05; color: #000; width: 30px; height: 30px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;
}

/* Modals Overlay */
.custom-modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.9); z-index: 2000;
    display: flex; align-items: center; justify-content: center;
}

.cropper-container-wrapper {
    max-width: 800px; width: 100%; padding: 20px;
}

.card-edit-container {
    background: #1e233c; width: 90%; max-width: 800px;
    border-radius: 20px; overflow: hidden;
}

.modal-header-custom {
    padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex; justify-content: space-between; align-items: center;
}
.modal-close-btn { background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; }

/* Buttons */
.btn-pokemon {
    background: linear-gradient(135deg, #FFCB05 0%, #f39c12 100%);
    border: none; color: #000; font-weight: 600;
    border-radius: 50px; padding: 0.375rem 1rem;
}
.btn-danger-pokemon {
    background: linear-gradient(135deg, #CC0000 0%, #ff4444 100%);
    border: none; color: #fff;
}

/* Fullscreen */
.fullscreen-viewer {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.95); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
.fullscreen-image { max-width: 90vw; max-height: 90vh; }

.form-group-custom { margin-bottom: 1rem; }
.form-group-custom label { display: block; color: rgba(255,255,255,0.7); margin-bottom: 5px; }
.form-group-custom input, .form-group-custom select {
    width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2);
    padding: 8px 12px; color: white; border-radius: 8px;
}

/* Toast Styles */
.toast-container-custom {
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toast-custom {
    background: rgba(30, 35, 60, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 12px 16px;
    color: white;
    min-width: 300px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Validation Error Styles */
.border-error {
    border-color: #ef4444 !important;
    border-width: 2px !important;
}

.error-message {
    display: block;
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
