@forelse ($userCards as $card)
    @php
        $bgImage = $card->images && isset($card->images['large']) ? $card->images['large'] : null;
        $fallbackBg = 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)';
        $setName = $card->set ? $card->set->name : 'N/D';
        $cardIncoming = isset($incomingByCard) && isset($incomingByCard[$card->id]) ? $incomingByCard[$card->id] : collect();
        $hasIncoming = $cardIncoming->count() > 0;
    @endphp
    <div class="col card-item d-flex" data-card-id="{{ $card->id }}">
        <div class="card w-100 border-0 overflow-hidden d-flex flex-column missing-card-wrapper"
            style="background: {{ $bgImage ? "url('$bgImage') center/cover no-repeat" : $fallbackBg }}; min-height: 400px; position: relative; border-radius: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5); {{ $hasIncoming ? 'border: 2px solid #fb923c !important; box-shadow: 0 0 25px rgba(251, 146, 60, 0.4) !important;' : '' }}">
            
            @if($hasIncoming)
                <div style="position: absolute; top: -1px; right: -1px; z-index: 30; background: linear-gradient(135deg, #fb923c, #f59e0b); color: #1a1a2e; padding: 6px 20px; border-bottom-left-radius: 16px; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.05em; box-shadow: -2px 2px 12px rgba(0,0,0,0.5);">
                    IN ARRIVO
                </div>
            @endif

            <div class="position-absolute top-0 start-0 w-100 h-100"
                style="background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.6) 40%, rgba(0,0,0,0.2) 100%);">
            </div>

            {{-- Checkbox di selezione --}}
            <div class="position-absolute top-0 start-0 p-3 z-index-1">
                <input type="checkbox" class="form-check-input missing-card-checkbox" 
                    value="{{ $card->id }}" 
                    style="width: 20px; height: 20px; cursor: pointer; background-color: rgba(0,0,0,0.4); border: 2px solid rgba(255,255,255,0.5);"
                    onchange="handleMissingCardSelection(this)">
            </div>

            <!-- Header: Nome e Set -->
            <div class="position-relative p-4 pb-0 z-index-1">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="text-white fw-bold mb-1 text-truncate" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8); padding-left: 28px;">
                        {{ $card->name }}
                    </h5>
                    <span class="badge bg-dark bg-opacity-75 text-white border border-secondary" style="backdrop-filter: blur(4px);">
                        {{ $setName }}
                    </span>
                </div>
            </div>

            <!-- Varianti Mancanti e In Arrivo -->
            <div class="position-relative mt-auto p-4 z-index-1 w-100">
                <div class="mb-2 text-white-50 small text-uppercase fw-bold">{{ __('Varianti Mancanti') }}</div>
                <div class="d-flex flex-column gap-2 w-100">
                    @foreach($card->missing_variants as $variant)
                        @php
                            $badgeColor = match(strtolower($variant)) {
                                'reverse' => 'bg-info text-dark',
                                'holo' => 'bg-warning text-dark',
                                'firstedition' => 'bg-danger text-white',
                                default => 'bg-secondary text-white'
                            };
                            $variantLabel = match(strtolower($variant)) {
                                'reverse' => 'R',
                                'holo' => 'H',
                                'firstedition' => '1°',
                                default => 'N'
                            };
                            $variantName = match(strtolower($variant)) {
                                'reverse' => 'Reverse Holo',
                                'holo' => 'Holo',
                                'firstedition' => __('Prima Edizione'),
                                default => __('Normale')
                            };
                            // Check if this specific variant has an incoming entry
                            $foilTypeForVariant = match(strtolower($variant)) {
                                'reverse' => 'reverse',
                                'holo' => 'holo',
                                default => 'normal',
                            };
                            $variantIncoming = $cardIncoming->where('foil_type', $foilTypeForVariant);
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle {{ $badgeColor }} d-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">{{ $variantLabel }}</span>
                                <span class="text-white fw-medium">{{ $variantName }}</span>
                            </div>
                            @if($variantIncoming->count() > 0)
                                @php $inc = $variantIncoming->first(); @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge d-flex align-items-center gap-1" 
                                        style="background: rgba(251, 146, 60, 0.2); color: #fb923c; border: 1px solid rgba(251, 146, 60, 0.4); font-size: 0.7rem;"
                                        @if($inc->notes) title="{{ $inc->notes }}" data-bs-toggle="tooltip" @endif>
                                        🚚 {{ __('In Arrivo') }}
                                        @if($inc->quantity > 1) x{{ $inc->quantity }} @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            
        </div>
    </div>
@empty
    <div class="col-12 text-center py-5">
        <h4 class="text-secondary fw-bold">{{ __('Nessuna carta mancante') }}</h4>
        <p class="text-muted">{{ __('Hai completato tutti i set che collezioni! Incredibile!') }}</p>
    </div>
@endforelse
