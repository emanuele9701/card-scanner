@extends('layouts.app')

@section('title', 'Collezioni disponibili')
@section('meta_description', 'Sfoglia tutte le collezioni di carte disponibili, organizzate per serie.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Page Header --}}
    <div class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight text-white">Collezioni disponibili</h1>
        <p class="mt-2 text-sm text-gray-500">Esplora tutti i set organizzati per serie</p>
    </div>

    @forelse ($series as $serie)
    {{-- Serie Section --}}
    <section class="mb-12" id="serie-{{ $serie->id }}">

        {{-- Serie Header --}}
        <div class="mb-6 mt-6 flex items-center gap-4">
            @if($serie->logo)
            <img src="{{ $serie->logo }}.png" alt="{{ $serie->name }}"
                class="h-8 max-w-[180px] object-contain brightness-0 invert opacity-70">
            @endif
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-white">{{ $serie->name }}</h2>
                <span class="rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-medium text-gray-400">
                    {{ $serie->sets->count() }} set
                </span>
            </div>
        </div>

        {{-- Sets Grid — 3 columns --}}
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            @foreach ($serie->sets as $set)
            <a href="{{ route('collezioni.set', $set) }}" id="set-card-{{ $set->id }}"
                class="group"
                style="display: flex; overflow: hidden; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.06); background: rgba(30,34,44,0.9); text-decoration: none; transition: all 0.25s ease;">

                {{-- Left — Symbol Panel --}}
                <div style="width: 90px; min-height: 80px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.04);">
                    @if($set->symbol)
                    <img src="{{ $set->symbol }}.png" alt="{{ $set->name }}"
                        style="width: 48px; height: 48px; object-fit: contain; opacity: 0.65; transition: all 0.3s ease;"
                        class="group-hover:opacity-100 group-hover:scale-110">
                    @else
                    <svg style="width: 32px; height: 32px; color: #4b5563;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    @endif
                </div>

                {{-- Right — Info --}}
                <div style="flex: 1; display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem;">
                    <div style="min-width: 0;">
                        {{-- Set Name --}}
                        <h3 class="group-hover:text-red-400" style="font-size: 0.875rem; font-weight: 600; color: #fff; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.2s;">
                            {{ $set->name }}
                        </h3>

                        {{-- Stats --}}
                        <p style="margin-top: 4px; font-size: 0.75rem; color: #6b7280; display: flex; align-items: center; gap: 6px;">
                            @if($set->card_total)
                            <span style="display: inline-flex; align-items: center; gap: 3px;">
                                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                {{ $set->card_total }} carte
                            </span>
                            @endif
                            @if($set->card_official)
                            <span style="color: #374151;">·</span>
                            <span>{{ $set->card_official }} ufficiali</span>
                            @endif
                        </p>

                        @if($set->release_date)
                        <p style="margin-top: 2px; font-size: 0.75rem; color: #4b5563; display: flex; align-items: center; gap: 4px;">
                            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $set->release_date->translatedFormat('d M Y') }}
                        </p>
                        @endif
                    </div>

                    {{-- Arrow --}}
                    <svg class="group-hover:translate-x-0.5" style="margin-left: 8px; width: 16px; height: 16px; flex-shrink: 0; color: #374151; transition: all 0.3s;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @empty
    {{-- Empty State --}}
    <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/[0.1] bg-white/[0.02] p-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/10">
                <svg class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-semibold text-white">Nessuna collezione disponibile</p>
                <p class="mt-1 text-sm text-gray-500">I set verranno visualizzati qui una volta importati.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>

<style>
    /* Responsive grid fallback */
    @media (max-width: 900px) {
        section>div[style*="grid-template-columns"] {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (max-width: 600px) {
        section>div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }

    /* Card hover effects */
    a.group:hover {
        border-color: rgba(255, 255, 255, 0.14) !important;
        background: rgba(40, 44, 56, 0.95) !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    a.group:hover div:first-child {
        background: rgba(255, 255, 255, 0.07) !important;
    }
</style>
@endsection