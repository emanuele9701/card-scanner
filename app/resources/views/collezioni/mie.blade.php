@extends('layouts.app')

@section('title', __('Le mie collezioni'))
@section('meta_description', __('Visualizza e gestisci le tue collezioni di carte.'))

@section('custom_style')
    <style>
        :root {
            --bg-main: #0f121b;
            --bg-card: #151923;
            --border-color: #272c3b;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --gold: #f59e0b;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
        }

        .stat-box {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-title {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .stat-badge {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }

        .series-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            margin-top: 40px;
        }

        .series-title {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .view-all {
            color: var(--text-muted);
            font-size: 14px;
            text-decoration: none;
        }

        .view-all:hover {
            color: #fff;
        }

        .set-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            transition: transform 0.2s, border-color 0.2s;
        }

        .set-card:hover {
            transform: translateY(-4px);
            border-color: #475569;
        }

        .set-card-image {
            height: 140px;
            background: linear-gradient(180deg, rgba(30,41,59,0) 0%, rgba(21,25,35,1) 100%), #1e293b;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .set-card-image img {
            max-height: 80px;
            max-width: 80%;
            object-fit: contain;
            opacity: 0.8;
            filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.5));
        }

        .set-card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .set-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .set-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .set-badge {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .progress-section {
            margin-bottom: 20px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .progress-text-white {
            color: #fff;
            font-weight: 500;
        }

        .progress-bar-container {
            height: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: var(--accent);
            border-radius: 2px;
        }

        .set-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .est-value-label {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .est-value-amount {
            color: var(--gold);
            font-size: 14px;
            font-weight: 600;
        }

    </style>
@endsection

@section('content')
    @php
        $collections = collect($collezioni);
        $series = $collections->groupBy(fn ($item) => $item->set?->serie?->name ?? __('Senza serie'));
        $ownedCards = 0;
        $totalSlots = 0;
        $totalValue = 0;
        $setCount = 0;
        $completedSets = 0;

        foreach ($collections->groupBy(fn ($item) => $item->set?->id ?? 0) as $setItems) {
            $set = $setItems->first()->set;
            if (! $set) continue;

            $setCount++;
            $ownedQty = $setItems->sum('quantity');
            $uniqueOwnedQty = $setItems->unique('card_id')->count();
            $cardTotal = $set->card_official ?? $set->card_total ?? 0;
            $totalSlots += $cardTotal;
            $ownedCards += $ownedQty;

            $progress = $cardTotal > 0 ? round($uniqueOwnedQty / $cardTotal * 100) : 0;
            if ($progress >= 100) {
                $completedSets++;
            }

            foreach ($setItems as $item) {
                $card = $item->card;
                if (! $card) continue;
                
                $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                $value = 0;
                if ($lastPrice) {
                    $isHolo = false;
                    if (is_array($item->variants)) {
                        $isHolo = in_array('holo', array_map('strtolower', $item->variants)) || in_array('reverse', array_map('strtolower', $item->variants));
                    }
                    $value = $isHolo ? ($lastPrice->trend_holo ?? $lastPrice->avg_holo ?? $lastPrice->trend ?? $lastPrice->avg ?? 0) : ($lastPrice->trend ?? $lastPrice->avg ?? 0);
                }
                $totalValue += $value * $item->quantity;
            }
        }
    @endphp

    <div class="container py-4" style="max-width: 1200px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-white fw-bold mb-0">{{ __('Le mie collezioni') }}</h1>
            @if(!$collections->isEmpty())
                <a href="{{ route('collezioni.mie.export') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 d-flex align-items-center gap-2">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ __('Esporta CSV') }}
                </a>
            @endif
        </div>
        
        @if($collections->isEmpty())
            {{-- Wizard / Empty State per PokeStash --}}
            <div class="row justify-content-center py-5 mt-4">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="stat-box text-center py-5" style="border: 1px dashed rgba(255,255,255,0.2); background: linear-gradient(180deg, rgba(30,41,59,0.5) 0%, rgba(21,25,35,0.8) 100%);">
                        <div class="mb-4">
                            <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color: #64748b; margin: 0 auto;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h2 class="h4 text-white fw-bold mb-3">{{ __('Benvenuto in PokeStash!') }}</h2>
                        <p class="text-secondary mb-4" style="line-height: 1.6;">{{ __('Il tuo caveau è ancora vuoto. Inizia a costruire la tua collezione cercando le tue carte preferite o esplorando i set ufficiali.') }}</p>
                        
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-2">
                            <a href="{{ route('collezioni.disponibili') }}" class="btn btn-primary px-4 py-2 rounded-pill" style="background-color: var(--accent); border: none; font-weight: 500;">
                                {{ __('Sfoglia i Set') }}
                            </a>
                            <a href="#" onclick="document.querySelector('input[name=q]').focus()" class="btn btn-outline-light px-4 py-2 rounded-pill" style="border-color: rgba(255,255,255,0.2); font-weight: 500;">
                                {{ __('Cerca una Carta') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
        {{-- Stats Row --}}
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-4">
                <div class="stat-box h-100">
                    <div class="stat-title">{{ __('VALORE TOTALE') }}</div>
                    <div class="stat-value text-warning">
                        € {{ number_format($totalValue, 2, ',', '.') }}
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="stat-box h-100">
                    <div class="stat-title">{{ __('CARTE TOTALI') }}</div>
                    <div class="stat-value">
                        {{ number_format($ownedCards, 0, ',', '.') }}
                        <span style="font-size: 14px; color: var(--text-muted); font-weight: 400;">/ {{ number_format($totalSlots, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress-bar-container mt-3">
                        <div class="progress-bar-fill" style="width: {{ $totalSlots > 0 ? min(100, round($ownedCards / $totalSlots * 100)) : 0 }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="stat-box h-100">
                    <div class="stat-title">{{ __('SET COMPLETATI') }}</div>
                    <div class="stat-value">
                        {{ $completedSets }}
                        <span style="font-size: 14px; color: var(--text-muted); font-weight: 400;">{{ __('Set') }}</span>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 10px; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ __('Stato collezione Master') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Series Sections --}}
        @foreach ($series as $serieName => $items)
            @php
                $sets = $items->groupBy(fn ($item) => $item->set?->id ?? 0);
            @endphp
            
            <div class="series-header">
                <h2 class="series-title">{{ $serieName }}</h2>
                <a href="#" class="view-all">{{ __('Vedi tutti') }}</a>
            </div>

            <div class="row g-4">
                @foreach ($sets as $setItems)
                    @php
                        $set = $setItems->first()->set;
                        if (!$set) continue;

                        $ownedQty = $setItems->sum('quantity');
                        $uniqueOwnedQty = $setItems->unique('card_id')->count();
                        $cardTotal = $set->card_total ?? 0;
                        $progress = $cardTotal > 0 ? min(100, round($uniqueOwnedQty / $cardTotal * 100)) : 0;
                        
                        $totalValueSet = $setItems->reduce(function ($carry, $item) {
                            $card = $item->card;
                            if (! $card) return $carry;
                            
                            $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                            $value = 0;
                            if ($lastPrice) {
                                $isHolo = false;
                                if (is_array($item->variants)) {
                                    $isHolo = in_array('holo', array_map('strtolower', $item->variants)) || in_array('reverse', array_map('strtolower', $item->variants));
                                }
                                $value = $isHolo ? ($lastPrice->trend_holo ?? $lastPrice->avg_holo ?? $lastPrice->trend ?? $lastPrice->avg ?? 0) : ($lastPrice->trend ?? $lastPrice->avg ?? 0);
                            }
                            return $carry + ($value * $item->quantity);
                        }, 0);
                    @endphp

                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="{{ route('collezioni.mie.set', $set) }}" class="set-card h-100">
                            <div class="set-card-image">
                                @if($set->logo)
                                    <img src="{{ $set->logo }}.png" alt="{{ $set->name }}">
                                @endif
                            </div>
                            <div class="set-card-content">
                                <div class="set-header">
                                    <h3 class="set-name">{{ $set->name }}</h3>
                                    @if($set->symbol)
                                        <div class="set-badge"><img src="{{ $set->symbol }}.png" alt="{{ __('Simbolo') }}" style="height: 1.5rem; object-fit: contain;"></div>
                                    @else
                                        <div class="set-badge">{{ __('N/D') }}</div>
                                    @endif
                                </div>

                                <div class="progress-section">
                                    <div class="progress-header">
                                        <span>{{ __('Collezione') }}</span>
                                        <span class="progress-text-white">{{ $uniqueOwnedQty }} / {{ $cardTotal }}</span>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill" style="width: {{ $progress }}%;"></div>
                                    </div>
                                </div>

                                <div class="set-footer">
                                    <span class="est-value-label">{{ __('VALORE STIMATO') }}</span>
                                    <span class="est-value-amount">€ {{ number_format($totalValueSet, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
