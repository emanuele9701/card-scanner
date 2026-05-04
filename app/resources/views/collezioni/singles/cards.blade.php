@php
    $isCollected = $card->isCollected ?? false;

    $rarityConfig = match (strtolower($card->rarity ?? '')) {
        'ultra rare', 'ultra-rare', 'secret rare' => [
            'label' => 'Ultra Rare',
            'chip_style' =>
                'background-color:rgba(255,179,177,0.08); border:1px solid rgba(255,179,177,0.3); color:#ffb3b1;',
            'dot_style' => 'background-color:#ffb3b1;',
        ],
        'rare', 'rare holo' => [
            'label' => $card->rarity === 'rare holo' ? 'Rare Holo' : 'Rare',
            'chip_style' =>
                'background-color:rgba(255,215,149,0.08); border:1px solid rgba(255,215,149,0.3); color:#ffd795;',
            'dot_style' => 'background-color:#ffd795;',
        ],
        'uncommon' => [
            'label' => 'Uncommon',
            'chip_style' =>
                'background-color:rgba(105,212,244,0.08); border:1px solid rgba(105,212,244,0.25); color:#69d4f4;',
            'dot_style' => 'background-color:#69d4f4;',
        ],
        default => [
            'label' => 'Common',
            'chip_style' =>
                'background-color:rgba(212,228,250,0.06); border:1px solid rgba(212,228,250,0.15); color:#a0b4cc;',
            'dot_style' => 'background-color:#a0b4cc;',
        ],
    };
@endphp

<style>
    .card-item {
        width: 100%;
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1.5rem;
        cursor: pointer;
        aspect-ratio: 1.05 / 1.35;
        max-height: 380px;
        background: linear-gradient(135deg, #142135 0%, #0b1522 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }

    .card-item:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28), 0 6px 22px rgba(36, 56, 86, 0.28);
        border-color: rgba(255, 255, 255, 0.16);
    }

    .card-glass-highlight {
        pointer-events: none;
        position: absolute;
        inset: 0;
        z-index: 10;
        border-radius: 1.5rem;
        background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.08), transparent 28%);
    }

    .card-checkbox {
        position: absolute;
        left: 12px;
        top: 12px;
        z-index: 30;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background-color: rgba(0, 0, 0, 0.34);
        backdrop-filter: blur(10px);
        opacity: 0;
        transition: opacity 0.25s ease, transform 0.25s ease;
    }

    .card-item:hover .card-checkbox {
        opacity: 1;
        transform: translateY(-1px);
    }

    .card-checkbox[data-selected="true"] {
        border-color: #fbb400;
        background-color: rgba(251, 180, 0, 0.22);
        opacity: 1;
    }

    .card-checkmark {
        display: none;
        width: 14px;
        height: 14px;
    }

    .card-checkbox[data-selected="true"] .card-checkmark {
        display: block;
    }

    .card-image-area {
        position: relative;
        min-height: 140px;
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, rgba(15, 28, 47, 0.9) 0%, rgba(6, 15, 28, 0.98) 100%);
    }

    .card-symbol-img {
        width: 100%;
        height: auto;
        max-height: 240px;
        object-fit: contain;
        opacity: 0.95;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .card-item:hover .card-symbol-img {
        transform: scale(1.05);
        opacity: 1;
    }

    .card-number-badge,
    .card-collected-badge {
        position: absolute;
        z-index: 20;
        border-radius: 0.85rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background-color: rgba(5, 20, 36, 0.72);
        padding: 4px 8px;
        font-size: 10px;
        color: rgba(212, 228, 250, 0.8);
        backdrop-filter: blur(10px);
    }

    .card-number-badge {
        left: 12px;
        top: 12px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        letter-spacing: 0.12em;
    }

    .card-collected-badge {
        right: 12px;
        top: 12px;
        width: 26px;
        height: 26px;
        padding: 0;
        display: grid;
        place-items: center;
        background-color: rgba(251, 180, 0, 0.18);
    }

    .card-hover-overlay {
        position: absolute;
        inset: 0;
        z-index: 20;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        padding: 16px;
        gap: 10px;
        background: linear-gradient(180deg, transparent 45%, rgba(5, 20, 36, 0.84) 100%);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .card-item:hover .card-hover-overlay {
        opacity: 1;
    }

    .btn-card-detail,
    .btn-card-add {
        width: 100%;
        border-radius: 0.95rem;
        padding: 10px 0;
        font-size: 0.77rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .btn-card-detail {
        color: #d4e4fa;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.12);
    }

    .btn-card-detail:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    .btn-card-add {
        background: #fbb400;
        color: #1b1100;
        border-color: rgba(255, 255, 255, 0.08);
        position: relative;
        overflow: hidden;
    }

    .btn-card-add:hover {
        background: #ffd795;
    }

    .btn-card-add.loading {
        pointer-events: none;
        opacity: 0.75;
    }

    .btn-card-loader {
        display: inline-block;
        font-size: 0.8rem;
        line-height: 1;
        margin-left: 0.5rem;
    }

    .btn-card-loader.visually-hidden {
        display: none;
    }

    .card-footer-area {
        position: relative;
        z-index: 20;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        background-color: rgba(5, 20, 36, 0.86);
        padding: 16px 14px;
        backdrop-filter: blur(16px);
    }

    .card-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #edf2ff;
        margin-bottom: 0.6rem;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .card-rarity-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 9999px;
        padding: 0.35rem 0.7rem;
        font-size: 0.67rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        backdrop-filter: blur(12px);
    }

    .card-rarity-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .card-type-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(212, 228, 250, 0.72);
    }
</style>

<div class="col">
    <div data-card-id="{{ $card->id }}" class="card-item">
        <div class="card-glass-highlight"></div>

        @if (!$isCollected)
            <div id="card-cb-{{ $card->id }}" data-selected="false" class="card-checkbox"
                onclick="event.stopPropagation(); cardToggleSelect({{ $card->id }})">
                <svg class="card-checkmark" width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6l3 3 5-5" stroke="#422c00" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
        @endif

        <div class="card-image-area">
            @if ($card->url_image)
                <img src="{{ $card->url_image }}/low.png" alt="{{ $card->name }}" class="card-symbol-img"
                    loading="lazy">
            @else
                <div class="d-flex align-items-center justify-content-center h-100">
                    <svg width="56" height="56" viewBox="0 0 80 80" fill="none"
                        style="opacity:0.15; color:#d4e4fa;">
                        <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="2" />
                        <circle cx="40" cy="40" r="18" fill="currentColor" opacity="0.4" />
                        <rect x="4" y="38" width="72" height="4" fill="currentColor" opacity="0.5" />
                    </svg>
                </div>
            @endif

            <div class="card-number-badge">
                #{{ str_pad($card->dexId, 3, '0', STR_PAD_LEFT) }}
            </div>

            @if ($isCollected)
                <div class="card-collected-badge">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6l3 3 5-5" stroke="#ffd795" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            @endif

            <div class="card-hover-overlay">
                <button type="button" class="btn-card-detail d-inline-flex justify-content-center align-items-center text-decoration-none" onclick="event.stopPropagation(); openModal({{ json_encode(['id' => $card->id, 'name' => $card->name, 'image' => $card->url_image]) }})">
                    {{ __('Vedi dettagli') }}
                </button>
                @if (!$isCollected)
                    <button type="button" class="btn-card-add w-100" onclick="event.stopPropagation(); addToCollection(this, {{ $card->id }})">
                        <span class="btn-card-add-text">{{ __('+ Aggiungi') }}</span>
                        <span class="btn-card-loader visually-hidden">{{ __('Caricamento...') }}</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="card-footer-area">
            <p class="card-name">{{ $card->name }}</p>
            <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                <span class="card-rarity-chip" style="{{ $rarityConfig['chip_style'] }}">
                    <span class="card-rarity-dot" style="{{ $rarityConfig['dot_style'] }}"></span>
                    {{ $rarityConfig['label'] }}
                </span>
                @if ($card->type)
                    <span class="card-type-label">{{ $card->type }}</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between; gap:10px; font-size:12px; color:#8c909f;">
                <span>{{ $card->evolve_from ? __('Evolve da') . ' ' . $card->evolve_from : __('Base') }}</span>
                <span>€ {{ number_format(optional($card->prices->sortByDesc('updated_at')->first())->avg ?? 0, 2, ',', '.') }}</span>
            </div>
            <div style="margin-top:10px; font-size:11px; color:#8c909f;">Illus. {{ $card->illustrator ?? '—' }}</div>
        </div>
    </div>
</div>

<script>
    (function() {
        if (window.cardToggleSelect) return;
        window.cardToggleSelect = function(cardId) {
            var cb = document.getElementById('card-cb-' + cardId);
            if (!cb) return;
            var nowSelected = cb.dataset.selected !== 'true';
            cb.dataset.selected = nowSelected ? 'true' : 'false';
            if (typeof toggleSelect === 'function') toggleSelect(cardId, nowSelected);
        };
    })();
</script>
