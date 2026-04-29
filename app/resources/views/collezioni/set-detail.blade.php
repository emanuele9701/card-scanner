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

        /* Selection bar */
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

        /* Modal */
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

        .modal-placeholder {
            display: flex;
            height: 192px;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            border: 1px dashed rgba(255, 255, 255, 0.1);
            font-size: 0.875rem;
            color: rgba(212, 228, 250, 0.3);
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
    </style>

    <div x-data="{
        selected: [],
        modalOpen: false,
        modalCard: null,
    
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
    
        openModal(card) {
            this.modalCard = card;
            this.modalOpen = true;
        },
    
        async addToCollection(cardIds) {
            await fetch('/api/collezione/aggiungi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ card_ids: cardIds }),
            });
            this.selected = [];
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
                <h1 class="text-white fw-bold mb-0" style="font-size:1.875rem; letter-spacing:-0.02em;">{{ $set->name }}
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
                <button @click="addToCollection(selected)" class="btn-add-selection">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Aggiungi alla collezione
                </button>
                <button @click="selected = []" class="btn-clear-selection">
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
            @keydown.escape.window="modalOpen = false" class="modal-overlay" style="display:none;">
            <div @click="modalOpen = false" class="position-absolute inset-0 w-100 h-100"></div>
            <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" class="card-modal-content p-4 position-relative"
                style="z-index:10;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;" x-text="modalCard?.name"></h2>
                    <button @click="modalOpen = false" class="btn-modal-close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="modal-placeholder">
                    Dettagli carta — coming soon
                </div>
            </div>
        </div>

    </div>
@endsection
