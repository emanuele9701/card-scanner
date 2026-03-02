<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

// Prevent Dropzone from auto-discovering all elements
Dropzone.autoDiscover = false;

// ---- State ----
const dropzoneRef = ref(null);
const results = ref([]);    // array of { image_url, name, type, set, card_number, illustrator, status, error }
const isProcessing = ref(false);
let dz = null;

// ---- Helpers ----
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function typeColor(type) {
    const map = {
        Fire: '#ef4444', Water: '#3b82f6', Grass: '#22c55e', Lightning: '#eab308',
        Psychic: '#a855f7', Fighting: '#f97316', Darkness: '#6b7280', Metal: '#94a3b8',
        Dragon: '#06b6d4', Colorless: '#d1d5db', Fairy: '#ec4899',
    };
    return map[type] || '#6366f1';
}

// ---- Dropzone init ----
onMounted(() => {
    dz = new Dropzone(dropzoneRef.value, {
        url: '/cards/upload-and-enhance',
        method: 'POST',
        paramName: 'image',
        maxFilesize: 25,   // MB
        acceptedFiles: 'image/*',
        parallelUploads: 1,
        addRemoveLinks: false,
        previewsContainer: false,   // we handle previews ourselves
        clickable: true,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },

        // --- Events ---
        sending(file) {
            isProcessing.value = true;
            // Push a "loading" row immediately with a local preview
            const reader = new FileReader();
            reader.onload = (e) => {
                results.value.unshift({
                    _id: Date.now(),
                    image_url: e.target.result,
                    name: null,
                    type: null,
                    set: null,
                    card_number: null,
                    illustrator: null,
                    status: 'loading',
                    error: null,
                    filename: file.name,
                });
                file._resultId = results.value[0]._id;
            };
            reader.readAsDataURL(file);
        },

        success(file, response) {
            isProcessing.value = false;
            const row = results.value.find(r => r._id === file._resultId);
            if (response.success && row) {
                Object.assign(row, {
                    image_url: response.data.image_url || row.image_url,
                    name: response.data.name,
                    type: response.data.type,
                    set: response.data.set,
                    set_id: response.data.set_id,
                    set_code: response.data.set_code,
                    card_number: response.data.card_number,
                    card_id: response.data.card_id,
                    illustrator: response.data.illustrator,
                    status: 'done',
                });
            } else if (row) {
                row.status = 'error';
                row.error = response.message || 'Errore sconosciuto';
            }
            dz.removeFile(file);
        },

        error(file, errorMessage) {
            isProcessing.value = false;
            const row = results.value.find(r => r._id === file._resultId);
            if (row) {
                row.status = 'error';
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
                    </div>

                    <div class="table-wrapper">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th class="col-img">Anteprima</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Set</th>
                                    <th>N° Carta</th>
                                    <th>Illustratore</th>
                                    <th class="col-status">Stato</th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr v-for="row in results" :key="row._id" :class="['result-row', row.status]">

                                    <!-- Preview -->
                                    <td class="col-img">
                                        <div class="card-thumb-wrapper">
                                            <img :src="row.image_url" :alt="row.filename" class="card-thumb" />
                                            <div v-if="row.status === 'loading'" class="thumb-overlay">
                                                <div class="spinner-sm"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Name -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-text"></span>
                                        <span v-else-if="row.status === 'error'" class="text-muted">—</span>
                                        <span v-else class="card-name">{{ row.name ?? '—' }}</span>
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
                                        <span v-else class="set-name">{{ row.set ?? '—' }}</span>
                                    </td>

                                    <!-- Card number -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-short"></span>
                                        <code v-else class="card-number">{{ row.card_number ?? '—' }}</code>
                                    </td>

                                    <!-- Illustrator -->
                                    <td>
                                        <span v-if="row.status === 'loading'" class="skeleton skeleton-text"></span>
                                        <span v-else class="illustrator">{{ row.illustrator ?? '—' }}</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="col-status">
                                        <span v-if="row.status === 'loading'" class="status-pill loading">
                                            <div class="spinner-xs"></div> Analisi…
                                        </span>
                                        <span v-else-if="row.status === 'done'" class="status-pill done">✓ Fatto</span>
                                        <span v-else class="status-pill error" :title="row.error">✕ Errore</span>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </transition>

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
    color: #f1f5f9;
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
