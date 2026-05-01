@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Dashboard — panoramica della tua collezione di carte.')

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
</style>
@endsection

@section('content')
<div class="container py-5" style="max-width: 1280px;">

    {{-- Page Header --}}
    <div class="mb-5">
        <h1 class="text-white fw-bold mb-1" style="font-size:1.875rem; letter-spacing:-0.02em;">La mia Dashboard</h1>
        <p class="text-secondary mb-0" style="font-size:0.875rem;">Panoramica e statistiche della tua collezione</p>
    </div>

    {{-- Stats Row --}}
    <div class="row g-4 mb-5">
        {{-- Set Posseduti --}}
        <div class="col-12 col-md-4">
            <div class="stat-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="stat-icon" style="background-color: rgba(99, 102, 241, 0.15);">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#818cf8" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <p class="text-secondary mb-1 fw-medium" style="font-size: 0.875rem;">Set Posseduti</p>
                    <h3 class="text-white mb-0 fw-bold">{{ $totalSetsOwned }}</h3>
                </div>
            </div>
        </div>

        {{-- Carte Totali --}}
        <div class="col-12 col-md-4">
            <div class="stat-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.15);">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#34d399" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-secondary mb-1 fw-medium" style="font-size: 0.875rem;">Carte Totali</p>
                    <h3 class="text-white mb-0 fw-bold">{{ $totalCardsOwned }}</h3>
                </div>
            </div>
        </div>

        {{-- Valore Stimato --}}
        <div class="col-12 col-md-4">
            <div class="stat-card p-4 h-100 d-flex align-items-center gap-3">
                <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.15);">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#fbbf24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-secondary mb-1 fw-medium" style="font-size: 0.875rem;">Valore Stimato</p>
                    <h3 class="text-white mb-0 fw-bold">€ {{ number_format($totalEstimatedValue, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Collections --}}
    <div class="mb-4">
        <h3 class="text-white fw-bold mb-4" style="font-size:1.25rem;">Stato completamento set</h3>
        
        @if($setsStats->isEmpty())
            <div class="text-center p-5 stat-card">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" class="mb-3 mx-auto">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h4 class="text-white fw-medium mb-2">Nessun set in collezione</h4>
                <p class="text-secondary mb-0">Inizia ad aggiungere carte alla tua collezione per visualizzare le statistiche.</p>
                <a href="{{ route('collezioni.disponibili') }}" class="btn btn-outline-light mt-4 rounded-pill px-4">Sfoglia Set</a>
            </div>
        @else
            <div class="row g-3">
                @foreach($setsStats as $stat)
                <div class="col-12 col-xl-6">
                    <div class="set-card d-flex h-100 overflow-hidden text-decoration-none">
                        <div class="set-symbol-container py-3">
                            @if($stat['symbol'])
                                <img src="{{ $stat['symbol'] }}.png" alt="Simbolo" class="img-fluid px-2" style="max-height: 40px; opacity: 0.8;">
                            @endif
                        </div>
                        <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="text-white mb-1 fw-semibold" style="font-size: 1rem;">{{ $stat['name'] }}</h5>
                                    <p class="text-secondary mb-0" style="font-size: 0.75rem;">Valore: <strong class="text-light">€ {{ number_format($stat['estimated_value'], 2, ',', '.') }}</strong></p>
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
        @endif
    </div>

</div>
@endsection
