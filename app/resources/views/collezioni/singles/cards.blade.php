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
        width: 144px;
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1.5rem;
        cursor: pointer;
        aspect-ratio: 2.5/4;
        background: linear-gradient(135deg, #1c2b3c, #122131);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease-out;
    }

    .card-item:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 0 28px rgba(251, 180, 0, 0.25), 0 12px 40px rgba(0, 0, 0, 0.55);
        border-color: rgba(255, 255, 255, 0.14);
    }

    .card-item:hover .card-hover-overlay {
        opacity: 1;
    }

    .card-item:hover .card-symbol-img {
        opacity: 1;
        transform: scale(1.1);
    }

    .card-glass-highlight {
        pointer-events: none;
        position: absolute;
        inset: 0;
        z-index: 10;
        border-radius: 1.5rem;
        background: linear-gradient(135deg, rgba(255, 179, 177, 0.04), transparent);
    }

    .card-checkbox {
        position: absolute;
        left: 10px;
        top: 10px;
        z-index: 30;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid;
        backdrop-filter: blur(8px);
        transition: all 0.2s;
    }

    .card-checkbox[data-selected="false"] {
        border-color: rgba(255, 255, 255, 0.2);
        background-color: rgba(5, 20, 36, 0.6);
        opacity: 0;
    }

    .card-item:hover .card-checkbox[data-selected="false"] {
        opacity: 1;
    }

    .card-checkbox[data-selected="true"] {
        border-color: #fbb400;
        background-color: #fbb400;
        opacity: 1;
    }

    .card-checkmark {
        display: none;
    }

    .card-checkbox[data-selected="true"] .card-checkmark {
        display: block;
    }

    .card-image-area {
        position: relative;
        min-height: 160px;
        flex: 1;
        overflow: hidden;
        background: linear-gradient(to bottom, #0d1c2d, #1c2b3c);
    }

    .card-symbol-img {
        width: 300px;
        object-fit: cover;
        opacity: 0.65;
        transition: opacity 0.3s, transform 0.3s;
    }

    .card-number-badge {
        position: absolute;
        left: 10px;
        top: 10px;
        z-index: 20;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.07);
        background-color: rgba(5, 20, 36, 0.7);
        padding: 2px 6px;
        font-family: monospace;
        font-size: 10px;
        letter-spacing: 0.1em;
        color: rgba(212, 228, 250, 0.5);
        backdrop-filter: blur(8px);
    }

    .card-collected-badge {
        position: absolute;
        right: 10px;
        top: 10px;
        z-index: 20;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 1px solid rgba(251, 180, 0, 0.4);
        background-color: rgba(251, 180, 0, 0.12);
        backdrop-filter: blur(8px);
    }

    .card-hover-overlay {
        position: absolute;
        inset: 0;
        z-index: 20;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        background: linear-gradient(to top, rgba(5, 20, 36, 0.9) 0%, rgba(5, 20, 36, 0.4) 50%, transparent 100%);
        padding: 12px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .btn-card-detail {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background-color: rgba(255, 255, 255, 0.1);
        padding: 8px 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: #d4e4fa;
        backdrop-filter: blur(8px);
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-card-detail:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .btn-card-add {
        width: 100%;
        border-radius: 0.75rem;
        background-color: #fbb400;
        padding: 8px 0;
        font-size: 0.75rem;
        font-weight: 700;
        color: #422c00;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-card-add:hover {
        background-color: #ffd795;
    }

    .card-footer-area {
        position: relative;
        z-index: 20;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        background-color: rgba(5, 20, 36, 0.85);
        padding: 12px 14px;
        backdrop-filter: blur(16px);
    }

    .card-name {
        font-size: 13px;
        font-weight: 700;
        color: #d4e4fa;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 6px;
        line-height: 1.3;
    }

    .card-rarity-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 9999px;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        backdrop-filter: blur(8px);
    }

    .card-rarity-dot {
        width: 6px;
        height: 6px;
        flex-shrink: 0;
        border-radius: 50%;
    }

    .card-type-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: rgba(212, 228, 250, 0.35);
    }
</style>

<div class="col">
    <div data-card-id="{{ $card->id }}" class="card-item">

        {{-- Glass highlight --}}
        <div class="card-glass-highlight"></div>

        {{-- Checkbox (solo se non raccolta) --}}
        @if (!$isCollected)
            <div id="card-cb-{{ $card->id }}" data-selected="false" class="card-checkbox"
                onclick="event.stopPropagation(); cardToggleSelect({{ $card->id }})">
                <svg class="card-checkmark" width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6l3 3 5-5" stroke="#422c00" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
        @endif

        {{-- Area immagine --}}
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

            {{-- Badge numero --}}
            <div class="card-number-badge">
                #{{ str_pad($card->dexId, 3, '0', STR_PAD_LEFT) }}
            </div>

            {{-- Badge raccolta --}}
            @if ($isCollected)
                <div class="card-collected-badge">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6l3 3 5-5" stroke="#ffd795" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            @endif

            {{-- Overlay hover --}}
            <div class="card-hover-overlay">
                <button class="btn-card-detail"
                    onclick="event.stopPropagation(); openModal({{ json_encode(['id' => $card->id, 'name' => $card->name, 'image' => $card->url_image]) }})">
                    Vedi dettagli
                </button>
                @if (!$isCollected)
                    <button class="btn-card-add"
                        onclick="event.stopPropagation(); addToCollection([{{ $card->id }}])">
                        + Aggiungi
                    </button>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer-area">
            <p class="card-name">{{ $card->name }}</p>
            <div class="d-flex align-items-center justify-content-between gap-1">
                <span class="card-rarity-chip" style="{{ $rarityConfig['chip_style'] }}">
                    <span class="card-rarity-dot" style="{{ $rarityConfig['dot_style'] }}"></span>
                    {{ $rarityConfig['label'] }}
                </span>
                @if ($card->type)
                    <span class="card-type-label">{{ $card->type }}</span>
                @endif
            </div>
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
            // Propaga al gestore globale della pagina se disponibile
            if (typeof toggleSelect === 'function') toggleSelect(cardId, nowSelected);
        };
    })();
</script>
