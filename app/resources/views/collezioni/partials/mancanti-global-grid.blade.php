@forelse ($userCards as $card)
    @php
        $bgImage = $card->images && isset($card->images['large']) ? $card->images['large'] : null;
        $fallbackBg = 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)';
        $setName = $card->set ? $card->set->name : 'N/D';
    @endphp
    <div class="col card-item d-flex">
        <div class="card w-100 border-0 overflow-hidden d-flex flex-column"
            style="background: {{ $bgImage ? "url('$bgImage') center/cover no-repeat" : $fallbackBg }}; min-height: 400px; position: relative; border-radius: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            
            <div class="position-absolute top-0 start-0 w-100 h-100"
                style="background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.6) 40%, rgba(0,0,0,0.2) 100%);">
            </div>

            <!-- Header: Nome e Set -->
            <div class="position-relative p-4 pb-0 z-index-1">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="text-white fw-bold mb-1 text-truncate" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
                        {{ $card->name }}
                    </h5>
                    <span class="badge bg-dark bg-opacity-75 text-white border border-secondary" style="backdrop-filter: blur(4px);">
                        {{ $setName }}
                    </span>
                </div>
            </div>

            <!-- Varianti e Offerte -->
            <div class="position-relative mt-auto p-4 z-index-1 w-100">
                <div class="mb-2 text-white-50 small text-uppercase fw-bold">{{ __('Varianti Mancanti e Offerte') }}</div>
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
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle {{ $badgeColor }} d-flex justify-content-center align-items-center" style="width: 24px; height: 24px;">{{ $variantLabel }}</span>
                                <span class="text-white fw-medium">{{ $variantName }}</span>
                            </div>
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
