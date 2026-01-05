<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    cardsBySet: Object,
    cardsWithoutSet: Array
});

const selectedCards = ref(new Set());
const cardSets = ref([]);
const currentCardData = ref(null);
const showCardModal = ref(false);
const showBulkSetModal = ref(false);
const showFullscreen = ref(false);
const fullscreenImageSrc = ref('');
const isEditMode = ref(false);
const isLoadingCard = ref(false);

const editForm = ref({
    card_name: '',
    hp: '',
    type: '',
    evolution_stage: '',
    weakness: '',
    resistance: '',
    retreat_cost: '',
    card_set_id: '',
    set_number: '',
    rarity: '',
    illustrator: ''
});

const bulkSetId = ref('');

const selectedCount = computed(() => selectedCards.value.size);
const hasSelectedCards = computed(() => selectedCards.value.size > 0);

onMounted(async () => {
    await loadCardSets();
});

const loadCardSets = async () => {
    try {
        const response = await axios.get('/cards/api/card-sets');
        if (response.data.success) {
            cardSets.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading card sets:', error);
    }
};

const toggleSet = (event) => {
    const header = event.currentTarget;
    const setCards = header.nextElementSibling;
    setCards.classList.toggle('collapsed');
    header.classList.toggle('collapsed');
};

const toggleCardSelection = (cardId, checked) => {
    if (checked) {
        selectedCards.value.add(cardId);
    } else {
        selectedCards.value.delete(cardId);
    }
};

const clearSelection = () => {
    selectedCards.value.clear();
};

const openFullscreenCard = (src) => {
    fullscreenImageSrc.value = src;
    showFullscreen.value = true;
};

const closeFullscreenCard = () => {
    showFullscreen.value = false;
    fullscreenImageSrc.value = '';
};

const viewEditCard = async (cardId, edit = false) => {
    showCardModal.value = true;
    isLoadingCard.value = true;
    isEditMode.value = edit;

    try {
        const response = await axios.get(`/cards/${cardId}/data`);
        if (response.data.success) {
            currentCardData.value = response.data.card;
            currentCardData.value.id = cardId;

            if (edit) {
                editForm.value = {
                    card_name: currentCardData.value.card_name || '',
                    hp: currentCardData.value.hp || '',
                    type: currentCardData.value.type || '',
                    evolution_stage: currentCardData.value.evolution_stage || '',
                    weakness: currentCardData.value.weakness || '',
                    resistance: currentCardData.value.resistance || '',
                    retreat_cost: currentCardData.value.retreat_cost || '',
                    card_set_id: currentCardData.value.card_set_id || '',
                    set_number: currentCardData.value.set_number || '',
                    rarity: currentCardData.value.rarity || '',
                    illustrator: currentCardData.value.illustrator || ''
                };
            }
        }
    } catch (error) {
        console.error('Error loading card:', error);
    } finally {
        isLoadingCard.value = false;
    }
};

const closeCardModal = () => {
    showCardModal.value = false;
    isEditMode.value = false;
    currentCardData.value = null;
};

const toggleEditMode = () => {
    if (currentCardData.value) {
        viewEditCard(currentCardData.value.id, true);
    }
};

const saveCardChanges = async () => {
    try {
        const response = await axios.put(`/cards/${currentCardData.value.id}/update`, editForm.value);
        if (response.data.success) {
            alert('Carta aggiornata con successo!');
            closeCardModal();
            router.reload();
        }
    } catch (error) {
        console.error('Error saving card:', error);
        alert('Errore durante il salvataggio');
    }
};

const deleteCard = async (cardId) => {
    if (!confirm('Sei sicuro di voler eliminare questa carta?')) return;

    try {
        const response = await axios.delete(`/cards/${cardId}`);
        if (response.data.success) {
            alert('Carta eliminata con successo!');
            router.reload();
        }
    } catch (error) {
        console.error('Error deleting card:', error);
        alert('Errore durante l\'eliminazione');
    }
};

const openBulkSetModal = () => {
    showBulkSetModal.value = true;
};

const closeBulkSetModal = () => {
    showBulkSetModal.value = false;
    bulkSetId.value = '';
};

const saveBulkSet = async () => {
    const cardIds = Array.from(selectedCards.value);
    if (cardIds.length === 0) {
        alert('Nessuna carta selezionata');
        return;
    }

    try {
        const response = await axios.post('/cards/assign-set', {
            card_ids: cardIds,
            card_set_id: bulkSetId.value || null
        });

        if (response.data.success) {
            alert(`Set assegnato a ${cardIds.length} carte!`);
            closeBulkSetModal();
            clearSelection();
            router.reload();
        }
    } catch (error) {
        console.error('Error assigning set:', error);
        alert('Errore durante l\'assegnazione');
    }
};
</script>

<template>
    <AppLayout>
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="page-title">La Mia Collezione</h1>
                <p class="page-subtitle">Tutte le tue carte Pokemon organizzate per set</p>
            </div>

            <div v-if="Object.keys(cardsBySet).length > 0 || cardsWithoutSet.length > 0">
                <!-- Cards organized by set -->
                <div v-for="(cards, setName) in cardsBySet" :key="setName" class="set-section">
                    <div class="set-header" @click="toggleSet">
                        <h3>
                            <i class="bi bi-collection"></i> {{ setName }}
                        </h3>
                        <div class="d-flex align-items-center gap-3">
                            <span class="set-badge">{{ cards.length }} {{ cards.length == 1 ? 'carta' : 'carte' }}</span>
                            <i class="bi bi-chevron-down collapse-icon"></i>
                        </div>
                    </div>
                    <div class="set-cards">
                        <div class="row g-3">
                            <div v-for="card in cards" :key="card.id" class="col-6 col-sm-4 col-md-3 col-lg-2 card-small">
                                <div class="glass-card" :data-card-id="card.id" :class="{ selected: selectedCards.has(card.id) }">
                                    <div class="card-selector">
                                        <input type="checkbox" @change="e => toggleCardSelection(card.id, e.target.checked)">
                                    </div>
                                    <img :src="card.image_url" :alt="card.card_name || 'Pokemon Card'" class="card-image" @click="openFullscreenCard(card.image_url)">
                                    <div class="card-name" :title="card.card_name || 'Sconosciuta'">
                                        {{ card.card_name || 'Sconosciuta' }}
                                    </div>
                                    <div v-if="card.hp || card.type" class="card-meta">
                                        {{ card.hp ? 'HP ' + card.hp : '' }}
                                        {{ card.hp && card.type ? ' • ' : '' }}
                                        {{ card.type || '' }}
                                    </div>
                                    <div class="card-actions">
                                        <button class="btn btn-sm btn-info" @click="viewEditCard(card.id, false)" title="Visualizza">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" @click="viewEditCard(card.id, true)" title="Modifica">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" @click="deleteCard(card.id)" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards without set -->
                <div v-if="cardsWithoutSet.length > 0" class="noset-section">
                    <div class="set-header" @click="toggleSet" style="background: transparent; border: none;">
                        <h3>
                            <i class="bi bi-question-circle"></i> Senza Set
                        </h3>
                        <div class="d-flex align-items-center gap-3">
                            <span class="set-badge" style="background: #dc3545;">{{ cardsWithoutSet.length }}</span>
                            <i class="bi bi-chevron-down collapse-icon"></i>
                        </div>
                    </div>
                    <div class="set-cards">
                        <div class="row g-3">
                            <div v-for="card in cardsWithoutSet" :key="card.id" class="col-6 col-sm-4 col-md-3 col-lg-2 card-small">
                                <div class="glass-card" :data-card-id="card.id" :class="{ selected: selectedCards.has(card.id) }">
                                    <div class="card-selector">
                                        <input type="checkbox" @change="e => toggleCardSelection(card.id, e.target.checked)">
                                    </div>
                                    <img :src="card.image_url" :alt="card.card_name || 'Pokemon Card'" class="card-image" @click="openFullscreenCard(card.image_url)">
                                    <div class="card-name" :title="card.card_name || 'Sconosciuta'">
                                        {{ card.card_name || 'Sconosciuta' }}
                                    </div>
                                    <div v-if="card.hp || card.type" class="card-meta">
                                        {{ card.hp ? 'HP ' + card.hp : '' }}
                                        {{ card.hp && card.type ? ' • ' : '' }}
                                        {{ card.type || '' }}
                                    </div>
                                    <div class="card-actions">
                                        <button class="btn btn-sm btn-info" @click="viewEditCard(card.id, false)" title="Visualizza">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" @click="viewEditCard(card.id, true)" title="Modifica">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" @click="deleteCard(card.id)" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="glass-card p-5 text-center">
                <i class="bi bi-inbox" style="font-size: 64px; color: rgba(255, 255, 255, 0.3);"></i>
                <h3 class="mt-3">Nessuna Carta Trovata</h3>
                <p class="text-white-50">Inizia a scansionare le tue carte!</p>
                <a href="/cards/upload" class="btn btn-pokemon mt-3">
                    <i class="bi bi-camera-fill"></i> Carica Carte
                </a>
            </div>
        </div>

        <!-- Floating Action Bar -->
        <div class="floating-bar" :class="{ active: hasSelectedCards }">
            <span class="count"><span>{{ selectedCount }}</span> carte selezionate</span>
            <button class="btn btn-warning btn-sm" @click="openBulkSetModal">
                <i class="bi bi-collection"></i> Assegna Set
            </button>
            <button class="btn btn-outline-light btn-sm" @click="clearSelection">
                <i class="bi bi-x-lg"></i> Annulla
            </button>
        </div>

        <!-- Fullscreen Viewer -->
        <div v-if="showFullscreen" class="fullscreen-viewer" @click="closeFullscreenCard">
            <img :src="fullscreenImageSrc" class="fullscreen-image" alt="Card">
        </div>

        <!-- Bulk Set Modal -->
        <div v-if="showBulkSetModal" class="modal-overlay" @click.self="closeBulkSetModal">
            <div class="modal-container" style="max-width: 500px;">
                <div class="modal-header-custom">
                    <h3>Assegna Set a Carte Selezionate</h3>
                    <button type="button" class="btn-close-modal" @click="closeBulkSetModal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p class="text-white-50 mb-3">Seleziona il set da assegnare a <strong>{{ selectedCount }}</strong> carte:</p>
                    <select class="form-select bg-dark text-white border-secondary" v-model="bulkSetId">
                        <option value="">Nessun Set (rimuovi)</option>
                        <option v-for="set in cardSets" :key="set.id" :value="set.id">
                            {{ set.name }} ({{ set.abbreviation }})
                        </option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeBulkSetModal">Annulla</button>
                    <button type="button" class="btn btn-success" @click="saveBulkSet">
                        <i class="bi bi-check-lg"></i> Assegna Set
                    </button>
                </div>
            </div>
        </div>

        <!-- View/Edit Card Modal -->
        <div v-if="showCardModal" class="modal-overlay" @click.self="closeCardModal">
            <div class="modal-container" style="max-width: 1100px; width: 95%;">
                <div v-if="isLoadingCard" class="loader-overlay active">
                    <div class="loader-card"></div>
                    <div class="loader-text">Caricamento carta...</div>
                </div>
                <div class="modal-header-custom">
                    <h3>{{ isEditMode ? 'Modifica Carta' : (currentCardData?.card_name || 'Dettagli Carta') }}</h3>
                    <button type="button" class="btn-close-modal" @click="closeCardModal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body modal-body-grid">
                    <div style="flex: 0 0 300px;">
                        <img :src="currentCardData?.image_url" alt="Card" style="width: 100%; border-radius: 10px; cursor: zoom-in;" @click="openFullscreenCard(currentCardData.image_url)">
                    </div>
                    <div style="flex: 1; overflow-y: auto; max-height: 500px;">
                        <!-- View Mode -->
                        <div v-if="!isEditMode && currentCardData">
                            <table class="table table-dark">
                                <tbody>
                                    <tr><td>Nome</td><td>{{ currentCardData.card_name || 'N/A' }}</td></tr>
                                    <tr><td>HP</td><td>{{ currentCardData.hp || 'N/A' }}</td></tr>
                                    <tr><td>Tipo</td><td>{{ currentCardData.type || 'N/A' }}</td></tr>
                                    <tr><td>Stadio Evoluzione</td><td>{{ currentCardData.evolution_stage || 'N/A' }}</td></tr>
                                    <tr><td>Debolezza</td><td>{{ currentCardData.weakness || 'N/A' }}</td></tr>
                                    <tr><td>Resistenza</td><td>{{ currentCardData.resistance || 'N/A' }}</td></tr>
                                    <tr><td>Costo Ritirata</td><td>{{ currentCardData.retreat_cost || 'N/A' }}</td></tr>
                                    <tr><td>Set</td><td>{{ currentCardData.card_set?.name || 'Nessun Set' }}</td></tr>
                                    <tr><td>Numero Set</td><td>{{ currentCardData.set_number || 'N/A' }}</td></tr>
                                    <tr><td>Rarità</td><td>{{ currentCardData.rarity || 'N/A' }}</td></tr>
                                    <tr><td>Illustratore</td><td>{{ currentCardData.illustrator || 'N/A' }}</td></tr>
                                    <tr v-if="currentCardData.estimated_value"><td><strong>💰 Valore Stimato</strong></td><td><strong style="color: #22c55e">{{ currentCardData.estimated_value }}</strong></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Edit Mode -->
                        <div v-else-if="isEditMode" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label text-warning">Nome Carta</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.card_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-warning">HP</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.hp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-warning">Tipo</label>
                                <select class="form-select bg-dark text-white border-secondary" v-model="editForm.type">
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
                                <select class="form-select bg-dark text-white border-secondary" v-model="editForm.evolution_stage">
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
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.weakness">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-warning">Resistenza</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.resistance">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-warning">Costo Ritirata</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.retreat_cost">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-warning">Set</label>
                                <select class="form-select bg-dark text-white border-secondary" v-model="editForm.card_set_id">
                                    <option value="">Nessun Set</option>
                                    <option v-for="set in cardSets" :key="set.id" :value="set.id">
                                        {{ set.name }} ({{ set.abbreviation }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-warning">Numero Set</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.set_number" placeholder="es. 002/094">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-warning">Rarità</label>
                                <select class="form-select bg-dark text-white border-secondary" v-model="editForm.rarity">
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
                                <input type="text" class="form-control bg-dark text-white border-secondary" v-model="editForm.illustrator">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeCardModal">Chiudi</button>
                    <button v-if="!isEditMode" type="button" class="btn btn-warning" @click="toggleEditMode">
                        <i class="bi bi-pencil"></i> Modifica
                    </button>
                    <button v-if="isEditMode" type="button" class="btn btn-success" @click="saveCardChanges">
                        <i class="bi bi-check-lg"></i> Salva
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: #FFCB05;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.6);
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
    border-color: #FFCB05;
    box-shadow: 0 4px 15px rgba(255, 203, 5, 0.2);
}

.set-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #FFCB05;
}

.set-badge {
    background: #FFCB05;
    color: #000;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.875rem;
}

.set-cards {
    max-height: 3000px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.set-cards.collapsed {
    max-height: 0;
}

.collapse-icon {
    transition: transform 0.3s ease;
}

.collapsed .collapse-icon {
    transform: rotate(-90deg);
}

.card-small {
    transition: transform 0.2s ease;
}

.card-small:hover {
    transform: translateY(-5px);
}

.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 10px;
    position: relative;
}

.glass-card.selected {
    border: 2px solid #FFCB05;
    box-shadow: 0 0 15px rgba(255, 203, 5, 0.5);
}

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
    accent-color: #FFCB05;
}

.card-image {
    aspect-ratio: 0.70;
    object-fit: cover;
    border-radius: 8px;
    width: 100%;
    cursor: zoom-in;
}

.card-name {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 8px 0 4px;
    color: #FFCB05;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-meta {
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

.floating-bar {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #1e233c 0%, #2a3050 100%);
    border: 1px solid #FFCB05;
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
    color: #FFCB05;
    font-weight: bold;
}

.fullscreen-viewer {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.92);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
}

.fullscreen-image {
    max-height: 90vh;
    max-width: 90vw;
    border-radius: 20px;
    box-shadow: 0 0 60px rgba(0, 0, 0, 0.8);
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9000;
    display: flex;
    align-items: center;
    justify-content: center;
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
    color: #FFCB05;
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

.modal-body {
    padding: 20px;
}

.modal-body-grid {
    display: flex;
    gap: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.loader-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(30, 35, 60, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    z-index: 100;
    border-radius: 12px;
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

@keyframes cardScan {
    0%, 100% {
        transform: scale(1) rotate(0deg);
    }
    50% {
        transform: scale(1.05) rotate(2deg);
    }
}

.loader-text {
    margin-top: 15px;
    color: #FFCB05;
    font-weight: 500;
}

.btn-pokemon {
    background: linear-gradient(135deg, #FFCB05 0%, #f39c12 100%);
    border: none;
    color: #000;
    font-weight: 600;
    padding: 10px 25px;
    border-radius: 50px;
    transition: transform 0.3s ease;
}

.btn-pokemon:hover {
    transform: scale(1.05);
}
</style>
