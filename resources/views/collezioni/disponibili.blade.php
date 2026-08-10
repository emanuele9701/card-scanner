@extends('layouts.app')

@section('title', __('Collezioni disponibili'))
@section('meta_description', __('Sfoglia tutte le collezioni di carte disponibili, organizzate per serie.'))

@section('content')

    <style>
        .set-card {
            display: flex;
            overflow: hidden;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background-color: rgba(30, 34, 44, 0.9);
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .set-card:hover {
            border-color: rgba(255, 255, 255, 0.14);
            background-color: rgba(40, 44, 56, 0.95);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(-1px);
        }

        .set-card:hover .set-symbol-panel {
            background-color: rgba(255, 255, 255, 0.07) !important;
        }

        .set-card:hover .set-symbol-img {
            opacity: 1;
            transform: scale(1.1);
        }

        .set-card:hover .set-name {
            color: #f87171 !important;
        }

        .set-card:hover .set-arrow {
            transform: translateX(2px);
        }

        .set-symbol-panel {
            width: 90px;
            min-height: 80px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.04);
            transition: background-color 0.25s;
        }

        .set-symbol-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            opacity: 0.65;
            transition: all 0.3s ease;
        }

        .set-info {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
        }

        .set-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.2s;
            margin: 0;
        }

        .set-meta {
            margin-top: 4px;
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .set-meta-date {
            margin-top: 2px;
            font-size: 0.75rem;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .set-arrow {
            margin-left: 8px;
            flex-shrink: 0;
            color: #374151;
            transition: transform 0.3s;
        }

        .serie-badge {
            background-color: rgba(255, 255, 255, 0.06);
            color: #9ca3af;
            border-radius: 9999px;
            padding: 0.125rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .serie-logo {
            height: 32px;
            max-width: 180px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }

        .empty-box {
            border: 1px dashed rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.02);
            border-radius: 1rem;
        }

        .empty-icon-wrap {
            width: 56px;
            height: 56px;
            background-color: rgba(16, 185, 129, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sets-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .sets-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .sets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container py-5" style="max-width: 1280px;">

        {{-- Page Header --}}
        @php
            $hasActiveFilters = request()->filled('search') || request()->filled('year') || (request()->has('sort') && request('sort') !== 'desc');
        @endphp

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="text-white fw-bold mb-1" style="font-size:1.875rem; letter-spacing:-0.02em;">{{ __('Collezioni disponibili') }}</h1>
                <p class="text-secondary mb-0" style="font-size:0.875rem;">{{ __('Esplora tutti i set di carte') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-light {{ $hasActiveFilters ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filterDrawer" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="filterDrawer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-funnel me-1" viewBox="0 0 16 16">
                        <path
                            d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .4.8l-4.5 6.5V13.5a.5.5 0 0 1-.8.4L7 10.7l-2.1 3.2a.5.5 0 0 1-.8-.4V8.8L1.6 1.8A.5.5 0 0 1 1.5 1.5z" />
                    </svg>
                    {{ __('Filtri') }}
                </button>
            </div>
        </div>

        {{-- Filter Drawer --}}
        <div class="collapse mb-4 {{ $hasActiveFilters ? 'show' : '' }}" id="filterDrawer">
            <div class="card card-body filter-card border" style="background: rgba(12, 19, 33, 0.96); border-color: rgba(255, 255, 255, 0.08);">
                <form id="filter-form" method="GET" action="{{ route('collezioni.disponibili') }}">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="search" class="form-label text-secondary small text-uppercase">{{ __('Nome set') }}</label>
                            <input id="search" name="search" type="text"
                                class="form-control bg-dark text-white border-secondary" value="{{ request('search') }}"
                                placeholder="{{ __('Cerca nome set...') }}" />
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="year" class="form-label text-secondary small text-uppercase">{{ __('Anno') }}</label>
                            <select id="year" name="year" class="form-select bg-dark text-white border-secondary">
                                <option value="">{{ __('Tutti gli anni') }}</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="sort" class="form-label text-secondary small text-uppercase">{{ __('Ordina') }}</label>
                            <select id="sort" name="sort" class="form-select bg-dark text-white border-secondary">
                                <option value="desc" @selected(request('sort', 'desc') === 'desc')>{{ __('Anno decrescente') }}</option>
                                <option value="asc" @selected(request('sort') === 'asc')>{{ __('Anno crescente') }}</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center gap-2 mt-3">
                            <button type="submit" class="btn btn-primary px-4 fw-bold">{{ __('Applica') }}</button>
                            <a href="{{ route('collezioni.disponibili') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sets Grid --}}
        @if ($sets->isEmpty())
            <div class="empty-box d-flex align-items-center justify-content-center p-5">
                <div class="d-flex flex-column align-items-center gap-3 text-center">
                    <div class="empty-icon-wrap">
                        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#34d399"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-white fw-semibold mb-1" style="font-size:1.25rem;">{{ __('Nessuna collezione trovata') }}</p>
                        <p class="text-secondary mb-0" style="font-size:0.875rem;">{{ __('Prova a modificare i filtri di ricerca.') }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="sets-grid">
                @foreach ($sets as $set)
                    <a href="{{ route('collezioni.set', $set) }}" id="set-card-{{ $set->id }}" class="set-card">
                        {{-- Left — Symbol Panel --}}
                        <div class="set-symbol-panel">
                            @if ($set->symbol)
                                <img src="{{ $set->symbol }}.png" alt="{{ $set->name }}" class="set-symbol-img">
                            @else
                                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#4b5563"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            @endif
                        </div>

                        {{-- Right — Info --}}
                        <div class="set-info">
                            <div style="min-width:0;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <p class="set-name">{{ $set->name }}</p>
                                    @if(isset($language) && $set->language && $set->language !== $language)
                                        @php
                                            $flags = ['it' => '🇮🇹', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'es' => '🇪🇸', 'jp' => '🇯🇵', 'ja' => '🇯🇵'];
                                            $flag = $flags[strtolower($set->language)] ?? strtoupper($set->language);
                                        @endphp
                                        <span title="Disponibile solo in {{ strtoupper($set->language) }}" style="font-size: 0.9rem; line-height: 1;">{{ $flag }}</span>
                                    @endif
                                </div>
                                
                                @if($set->serie)
                                    <div class="serie-badge mt-1 d-inline-block">{{ $set->serie->name }}</div>
                                @endif

                                <p class="set-meta mt-1">
                                    @if ($set->card_total)
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            {{ $set->card_total }} {{ __('carte') }}
                                        </span>
                                    @endif
                                </p>

                                @if ($set->release_date)
                                    <p class="set-meta-date">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ $set->release_date->translatedFormat('d M Y') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Arrow --}}
                            <svg class="set-arrow" width="16" height="16" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 d-flex justify-content-center">
                {{ $sets->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
