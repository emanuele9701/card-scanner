<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useModal } from '@/composables/useModal';
import axios from 'axios';

const props = defineProps({
    cards: Object,
    availableGames: Array,
    availableSets: Array,
    availableVariants: Array,
    filters: Object
});

const { showConfirm, showAlert } = useModal();

const selectedCards = ref(new Set());
const cardSets = ref([]);
const currentCardData = ref(null);
const showCardModal = ref(false);
const showBulkSetModal = ref(false);
const showFullscreen = ref(false);
const fullscreenImageSrc = ref('');
const isEditMode = ref(false);
const isLoadingCard = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const isAssigningSet = ref(false);

// Inventory management
const showInventoryForm = ref(false);
const inventoryOptions = ref({ rarity_variants: [], conditions: [] });
const isSavingInventory = ref(false);
const editingInventoryId = ref(null);
const inventoryForm = ref({
    quantity: 1,
    rarity_variant: 'Standard',
    condition: 'Near Mint',
    notes: ''
});

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

// Filters - initialized from server
const searchQuery = ref(props.filters?.search || '');
const selectedGame = ref(props.filters?.game || '');
const selectedSet = ref(props.filters?.set || '');
const showCardsWithoutSet = ref(props.filters?.without_set || false);
const showCardsWithoutRarity = ref(props.filters?.without_rarity || false);
const showOnlyDuplicates = ref(props.filters?.only_duplicates || false);
const selectedVariant = ref(props.filters?.rarity_variant || '');
const perPage = ref(25); // Cards per page

// Sorting - initialized from server
const sortColumn = ref(props.filters?.sort_column || '');
const sortDirection = ref(props.filters?.sort_direction || 'asc');

// Multi-selection with shift
const lastClickedIndex = ref(null);

// Debounce timer for search
let searchDebounce = null;

// Watch filters and reload data
const reloadCards = () => {
    router.get('/cards', {
        search: searchQuery.value,
        game: selectedGame.value,
        set: selectedSet.value,
        without_set: showCardsWithoutSet.value ? 1 : 0,
        without_rarity: showCardsWithoutRarity.value ? 1 : 0,
        only_duplicates: showOnlyDuplicates.value ? 1 : 0,
        rarity_variant: selectedVariant.value,
        sort_column: sortColumn.value,
        sort_direction: sortDirection.value,
        per_page: perPage.value,
        page: props.cards.current_page
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['cards']
    });
};

// Watch for filter changes with debounce on search
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        reloadCards();
    }, 500);
});

watch([selectedGame, selectedSet, showCardsWithoutSet, showCardsWithoutRarity, showOnlyDuplicates, selectedVariant], () => {
    reloadCards();
});

watch(perPage, () => {
    reloadCards();
});

watch([sortColumn, sortDirection], () => {
    reloadCards();
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

const toggleCardSelection = (cardId, checked, event, index) => {
    if (event?.shiftKey && lastClickedIndex.value !== null && checked) {
        // Shift-click range selection
        const start = Math.min(lastClickedIndex.value, index);
        const end = Math.max(lastClickedIndex.value, index);
        
        for (let i = start; i <= end; i++) {
            if (props.cards.data[i]) {
                selectedCards.value.add(props.cards.data[i].id);
            }
        }
    } else {
        if (checked) {
            selectedCards.value.add(cardId);
        } else {
            selectedCards.value.delete(cardId);
        }
    }
    
    if (checked) {
        lastClickedIndex.value = index;
    }
};

const clearSelection = () => {
    selectedCards.value.clear();
    lastClickedIndex.value = null;
};

const sortBy = (column) => {
    if (sortColumn.value === column) {
        // Toggle direction if same column
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, default to ascending
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const goToPage = (page) => {
    router.get('/cards', {
        search: searchQuery.value,
        game: selectedGame.value,
        set: selectedSet.value,
        without_set: showCardsWithoutSet.value ? 1 : 0,
        without_rarity: showCardsWithoutRarity.value ? 1 : 0,
        only_duplicates: showOnlyDuplicates.value ? 1 : 0,
        rarity_variant: selectedVariant.value,
        sort_column: sortColumn.value,
        sort_direction: sortDirection.value,
        per_page: perPage.value,
        page: page
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['cards']
    });
};

const generatePageNumbers = () => {
    const pages = [];
    const current = props.cards.current_page;
    const last = props.cards.last_page;
    
    if (last <= 7) {
        // Show all pages if there are 7 or fewer
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);
        
        // Show ellipsis or pages around current
        if (current > 3) {
            pages.push('...');
        }
        
        // Show pages around current page
        for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
            if (!pages.includes(i)) {
                pages.push(i);
            }
        }
        
        // Show ellipsis or pages before last
        if (current < last - 2) {
            pages.push('...');
        }
        
        // Always show last page
        if (!pages.includes(last)) {
            pages.push(last);
        }
    }
    
    return pages;
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
    if (isSaving.value) return;
    isSaving.value = true;

    try {
        const response = await axios.put(`/cards/${currentCardData.value.id}/update`, editForm.value);
        if (response.data.success) {
            await showAlert('Carta aggiornata con successo!', 'success');
            closeCardModal();
            router.reload();
        }
    } catch (error) {
        console.error('Error saving card:', error);
        await showAlert('Errore durante il salvataggio', 'error');
    } finally {
        isSaving.value = false;
    }
};

const deleteCard = async (cardId) => {
    const confirmed = await showConfirm(
        'Sei sicuro di voler eliminare questa carta?',
        'Conferma Eliminazione',
        { confirmText: 'Elimina', cancelText: 'Annulla' }
    );
    
    if (!confirmed) return;
    if (isDeleting.value) return;
    isDeleting.value = true;

    try {
        const response = await axios.delete(`/cards/${cardId}`);
        if (response.data.success) {
            await showAlert('Carta eliminata con successo!', 'success');
            router.reload();
        }
    } catch (error) {
        console.error('Error deleting card:', error);
        await showAlert('Errore durante l\'eliminazione', 'error');
    } finally {
        isDeleting.value = false;
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
        await showAlert('Nessuna carta selezionata', 'warning');
        return;
    }

    if (isAssigningSet.value) return;
    isAssigningSet.value = true;

    try {
        const response = await axios.post('/cards/assign-set', {
            card_ids: cardIds,
            card_set_id: bulkSetId.value || null
        });

        if (response.data.success) {
            await showAlert(`Set assegnato a ${cardIds.length} carte!`, 'success');
            closeBulkSetModal();
            clearSelection();
            router.reload();
        }
    } catch (error) {
        console.error('Error assigning set:', error);
        await showAlert('Errore durante l\'assegnazione', 'error');
    } finally {
        isAssigningSet.value = false;
    }
};

// Rarity Management
const showBulkRarityModal = ref(false);
const bulkRarity = ref('');
const isAssigningRarity = ref(false);

const updateCardRarity = async (cardId, rarity) => {
    try {
        const response = await axios.put(`/cards/${cardId}/update`, { rarity });
        if (response.data.success) {
            // Update local data
            const card = props.cards.data.find(c => c.id === cardId);
            if (card) card.rarity = rarity;
        }
    } catch (error) {
        console.error('Error updating rarity:', error);
        await showAlert('Errore durante l\'aggiornamento', 'error');
    }
};

const openBulkRarityModal = () => {
    showBulkRarityModal.value = true;
};

const closeBulkRarityModal = () => {
    showBulkRarityModal.value = false;
    bulkRarity.value = '';
};

const saveBulkRarity = async () => {
    const cardIds = Array.from(selectedCards.value);
    if (cardIds.length === 0) {
        await showAlert('Nessuna carta selezionata', 'warning');
        return;
    }

    if (isAssigningRarity.value) return;
    isAssigningRarity.value = true;

    try {
        // Update each card
        await Promise.all(cardIds.map(id => 
            axios.put(`/cards/${id}/update`, { rarity: bulkRarity.value || null })
        ));
        
        await showAlert(`Rarità aggiornata per ${cardIds.length} carte!`, 'success');
        closeBulkRarityModal();
        clearSelection();
        router.reload();
    } catch (error) {
        console.error('Error assigning rarity:', error);
        await showAlert('Errore durante l\'aggiornamento', 'error');
    } finally {
        isAssigningRarity.value = false;
    }
};

const bulkDelete = async () => {
    const cardIds = Array.from(selectedCards.value);
    if (cardIds.length === 0) {
        await showAlert('Nessuna carta selezionata', 'warning');
        return;
    }

    const confirmed = await showConfirm(
        `Sei sicuro di voler eliminare ${cardIds.length} carte?`,
        'Conferma Eliminazione Multipla',
        { confirmText: 'Elimina', cancelText: 'Annulla' }
    );
    
    if (!confirmed) return;
    if (isDeleting.value) return;
    isDeleting.value = true;

    try {
        const response = await axios.post('/cards/bulk-delete', {
            card_ids: cardIds
        });

        if (response.data.success) {
            await showAlert(response.data.message || 'Carte eliminate con successo!', 'success');
            clearSelection();
            router.reload();
        }
    } catch (error) {
        console.error('Error in bulk delete:', error);
        await showAlert('Errore durante l\'eliminazione multipla', 'error');
    } finally {
        isDeleting.value = false;
    }
};

// Inventory Management Functions
const loadInventoryOptions = async () => {
    try {
        const response = await axios.get('/cards/api/inventory-options');
        if (response.data.success) {
            inventoryOptions.value = response.data.data;
        }
    } catch (error) {
        console.error('Error loading inventory options:', error);
    }
};

const openInventoryForm = (inventoryItem = null) => {
    if (inventoryItem) {
        // Editing existing
        editingInventoryId.value = inventoryItem.id;
        inventoryForm.value = {
            quantity: inventoryItem.quantity,
            rarity_variant: inventoryItem.rarity_variant,
            condition: inventoryItem.condition,
            notes: inventoryItem.notes || ''
        };
    } else {
        // New item
        editingInventoryId.value = null;
        inventoryForm.value = {
            quantity: 1,
            rarity_variant: 'Standard',
            condition: 'Near Mint',
            notes: ''
        };
    }
    showInventoryForm.value = true;
};

const closeInventoryForm = () => {
    showInventoryForm.value = false;
    editingInventoryId.value = null;
    inventoryForm.value = {
        quantity: 1,
        rarity_variant: 'Standard',
        condition: 'Near Mint',
        notes: ''
    };
};

const saveInventory = async () => {
    if (isSavingInventory.value) return;
    isSavingInventory.value = true;

    try {
        let response;
        if (editingInventoryId.value) {
            // Update existing
            response = await axios.put(`/cards/inventory/${editingInventoryId.value}`, inventoryForm.value);
        } else {
            // Create new
            response = await axios.post(`/cards/${currentCardData.value.id}/inventory`, inventoryForm.value);
        }

        if (response.data.success) {
            await showAlert('Inventario aggiornato!', 'success');
            closeInventoryForm();
            // Refresh card data
            await viewEditCard(currentCardData.value.id, isEditMode.value);
            router.reload({ only: ['cards'] });
        }
    } catch (error) {
        console.error('Error saving inventory:', error);
        await showAlert('Errore durante il salvataggio', 'error');
    } finally {
        isSavingInventory.value = false;
    }
};

const deleteInventory = async (inventoryId) => {
    const confirmed = await showConfirm(
        'Sei sicuro di voler eliminare questo elemento?',
        'Conferma Eliminazione',
        { confirmText: 'Elimina', cancelText: 'Annulla' }
    );
    
    if (!confirmed) return;

    try {
        const response = await axios.delete(`/cards/inventory/${inventoryId}`);
        if (response.data.success) {
            await showAlert('Elemento eliminato!', 'success');
            // Refresh card data
            await viewEditCard(currentCardData.value.id, isEditMode.value);
            router.reload({ only: ['cards'] });
        }
    } catch (error) {
        console.error('Error deleting inventory:', error);
        await showAlert('Errore durante l\'eliminazione', 'error');
    }
};

// Load inventory options on mount
onMounted(async () => {
    await loadCardSets();
    await loadInventoryOptions();
});
</script>

<template>
    <AppLayout>
        <Head title="Card Scanner - Collezione" />
        <div class="collection-page">
            <!-- Hero Header -->
            <div class="collection-hero">
                <div class="hero-glow"></div>
                <h1 class="hero-title">
                    <i class="bi bi-collection-fill hero-icon"></i>
                    La Mia Collezione
                </h1>
                <p class="hero-subtitle">Gestisci e organizza le tue carte da gioco</p>
                <div class="hero-stats" v-if="cards.total">
                    <div class="stat-pill">
                        <i class="bi bi-layers-fill"></i>
                        <span>{{ cards.total }} carte totali</span>
                    </div>
                    <div class="stat-pill" v-if="cards.from && cards.to">
                        <i class="bi bi-eye-fill"></i>
                        <span>{{ cards.from }}–{{ cards.to }} visualizzate</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card">
                <div class="filters-grid">
                    <!-- Search -->
                    <div class="filter-group filter-search">
                        <label class="filter-label"><i class="bi bi-search me-1"></i>Cerca</label>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Nome o numero..."
                            class="filter-input"
                        />
                    </div>
                    
                    <!-- Game Filter -->
                    <div class="filter-group">
                        <label class="filter-label"><i class="bi bi-controller me-1"></i>Gioco</label>
                        <select v-model="selectedGame" class="filter-input">
                            <option value="">Tutti</option>
                            <option v-for="game in availableGames" :key="game" :value="game">{{ game }}</option>
                        </select>
                    </div>

                    <!-- Set Filter -->
                    <div class="filter-group">
                        <label class="filter-label"><i class="bi bi-folder me-1"></i>Set</label>
                        <select v-model="selectedSet" class="filter-input">
                            <option value="">Tutti</option>
                            <option v-for="set in availableSets" :key="set" :value="set">{{ set }}</option>
                        </select>
                    </div>
                    
                    <!-- Per Page Selector -->
                    <div class="filter-group filter-small">
                        <label class="filter-label"><i class="bi bi-grid-3x3 me-1"></i>Per pagina</label>
                        <select v-model.number="perPage" class="filter-input">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <!-- Variant Filter -->
                    <div class="filter-group">
                        <label class="filter-label"><i class="bi bi-diamond me-1"></i>Variante</label>
                        <select v-model="selectedVariant" class="filter-input">
                            <option value="">Tutte</option>
                            <option v-for="variant in availableVariants" :key="variant" :value="variant">{{ variant }}</option>
                        </select>
                    </div>
                </div>
                
                <!-- Toggle Filters -->
                <div class="toggle-filters">
                    <button 
                        class="toggle-pill" 
                        :class="{ active: showCardsWithoutSet }"
                        @click="showCardsWithoutSet = !showCardsWithoutSet"
                    >
                        <i class="bi bi-folder-x"></i> Senza set
                    </button>
                    <button 
                        class="toggle-pill" 
                        :class="{ active: showCardsWithoutRarity }"
                        @click="showCardsWithoutRarity = !showCardsWithoutRarity"
                    >
                        <i class="bi bi-star"></i> Senza rarità
                    </button>
                    <button 
                        class="toggle-pill" 
                        :class="{ active: showOnlyDuplicates }"
                        @click="showOnlyDuplicates = !showOnlyDuplicates"
                    >
                        <i class="bi bi-stack"></i> Solo doppie
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div v-if="selectedCards.size > 0" class="bulk-bar">
                <div class="bulk-bar-content">
                    <div class="bulk-bar-left">
                        <span class="bulk-count">{{ selectedCards.size }}</span>
                        <span class="bulk-label">carte selezionate</span>
                    </div>
                    <div class="bulk-bar-right">
                        <button class="bulk-btn bulk-btn-set" @click="openBulkSetModal">
                            <i class="bi bi-folder"></i> Set
                        </button>
                        <button class="bulk-btn bulk-btn-rarity" @click="openBulkRarityModal">
                            <i class="bi bi-star"></i> Rarità
                        </button>
                        <button class="bulk-btn bulk-btn-delete" @click="bulkDelete">
                            <i class="bi bi-trash"></i> Elimina
                        </button>
                        <button class="bulk-btn bulk-btn-clear" @click="clearSelection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Select All -->
            <div v-if="cards.data && cards.data.length > 0" class="select-all-bar">
                <label class="select-all-label">
                    <input type="checkbox" @change="e => { if(e.target.checked) cards.data.forEach(c => selectedCards.add(c.id)); else clearSelection(); }" :checked="cards.data.length > 0 && selectedCards.size === cards.data.length">
                    <span>Seleziona tutte</span>
                </label>
                <button class="sort-btn" @click="sortBy('set_number')">
                    <i class="bi bi-sort-numeric-down"></i> Numero
                    <i v-if="sortColumn === 'set_number'" class="bi" :class="sortDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down'" style="font-size: 0.7rem;"></i>
                </button>
            </div>

            <!-- Card Grid -->
            <div v-if="cards.data && cards.data.length > 0" class="cards-grid">
                <div 
                    v-for="(card, index) in cards.data" 
                    :key="card.id" 
                    class="card-tile"
                    :class="{ selected: selectedCards.has(card.id) }"
                >
                    <!-- Selection Checkbox -->
                    <div class="tile-checkbox">
                        <input type="checkbox" @change="e => toggleCardSelection(card.id, e.target.checked, e, index)" :checked="selectedCards.has(card.id)">
                    </div>

                    <!-- Quantity Badge -->
                    <div v-if="card.inventory_sum_quantity" class="tile-qty">
                        {{ card.inventory_sum_quantity }}
                    </div>

                    <!-- Card Image -->
                    <div class="tile-image-wrap" @click="openFullscreenCard(card.image_url)">
                        <img :src="card.image_url" :alt="card.card_name" class="tile-image">
                    </div>

                    <!-- Card Info -->
                    <div class="tile-info">
                        <div class="tile-name">{{ card.card_name || 'Sconosciuta' }}</div>
                        <div class="tile-meta" v-if="card.hp || card.type">
                            {{ card.hp ? 'HP ' + card.hp : '' }}{{ card.hp && card.type ? ' · ' : '' }}{{ card.type || '' }}
                        </div>
                        <div class="tile-set" v-if="card.card_set">
                            <span class="set-badge">{{ card.card_set.abbreviation }}</span>
                            <span class="set-number">{{ card.set_number || '' }}</span>
                        </div>
                        <div class="tile-set" v-else>
                            <span class="set-badge set-none">N/A</span>
                        </div>
                        <div class="tile-game" v-if="card.game">{{ card.game }}</div>
                    </div>

                    <!-- Hover Actions -->
                    <div class="tile-actions">
                        <button class="action-btn action-view" @click="viewEditCard(card.id, false)" title="Visualizza">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        <button class="action-btn action-edit" @click="viewEditCard(card.id, true)" title="Modifica">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="action-btn action-delete" @click="deleteCard(card.id)" title="Elimina">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="cards.data && cards.data.length > 0" class="pagination-bar">
                <div class="pagination-info">
                    Pagina {{ cards.current_page }} di {{ cards.last_page }}
                </div>
                <div class="pagination-buttons">
                    <button class="page-btn" :disabled="cards.current_page === 1" @click="goToPage(cards.current_page - 1)">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <template v-for="page in generatePageNumbers()" :key="page">
                        <span v-if="page === '...'" class="page-ellipsis">…</span>
                        <button v-else class="page-btn" :class="{ active: page === cards.current_page }" @click="goToPage(page)">
                            {{ page }}
                        </button>
                    </template>
                    <button class="page-btn" :disabled="cards.current_page === cards.last_page" @click="goToPage(cards.current_page + 1)">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!cards.data || cards.data.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-collection"></i>
                </div>
                <h3 class="empty-title">Nessuna Carta Trovata</h3>
                <p class="empty-text">Prova a modificare i filtri o carica nuove carte.</p>
                <a href="/cards/upload" class="empty-cta">
                    <i class="bi bi-camera-fill me-2"></i>Scansiona Carte
                </a>
            </div>

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

        <!-- Bulk Rarity Modal -->
        <div v-if="showBulkRarityModal" class="modal-overlay" @click.self="closeBulkRarityModal">
            <div class="modal-container" style="max-width: 500px;">
                <div class="modal-header-custom">
                    <h3>Assegna Rarità a Carte Selezionate</h3>
                    <button type="button" class="btn-close-modal" @click="closeBulkRarityModal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p class="text-white-50 mb-3">Seleziona la rarità da assegnare a <strong>{{ selectedCards.size }}</strong> carte:</p>
                    <select class="form-select bg-dark text-white border-secondary" v-model="bulkRarity">
                        <option value="">Nessuna Rarità (rimuovi)</option>
                        <option value="Comune">Comune</option>
                        <option value="Non Comune">Non Comune</option>
                        <option value="Rara">Rara</option>
                        <option value="Rara Holo">Rara Holo</option>
                        <option value="Ultra Rara">Ultra Rara</option>
                        <option value="Segreta">Segreta</option>
                        <option value="Promo">Promo</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeBulkRarityModal">Annulla</button>
                    <button type="button" class="btn btn-info text-white" @click="saveBulkRarity" :disabled="isAssigningRarity">
                        <span v-if="isAssigningRarity" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check-lg"></i> Assegna Rarità
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
                    <h3>
                        <span class="text-warning me-2" v-if="currentCardData?.set_number"><small class="text-white-50">#{{ currentCardData.set_number }}</small></span>
                        {{ isEditMode ? 'Modifica Carta' : (currentCardData?.card_name || 'Dettagli Carta') }}
                    </h3>
                    <button type="button" class="btn-close-modal" @click="closeCardModal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <div class="modal-body modal-body-grid">
                    <!-- Image Column -->
                    <div class="modal-image-col">
                        <div class="card-image-display" @click="openFullscreenCard(currentCardData.image_url)">
                            <img :src="currentCardData?.image_url" alt="Card" class="img-fluid rounded shadow-lg">
                            <div class="zoom-hint"><i class="bi bi-zoom-in"></i></div>
                        </div>
                        
                        <!-- Quick Actions under image (Mobile) -->
                        <div class="d-flex gap-2 justify-content-center mt-3 d-md-none">
                            <button v-if="!isEditMode" type="button" class="btn btn-warning flex-grow-1" @click="toggleEditMode">
                                <i class="bi bi-pencil"></i> Modifica
                            </button>
                        </div>
                    </div>

                    <!-- Info Column -->
                    <div class="modal-info-col custom-scrollbar">
                        <!-- View Mode -->
                        <div v-if="!isEditMode && currentCardData" class="fade-in">
                            
                            <!-- Header Stats -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h2 class="display-name mb-1">{{ currentCardData.card_name || 'Sconosciuta' }}</h2>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark border border-secondary text-light">{{ currentCardData.evolution_stage || 'Base' }}</span>
                                        <span v-if="currentCardData.card_set" class="badge bg-warning text-dark">{{ currentCardData.card_set.name }}</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="hp-badge" v-if="currentCardData.hp">
                                        <small>HP</small> {{ currentCardData.hp }}
                                    </div>
                                    <div class="type-icon mt-1" v-if="currentCardData.type">
                                        {{ currentCardData.type }}
                                    </div>
                                </div>
                            </div>

                            <!-- Info Grid -->
                            <div class="glass-panel mb-4">
                                <div class="row g-0">
                                    <div class="col-6 col-md-4 info-item">
                                        <label>Debolezza</label>
                                        <span>{{ currentCardData.weakness || '-' }}</span>
                                    </div>
                                    <div class="col-6 col-md-4 info-item">
                                        <label>Resistenza</label>
                                        <span>{{ currentCardData.resistance || '-' }}</span>
                                    </div>
                                    <div class="col-6 col-md-4 info-item">
                                        <label>Ritirata</label>
                                        <span>{{ currentCardData.retreat_cost || '-' }}</span>
                                    </div>
                                    <div class="col-6 col-md-4 info-item">
                                        <label>Rarità</label>
                                        <span>{{ currentCardData.rarity || '-' }}</span>
                                    </div>
                                    <div class="col-6 col-md-4 info-item">
                                        <label>Illustratore</label>
                                        <span class="text-truncate d-block">{{ currentCardData.illustrator || '-' }}</span>
                                    </div>
                                    <div class="col-6 col-md-4 info-item highlight">
                                        <label>Valore</label>
                                        <span class="text-success">{{ currentCardData.estimated_value || 'N/D' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Attacks -->
                            <div v-if="currentCardData.attacks && currentCardData.attacks.length" class="mb-4">
                                <h5 class="section-header"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Attacchi</h5>
                                <div class="attacks-list">
                                    <div v-for="(attack, idx) in currentCardData.attacks" :key="idx" class="attack-row">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="attack-cost">
                                                    <span v-if="Array.isArray(attack.cost)">{{ attack.cost.join('') }}</span>
                                                    <span v-else>{{ attack.cost }}</span>
                                                </div>
                                                <span class="attack-name">{{ attack.name }}</span>
                                            </div>
                                            <span class="attack-damage" v-if="attack.damage">{{ attack.damage }}</span>
                                        </div>
                                        <p class="attack-text" v-if="attack.text">{{ attack.text }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory Section -->
                            <div class="inventory-section mt-4">
                                <div class="glass-panel p-3 border-warning-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 text-warning fw-bold">
                                            <i class="bi bi-box-seam me-2"></i>
                                            Le Mie Copie 
                                            <span class="badge bg-warning text-dark ms-1">{{ currentCardData.total_quantity || 0 }}</span>
                                        </h5>
                                        <button class="btn btn-sm btn-outline-warning" @click="openInventoryForm()">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Inventory Form -->
                                    <div v-if="showInventoryForm" class="inventory-form-wrapper mb-3">
                                        <h6 class="form-title">{{ editingInventoryId ? 'Modifica Copia' : 'Nuova Copia' }}</h6>
                                        <div class="row g-2">
                                            <div class="col-3">
                                                <label>Quantità</label>
                                                <input type="number" min="1" class="form-control form-control-sm" v-model.number="inventoryForm.quantity">
                                            </div>
                                            <div class="col-5">
                                                <label>Variante</label>
                                                <select class="form-select form-select-sm" v-model="inventoryForm.rarity_variant">
                                                    <option v-for="variant in inventoryOptions.rarity_variants" :key="variant" :value="variant">{{ variant }}</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label>Condizione</label>
                                                <select class="form-select form-select-sm" v-model="inventoryForm.condition">
                                                    <option v-for="cond in inventoryOptions.conditions" :key="cond" :value="cond">{{ cond }}</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label>Note</label>
                                                <input type="text" class="form-control form-control-sm" v-model="inventoryForm.notes" placeholder="...">
                                            </div>
                                            <div class="col-12 d-flex gap-2 mt-2">
                                                <button class="btn btn-sm btn-success flex-grow-1" @click="saveInventory">Salva</button>
                                                <button class="btn btn-sm btn-secondary" @click="closeInventoryForm">Annulla</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Inventory List -->
                                    <div v-if="currentCardData.inventory && currentCardData.inventory.length > 0" class="inventory-list-mobile">
                                        <div v-for="item in currentCardData.inventory" :key="item.id" class="inventory-item-row">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-warning text-dark rounded-pill">{{ item.quantity }}x</span>
                                                <div>
                                                    <div class="small fw-bold">{{ item.rarity_variant }} <span class="text-white-50 ms-1 opacity-50">·</span> {{ item.condition }}</div>
                                                    <div class="smaller text-white-50" v-if="item.notes">{{ item.notes }}</div>
                                                </div>
                                            </div>
                                            <div class="btn-group">
                                                <button class="btn-icon-sm" @click="openInventoryForm(item)"><i class="bi bi-pencil"></i></button>
                                                <button class="btn-icon-sm text-danger" @click="deleteInventory(item.id)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-3">
                                        <small class="text-white-50">Nessuna copia in collezione</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode (Keep existing form but styled if needed) -->
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
                            <!-- ... other edit fields ... -->
                            <!-- Simplified for brevity, assume similar structure works or rely on existing grid -->
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
                                    <option value="Double Rare">Double Rare (ex/V/GX)</option>
                                    <option value="Rara Illustrazione">Rara Illustrazione (AR/IR)</option>
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
                    <button v-if="!isEditMode" type="button" class="btn btn-warning desktop-only" @click="toggleEditMode">
                        <i class="bi bi-pencil"></i> Modifica
                    </button>
                    <button 
                        v-if="isEditMode" 
                        type="button" 
                        class="btn btn-success" 
                        @click="saveCardChanges"
                        :disabled="isSaving"
                    >
                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check-lg"></i> Salva
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <ConfirmModal />
    </AppLayout>
</template>

<style scoped>
/* ===== Modal Redesign ===== */
.modal-body-grid {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.modal-image-col {
    flex: 0 0 320px;
}

.modal-info-col {
    flex: 1;
    min-width: 0;
}

.card-image-display {
    position: relative;
    cursor: zoom-in;
    transition: transform 0.2s;
}
.card-image-display:hover { transform: scale(1.02); }
.zoom-hint {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.2s;
    background: rgba(0,0,0,0.5);
    color: white;
    border-radius: 50%;
    width: 50px; height: 50px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}
.card-image-display:hover .zoom-hint { opacity: 1; }

/* Headers */
.display-name {
    font-weight: 800;
    color: #fff;
    font-size: 2rem;
    line-height: 1.1;
}

.hp-badge {
    color: #ff4444;
    font-weight: 800;
    font-size: 1.8rem;
    line-height: 1;
}
.hp-badge small {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
    vertical-align: middle;
}
.type-icon {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.7);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
}

/* Glass Panel & Info Grid */
.glass-panel {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
}
.border-warning-subtle { border-color: rgba(255, 203, 5, 0.2); }

.info-item {
    padding: 12px 15px;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.info-item label {
    display: block;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    margin-bottom: 2px;
}
.info-item span {
    font-weight: 600;
    color: #e0e0e0;
}
.info-item.highlight span { font-weight: 800; }

/* Attacks */
.section-header {
    font-size: 1rem;
    color: rgba(255,255,255,0.8);
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.attack-row {
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
    border-left: 3px solid #ffcb05;
}
.attack-name { font-weight: 700; font-size: 1.1rem; color: #fff; }
.attack-damage { font-weight: 800; font-size: 1.2rem; color: #fff; }
.attack-text { font-size: 0.85rem; color: rgba(255,255,255,0.6); margin: 5px 0 0; line-height: 1.4; }

/* Inventory */
.inventory-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: rgba(0,0,0,0.2);
    border-radius: 8px;
    margin-bottom: 8px;
}
.btn-icon-sm {
    background: none; border: none; color: rgba(255,255,255,0.5);
    padding: 4px; transition: color 0.2s;
}
.btn-icon-sm:hover { color: #fff; }

/* Existing layout styles (kept for context) */
.collection-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px 60px;
}

/* ===== Hero (Mobile Overrides Included) ===== */
.collection-hero {
    text-align: center;
    padding: 40px 0 30px;
    position: relative;
}
/* ... previous styles ... */

/* Mobile Responsiveness for Modal */
@media (max-width: 900px) {
    .modal-body-grid {
        flex-direction: column;
        gap: 20px;
    }
    .modal-image-col {
        flex: 0 0 auto;
        width: 100%;
        max-width: 250px;
        margin: 0 auto;
        text-align: center;
    }
    .display-name { font-size: 1.6rem; }
    .hp-badge { font-size: 1.5rem; }
    
    .info-item {
        font-size: 0.9rem;
        padding: 10px;
    }
    
    /* Improve mobile table look inside inventory form */
    .inventory-form-wrapper {
        background: rgba(0,0,0,0.3);
        padding: 15px;
        border-radius: 10px;
    }
}



/* ===== Hero ===== */
.collection-hero {
    text-align: center;
    padding: 40px 0 30px;
    position: relative;
}
.hero-glow {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 400px;
    height: 200px;
    background: radial-gradient(ellipse, rgba(255, 203, 5, 0.12) 0%, transparent 70%);
    pointer-events: none;
}
.hero-title {
    font-size: 2.4rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.5px;
    margin: 0 0 6px;
}
.hero-icon {
    color: #FFCB05;
    margin-right: 10px;
    font-size: 2rem;
}
.hero-subtitle {
    color: rgba(255, 255, 255, 0.45);
    font-size: 1rem;
    margin: 0 0 18px;
}
.hero-stats {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 203, 5, 0.1);
    border: 1px solid rgba(255, 203, 5, 0.2);
    color: #FFCB05;
    border-radius: 50px;
    padding: 6px 16px;
    font-size: 0.82rem;
    font-weight: 600;
}

/* ===== Filters ===== */
.filters-card {
    background: rgba(20, 24, 46, 0.7);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 16px;
    padding: 22px 24px 18px;
    margin-bottom: 24px;
}
.filters-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(4, 1fr);
    gap: 14px;
    align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 5px; }
.filter-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: rgba(255, 203, 5, 0.7);
}
.filter-input {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 9px 14px;
    color: #fff;
    font-size: 0.88rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    width: 100%;
}
.filter-input:focus {
    border-color: rgba(255, 203, 5, 0.5);
    box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.08);
}
.filter-input option { background: #1a1e36; }
.toggle-filters {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    flex-wrap: wrap;
}
.toggle-pill {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 50px;
    padding: 6px 16px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.25s;
    font-weight: 500;
}
.toggle-pill:hover {
    border-color: rgba(255, 203, 5, 0.4);
    color: rgba(255, 255, 255, 0.8);
}
.toggle-pill.active {
    background: rgba(255, 203, 5, 0.15);
    border-color: #FFCB05;
    color: #FFCB05;
}

/* ===== Bulk Actions Bar ===== */
.bulk-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(255, 203, 5, 0.95), rgba(245, 180, 0, 0.95));
    backdrop-filter: blur(12px);
    padding: 16px 24px;
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.4);
    animation: slideUp 0.3s ease-out;
}
@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.bulk-bar > * {
    flex-shrink: 0;
}
.bulk-bar-content {
    max-width: 1200px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}
.bulk-bar-left { display: flex; align-items: center; gap: 10px; }
.bulk-bar-right { display: flex; gap: 6px; flex-wrap: wrap; }
.bulk-count {
    background: rgba(0, 0, 0, 0.8);
    color: #FFCB05;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 4px 14px;
    border-radius: 50px;
}
.bulk-label { 
    color: rgba(0, 0, 0, 0.85); 
    font-size: 0.9rem; 
    font-weight: 600;
}
.bulk-btn {
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s, opacity 0.15s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.bulk-btn:hover { transform: scale(1.05); }
.bulk-btn-set { background: rgba(30, 35, 60, 0.95); color: #fff; }
.bulk-btn-rarity { background: rgba(13, 202, 240, 0.95); color: #000; }
.bulk-btn-delete { background: rgba(220, 53, 69, 0.95); color: #fff; }
.bulk-btn-clear { background: rgba(0, 0, 0, 0.7); color: #fff; }

/* ===== Select All Bar ===== */
.select-all-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    padding: 0 4px;
}
.select-all-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.82rem;
    cursor: pointer;
}
.select-all-label input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: #FFCB05;
    cursor: pointer;
}
.sort-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 5px 12px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.2s;
}
.sort-btn:hover {
    border-color: rgba(255, 203, 5, 0.4);
    color: #FFCB05;
}

/* ===== Card Grid ===== */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.card-tile {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 14px;
    padding: 10px;
    position: relative;
    transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
    cursor: default;
}
.card-tile:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 203, 5, 0.3);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3), 0 0 15px rgba(255, 203, 5, 0.05);
}
.card-tile.selected {
    border-color: #FFCB05;
    box-shadow: 0 0 20px rgba(255, 203, 5, 0.15);
}

/* Tile Checkbox */
.tile-checkbox {
    position: absolute;
    top: 14px;
    left: 14px;
    z-index: 5;
}
.tile-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #FFCB05;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s;
}
.card-tile:hover .tile-checkbox input[type="checkbox"],
.card-tile.selected .tile-checkbox input[type="checkbox"] {
    opacity: 1;
}

/* Tile Quantity */
.tile-qty {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 5;
    background: #FFCB05;
    color: #000;
    font-weight: 800;
    font-size: 0.75rem;
    min-width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50px;
    padding: 0 7px;
}

/* Tile Image */
.tile-image-wrap {
    cursor: zoom-in;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}
.tile-image {
    width: 100%;
    aspect-ratio: 0.72;
    object-fit: cover;
    display: block;
    transition: transform 0.3s;
}
.card-tile:hover .tile-image {
    transform: scale(1.03);
}

/* Tile Info */
.tile-info { padding: 0 2px; }
.tile-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #FFCB05;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.tile-meta {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 5px;
}

.tile-set {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
}
.set-badge {
    background: rgba(255, 203, 5, 0.15);
    color: #FFCB05;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.set-badge.set-none {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.3);
}
.set-number {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.35);
}
.tile-game {
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.25);
    margin-top: 2px;
}

/* ===== Tile Actions (Hover Overlay) ===== */
.tile-actions {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    gap: 4px;
    justify-content: center;
    opacity: 0;
    transform: translateY(6px);
    transition: opacity 0.2s, transform 0.2s;
    pointer-events: none;
}
.card-tile:hover .tile-actions {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}
.action-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: transform 0.15s;
    backdrop-filter: blur(8px);
}
.action-btn:hover { transform: scale(1.12); }
.action-view { background: rgba(255, 203, 5, 0.85); color: #000; }
.action-edit { background: rgba(13, 202, 240, 0.85); color: #000; }
.action-delete { background: rgba(220, 53, 69, 0.85); color: #fff; }

/* ===== Pagination ===== */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 4px;
    flex-wrap: wrap;
    gap: 12px;
}
.pagination-info {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.82rem;
}
.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 4px;
}
.page-btn {
    min-width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.page-btn:hover:not(:disabled) {
    border-color: rgba(255, 203, 5, 0.4);
    color: #FFCB05;
}
.page-btn.active {
    background: #FFCB05;
    color: #000;
    border-color: #FFCB05;
}
.page-btn:disabled {
    opacity: 0.25;
    cursor: not-allowed;
}
.page-ellipsis {
    color: rgba(255, 255, 255, 0.3);
    padding: 0 6px;
    font-size: 0.85rem;
}

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}
.empty-icon {
    font-size: 4rem;
    color: rgba(255, 255, 255, 0.1);
    margin-bottom: 16px;
}
.empty-title {
    color: rgba(255, 255, 255, 0.5);
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 8px;
}
.empty-text {
    color: rgba(255, 255, 255, 0.3);
    margin: 0 0 24px;
}
.empty-cta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #FFCB05, #f39c12);
    color: #000;
    font-weight: 700;
    padding: 12px 28px;
    border-radius: 50px;
    text-decoration: none;
    transition: transform 0.2s;
}
.empty-cta:hover {
    transform: scale(1.05);
    color: #000;
}

/* ===== Fullscreen Viewer ===== */
.fullscreen-viewer {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
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
    border-radius: 16px;
    box-shadow: 0 0 80px rgba(0, 0, 0, 0.8);
}

/* ===== Modals ===== */
.modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-container {
    background: linear-gradient(180deg, #1e233c 0%, #171b30 100%);
    border: 1px solid rgba(255, 203, 5, 0.2);
    border-radius: 18px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
}
.modal-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 203, 5, 0.03);
}
.modal-header-custom h3 {
    margin: 0;
    color: #FFCB05;
    font-weight: 700;
}
.btn-close-modal {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    font-size: 1.2rem;
    cursor: pointer;
    transition: color 0.2s;
}
.btn-close-modal:hover { color: #fff; }
.modal-body { 
    padding: 24px; 
    overflow-y: auto;
}
.modal-body::-webkit-scrollbar {
    width: 8px;
}
.modal-body::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
.modal-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 4px;
}
.modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}
/* .modal-body-grid replaced by new design */
.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* ===== Loader ===== */
.loader-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(30, 35, 60, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    z-index: 100;
    border-radius: 18px;
}
.loader-card {
    width: 80px;
    height: 110px;
    background: linear-gradient(135deg, #ffcb05, #ffd84d);
    border-radius: 10px;
    animation: cardScan 1.5s ease-in-out infinite;
    box-shadow: 0 8px 30px rgba(255, 203, 5, 0.3);
}
@keyframes cardScan {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.05) rotate(2deg); }
}
.loader-text {
    margin-top: 15px;
    color: #FFCB05;
    font-weight: 500;
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
    .filters-grid {
        grid-template-columns: 1fr 1fr;
    }
    .cards-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
}
@media (max-width: 550px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    .cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .hero-title { font-size: 1.6rem; }
    .collection-page { padding: 0 12px 40px; }
    
    /* Mobile Bulk Bar */
    .bulk-bar {
        padding: 10px 15px;
    }
    .bulk-bar-content {
        gap: 10px;
        justify-content: center;
    }
    .bulk-bar-left {
        width: 100%;
        justify-content: center;
        margin-bottom: 5px;
    }
    .bulk-bar-right {
        width: 100%;
        justify-content: center;
        gap: 8px;
    }
    .bulk-btn {
        flex: 1;
        padding: 8px 10px;
        font-size: 0.75rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }
    .bulk-btn i { font-size: 1rem; }

    /* Mobile Modals - Removed Legacy Styles */

    /* Mobile Hero */
    .hero-title { font-size: 1.8rem; }
    .hero-glow { width: 250px; height: 150px; top: -40px; }
    .hero-subtitle { font-size: 0.9rem; margin-bottom: 12px; }
}
</style>
