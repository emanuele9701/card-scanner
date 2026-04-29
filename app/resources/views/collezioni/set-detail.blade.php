@extends('layouts.app')
@section('title', $set->name)
@section('meta_description', 'Dettaglio del set ' . $set->name)

@section('content')
    <style>
        .breadcrumb-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .breadcrumb-link:hover {
            color: #fff;
        }

        .set-header-icon {
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background-color: rgba(255, 255, 255, 0.04);
        }

        .selection-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(251, 180, 0, 0.3);
            background-color: rgba(5, 20, 36, 0.9);
            padding: 0.75rem 1.25rem;
            box-shadow: 0 0 32px rgba(251, 180, 0, 0.2);
            backdrop-filter: blur(16px);
        }

        .selection-bar-divider {
            width: 1px;
            height: 16px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-add-selection {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 9999px;
            background-color: #fbb400;
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #422c00;
            border: none;
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .btn-add-selection:hover {
            background-color: #ffd795;
        }

        .btn-clear-selection {
            border-radius: 9999px;
            padding: 0.375rem;
            color: rgba(212, 228, 250, 0.4);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-clear-selection:hover {
            color: #d4e4fa;
        }

        .card-modal-content {
            background-color: #122131;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            max-width: 768px;
            width: 100%;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(1, 15, 31, 0.8);
            backdrop-filter: blur(4px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .btn-modal-close {
            border-radius: 0.5rem;
            padding: 0.375rem;
            color: rgba(212, 228, 250, 0.4);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-modal-close:hover {
            color: #d4e4fa;
        }

        .modal-detail-label {
            color: rgba(212, 228, 250, 0.45);
            font-weight: 500;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .modal-detail-value {
            color: rgba(212, 228, 250, 0.85);
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .modal-th {
            color: rgba(212, 228, 250, 0.4);
            font-weight: 600;
            text-align: left;
            padding: 0.5rem 0.75rem;
            white-space: nowrap;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        .modal-td {
            color: rgba(212, 228, 250, 0.75);
            padding: 0.6rem 0.75rem;
            white-space: nowrap;
        }

        tr:hover .modal-td {
            background-color: rgba(255, 255, 255, 0.025);
        }
    </style>

    {{-- Variabile rotta generata lato PHP, così evitiamo conflitti di virgolette dentro x-data --}}
    @php $cardShowRoute = route('card.show', ':card'); @endphp
    @php $addCardToCollection = route('collezioni.cards.addToCollection', ':card'); @endphp

    <div x-data="{
        selected: [],
        modalOpen: false,
        modalCard: null,
        modalLoading: false,
    
        toggleSelect(cardId) {
            if (this.selected.includes(cardId)) {
                this.selected = this.selected.filter(id => id !== cardId);
            } else {
                this.selected.push(cardId);
            }
        },
    
        isSelected(cardId) {
            return this.selected.includes(cardId);
        },
    
        async openModal(card) {
            this.modalOpen = true;
            this.modalLoading = true;
            this.modalCard = null;
    
            try {
                const uriRoute = '{{ $cardShowRoute }}';
                const response = await fetch(uriRoute.replace(':card', card.id), {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    credentials: 'same-origin'
                });
    
                if (response.ok) {
                    this.modalCard = await response.json();
                }
                this.modalLoading = false;
            } catch (e) {
                console.error(e);
            } finally {
                this.modalLoading = false;
            }
        },
    
        async addToCollection(cardIds) {
            const uri = ('{{ $addCardToCollection }}').replace(':card', cardIds)
            await fetch(uri, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ card_ids: cardIds }),
            });
        },
    
        activeVariants(variants) {
            if (!variants) return [];
            const labels = {
                normal: 'Normal',
                holo: 'Holo',
                reverse: 'Reverse',
                firstEdition: '1ª Ed.',
                wPromo: 'Promo'
            };
            return Object.entries(variants)
                .filter(([, v]) => v === true)
                .map(([k]) => labels[k] ?? k);
        },
    
        formatPrice(val) {
            if (val === null || val === undefined) return '—';
            return parseFloat(val).toFixed(2) + ' €';
        },
    }" class="position-relative container py-5" style="max-width:1280px;">

        {{-- Breadcrumb --}}
        <nav class="d-flex align-items-center gap-2 mb-4" style="font-size:0.875rem;">
            <a href="{{ route('collezioni.disponibili') }}" class="breadcrumb-link">Collezioni disponibili</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            @if ($set->serie)
                <span class="text-secondary">{{ $set->serie->name }}</span>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
            <span class="text-white">{{ $set->name }}</span>
        </nav>

        {{-- Page Header --}}
        <div class="d-flex align-items-center gap-3 mb-5">
            @if ($set->symbol)
                <div class="set-header-icon">
                    <img src="{{ $set->symbol }}.png" alt="Simbolo {{ $set->name }}"
                        style="width:32px;height:32px;object-fit:contain;">
                </div>
            @endif
            <div>
                <h1 class="text-white fw-bold mb-0" style="font-size:1.875rem; letter-spacing:-0.02em;">
                    {{ $set->name }}
                </h1>
                @if ($set->serie)
                    <p class="text-secondary mb-0 mt-1" style="font-size:0.875rem;">{{ $set->serie->name }}</p>
                @endif
            </div>
        </div>

        {{-- Grid carte --}}
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
            @foreach ($set->cards as $card)
                @include('collezioni.singles.cards', ['card' => $card])
            @endforeach
        </div>

        {{-- Bottone flottante selezione multipla --}}
        <div x-show="selected.length > 0" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="position-fixed bottom-0 start-50 translate-middle-x mb-4" style="z-index:1040; display:none;">
            <div class="selection-bar">
                <span style="font-size:0.875rem; font-weight:600; color:#ffd795;">
                    <span x-text="selected.length"></span> carte selezionate
                </span>
                <div class="selection-bar-divider"></div>
                <button x-on:click="addToCollection(selected)" class="btn-add-selection">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Aggiungi alla collezione
                </button>
                <button x-on:click="selected = []" class="btn-clear-selection">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Modal dettagli carta --}}
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            x-on:keydown.escape.window="modalOpen = false" class="modal-overlay" style="display:none;">

            {{-- Sfondo cliccabile per chiudere --}}
            <div x-on:click="modalOpen = false" class="position-absolute inset-0 w-100 h-100"></div>

            <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" class="card-modal-content p-4 position-relative"
                style="z-index:10; max-width:860px; width:100%; max-height:90vh; overflow-y:auto;">

                {{-- Header modal --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;"
                            x-text="modalCard?.name ?? '...'"></h2>
                        <span x-show="modalCard?.rarity" class="badge mt-1"
                            style="background:rgba(251,180,0,0.15); color:#fbb400; border:1px solid rgba(251,180,0,0.3); font-size:0.7rem; font-weight:600;"
                            x-text="modalCard?.rarity"></span>
                    </div>
                    <button x-on:click="modalOpen = false" class="btn-modal-close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <template x-if="modalLoading">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border" style="color:#fbb400;" role="status">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                    </div>
                </template>

                {{-- Corpo del modal --}}
                <div x-show="!modalLoading && modalCard">

                    {{-- Immagine + dettagli --}}
                    <div class="row g-4 align-items-start mb-4">

                        {{-- Immagine carta --}}
                        <div class="col-12 col-md-4 d-flex justify-content-center">
                            <div
                                style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08);
                                        border-radius:1rem; padding:1rem; display:inline-flex;">
                                <img :src="(modalCard?.url_image ?? '') + '/high.png'" :alt="modalCard?.name"
                                    style="max-width:180px; width:100%; border-radius:0.5rem; object-fit:contain;">
                            </div>
                        </div>

                        {{-- Dettagli carta --}}
                        <div class="col-12 col-md-8">
                            <dl class="row g-0" style="font-size:0.875rem;">

                                <dt class="col-5 modal-detail-label">Dex ID</dt>
                                <dd class="col-7 modal-detail-value" x-text="modalCard?.dexId ?? '—'"></dd>

                                <dt class="col-5 modal-detail-label">Tipo</dt>
                                <dd class="col-7 modal-detail-value">
                                    <template x-if="modalCard?.types?.length">
                                        <div class="d-flex flex-wrap gap-1">
                                            <template x-for="t in modalCard.types" :key="t">
                                                <span class="badge"
                                                    style="background:rgba(99,179,237,0.15); color:#63b3ed;
                                                             border:1px solid rgba(99,179,237,0.3); font-size:0.7rem;"
                                                    x-text="t"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <span x-show="!modalCard?.types?.length" style="color:rgba(212,228,250,0.4);">—</span>
                                </dd>

                                <dt class="col-5 modal-detail-label">Stage</dt>
                                <dd class="col-7 modal-detail-value" x-text="modalCard?.level_stage ?? '—'"></dd>

                                <dt class="col-5 modal-detail-label">Abilità</dt>
                                <dd class="col-7 modal-detail-value">
                                    <template x-if="modalCard?.abilities?.length">
                                        <ul class="list-unstyled mb-0">
                                            <template x-for="ab in modalCard.abilities" :key="ab.name">
                                                <li>
                                                    <span class="fw-semibold" style="color:#d4e4fa;"
                                                        x-text="ab.name"></span>
                                                    <span x-show="ab.type" class="ms-1 badge"
                                                        style="background:rgba(154,230,180,0.15); color:#9ae6b4;
                                                                 border:1px solid rgba(154,230,180,0.3); font-size:0.65rem;"
                                                        x-text="ab.type"></span>
                                                    <p x-show="ab.effect" x-text="ab.effect"
                                                        style="font-size:0.75rem; color:rgba(212,228,250,0.5); margin:2px 0 6px;">
                                                    </p>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <span x-show="!modalCard?.abilities?.length"
                                        style="color:rgba(212,228,250,0.4);">Nessuna abilità</span>
                                </dd>

                                <dt class="col-5 modal-detail-label">Varianti</dt>
                                <dd class="col-7 modal-detail-value">
                                    <template x-if="activeVariants(modalCard?.variants).length">
                                        <div class="d-flex flex-wrap gap-1">
                                            <template x-for="v in activeVariants(modalCard?.variants)"
                                                :key="v">
                                                <span class="badge"
                                                    style="background:rgba(255,255,255,0.06); color:rgba(212,228,250,0.8);
                                                             border:1px solid rgba(255,255,255,0.1); font-size:0.7rem;"
                                                    x-text="v"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <span x-show="!activeVariants(modalCard?.variants).length"
                                        style="color:rgba(212,228,250,0.4);">—</span>
                                </dd>

                                <dt class="col-5 modal-detail-label">Ultimo prezzo</dt>
                                <dd class="col-7 modal-detail-value">
                                    <span class="fw-bold" style="color:#fbb400; font-size:1rem;"
                                        x-text="formatPrice(modalCard?.prices?.[0]?.trend)"></span>
                                </dd>

                            </dl>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div style="border-top:1px solid rgba(255,255,255,0.07); margin-bottom:1.25rem;"></div>

                    {{-- Tabella storico prezzi --}}
                    <div>
                        <h3 class="fw-semibold mb-3"
                            style="font-size:0.875rem; color:rgba(212,228,250,0.6);
                                   text-transform:uppercase; letter-spacing:0.05em;">
                            Storico prezzi
                        </h3>
                        <div style="overflow-x:auto;">
                            <table class="w-100" style="font-size:0.8125rem; border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                                        <th class="modal-th">Data</th>
                                        <th class="modal-th">Trend</th>
                                        <th class="modal-th">Media 1g</th>
                                        <th class="modal-th">Media 7g</th>
                                        <th class="modal-th">Media 30g</th>
                                        <th class="modal-th">Provider</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="modalCard?.prices?.length">
                                        <template x-for="price in modalCard.prices" :key="price.id">
                                            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                                                <td class="modal-td" style="color:rgba(212,228,250,0.5);"
                                                    x-text="price.updated_at
                                                        ? new Date(price.updated_at).toLocaleDateString('it-IT')
                                                        : new Date().toLocaleDateString('it-IT')">
                                                </td>
                                                <td class="modal-td fw-semibold" style="color:#fbb400;"
                                                    x-text="formatPrice(price.trend)"></td>
                                                <td class="modal-td" x-text="formatPrice(price.avg_1d)"></td>
                                                <td class="modal-td" x-text="formatPrice(price.avg_7d)"></td>
                                                <td class="modal-td" x-text="formatPrice(price.avg_30d)"></td>
                                                <td class="modal-td">
                                                    <span class="badge"
                                                        style="background:rgba(251,180,0,0.1); color:#fbb400;
                                                                 border:1px solid rgba(251,180,0,0.25); font-size:0.68rem;">
                                                        CardMarket
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template x-if="!modalCard?.prices?.length">
                                        <tr>
                                            <td colspan="6" class="modal-td text-center"
                                                style="color:rgba(212,228,250,0.3); padding:1.5rem 0;">
                                                Nessun dato di prezzo disponibile
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>{{-- fine x-show contenuto --}}
            </div>
        </div>

    </div>
@endsection
