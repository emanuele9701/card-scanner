<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as bootstrap from 'bootstrap';
import { Head } from '@inertiajs/vue3';
import Dropzone from 'dropzone';
import axios from 'axios';
import 'dropzone/dist/dropzone.css';
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";
import { route } from 'ziggy-js';
import { Ziggy } from '../../ziggy.js';

// Prevent Dropzone from auto-discovering all elements
Dropzone.autoDiscover = false;
let headersCalls;

const props = defineProps({
    sets: Object
});


// ---- State ----
const dropzoneRef = ref(null);
const results = ref([]);    // array of { image_url, name, type, set, card_number, illustrator, status, error }
const localChangeRow = {};
const isProcessing = ref(false);

// ---- Edit Modal state ----
const editingRow = ref(null);
const editingIndex = ref(null);
const editModalRef = ref(null);
let bsModal = null;

// FIX: counter to track parallel uploads and avoid premature isProcessing = false
let activeUploads = 0;

const typeOptions = [
    { label: 'Normale', value: 'Normal', color: '#A8A77A' },
    { label: 'Fuoco', value: 'Fire', color: '#EE8130' },
    { label: 'Acqua', value: 'Water', color: '#6390F0' },
    { label: 'Erba', value: 'Grass', color: '#7AC74C' },
    { label: 'Elettro', value: 'Electric', color: '#F7D02C' },
    { label: 'Ghiaccio', value: 'Ice', color: '#96D9D6' },
    { label: 'Lotta', value: 'Fighting', color: '#C22E28' },
    { label: 'Veleno', value: 'Poison', color: '#A33EA1' },
    { label: 'Terra', value: 'Ground', color: '#E2BF65' },
    { label: 'Volante', value: 'Flying', color: '#A98FF3' },
    { label: 'Psico', value: 'Psychic', color: '#F95587' },
    { label: 'Coleottero', value: 'Bug', color: '#A6B91A' },
    { label: 'Roccia', value: 'Rock', color: '#B6A136' },
    { label: 'Spettro', value: 'Ghost', color: '#735797' },
    { label: 'Drago', value: 'Dragon', color: '#6F35FC' },
    { label: 'Buio', value: 'Dark', color: '#705746' },
    { label: 'Acciaio', value: 'Steel', color: '#B7B7CE' },
    { label: 'Folletto', value: 'Fairy', color: '#D685AD' },
]

const setOptions = props.sets;
const selectedCards = ref([]);
let dz = null;
// ---- Helpers ----
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function typeColor(type) {
    return typeOptions.find(t => t.value === type)?.color || '#6366f1';
}

function saveLocalCard(row, index) {
    row.isEditing = false;
    localChangeRow[row.card_id] = JSON.parse(JSON.stringify(row));
}

function deleteChangesCard(cardRow, index) {
    const restoredRow = localChangeRow[cardRow.card_id];
    if (restoredRow) {
        Object.assign(cardRow, restoredRow);
        cardRow.isEditing = false;
        localChangeRow[cardRow.card_id] = JSON.parse(JSON.stringify(results.value[index]));
    }
}

async function saveCard(cardRow, index) {
    localChangeRow[cardRow.card_id] = JSON.parse(JSON.stringify(results.value[index]));
    cardRow.isSaving = true;
    cardRow.isProcessing = true;
    try {
        const postData = {
            card_id: cardRow.card_id,
            card_name: cardRow.name,
            language_card: cardRow.language,
            type: cardRow.type,
            set_number: cardRow.card_number,
            illustrator: cardRow.illustrator,
            card_set_id: cardRow.set_id,
            game: 'pokemon',
            rarity: cardRow.rarity || null,
        };
        const response = await axios.post(route('cards.save', {}, true, Ziggy), postData, headersCalls);

        if (response.data.success) {
            cardRow.isProcessing = false;
            cardRow.isEditing = false;
            cardRow.status = 'done';
            cardRow.isSave = true;
            cardRow.isSaving = false;
            cardRow.retry = false;
            cardRow.retryType = null;
        } else {
            cardRow.isProcessing = false;
            cardRow.status = 'error';
            cardRow.isSave = false;
            cardRow.isSaving = false;
            cardRow.error = response.data.message || "Errore durante il salvataggio";
            cardRow.retry = true;
            cardRow.retryType = 'save';
        }
    } catch (e) {
        cardRow.isProcessing = false;
        console.error("Errore durante il salvataggio:", e);
        cardRow.status = 'error';
        cardRow.isSaving = false;
        cardRow.retry = true;
        cardRow.retryType = 'save';
        cardRow.error = e.response?.data?.message || "Errore nel salvataggio finale";
    }
}

async function retryRow(row, index) {
    row.error = null;
    row.retry = false;

    if (row.retryType === 'upload') {
        // FIX: set the row to loading BEFORE calling dz.addFile.
        // The sending handler will detect the existing _resultId and reuse this row,
        // so it will NOT create a duplicate loading row.
        row.status = 'loading';
        row.retryType = null;
        dz.addFile(row._file);
    } else if (row.retryType === 'save') {
        row.retryType = null;
        row.status = 'done';
        await saveCard(row, index);
    }
}

async function retrySelectedCards() {
    const rowsToRetry = results.value
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => row.isSelected && row.status === 'error' && row.retry);

    for (const { row, index } of rowsToRetry) {
        await retryRow(row, index);
    }
    // Deseleziona tutte le carte dopo il retry
    selectedCards.value = [];
    results.value.forEach(r => r.isSelected = false);
}

function editRow(cardRow, index) {
    // Save a snapshot for potential cancel
    localChangeRow[cardRow.card_id] = JSON.parse(JSON.stringify(results.value[index]));
    editingRow.value = cardRow;
    editingIndex.value = index;
    if (!bsModal) {
        bsModal = new bootstrap.Modal(editModalRef.value);
    }
    bsModal.show();
}

function saveFromModal() {
    if (editingRow.value !== null && editingIndex.value !== null) {
        saveLocalCard(editingRow.value, editingIndex.value);
    }
    bsModal.hide();
    editingRow.value = null;
    editingIndex.value = null;
}

function cancelFromModal() {
    if (editingRow.value !== null && editingIndex.value !== null) {
        deleteChangesCard(editingRow.value, editingIndex.value);
    }
    bsModal.hide();
    editingRow.value = null;
    editingIndex.value = null;
}

async function deleteRow(row) {
    try {
        await axios.post(route('cards.discard', {}, true, Ziggy), {
            card_id: row.card_id
        }, headersCalls);

        results.value = results.value.filter(r => r.card_id !== row.card_id);
    } catch (e) {
        console.error('Errore durante l\'eliminazione della carta', e);
    }
}

async function saveSelectedCards() {
    if (!confirm(`Sei sicuro di voler salvare ${selectedCards.value.length} carte selezionate? Questa azione è irreversibile.`)) {
        return;
    }

    const cardsToSave = results.value.filter(r =>
        r.isSelected &&
        r.status === 'done' &&
        r.card_id !== null &&
        r.card_id !== undefined
    );

    if (cardsToSave.length === 0) {
        alert('Nessuna carta valida e pronta da salvare. Seleziona solo carte completate.');
        return;
    }

    cardsToSave.forEach(r => {
        r.isProcessing = true;
    });

    let dataToSave = cardsToSave.map(cardRow => ({
        card_id: cardRow.card_id,
        card_name: cardRow.name,
        language_card: cardRow.language,
        type: cardRow.type,
        set_number: cardRow.card_number,
        illustrator: cardRow.illustrator,
        card_set_id: cardRow.set_id,
        game: 'pokemon',
        rarity: cardRow.rarity || null,
    }));

    try {
        const response = await axios.post(route('cards.save', {}, true, Ziggy), {
            cards: dataToSave
        }, headersCalls);

        if (response.data.success) {
            cardsToSave.forEach(r => {
                r.isSave = true;
                r.isEditing = false;
                r.status = 'done';
                r.isProcessing = false;
            });
        } else {
            cardsToSave.forEach(r => {
                r.isProcessing = false;
            });
            alert(response.data.message || "Errore durante il salvataggio delle carte");
        }
    } catch (e) {
        cardsToSave.forEach(r => {
            r.isProcessing = false;
        });
        console.error('Errore nel salvataggio batch:', e);
        alert('Errore durante il salvataggio delle carte');
    } finally {
        selectedCards.value = [];
        results.value.forEach(r => r.isSelected = false);
    }
}

async function deleteSelectedCards() {
    if (!confirm(`Sei sicuro di voler eliminare le carte selezionate? Questa azione è irreversibile.`)) {
        return;
    }

    // Collect all selected rows (including error rows that may not have a card_id)
    const selectedRows = results.value.filter(r => r.isSelected);

    // Only send to backend the rows that actually have a card_id
    const validCardIds = selectedRows
        .filter(r => r.card_id !== null && r.card_id !== undefined)
        .map(r => r.card_id);

    if (validCardIds.length > 0) {
        try {
            const response = await axios.post(route('cards.discard', {}, true, Ziggy), {
                cards_id: validCardIds
            }, headersCalls);

            if (!response.data.success) {
                alert(response.data.message || "Errore durante l'eliminazione delle carte");
                return;
            }
        } catch (e) {
            console.error('Errore durante l\'eliminazione:', e);
            alert("Errore durante l'eliminazione delle carte");
            return;
        }
    }

    // Remove all selected rows from the frontend (including error-only rows without card_id)
    const selectedInternalIds = new Set(selectedRows.map(r => r._id));
    results.value = results.value.filter(r => !selectedInternalIds.has(r._id));
    selectedCards.value = [];
}

// ---- Dropzone init ----
onMounted(() => {
    headersCalls = {
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
        }
    };
    dz = new Dropzone(dropzoneRef.value, {
        url: route('cards.upload-and-enhance', {}, true, Ziggy),
        method: 'POST',
        paramName: 'image',
        maxFilesize: 20,
        acceptedFiles: 'image/*',
        parallelUploads: 3,
        addRemoveLinks: false,
        previewsContainer: false,
        clickable: true,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },

        // --- Events ---
        sending(file) {
            // FIX: increment counter for accurate processing state
            activeUploads++;
            isProcessing.value = true;

            // FIX: if the file already has a _resultId it's a retry upload.
            // The existing row was already set to 'loading' in retryRow.
            // We only need to refresh the image preview — no new row is created.
            if (file._resultId) {
                const existingResultId = file._resultId;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const row = results.value.find(r => r._id === existingResultId);
                    if (row) row.image_url = e.target.result;
                };
                reader.readAsDataURL(file);
                return;
            }

            // FIX: assign _resultId SYNCHRONOUSLY before the async FileReader,
            // and create the row immediately so success/error can always find it.
            const resultId = crypto.randomUUID();
            file._resultId = resultId;

            results.value.unshift({
                _id: resultId,
                image_url: null,   // will be set by FileReader below
                name: null,
                type: null,
                set: null,
                card_number: null,
                illustrator: null,
                status: 'loading',
                language: "",
                error: null,
                filename: file.name,
                isEditing: false,
                isSave: false,
                isSaving: false,
                isSelected: false,
                isProcessing: false,
                retry: false,
                retryType: null,
                _file: file,
            });

            // Async: load the local preview and update only the image_url field
            const reader = new FileReader();
            reader.onload = (e) => {
                const row = results.value.find(r => r._id === resultId);
                if (row) row.image_url = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        success(file, response) {
            // FIX: decrement counter; turn off spinner only when all uploads are done
            activeUploads--;
            if (activeUploads === 0) isProcessing.value = false;

            const row = results.value.find(r => r._id === file._resultId);
            if (response.success && row) {
                Object.assign(row, {
                    image_url: response.data.image_url || row.image_url,
                    name: response.data.name,
                    type: response.data.type,
                    language: response.data.language_card ?? "",
                    set: response.data.set,
                    set_id: response.data.set_id,
                    set_code: response.data.set_code,
                    card_number: response.data.card_number,
                    card_id: response.data.card_id,
                    illustrator: response.data.illustrator,
                    status: 'done',
                    isEditing: false,
                    isSave: false,
                    isSaving: false,
                    isSelected: false,
                    retry: false,
                });
            } else if (row) {
                row.status = 'error';
                row.error = response.message || 'Errore sconosciuto';
                row.retry = true;
                row.retryType = 'upload';
            }
            dz.removeFile(file);
        },

        error(file, errorMessage) {
            // FIX: decrement counter; turn off spinner only when all uploads are done
            activeUploads--;
            if (activeUploads === 0) isProcessing.value = false;

            const row = results.value.find(r => r._id === file._resultId);
            if (row) {
                row.status = 'error';
                row.retry = true;
                row.retryType = 'upload';
                row._file = file;
                row.error = typeof errorMessage === 'string'
                    ? errorMessage
                    : (errorMessage?.message ?? 'Errore di rete');
            }
            dz.removeFile(file);
        },
    });
});

onBeforeUnmount(() => {
    if (dz) dz.destroy();
});

function toggleCheckbox(index) {

    if (index == null) {
        // Sono tutte da selezionare
        results.value.map((r) => r.isSelected = true);
    } else {
        results.value[index].isSelected = !results.value[index].isSelected;
        const cardId = results.value[index].card_id;

        // Solo carte con card_id valido (non errori di upload senza ID)
        if (cardId !== null && cardId !== undefined) {
            if (results.value[index].isSelected) {
                if (!selectedCards.value.includes(cardId)) {
                    selectedCards.value.push(cardId);
                }
            } else {
                const pos = selectedCards.value.indexOf(cardId);
                if (pos !== -1) selectedCards.value.splice(pos, 1);
            }
        }
    }
}
</script>

<template>

    <Head title="Carica Carta" />
    <AppLayout>
        <div class="upload-page">

            <!-- ── Header ── -->
            <div class="page-header">
                <div class="header-glow"></div>
                <h1 class="page-title">
                    <span class="title-icon">⚡</span>
                    Carica &amp; Riconosci
                </h1>
                <p class="page-subtitle">
                    Trascina le immagini delle tue carte — l'AI le identifica automaticamente
                </p>
            </div>

            <!-- ── Dropzone ── -->
            <div class="dropzone-wrapper">
                <div ref="dropzoneRef" class="custom-dropzone" id="card-dropzone">
                    <div class="dz-default dz-message">
                        <div class="dz-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                        <span class="dz-label">Trascina le foto qui</span>
                        <span class="dz-sublabel">o <strong>clicca</strong> per selezionare · JPG, PNG, WEBP · max 25
                            MB</span>
                    </div>
                </div>

                <!-- Processing badge -->
                <transition name="fade">
                    <div v-if="isProcessing" class="processing-badge">
                        <div class="spinner"></div>
                        <span>Analisi AI in corso…</span>
                    </div>
                </transition>
            </div>

            <!-- ── Results Table ── -->
            <transition name="slide-up">
                <div v-if="results.length > 0" class="results-section">
                    <div class="results-header">
                        <h2 class="results-title">
                            Carte riconosciute
                            <span class="badge">{{results.filter(r => r.status === 'done').length}}</span>
                        </h2>
                        <!--
                            FIX: was `v-if="selectedCards.length > 0"` — this hid all buttons when
                            only error cards (without card_id) were selected, because those rows are
                            never added to selectedCards. Now we check isSelected directly on results.
                        -->
                        <div class="d-flex gap-2 mt-2" v-if="results.some(r => r.isSelected)">
                            <button class="btn btn-warning btn-sm" @click="retrySelectedCards()"
                                v-if="results.some(r => r.isSelected && r.status === 'error' && r.retry)">
                                <font-awesome-icon :icon="['fas', 'redo']" /> Rielabora
                            </button>
                            <button class="btn btn-success btn-sm" @click="saveSelectedCards()"
                                v-if="results.some(r => r.isSelected && r.status === 'done' && !r.isSave)">
                                <font-awesome-icon :icon="['fad', 'save']" /> Salva selezione
                            </button>
                            <button class="btn btn-danger btn-sm" @click="deleteSelectedCards()">
                                <font-awesome-icon :icon="['fad', 'trash']" /> Elimina selezione
                            </button>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" @change="toggleCheckbox(null)">
                                    </th>
                                    <th class="col-img">Anteprima</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Set</th>
                                    <th>N° Carta</th>
                                    <th>Illustratore</th>
                                    <th class="col-status">Stato</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr v-for="(row, index) in results" :key="row._id" :class="['result-row', row.status]">

                                    <td>
                                        <!-- Solo se non è salvata -->
                                        <input v-if="!(row.status === 'done' && row.isSave)" type="checkbox"
                                            :checked="row.isSelected" @change="toggleCheckbox(index)">
                                    </td>
                                    <!-- Preview -->
                                    <td class="col-img">
                                        <div class="card-thumb-wrapper">
                                            <img v-if="row.image_url" :src="row.image_url" :alt="row.filename"
                                                class="card-thumb" />
                                            <div v-if="row.status === 'loading'" class="thumb-overlay">
                                                <div class="spinner-sm"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Name -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-text"></span>
                                        <span v-else-if="row.status === 'error'" class="text-muted">—</span>
                                        <span v-else-if="row.status === 'done'" class="card-name">{{
                                            row.name + " (" + row.language + ")" ?? '—' }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>

                                    <!-- Type -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-chip"></span>
                                        <span v-else-if="row.type" class="type-chip"
                                            :style="{ background: typeColor(row.type) }">
                                            {{ row.type }}
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>

                                    <!-- Set -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-text"></span>
                                        <span v-else-if="row.status === 'done'" class="set-name">{{ row.set ?? '—' }}</span>
                                        <span v-else class="set-name">{{ row.set ?? '—' }}</span>
                                    </td>

                                    <!-- Card number -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-short"></span>
                                        <span v-else-if="row.status === 'done'">{{ row.card_number ?? '—' }}</span>
                                        <code v-else class="card-number">{{ row.card_number ?? '—' }}</code>
                                    </td>

                                    <!-- Illustrator -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-short"></span>
                                        <span v-else-if="row.status === 'done'">{{ row.illustrator ?? '—' }}</span>
                                        <code v-else class="card-number">{{ row.illustrator ?? '—' }}</code>
                                    </td>

                                    <!-- Status -->
                                    <td class="col-status">
                                        <span v-if="row.status === 'loading'" class="status-pill loading">
                                            <div class="spinner-xs"></div> Analisi…
                                        </span>
                                        <span v-else-if="row.status === 'done' && !row.isSave && !row.isSaving"
                                            class="status-pill done">✓ Fatto</span>
                                        <span v-else-if="row.status === 'done' && row.isSave && !row.isSaving"
                                            class="status-pill done">✓ Salvata</span>
                                        <span v-else-if="row.status === 'done' && !row.isSave && row.isSaving"
                                            class="status-pill done">Salvataggio in corso</span>
                                        <span v-else class="status-pill error" :title="row.error">✕ Errore</span>

                                        <!-- Messaggio errore + pulsante Riprova -->
                                        <div v-if="row.status === 'error' && row.retry" class="retry-wrapper">
                                            <span class="error-message" :title="row.error">{{ row.error }}</span>
                                            <button class="btn-retry" @click="retryRow(row, index)"
                                                :title="row.retryType === 'upload' ? 'Riprova upload immagine' : 'Riprova salvataggio'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.2" width="13" height="13">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12a7.5 7.5 0 0 1 13.28-4.78L20 9.5M20 4v5.5h-5.5M19.5 12A7.5 7.5 0 0 1 6.22 16.78L4 14.5M4 20v-5.5h5.5" />
                                                </svg>
                                                {{ row.retryType === 'upload' ? 'Riprova upload' : 'Riprova salvataggio'
                                                }}
                                            </button>
                                        </div>
                                    </td>

                                    <td class="col-action d-flex flex-column gap-3" v-if="!row.isSave && !row.isSaving">
                                        <span style="cursor: pointer;" class="text-center bg-success p-1"
                                            v-if="row.status === 'done'">
                                            <font-awesome-icon :icon="['fad', 'save']" @click="saveCard(row, index)" />
                                        </span>

                                        <span style="cursor: pointer;" class="text-center bg-primary p-1"
                                            v-if="row.status === 'done'">
                                            <font-awesome-icon :icon="['fas', 'pencil']" @click="editRow(row, index)" />
                                        </span>

                                        <span style="cursor: pointer;" class="text-center bg-danger p-1"
                                            v-if="row.status === 'done'">
                                            <font-awesome-icon :icon="['fas', 'trash']" @click="deleteRow(row)" />
                                        </span>
                                    </td>


                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </transition>

        </div>

        <!-- ── Edit Card Modal ── -->
        <div class="modal fade" id="editCardModal" ref="editModalRef" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark text-light border border-secondary">

                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="editCardModalLabel">
                            ✏️ Modifica Carta
                            <span v-if="editingRow" class="ms-2 text-muted fs-6">{{ editingRow.name }}</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="cancelFromModal" aria-label="Chiudi"></button>
                    </div>

                    <div class="modal-body" v-if="editingRow">
                        <div class="row g-3">

                            <!-- Anteprima + Nome -->
                            <div class="col-md-3 d-flex justify-content-center align-items-start">
                                <div class="card-thumb-wrapper" style="width:90px;height:125px;">
                                    <img v-if="editingRow.image_url" :src="editingRow.image_url" class="card-thumb" :alt="editingRow.filename" />
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="row g-3">

                                    <!-- Nome -->
                                    <div class="col-md-8">
                                        <label class="form-label text-secondary small">Nome Carta</label>
                                        <input v-model="editingRow.name" class="form-control form-control-sm bg-secondary text-light border-0" placeholder="Nome carta" />
                                    </div>

                                    <!-- Lingua -->
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Lingua</label>
                                        <input v-model="editingRow.language" class="form-control form-control-sm bg-secondary text-light border-0" placeholder="es. IT, EN…" />
                                    </div>

                                    <!-- Tipo -->
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small">Tipo</label>
                                        <select v-model="editingRow.type" class="form-select form-select-sm bg-secondary text-light border-0">
                                            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Set -->
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small">Set</label>
                                        <select v-model="editingRow.set_id" class="form-select form-select-sm bg-secondary text-light border-0">
                                            <option v-for="opt in setOptions" :key="opt.id" :value="opt.id">
                                                {{ opt.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- N° Carta -->
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small">N° Carta</label>
                                        <input v-model="editingRow.card_number" class="form-control form-control-sm bg-secondary text-light border-0" placeholder="es. 001/200" />
                                    </div>

                                    <!-- Illustratore -->
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small">Illustratore</label>
                                        <input v-model="editingRow.illustrator" class="form-control form-control-sm bg-secondary text-light border-0" placeholder="Nome illustratore" />
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="cancelFromModal">
                            <font-awesome-icon :icon="['fas', 'times']" /> Annulla
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" @click="saveFromModal">
                            <font-awesome-icon :icon="['fas', 'check']" /> Salva modifiche
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
/* ── Page ── */
.upload-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
    font-family: 'Inter', sans-serif;
}

/* ── Header ── */
.page-header {
    position: relative;
    text-align: center;
    margin-bottom: 2.5rem;
    overflow: hidden;
}

.header-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 80% at 50% 0%, rgba(99, 102, 241, .18) 0%, transparent 70%);
    pointer-events: none;
}

.page-title {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #f8fafc;
    margin: 0 0 .5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
}

.title-icon {
    font-size: 1.4em;
    filter: drop-shadow(0 0 8px #eab308);
}

.page-subtitle {
    color: #94a3b8;
    font-size: 1rem;
    margin: 0;
}

/* ── Dropzone wrapper ── */
.dropzone-wrapper {
    position: relative;
    margin-bottom: 2.5rem;
}

.custom-dropzone {
    border: 2px dashed rgba(99, 102, 241, .5);
    border-radius: 1.25rem;
    background: rgba(15, 23, 42, .6);
    backdrop-filter: blur(12px);
    padding: 3.5rem 2rem;
    cursor: pointer;
    transition: border-color .25s, background .25s, box-shadow .25s;
    text-align: center;
}

.custom-dropzone:hover,
.custom-dropzone.dz-drag-hover {
    border-color: #6366f1;
    background: rgba(99, 102, 241, .08);
    box-shadow: 0 0 40px -10px rgba(99, 102, 241, .35);
}

.dz-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
    pointer-events: none;
}

.dz-icon {
    width: 60px;
    height: 60px;
    color: #6366f1;
    opacity: .85;
}

.dz-icon svg {
    width: 100%;
    height: 100%;
}

.dz-label {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
}

.dz-sublabel {
    font-size: .875rem;
    color: #64748b;
}

.dz-sublabel strong {
    color: #6366f1;
    font-weight: 600;
}

/* ── Processing badge ── */
.processing-badge {
    position: absolute;
    bottom: -1.2rem;
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: .8rem;
    font-weight: 600;
    padding: .35rem .9rem;
    border-radius: 9999px;
    box-shadow: 0 4px 20px rgba(99, 102, 241, .4);
}

/* ── Results ── */
.results-section {
    background: rgba(15, 23, 42, .7);
    border: 1px solid rgba(99, 102, 241, .2);
    border-radius: 1.25rem;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.results-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(99, 102, 241, .15);
}

.results-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0;
    display: flex;
    align-items: center;
    gap: .75rem;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    padding: 0 8px;
    background: rgba(99, 102, 241, .25);
    color: #a5b4fc;
    border-radius: 9999px;
    font-size: .8rem;
    font-weight: 700;
}

/* ── Table ── */
.table-wrapper {
    overflow-x: auto;
}

.results-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .9rem;
    color: #cbd5e1;
}

.results-table thead tr {
    background: rgba(30, 41, 59, .8);
}

.results-table th {
    padding: .9rem 1.2rem;
    text-align: left;
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    white-space: nowrap;
}

.results-table td {
    padding: .9rem 1.2rem;
    vertical-align: middle;
    border-top: 1px solid rgba(51, 65, 85, .5);
}

.result-row:hover td {
    background: rgba(99, 102, 241, .05);
}

.result-row.error td {
    opacity: .65;
}

/* Thumb */
.col-img {
    width: 72px;
}

.card-thumb-wrapper {
    position: relative;
    width: 56px;
    height: 78px;
    border-radius: .5rem;
    overflow: hidden;
    background: rgba(30, 41, 59, .8);
}

.card-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Text / chips */
.card-name {
    font-weight: 600;
}

.type-chip {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 9999px;
    font-size: .78rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, .4);
}

.set-name {
    color: #94a3b8;
    font-size: .875rem;
}

.card-number {
    font-family: 'JetBrains Mono', monospace;
    font-size: .82rem;
    color: #a5b4fc;
    background: rgba(99, 102, 241, .12);
    padding: .15rem .45rem;
    border-radius: .3rem;
}

.illustrator {
    font-style: italic;
    color: #94a3b8;
    font-size: .875rem;
}

.text-muted {
    color: #475569;
}

/* Status pills */
.col-status {
    text-align: center;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .75rem;
    border-radius: 9999px;
    font-size: .78rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-pill.loading {
    background: rgba(99, 102, 241, .15);
    color: #a5b4fc;
}

.status-pill.done {
    background: rgba(34, 197, 94, .15);
    color: #4ade80;
}

.status-pill.error {
    background: rgba(239, 68, 68, .15);
    color: #f87171;
    cursor: help;
}

/* ── Retry ── */
.retry-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    margin-top: .5rem;
}

.error-message {
    font-size: .72rem;
    color: #f87171;
    max-width: 160px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: .85;
}

.btn-retry {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .65rem;
    border-radius: 9999px;
    border: 1.5px solid rgba(251, 146, 60, .6);
    background: rgba(251, 146, 60, .08);
    color: #fb923c;
    font-size: .72rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s, border-color .2s, transform .15s;
    white-space: nowrap;
}

.btn-retry:hover {
    background: rgba(251, 146, 60, .18);
    border-color: #fb923c;
    transform: scale(1.04);
}

.btn-retry:active {
    transform: scale(.97);
}

/* ── Skeletons ── */
.skeleton {
    display: inline-block;
    background: linear-gradient(90deg, rgba(51, 65, 85, .6) 25%, rgba(71, 85, 105, .6) 50%, rgba(51, 65, 85, .6) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: .35rem;
}

.skeleton-text {
    width: 110px;
    height: 14px;
}

.skeleton-short {
    width: 55px;
    height: 14px;
}

.skeleton-chip {
    width: 70px;
    height: 22px;
    border-radius: 9999px;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0
    }

    100% {
        background-position: -200% 0
    }
}

/* ── Spinners ── */
.spinner,
.spinner-sm,
.spinner-xs {
    display: inline-block;
    border-radius: 50%;
    border-style: solid;
    border-color: rgba(255, 255, 255, .2);
    border-top-color: #fff;
    animation: spin .7s linear infinite;
}

.spinner {
    width: 18px;
    height: 18px;
    border-width: 2.5px;
}

.spinner-sm {
    width: 22px;
    height: 22px;
    border-width: 3px;
}

.spinner-xs {
    width: 12px;
    height: 12px;
    border-width: 2px;
}

@keyframes spin {
    to {
        transform: rotate(360deg)
    }
}

/* ── Transitions ── */
.fade-enter-active,
.fade-leave-active {
    transition: opacity .3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active {
    transition: all .4s cubic-bezier(.16, 1, .3, 1);
}

.slide-up-enter-from {
    opacity: 0;
    transform: translateY(24px);
}
</style>