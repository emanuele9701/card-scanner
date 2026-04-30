@extends('layouts.app')

@section('title', 'Le mie collezioni')
@section('meta_description', 'Visualizza e gestisci le tue collezioni di carte.')

@section('custom_style')
    <style>
        :root {
            color-scheme: dark;
            background-color: #0b1326;
            color: #dae2fd;
        }

        .font-h1 {
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .font-h2 {
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 600;
            line-height: 1.3;
        }

        .font-h3 {
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
        }

        .font-body-lg {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }

        .font-body-sm {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .font-label-caps {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .font-stat-value {
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .bg-surface-container {
            background-color: #171f33;
        }

        .bg-surface-container-high {
            background-color: #222a3d;
        }

        .bg-surface-container-highest {
            background-color: #2d3449;
        }

        .bg-primary {
            background-color: #adc6ff;
        }

        .text-on-primary {
            color: #002e6a;
        }

        .text-on-surface {
            color: #dae2fd;
        }

        .text-outline {
            color: #8c909f;
        }

        .text-tertiary {
            color: #ffb95f;
        }

        .border-outline-variant {
            border-color: #424754;
        }

        .rounded-xl {
            border-radius: 1rem;
        }

        .rounded-lg {
            border-radius: 0.75rem;
        }

        .rounded-full {
            border-radius: 9999px;
        }

        .section-header {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .section-description {
            margin-top: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            color: #8c909f;
            max-width: 620px;
        }

        .search-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: rgba(30, 41, 59, 0.95);
            border: 1px solid #334155;
            border-radius: 0.75rem;
            padding: 12px 16px;
            width: 100%;
            max-width: 420px;
        }

        .search-card input {
            background: transparent;
            border: none;
            outline: none;
            color: #dae2fd;
            width: 100%;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .search-card input::placeholder {
            color: #8c909f;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: #adc6ff;
            color: #002e6a;
            border-radius: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: filter 0.2s, transform 0.2s;
            text-decoration: none;
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .stats-grid {
            display: grid;
            gap: 20px;
        }

        .stat-card {
            padding: 20px;
            border-radius: 1rem;
            border: 1px solid #424754;
            background-color: #171f33;
        }

        .stat-card strong {
            display: block;
            margin-top: 10px;
        }

        .progress-track {
            height: 8px;
            background-color: #131b2e;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 100%;
            background-color: #adc6ff;
            border-radius: 9999px;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .section-heading h2 {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 600;
        }

        .section-heading span {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #8c909f;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .set-card {
            border-radius: 1rem;
            border: 1px solid #334155;
            background-color: #222a3d;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            min-height: 220px;
            color: #dae2fd;
            text-align: left;
            width: 100%;
        }

        .set-card:hover {
            transform: translateY(-4px);
            border-color: #adc6ff;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
        }

        .set-card-meta {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .set-card-logo {
            width: 64px;
            height: 80px;
            border-radius: 1rem;
            border: 1px solid #334155;
            background-color: #2d3449;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .set-card-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .set-card-title {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 600;
        }

        .set-card-series {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8c909f;
        }

        .set-card-footer {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .set-card-foot {
            background-color: #171f33;
            border: 1px solid #334155;
            border-radius: 1rem;
            padding: 14px;
        }

        .set-card-foot strong {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #ffb95f;
            margin-bottom: 6px;
        }

        .set-card-foot small {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            color: #8c909f;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .set-card-progress {
            height: 8px;
            border-radius: 9999px;
            background-color: #131b2e;
            overflow: hidden;
            margin-top: 10px;
        }

        .set-card-progress-bar {
            height: 100%;
            background-color: #adc6ff;
            border-radius: 9999px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 19, 38, 0.95);
            backdrop-filter: blur(16px);
            z-index: 1100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.is-open {
            display: flex;
        }

        .modal-panel {
            width: min(900px, 100%);
            max-height: 85vh;
            border-radius: 1rem;
            border: 1px solid #334155;
            background-color: #171f33;
            overflow: hidden;
            box-shadow: 0 22px 44px rgba(0, 0, 0, 0.3);
        }

        .modal-body {
            padding: 18px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 18px;
            border-bottom: 1px solid #334155;
        }

        .modal-title {
            margin: 0 0 8px;
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 700;
        }

        .modal-subtitle {
            margin: 0;
            color: #8c909f;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .modal-close {
            border: none;
            background: transparent;
            color: #dae2fd;
            width: 36px;
            height: 36px;
            border-radius: 0.75rem;
            cursor: pointer;
            display: grid;
            place-items: center;
        }

        .modal-cards {
            display: grid;
            gap: 16px;
            padding: 18px;
        }

        .modal-card {
            background-color: #222a3d;
            border: 1px solid #334155;
            border-radius: 1rem;
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        .modal-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 0.75rem;
            background: #1b2339;
        }

        .modal-card-title {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: #dae2fd;
        }

        .modal-card-subtitle {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #8c909f;
        }

        .badge-pill {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            font-size: 12px;
            color: #dae2fd;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 9999px;
            padding: 8px 12px;
            border: 1px solid #334155;
        }

        .empty-state {
            border: 1px dashed rgba(255, 255, 255, 0.14);
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 1rem;
            padding: 40px 30px;
            text-align: center;
            color: #dae2fd;
        }

        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .modal-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .modal-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
@endsection

@section('content')
    @php
        $collections = collect($collezioni);
        $series = $collections->groupBy(fn ($item) => $item->set?->serie?->name ?? 'Senza serie');
        $ownedCards = 0;
        $totalSlots = 0;
        $totalValue = 0;
        $setCount = 0;
        $completedSets = 0;
        $nextSetName = null;
        $bestNextProgress = -1;

        foreach ($collections->groupBy(fn ($item) => $item->set?->id ?? 0) as $setItems) {
            $set = $setItems->first()->set;
            if (! $set) {
                continue;
            }

            $setCount++;
            $ownedQty = $setItems->sum('quantity');
            $cardTotal = $set->card_total ?? 0;
            $totalSlots += $cardTotal;
            $ownedCards += $ownedQty;

            $progress = $cardTotal > 0 ? round($ownedQty / $cardTotal * 100) : 0;
            if ($progress >= 100) {
                $completedSets++;
            }

            if ($progress < 100 && $progress > $bestNextProgress) {
                $bestNextProgress = $progress;
                $nextSetName = $set->name;
            }

            foreach ($setItems as $item) {
                $card = $item->card;
                if (! $card) {
                    continue;
                }
                $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                $value = $lastPrice?->avg ?? $lastPrice?->trend ?? 0;
                $totalValue += $value * $item->quantity;
            }
        }

        $overallProgress = $totalSlots > 0 ? round($ownedCards / $totalSlots * 100) : 0;
        $now = now();
    @endphp

    <main class="container" style="max-width: 1440px;">
        <section class="mb-5">
            <div class="section-header">
                <div>
                    <p class="font-label-caps text-outline mb-2">Le mie collezioni</p>
                    <h1 class="section-title">Gestisci il tuo portfolio di carte</h1>
                    <p class="section-description">Una vista chiara delle tue serie, il progresso dei set e il valore totale della collezione.</p>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
                    <div class="search-card">
                        <span class="material-symbols-outlined text-outline" style="font-size:18px;">search</span>
                        <input type="text" placeholder="Cerca un set..." />
                    </div>
                    <a href="{{ route('collezioni.disponibili') }}" class="btn-primary">AGGIUNGI SET</a>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">VALORE TOTALE</p>
                <p class="font-stat-value text-tertiary">€ {{ number_format($totalValue, 2, ',', '.') }}</p>
                <div style="margin-top:14px; display:flex; gap:8px; align-items:center; color:#adc6ff; font-size:11px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">trending_up</span>
                    <span>+4.2% questo mese</span>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">CARTE TOTALI</p>
                <p class="font-stat-value text-on-surface">{{ number_format($ownedCards, 0, ',', '.') }} <span class="font-body-sm text-outline" style="font-weight:400;">/ {{ number_format($totalSlots, 0, ',', '.') }}</span></p>
                <div class="progress-track">
                    <div class="progress-bar" style="width: {{ $overallProgress }}%;"></div>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">SET COMPLETATI</p>
                <p class="font-stat-value text-on-surface">{{ $completedSets }} <span class="font-body-sm text-outline" style="font-weight:400;">Sets</span></p>
                <div style="margin-top:14px; display:flex; gap:8px; align-items:center; color:#ffb95f; font-size:11px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">military_tech</span>
                    <span>Prossimo: {{ $nextSetName ?? 'Nessuno' }} ({{ $bestNextProgress }}%)</span>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">ULTIMO AGGIORNAMENTO</p>
                <p class="font-stat-value text-on-surface">{{ $now->translatedFormat('d F Y') }}</p>
                <p class="text-xs text-outline" style="margin-top:12px; font-weight:600; text-transform:uppercase;">Sync: {{ $now->format('H:i') }}</p>
            </div>
        </section>

        @if ($collections->isEmpty())
            <div class="empty-state">
                <h2 class="font-h3" style="margin-bottom:12px;">Nessuna collezione salvata</h2>
                <p class="font-body-lg" style="color:#8c909f;">Inizia ad aggiungere set alla collezione per vedere il progresso, il valore e le carte possedute.</p>
            </div>
        @else
            @foreach ($series as $serieName => $items)
                @php
                    $sets = $items->groupBy(fn ($item) => $item->set?->id ?? 0);
                @endphp

                <section class="mb-8">
                    <div class="section-heading">
                        <h2>{{ $serieName }}</h2>
                        <span>{{ $sets->count() }} set</span>
                    </div>
                    <div class="modal-cards" style="grid-template-columns: repeat(1, minmax(0, 1fr));">
                        @foreach ($sets as $setItems)
                            @php
                                $set = $setItems->first()->set;
                                $ownedQty = $setItems->sum('quantity');
                                $cardTotal = $set->card_total ?? 0;
                                $progress = $cardTotal > 0 ? round($ownedQty / $cardTotal * 100) : 0;
                                $totalValueSet = $setItems->reduce(function ($carry, $item) {
                                    $card = $item->card;
                                    if (! $card) {
                                        return $carry;
                                    }
                                    $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                                    $value = $lastPrice?->avg ?? $lastPrice?->trend ?? 0;
                                    return $carry + ($value * $item->quantity);
                                }, 0);
                                $setData = [
                                    'id' => $set->id,
                                    'name' => $set->name,
                                    'logo' => $set->logo,
                                    'owned' => $ownedQty,
                                    'total' => $cardTotal,
                                    'value' => number_format($totalValueSet, 2, ',', '.'),
                                    'progress' => $progress,
                                    'cards' => $setItems->map(function ($item) {
                                        $card = $item->card;
                                        return [
                                            'id' => $card->id,
                                            'name' => $card->name,
                                            'dexId' => $card->dexId,
                                            'url_image' => $card->url_image,
                                            'rarity' => $card->rarity,
                                            'type' => is_array($card->types) ? implode(', ', $card->types) : $card->types,
                                            'quantity' => $item->quantity,
                                            'condition' => $item->condition,
                                        ];
                                    })->values(),
                                ];
                            @endphp

                            <button type="button" class="set-card" data-set='@json($setData)' onclick="openSetModal(this)">
                                <div class="set-card-meta">
                                    <div class="set-card-logo">
                                        @if ($set->logo)
                                            <img src="{{ $set->logo }}.png" alt="{{ $set->name }}" />
                                        @else
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#8c909f" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M12 4v16" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="set-card-title">{{ $set->name }}</p>
                                        <p class="set-card-series">{{ $set->serie?->name ?? 'Serie sconosciuta' }}</p>
                                    </div>
                                </div>
                                <div class="set-card-footer">
                                    <div class="set-card-foot">
                                        <strong>{{ $ownedQty }}</strong>
                                        <small>Carte possedute</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>{{ $cardTotal }}</strong>
                                        <small>Carte totali set</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>€ {{ number_format($totalValueSet, 2, ',', '.') }}</strong>
                                        <small>Valore</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>{{ $progress }}%</strong>
                                        <small>Copertura</small>
                                    </div>
                                </div>
                                <div class="set-card-progress">
                                    <div class="set-card-progress-bar" style="width: {{ $progress }}%;"></div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </main>

    <div id="set-collection-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-set-title">
        <div class="modal-panel">
            <div class="modal-header">
                <div>
                    <p class="font-label-caps text-outline">Carte nel set</p>
                    <h2 id="modal-set-title" class="modal-title">...</h2>
                    <p id="modal-set-meta" class="modal-subtitle">...</p>
                </div>
                <button type="button" class="modal-close" onclick="closeSetModal()" aria-label="Chiudi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal-card-grid" class="modal-cards"></div>
            </div>
        </div>
    </div>

    <script>
        function openSetModal(button) {
            var setData = JSON.parse(button.dataset.set || '{}');
            var overlay = document.getElementById('set-collection-modal');
            var title = document.getElementById('modal-set-title');
            var meta = document.getElementById('modal-set-meta');
            var grid = document.getElementById('modal-card-grid');

            title.textContent = setData.name || 'Set';
            meta.textContent = setData.owned + ' carte possedute · valore stimato ' + (setData.value ?? '0,00') + ' €';

            grid.innerHTML = '';
            if (Array.isArray(setData.cards) && setData.cards.length) {
                setData.cards.forEach(function(card) {
                    var imageHtml = card.url_image ? '<img src="' + card.url_image + '/low.png" alt="' + card.name + '" />' : '<div style="height:180px;background:#1b2339;border-radius:0.75rem"></div>';
                    var cardHtml =
                        '<div class="modal-card">' +
                        imageHtml +
                        '<div>' +
                        '<p class="modal-card-title">' + card.name + '</p>' +
                        '<p class="modal-card-subtitle">#' + String(card.dexId || '—').padStart(3, '0') + ' · ' + (card.type || 'N/A') + '</p>' +
                        '<div class="badge-pill"><strong>Qta: ' + card.quantity + '</strong> · ' + card.condition + '</div>' +
                        '</div>' +
                        '</div>';
                    grid.insertAdjacentHTML('beforeend', cardHtml);
                });
            } else {
                grid.innerHTML = '<div class="empty-state"><p class="font-body-lg" style="color:#8c909f;">Non ci sono carte salvate in questo set.</p></div>';
            }
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeSetModal() {
            var overlay = document.getElementById('set-collection-modal');
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    </script>
@endsection

@section('content')
    @php
        $collections = collect($collezioni);
        $series = $collections->groupBy(fn ($item) => $item->set?->serie?->name ?? 'Senza serie');
        $ownedCards = 0;
        $totalSlots = 0;
        $totalValue = 0;
        $setCount = 0;
        $completedSets = 0;
        $nextSetName = null;
        $bestNextProgress = -1;

        foreach ($collections->groupBy(fn ($item) => $item->set?->id ?? 0) as $setItems) {
            $set = $setItems->first()->set;
            if (! $set) {
                continue;
            }

            $setCount++;
            $ownedQty = $setItems->sum('quantity');
            $cardTotal = $set->card_total ?? 0;
            $totalSlots += $cardTotal;
            $ownedCards += $ownedQty;

            $progress = $cardTotal > 0 ? round($ownedQty / $cardTotal * 100) : 0;
            if ($progress >= 100) {
                $completedSets++;
            }

            if ($progress < 100 && $progress > $bestNextProgress) {
                $bestNextProgress = $progress;
                $nextSetName = $set->name;
            }

            foreach ($setItems as $item) {
                $card = $item->card;
                if (! $card) {
                    continue;
                }
                $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                $value = $lastPrice?->avg ?? $lastPrice?->trend ?? 0;
                $totalValue += $value * $item->quantity;
            }
        }

        $overallProgress = $totalSlots > 0 ? round($ownedCards / $totalSlots * 100) : 0;
        $now = now();
    @endphp

    <main class="container" style="max-width: 1440px;">
        <section class="mb-5">
            <div class="section-header">
                <div>
                    <p class="font-label-caps text-outline mb-2">Le mie collezioni</p>
                    <h1 class="section-title">Gestisci il tuo portfolio di carte</h1>
                    <p class="section-description">Una vista chiara delle tue serie, il progresso dei set e il valore totale della collezione.</p>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
                    <div class="search-card">
                        <span class="material-symbols-outlined text-outline" style="font-size:18px;">search</span>
                        <input type="text" placeholder="Cerca un set..." />
                    </div>
                    <a href="{{ route('collezioni.disponibili') }}" class="btn-primary">AGGIUNGI SET</a>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">VALORE TOTALE</p>
                <p class="font-stat-value text-tertiary">€ {{ number_format($totalValue, 2, ',', '.') }}</p>
                <div style="margin-top:14px; display:flex; gap:8px; align-items:center; color:#adc6ff; font-size:11px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">trending_up</span>
                    <span>+4.2% questo mese</span>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">CARTE TOTALI</p>
                <p class="font-stat-value text-on-surface">{{ number_format($ownedCards, 0, ',', '.') }} <span class="font-body-sm text-outline" style="font-weight:400;">/ {{ number_format($totalSlots, 0, ',', '.') }}</span></p>
                <div class="progress-track">
                    <div class="progress-bar" style="width: {{ $overallProgress }}%;"></div>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">SET COMPLETATI</p>
                <p class="font-stat-value text-on-surface">{{ $completedSets }} <span class="font-body-sm text-outline" style="font-weight:400;">Sets</span></p>
                <div style="margin-top:14px; display:flex; gap:8px; align-items:center; color:#ffb95f; font-size:11px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">military_tech</span>
                    <span>Prossimo: {{ $nextSetName ?? 'Nessuno' }} ({{ $bestNextProgress }}%)</span>
                </div>
            </div>
            <div class="stat-card bg-surface-container">
                <p class="font-label-caps text-outline">ULTIMO AGGIORNAMENTO</p>
                <p class="font-stat-value text-on-surface">{{ $now->translatedFormat('d F Y') }}</p>
                <p class="text-xs text-outline" style="margin-top:12px; font-weight:600; text-transform:uppercase;">Sync: {{ $now->format('H:i') }}</p>
            </div>
        </section>

        @if ($collections->isEmpty())
            <div class="empty-state">
                <h2 class="font-h3" style="margin-bottom:12px;">Nessuna collezione salvata</h2>
                <p class="font-body-lg" style="color:#8c909f;">Inizia ad aggiungere set alla collezione per vedere il progresso, il valore e le carte possedute.</p>
            </div>
        @else
            @foreach ($series as $serieName => $items)
                @php
                    $sets = $items->groupBy(fn ($item) => $item->set?->id ?? 0);
                @endphp

                <section class="mb-8">
                    <div class="section-heading">
                        <h2>{{ $serieName }}</h2>
                        <span>{{ $sets->count() }} set</span>
                    </div>
                    <div class="modal-cards" style="grid-template-columns: repeat(1, minmax(0, 1fr));">
                        @foreach ($sets as $setItems)
                            @php
                                $set = $setItems->first()->set;
                                $ownedQty = $setItems->sum('quantity');
                                $cardTotal = $set->card_total ?? 0;
                                $progress = $cardTotal > 0 ? round($ownedQty / $cardTotal * 100) : 0;
                                $totalValueSet = $setItems->reduce(function ($carry, $item) {
                                    $card = $item->card;
                                    if (! $card) {
                                        return $carry;
                                    }
                                    $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                                    $value = $lastPrice?->avg ?? $lastPrice?->trend ?? 0;
                                    return $carry + ($value * $item->quantity);
                                }, 0);
                                $setData = [
                                    'id' => $set->id,
                                    'name' => $set->name,
                                    'logo' => $set->logo,
                                    'owned' => $ownedQty,
                                    'total' => $cardTotal,
                                    'value' => number_format($totalValueSet, 2, ',', '.'),
                                    'progress' => $progress,
                                    'cards' => $setItems->map(function ($item) {
                                        $card = $item->card;
                                        return [
                                            'id' => $card->id,
                                            'name' => $card->name,
                                            'dexId' => $card->dexId,
                                            'url_image' => $card->url_image,
                                            'rarity' => $card->rarity,
                                            'type' => is_array($card->types) ? implode(', ', $card->types) : $card->types,
                                            'quantity' => $item->quantity,
                                            'condition' => $item->condition,
                                        ];
                                    })->values(),
                                ];
                            @endphp

                            <button type="button" class="set-card" data-set='@json($setData)' onclick="openSetModal(this)">
                                <div class="set-card-meta">
                                    <div class="set-card-logo">
                                        @if ($set->logo)
                                            <img src="{{ $set->logo }}.png" alt="{{ $set->name }}" />
                                        @else
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#8c909f" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M12 4v16" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="set-card-title">{{ $set->name }}</p>
                                        <p class="set-card-series">{{ $set->serie?->name ?? 'Serie sconosciuta' }}</p>
                                    </div>
                                </div>
                                <div class="set-card-footer">
                                    <div class="set-card-foot">
                                        <strong>{{ $ownedQty }}</strong>
                                        <small>Carte possedute</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>{{ $cardTotal }}</strong>
                                        <small>Carte totali set</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>€ {{ number_format($totalValueSet, 2, ',', '.') }}</strong>
                                        <small>Valore</small>
                                    </div>
                                    <div class="set-card-foot">
                                        <strong>{{ $progress }}%</strong>
                                        <small>Copertura</small>
                                    </div>
                                </div>
                                <div class="set-card-progress">
                                    <div class="set-card-progress-bar" style="width: {{ $progress }}%;"></div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </main>

    <div id="set-collection-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-set-title">
        <div class="modal-panel">
            <div class="modal-header">
                <div>
                    <p class="font-label-caps text-outline">Carte nel set</p>
                    <h2 id="modal-set-title" class="modal-title">...</h2>
                    <p id="modal-set-meta" class="modal-subtitle">...</p>
                </div>
                <button type="button" class="modal-close" onclick="closeSetModal()" aria-label="Chiudi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal-card-grid" class="modal-cards"></div>
            </div>
        </div>
    </div>

    <script>
        function openSetModal(button) {
            var setData = JSON.parse(button.dataset.set || '{}');
            var overlay = document.getElementById('set-collection-modal');
            var title = document.getElementById('modal-set-title');
            var meta = document.getElementById('modal-set-meta');
            var grid = document.getElementById('modal-card-grid');

            title.textContent = setData.name || 'Set';
            meta.textContent = setData.owned + ' carte possedute · valore stimato ' + (setData.value ?? '0,00') + ' €';

            grid.innerHTML = '';
            if (Array.isArray(setData.cards) && setData.cards.length) {
                setData.cards.forEach(function(card) {
                    var imageHtml = card.url_image ? '<img src="' + card.url_image + '/low.png" alt="' + card.name + '" />' : '<div style="height:180px;background:#1b2339;border-radius:0.75rem"></div>';
                    var cardHtml =
                        '<div class="modal-card">' +
                        imageHtml +
                        '<div>' +
                        '<p class="modal-card-title">' + card.name + '</p>' +
                        '<p class="modal-card-subtitle">#' + String(card.dexId || '—').padStart(3, '0') + ' · ' + (card.type || 'N/A') + '</p>' +
                        '<div class="badge-pill"><strong>Qta: ' + card.quantity + '</strong> · ' + card.condition + '</div>' +
                        '</div>' +
                        '</div>';
                    grid.insertAdjacentHTML('beforeend', cardHtml);
                });
            } else {
                grid.innerHTML = '<div class="empty-state"><p class="font-body-lg" style="color:#8c909f;">Non ci sono carte salvate in questo set.</p></div>';
            }
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeSetModal() {
            var overlay = document.getElementById('set-collection-modal');
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    </script>
@endsection
