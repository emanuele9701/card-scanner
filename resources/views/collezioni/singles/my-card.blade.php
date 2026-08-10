@php
    // Normalizza il valore di rarità in minuscolo per il matching
    $rarityRaw = strtolower(trim($card->rarity ?? ''));

    $rarityConfig = match (true) {

        // ── Hyper / Secret / Shiny ────────────────────────────────────────────
        in_array($rarityRaw, [
            'hyper rare', 'rara iper',                         // EN / IT
            'secret rare', 'segreto rara',                     // EN / IT
            'shiny rare', 'one shiny',                         // EN
            'special illustration rare', 'rara illustrazione speciale', // EN / IT
        ]) => [
            'label'      => 'Secret',
            'chip_style' => 'background:linear-gradient(90deg,rgba(255,100,200,0.15),rgba(255,179,177,0.08)); border:1px solid rgba(255,100,200,0.4); color:#ff64c8;',
            'dot_style'  => 'background-color:#ff64c8;',
        ],

        // ── Ultra Rare / Double Rare ──────────────────────────────────────────
        in_array($rarityRaw, [
            'ultra rare', 'ultra-rare', 'ultrarara',           // EN / IT
            'double rare', 'rara doppia',                      // EN / IT
            'two star', 'deux étoiles', 'deux √©toiles',       // EN / FR
        ]) => [
            'label'      => 'Ultra Rare',
            'chip_style' => 'background-color:rgba(255,179,177,0.08); border:1px solid rgba(255,179,177,0.3); color:#ffb3b1;',
            'dot_style'  => 'background-color:#ffb3b1;',
        ],

        // ── Rare Holo / V / VMAX / Illustration Rare / Diamonds ──────────────
        in_array($rarityRaw, [
            'rare holo', 'holo rare', 'olografica rara',       // EN / IT
            'holo rare v', 'olografica rara v',
            'holo rare vmax', 'olografica rara vmax',
            'illustration rare', 'rara illustrazione',         // EN / IT
            'three diamond', 'trois diamant',                  // EN / FR
            'four diamond',
            'one star', 'une étoile', 'une √©toile',           // EN / FR
        ]) => [
            'label'      => 'Rare Holo',
            'chip_style' => 'background-color:rgba(255,215,149,0.12); border:1px solid rgba(255,215,149,0.4); color:#ffd795;',
            'dot_style'  => 'background-color:#ffd795;',
        ],

        // ── Rare (base) / One/Two Diamond ────────────────────────────────────
        in_array($rarityRaw, [
            'rare', 'rara',                                    // EN / IT
            'one diamond', 'une diamant',                      // EN / FR
            'two diamond', 'deux diamant',                     // EN / FR
        ]) => [
            'label'      => 'Rare',
            'chip_style' => 'background-color:rgba(255,215,149,0.06); border:1px solid rgba(255,215,149,0.25); color:#e8c56a;',
            'dot_style'  => 'background-color:#e8c56a;',
        ],

        // ── Uncommon ─────────────────────────────────────────────────────────
        in_array($rarityRaw, [
            'uncommon', 'non comune',                          // EN / IT
        ]) => [
            'label'      => 'Uncommon',
            'chip_style' => 'background-color:rgba(105,212,244,0.08); border:1px solid rgba(105,212,244,0.25); color:#69d4f4;',
            'dot_style'  => 'background-color:#69d4f4;',
        ],

        // ── Common / None / fallback ──────────────────────────────────────────
        default => [
            'label'      => in_array($rarityRaw, ['none', 'nessuna', '']) ? '—' : 'Common',
            'chip_style' => 'background-color:rgba(212,228,250,0.06); border:1px solid rgba(212,228,250,0.15); color:#a0b4cc;',
            'dot_style'  => 'background-color:#a0b4cc;',
        ],
    };

    $price = optional($card->prices->sortByDesc('updated_at')->first());
    
    $totalValue = 0;
    $totalQty = 0;
    $conditions = [];
    
    foreach($card->collectors as $copy) {
        $totalQty += $copy->quantity;
        if($copy->condition && !in_array($copy->condition, $conditions)) {
            $conditions[] = $copy->condition;
        }
        
        $totalValue += $copy->getCalculatedPrice() * $copy->quantity;
    }
    
    $conditionsText = implode(', ', $conditions);
    
    // Compute Variant Badges
    $variantBadges = [];
    $vMap = [
        'reverse' => ['R', 'bg-info text-dark'],
        'holo' => ['H', 'bg-warning text-dark'],
        'firstedition' => ['1°', 'bg-danger text-white'],
        'normal' => ['N', 'bg-secondary text-white']
    ];
    
    $isMissingTab = ($tab ?? 'owned') === 'missing';
    $isDoppieTab = ($tab ?? 'owned') === 'doppie';

    $listToMap = [];
    if ($isMissingTab) {
        $listToMap = $card->missing_variants ?? [];
    } elseif ($isDoppieTab) {
        $listToMap = array_keys($card->doppie_variants ?? []);
    } else {
        $listToMap = $card->owned_variants ?? [];
    }
    
    if(empty($listToMap)) $listToMap = ['normal'];
    
    foreach($listToMap as $v) {
        $parts = explode('|', $v);
        $vLowRaw = strtolower(str_replace(' ', '', $parts[0]));
        $langRaw = $parts[1] ?? '';
        
        $vLow = $vLowRaw; // The variant key for map lookup

        $extraInfo = '';
        if ($isDoppieTab && isset($card->doppie_variants[$v])) {
            $extraInfo = '<span class="ms-1 fw-bold" style="font-size: 0.5rem; opacity: 0.9;">x' . $card->doppie_variants[$v] . '</span>';
        }
        
        $langFlagHtml = '';
        if ($langRaw) {
            $langFlagStr = match($langRaw) {
                'it' => '🇮🇹',
                'en' => '🇬🇧',
                'fr' => '🇫🇷',
                'de' => '🇩🇪',
                'es' => '🇪🇸',
                'jp', 'ja' => '🇯🇵',
                'pt' => '🇵🇹',
                default => strtoupper($langRaw)
            };
            $langFlagHtml = '<span style="font-size: 0.6rem; margin-left: 3px;">' . $langFlagStr . '</span>';
        }

        $incomingBadge = '';
        $isVariantIncoming = false;
        if ($isMissingTab && isset($cardIncoming)) {
            $foilTypeForVariant = match($vLow) {
                'reverse' => 'reverse',
                'holo' => 'holo',
                default => 'normal',
            };
            $variantIncoming = $cardIncoming->where('foil_type', $foilTypeForVariant);
            if ($variantIncoming->count() > 0) {
                $isVariantIncoming = true;
                $inc = $variantIncoming->first();
                $qtyStr = $inc->quantity > 1 ? " x{$inc->quantity}" : "";
                $titleStr = $inc->notes ? htmlspecialchars($inc->notes) : 'In Arrivo';
            }
        }
        
        if (isset($vMap[$vLow])) {
            if ($isVariantIncoming) {
                // Orange badge for incoming variants
                $variantBadges[] = '<span class="badge rounded-pill d-inline-flex align-items-center justify-content-center" title="' . ucfirst($vLow) . ' – ' . $titleStr . '" style="font-size:0.55rem; padding: 2px 5px; min-width:18px; background: linear-gradient(135deg, #fb923c, #f59e0b); color: #1a1a2e; border: 1px solid rgba(251, 146, 60, 0.6); box-shadow: 0 0 6px rgba(251, 146, 60, 0.4);"><span>🚚 ' . $vMap[$vLow][0] . $langFlagHtml . $qtyStr . '</span>' . $extraInfo . '</span>';
            } else {
                $variantBadges[] = '<span class="badge rounded-pill d-inline-flex align-items-center justify-content-center ' . $vMap[$vLow][1] . '" title="'.ucfirst($vLow).'" style="font-size:0.55rem; padding: 2px 5px; min-width:18px;"><span>' . $vMap[$vLow][0] . $langFlagHtml . '</span>' . $extraInfo . '</span>';
            }
        } else {
            if ($isVariantIncoming) {
                $variantBadges[] = '<span class="badge rounded-pill d-inline-flex align-items-center justify-content-center" title="' . ucfirst($vLow) . ' – ' . $titleStr . '" style="font-size:0.55rem; padding: 2px 5px; min-width:18px; background: linear-gradient(135deg, #fb923c, #f59e0b); color: #1a1a2e; border: 1px solid rgba(251, 146, 60, 0.6); box-shadow: 0 0 6px rgba(251, 146, 60, 0.4);"><span>🚚 '.strtoupper(substr($vLow, 0, 1)) . $langFlagHtml . $qtyStr . '</span>' . $extraInfo . '</span>';
            } else {
                $variantBadges[] = '<span class="badge rounded-pill d-inline-flex align-items-center justify-content-center bg-light text-dark" title="'.ucfirst($vLow).'" style="font-size:0.55rem; padding: 2px 5px; min-width:18px;"><span>'.strtoupper(substr($vLow, 0, 1)) . $langFlagHtml . '</span>' . $extraInfo . '</span>';
            }
        }
    }
    $variantsHtml = implode(' ', $variantBadges);

    $hasMissingIncoming = count($card->missing_incoming_variants ?? []) > 0;
    $hasPureMissing = count($card->pure_missing_variants ?? []) > 0;
    
    // Fallback if missing_incoming_variants is not set (e.g. from an old cache or non-missing tab)
    if (!isset($card->missing_incoming_variants)) {
        $hasIncoming = isset($cardIncoming) && $cardIncoming->count() > 0;
        if ($isMissingTab) {
            $hasMissingIncoming = $hasIncoming;
            $hasPureMissing = !$hasIncoming;
        }
    } else {
        $hasIncoming = $hasMissingIncoming;
    }
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
    .card-qty-badge {
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
        left: 40px;
        top: 12px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        letter-spacing: 0.12em;
    }

    .card-qty-badge {
        right: 12px;
        top: 12px;
        font-weight: 700;
        background-color: rgba(59, 130, 246, 0.2);
        border-color: rgba(59, 130, 246, 0.3);
        color: #93c5fd;
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

    .btn-card-manage {
        width: 100%;
        border-radius: 0.95rem;
        padding: 10px 0;
        font-size: 0.77rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border: 1px solid transparent;
        cursor: pointer;
        color: #1b1100;
        background: #fbb400;
        border-color: rgba(255, 255, 255, 0.08);
    }

    .btn-card-manage:hover {
        background: #ffd795;
    }

    .btn-card-detail {
        width: 100%;
        border-radius: 0.95rem;
        padding: 10px 0;
        font-size: 0.77rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border: 1px solid transparent;
        cursor: pointer;
        color: #d4e4fa;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.12);
    }

    .btn-card-detail:hover {
        background: rgba(255, 255, 255, 0.14);
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

    .card-selection-checkbox {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 25;
    }
    
    .card-selection-checkbox .form-check-input {
        width: 20px;
        height: 20px;
        border-color: rgba(255, 255, 255, 0.4);
        background-color: rgba(0, 0, 0, 0.4);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .card-selection-checkbox .form-check-input:checked {
        background-color: #ef4444;
        border-color: #ef4444;
    }

    .card-rarity-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .card-cond-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(212, 228, 250, 0.72);
        background: rgba(255,255,255,0.05);
        padding: 2px 6px;
        border-radius: 4px;
    }
</style>

<div class="col">
    <div data-card-id="{{ $card->id }}" class="card-item" style="{{ $hasIncoming ? 'border: 2px solid #fb923c; box-shadow: 0 0 20px rgba(251, 146, 60, 0.3);' : '' }}">
        @if($hasIncoming)
            <div style="position: absolute; top: -1px; right: -1px; z-index: 30; background: linear-gradient(135deg, #fb923c, #f59e0b); color: #1a1a2e; padding: 4px 16px; border-bottom-left-radius: 16px; font-weight: 800; font-size: 0.7rem; letter-spacing: 0.05em; box-shadow: -2px 2px 10px rgba(0,0,0,0.5);">
                IN ARRIVO
            </div>
        @endif

        <div class="card-glass-highlight"></div>

        <div class="card-image-area">
            <div class="card-selection-checkbox" onclick="event.stopPropagation();">
                <input type="checkbox" class="form-check-input mass-select-checkbox" value="{{ $card->id }}" onchange="handleCardSelection(this)">
            </div>
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

            @if(isset($watchlistedCardIds) && in_array($card->id, $watchlistedCardIds))
                <div class="watchlisted-badge" style="position:absolute; top:12px; right:{{ !$isMissingTab && $totalQty > 0 ? '42px' : '12px' }}; z-index:25; background:rgba(99,179,237,0.9); color:#fff; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.5);" title="In Watchlist">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
            @endif

            <div class="card-number-badge">
                #{{ str_pad($card->dexId, 3, '0', STR_PAD_LEFT) }}
            </div>

            @if(!$isMissingTab && $totalQty > 0)
                <div class="card-qty-badge">
                    x{{ $totalQty }}
                </div>
            @endif

            <div class="card-hover-overlay">
                @if($isMissingTab)
                    <button type="button" class="btn-card-manage d-inline-flex justify-content-center align-items-center text-decoration-none mb-2" onclick="event.stopPropagation(); openManageModal({{ $card->id }}, '{{ addslashes($card->name) }}')">
                        {{ __('+ Aggiungi') }}
                    </button>
                    <button type="button" class="btn-card-detail w-100 d-inline-flex justify-content-center align-items-center text-decoration-none" onclick="event.stopPropagation(); openModal({{ json_encode(['id' => $card->id, 'name' => $card->name, 'image' => $card->url_image]) }})">
                        {{ __('Dettagli') }}
                    </button>
                @else
                    <button type="button" class="btn-card-manage d-inline-flex justify-content-center align-items-center text-decoration-none mb-2" onclick="event.stopPropagation(); openManageModal({{ $card->id }}, '{{ addslashes($card->name) }}')">
                        {{ __('Gestisci Copie') }}
                    </button>
                    <div class="d-flex gap-2 w-100">
                        <button type="button" class="btn-card-detail flex-grow-1 d-inline-flex justify-content-center align-items-center text-decoration-none" onclick="event.stopPropagation(); openModal({{ json_encode(['id' => $card->id, 'name' => $card->name, 'image' => $card->url_image]) }})">
                            {{ __('Dettagli') }}
                        </button>
                        <button type="button" title="Rimuovi tutta la carta" class="btn-card-detail flex-shrink-0 d-inline-flex justify-content-center align-items-center text-danger" style="width: 42px; padding: 0; background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.25);" onclick="event.stopPropagation(); removeEntireCard({{ $card->id }}, this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </div>
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
                @if ($conditionsText)
                    <span class="card-cond-label text-truncate" style="max-width: 80px;" title="{{ $conditionsText }}">{{ $conditionsText }}</span>
                @endif
            </div>
            
            @if($variantsHtml)
                <div style="margin-bottom: 6px;" class="d-flex flex-wrap gap-1 align-items-center">
                    <span style="font-size: 10px; color: rgba(212, 228, 250, 0.6);">{{ $isMissingTab ? __('Mancano:') : ($isDoppieTab ? __('Doppie:') : __('Possiedi:')) }}</span>
                    {!! $variantsHtml !!}
                </div>
            @else
                <div style="font-size: 10px; color: transparent; margin-bottom: 6px;">&nbsp;</div>
            @endif

            @if($isMissingTab)
                <div class="mt-2 pt-2 border-top border-secondary d-flex flex-column gap-1">
                    @if($hasMissingIncoming)
                        <div class="d-flex justify-content-between gap-1 w-100">
                            <button type="button" class="btn btn-sm btn-success flex-grow-1 fw-bold" style="font-size: 0.7rem; padding: 4px 8px; box-shadow: 0 2px 4px rgba(34,197,94,0.2);" onclick="event.stopPropagation(); window.selectedCards = new Set([{{ $card->id }}]); window.openIncomingArrivedModal();">📦 Arrivate</button>
                            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" style="font-size: 0.7rem; padding: 4px 8px;" title="Annulla In Arrivo" onclick="event.stopPropagation(); window.cancelIncomingByCardId({{ $card->id }}, this);">❌</button>
                        </div>
                    @endif
                    
                    @if($hasPureMissing || (!$hasMissingIncoming && !$hasPureMissing))
                        <button type="button" class="btn btn-sm btn-outline-warning w-100 fw-bold" style="font-size: 0.7rem; padding: 4px 8px;" onclick="event.stopPropagation(); window.selectedCards = new Set([{{ $card->id }}]); window.openIncomingAddModal();">🚚 Segna In Arrivo</button>
                    @endif
                </div>
            @endif

            @if(!$isMissingTab)
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:12px; color:#8c909f;">
                    <span>{{ __('Valore totale') }}</span>
                    <span style="color: #f59e0b; font-weight: 600;">€ {{ number_format($totalValue, 2, ',', '.') }}</span>
                </div>
            @else
                @php
                    $singlePrice = $price->trend ?? $price->avg ?? 0;
                @endphp
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:12px; color:#8c909f;">
                    <span>{{ __('Valore stimato') }}</span>
                    <span style="color: #f59e0b; font-weight: 600;">€ {{ number_format($singlePrice, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
