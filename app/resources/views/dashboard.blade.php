@extends('layouts.app')

@section('title', __('Dashboard'))
@section('meta_description', __('Dashboard — panoramica della tua collezione di carte.'))

@section('custom_style')
<style>
    .stat-card {
        background-color: rgba(30, 34, 44, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 1rem;
        backdrop-filter: blur(10px);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border-color: rgba(255, 255, 255, 0.1);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .progress {
        height: 8px;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar {
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        border-radius: 4px;
    }
    .set-card {
        background-color: rgba(30, 34, 44, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 0.75rem;
        transition: all 0.25s ease;
    }
    .set-card:hover {
        border-color: rgba(255, 255, 255, 0.14);
        background-color: rgba(40, 44, 56, 0.95);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    .set-symbol-container {
        width: 80px;
        background-color: rgba(255, 255, 255, 0.03);
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Stile per le Top Cards */
    .top-card-item {
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
        background: linear-gradient(135deg, #142135 0%, #0b1522 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
    }
    .top-card-item:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 35px rgba(251, 180, 0, 0.15), 0 5px 15px rgba(0,0,0,0.4);
        border-color: rgba(251, 180, 0, 0.3);
    }
    .top-card-item::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.1), transparent 60%);
        pointer-events: none;
    }
    .top-card-img-wrapper {
        position: relative;
        width: 100%;
        padding-top: 139%; /* Aspect ratio carte Pokémon */
        margin-bottom: 1rem;
    }
    .top-card-img-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
    }
    .top-card-price-badge {
        background: linear-gradient(90deg, #fbb400, #f59e0b);
        color: #1b1100;
        font-weight: 800;
        padding: 0.35rem 0.8rem;
        border-radius: 999px;
        font-size: 0.85rem;
        box-shadow: 0 4px 10px rgba(251, 180, 0, 0.25);
        margin-top: auto;
    }

    /* Empty State Styles */
    .empty-state-container {
        min-height: 60vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        position: relative;
        padding: 3rem 1rem;
    }
    .empty-hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        background: linear-gradient(135deg, #fff, #a5b4fc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1rem;
        z-index: 10;
    }
    .empty-hero-subtitle {
        font-size: 1.1rem;
        color: #9ca3af;
        max-width: 550px;
        margin-bottom: 3.5rem;
        z-index: 10;
        line-height: 1.6;
    }
    .cta-cards-container {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        justify-content: center;
        z-index: 10;
    }
    .cta-card {
        background: linear-gradient(145deg, rgba(30, 34, 44, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1.25rem;
        padding: 2.5rem 2rem;
        width: 280px;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
    }
    .cta-card:hover {
        transform: translateY(-10px);
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(99, 102, 241, 0.2);
    }
    .cta-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 60%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .cta-card:hover::before {
        opacity: 1;
    }
    .cta-icon-wrapper {
        width: 72px;
        height: 72px;
        margin: 0 auto 1.5rem auto;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.05);
        transition: transform 0.3s;
    }
    .cta-card:hover .cta-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }
    .cta-title {
        color: #fff;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }
    .cta-desc {
        color: #9ca3af;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .bg-glow {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        pointer-events: none;
    }
</style>
@endsection

@section('content')
<div class="container py-5" style="max-width: 1280px;">

    @if($totalCardsOwned == 0)
        {{-- EMPTY STATE UI --}}
        <div class="empty-state-container">
            <div class="bg-glow"></div>
            <h1 class="empty-hero-title">{{ __('La tua collezione aspetta di essere riempita!') }}</h1>
            <p class="empty-hero-subtitle">{{ __('Ogni grande collezione inizia da una singola carta. Aggiungi la tua prima carta e guarda le tue statistiche prendere vita.') }}</p>
            
            <div class="cta-cards-container">
                {{-- CTA 1: Cerca --}}
                <a href="#" onclick="document.getElementById('nav-search-input').focus(); return false;" class="cta-card">
                    <div class="cta-icon-wrapper" style="color: #60a5fa;">
                        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="cta-title">{{ __('Cerca una carta') }}</h3>
                    <p class="cta-desc">{{ __('Usa la barra di ricerca in alto per trovare e aggiungere istantaneamente qualsiasi carta.') }}</p>
                </a>

                {{-- CTA 2: Espansioni --}}
                <a href="{{ route('collezioni.disponibili') }}" class="cta-card">
                    <div class="cta-icon-wrapper" style="color: #34d399;">
                        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="cta-title">{{ __('Sfoglia i Set') }}</h3>
                    <p class="cta-desc">{{ __('Esplora tutte le espansioni ufficiali e inizia a completare il tuo primo set.') }}</p>
                </a>
            </div>
        </div>
    @else
        {{-- NORMAL DASHBOARD --}}
        {{-- Top Section: Stats (Left) & Top Cards Carousel (Right) --}}
        <div class="row g-4 mb-5">
        
        {{-- Colonna SX: Statistiche --}}
        <div class="col-12 col-lg-6 d-flex flex-column">
            
            {{-- Page Header --}}
            <div class="mb-4">
                <h1 class="text-white fw-bold mb-1" style="font-size:1.875rem; letter-spacing:-0.02em;">{{ __('La mia Dashboard') }}</h1>
                <p class="text-secondary mb-0" style="font-size:0.875rem;">{{ __('Panoramica e statistiche della tua collezione') }}</p>
            </div>

            <div class="row g-3">
                {{-- Set Posseduti --}}
                <div class="col-12 col-md-6">
                    <div class="stat-card p-4 d-flex flex-column align-items-start gap-3 h-100">
                        <div class="stat-icon" style="background-color: rgba(99, 102, 241, 0.15);">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-secondary mb-1 fw-medium" style="font-size: 0.85rem;">{{ __('Set Posseduti') }}</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $totalSetsOwned }}</h3>
                        </div>
                    </div>
                </div>

                {{-- Carte Totali --}}
                <div class="col-12 col-md-6">
                    <div class="stat-card p-4 d-flex flex-column align-items-start gap-3 h-100">
                        <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.15);">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#34d399" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-secondary mb-1 fw-medium" style="font-size: 0.85rem;">{{ __('Carte Totali') }}</p>
                            <h3 class="text-white mb-0 fw-bold">{{ $totalCardsOwned }}</h3>
                        </div>
                    </div>
                </div>

                {{-- Valore Stimato --}}
                <div class="col-12 col-md-6">
                    <div class="stat-card p-4 d-flex flex-column align-items-start gap-3 h-100">
                        <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.15);">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#fbbf24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-secondary mb-1 fw-medium" style="font-size: 0.85rem;">{{ __('Valore Stimato') }}</p>
                            <h3 class="text-white mb-0 fw-bold">€ {{ number_format($totalEstimatedValue, 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonna DX: Top Cards Carousel --}}
        <div class="col-12 col-lg-6 d-flex flex-column align-items-center mt-lg-0 mt-4">
            @if(isset($topCards) && $topCards->count() > 0)
                <h3 class="text-white fw-bold mb-4 d-flex align-items-center gap-2 align-self-lg-start align-self-center" style="font-size:1.15rem;">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#fbb400" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    {{ __('Le tue carte più preziose') }}
                </h3>
                
                <div id="topCardsCarousel" class="carousel slide position-relative w-100 d-flex justify-content-center" data-bs-ride="carousel" data-bs-interval="3000" style="max-width: 320px;">
                    <div class="carousel-inner pb-5 w-100 px-4">
                        @foreach($topCards as $index => $top)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="d-flex justify-content-center">
                                <div class="top-card-item" style="width: 200px;" onclick="window.location='{{ route('cards.search', ['q' => $top['card']->name . ' ' . $top['card']->dexId]) }}'">
                                    <div class="top-card-img-wrapper" style="padding-top: 139%;">
                                        @if($top['card'] && $top['card']->url_image)
                                            <img src="{{ $top['card']->url_image }}/low.png" alt="{{ $top['card']->name }}" loading="lazy" onerror="this.style.display='none'">
                                        @else
                                            <div class="bg-dark rounded d-flex flex-column align-items-center justify-content-center w-100 h-100 border border-secondary text-secondary" style="position: absolute; top: 0; left: 0;">
                                                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="small mt-2" style="font-size:0.75rem;">{{ __('No img') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <h6 class="text-white mb-1 w-100 text-truncate fw-bold" style="font-size: 0.95rem;" title="{{ $top['card']?->name }}">{{ $top['card']?->name ?? __('Sconosciuta') }}</h6>
                                    <p class="text-secondary mb-3 w-100 text-truncate" style="font-size: 0.75rem;">{{ $top['card']?->set?->name ?? '' }}</p>
                                    <div class="mt-auto w-100">
                                        <div class="top-card-price-badge" style="font-size: 0.9rem; padding: 0.35rem 0.8rem;">€ {{ number_format($top['total_price'], 2, ',', '.') }}</div>
                                        @if($top['quantity'] > 1)
                                            <div class="text-secondary mt-2" style="font-size: 0.7rem;">({{ $top['quantity'] }} copie a €{{ number_format($top['unit_price'], 2, ',', '.') }})</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if(count($topCards) > 1)
                    <div class="carousel-indicators" style="bottom: 0px;">
                        @foreach($topCards as $index => $top)
                            <button type="button" data-bs-target="#topCardsCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    
                    <button class="carousel-control-prev" type="button" data-bs-target="#topCardsCarousel" data-bs-slide="prev" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; top: 50%; transform: translateY(-50%); left: -10px;">
                        <span class="carousel-control-prev-icon" aria-hidden="true" style="width: 1rem; height: 1rem;"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#topCardsCarousel" data-bs-slide="next" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; top: 50%; transform: translateY(-50%); right: -10px;">
                        <span class="carousel-control-next-icon" aria-hidden="true" style="width: 1rem; height: 1rem;"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    @endif
                </div>
            @else
                <div class="text-center p-5 stat-card w-100">
                    <p class="text-secondary mb-0">{{ __('Nessuna carta preziosa trovata.') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Progress Collections (Priorità Media) --}}
    <div class="mb-4" x-data="{ showAll: false }">
        <h3 class="text-white fw-bold mb-4" style="font-size:1.25rem;">{{ __('Stato completamento set') }}</h3>
        
        @if($setsStats->isEmpty())
            <div class="text-center p-5 stat-card">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" class="mb-3 mx-auto">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h4 class="text-white fw-medium mb-2">{{ __('Nessun set in collezione') }}</h4>
                <p class="text-secondary mb-0">{{ __('Inizia ad aggiungere carte alla tua collezione per visualizzare le statistiche.') }}</p>
                <a href="{{ route('collezioni.disponibili') }}" class="btn btn-outline-light mt-4 rounded-pill px-4">{{ __('Sfoglia Set') }}</a>
            </div>
        @else
            <div class="row g-3">
                @foreach($setsStats as $index => $stat)
                <div class="col-12 col-xl-6" x-show="showAll || {{ $index }} < 4" x-transition.opacity>
                    <div class="set-card d-flex h-100 overflow-hidden text-decoration-none">
                        <div class="set-symbol-container py-3">
                            @if($stat['symbol'])
                                <img src="{{ $stat['symbol'] }}.png" alt="Simbolo" class="img-fluid px-2" style="max-height: 40px; opacity: 0.8;" onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="text-white mb-1 fw-semibold" style="font-size: 1rem;">{{ $stat['name'] }}</h5>
                                    <p class="text-secondary mb-0" style="font-size: 0.75rem;">{{ __('Valore:') }} <strong class="text-light">€ {{ number_format($stat['estimated_value'], 2, ',', '.') }}</strong></p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-dark border border-secondary text-light fw-normal">
                                        {{ $stat['unique_cards'] }} / {{ $stat['official_cards'] > 0 ? $stat['official_cards'] : '?' }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="progress flex-grow-1">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $stat['completion_percentage'] }}%" aria-valuenow="{{ $stat['completion_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-white fw-medium" style="font-size: 0.875rem; min-width: 40px; text-align: right;">
                                    {{ number_format($stat['completion_percentage'], 1, ',', '') }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            @if(count($setsStats) > 4)
            <div class="text-center mt-4">
                <button @click="showAll = !showAll" type="button" class="btn btn-outline-secondary rounded-pill px-4 text-white border-secondary" style="font-size: 0.875rem;">
                    <span x-show="!showAll">{{ __('Mostra tutti gli altri set') }} ({{ count($setsStats) - 4 }})</span>
                    <span x-show="showAll" x-cloak>{{ __('Mostra meno') }}</span>
                </button>
            </div>
            @endif
        @endif
    </div>
    @endif
</div>
@endsection
