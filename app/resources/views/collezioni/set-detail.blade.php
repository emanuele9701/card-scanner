@extends('layouts.app')
@section('title', $set->name)
@section('meta_description', 'Dettaglio del set ' . $set->name)

@section('content')
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
    }" class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('collezioni.disponibili') }}" class="transition hover:text-white">Collezioni disponibili</a>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            @if ($set->serie)
                <span class="text-gray-500">{{ $set->serie->name }}</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
            <span class="text-white">{{ $set->name }}</span>
        </nav>

        {{-- Page Header --}}
        <div class="mb-10 flex items-center space-x-10">
            @if ($set->symbol)
                <div
                    class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl border border-white/[0.08] bg-white/[0.04]">
                    <img src="{{ $set->symbol }}.png" alt="Simbolo {{ $set->name }}" class="h-8 w-8 object-contain">
                </div>
            @endif
            <div style="margin-left: 10px;">
                <h1 class="text-3xl font-bold tracking-tight text-white">{{ $set->name }}</h1>
                @if ($set->serie)
                    <p class="mt-1 text-sm text-gray-500">{{ $set->serie->name }}</p>
                @endif
            </div>
        </div>

        {{-- Grid carte --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4">
            @foreach ($set->cards as $card)
                @include('collezioni.singles.cards', ['card' => $card])
            @endforeach
        </div>

        {{-- Bottone flottante selezione multipla --}}
        <div x-show="selected.length > 0" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4" class="fixed bottom-8 left-1/2 z-50 -translate-x-1/2"
            style="display:none">
            <div
                class="flex items-center gap-3 rounded-full border border-[#fbb400]/30 bg-[#051424]/90 px-5 py-3 shadow-[0_0_32px_rgba(251,180,0,0.2)] backdrop-blur-xl">
                <span class="text-sm font-semibold text-[#ffd795]">
                    <span x-text="selected.length"></span> carte selezionate
                </span>
                <div class="h-4 w-px bg-white/10"></div>
                <button @click="addToCollection(selected)"
                    class="flex items-center gap-2 rounded-full bg-[#fbb400] px-4 py-1.5 text-sm font-bold text-[#422c00] transition hover:bg-[#ffd795]">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Aggiungi alla collezione
                </button>
                <button @click="selected = []" class="rounded-full p-1.5 text-[#d4e4fa]/40 transition hover:text-[#d4e4fa]">
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
            @keydown.escape.window="modalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none">
            <div @click="modalOpen = false" class="absolute inset-0 bg-[#010f1f]/80 backdrop-blur-sm"></div>
            <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative z-10 w-full max-w-lg rounded-2xl border border-white/[0.08] bg-[#122131] p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-[#d4e4fa]" x-text="modalCard?.name"></h2>
                    <button @click="modalOpen = false"
                        class="rounded-lg p-1.5 text-[#d4e4fa]/40 transition hover:text-[#d4e4fa]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div
                    class="flex h-48 items-center justify-center rounded-xl border border-dashed border-white/10 text-sm text-[#d4e4fa]/30">
                    Dettagli carta — coming soon
                </div>
            </div>
        </div>
    </div>
@endsection
