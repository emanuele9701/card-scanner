@if (request()->has('selected_cards') && !empty(request('selected_cards')))
    <div class="col-12 mb-4">
        <div
            class="alert alert-info border-info bg-info bg-opacity-10 text-info d-flex align-items-center gap-3 rounded-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-bullseye flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                <path d="M8 13A5 5 0 1 1 8 3a5 5 0 0 1 0 10zm0 1A6 6 0 1 0 8 2a6 6 0 0 0 0 12z" />
                <path d="M8 11A3 3 0 1 1 8 5a3 3 0 0 1 0 6zm0 1A4 4 0 1 0 8 4a4 4 0 0 0 0 8z" />
                <path d="M9.5 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
            </svg>
            <div>
                <strong>Ricerca mirata attiva!</strong> Stiamo calcolando le combinazioni ottimali solo per le carte che
                hai selezionato.
                <a href="{{ request()->fullUrlWithQuery(['selected_cards' => null]) }}" class="alert-link ms-2">Cerca su
                    tutto il set</a>
            </div>
        </div>
    </div>
@endif

<div class="col-12 mb-4">
    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3">
        <h4 class="text-white fw-bold mb-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-cart-check-fill text-success me-2" viewBox="0 0 16 16">
                <path
                    d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708z" />
            </svg>
            Carrello Ottimizzato
        </h4>
        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">Copertura massima, minor costo!</span>
    </div>
</div>

@forelse ($sellers as $index => $seller)
    <div class="col-12 mb-5">
        <div class="card bg-dark border border-secondary rounded-4 overflow-hidden shadow-lg position-relative">
            <!-- Header Venditore -->
            <div class="card-header border-bottom border-secondary p-4 d-flex justify-content-between align-items-center"
                style="background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.01) 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex justify-content-center align-items-center flex-shrink-0 text-white fw-bold fs-4"
                        style="width: 56px; height: 56px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);">
                        #{{ $index + 1 }}
                    </div>
                    <div>
                        <h4 class="card-title text-white mb-1 fw-bold d-flex align-items-center gap-2">
                            {{ $seller->seller_name }}
                            <span class="badge bg-info text-dark ms-2">{{ count($seller->cards) }} {{ __('carte') }}</span>
                        </h4>
                        <div class="d-flex align-items-center gap-2 text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                                class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                <path
                                    d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6" />
                            </svg>
                            {{ $seller->seller_country ?: 'N/D' }}
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <div class="text-secondary small text-uppercase fw-bold mb-1">Totale parziale carrello</div>
    @push('styles')
<style>
.glass-badge {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 0.5rem;
    padding: 0.2rem 0.5rem;
}
</style>
@endpush

                    <div class="text-warning fw-bold display-6 mb-2 glass-badge">€
                        {{ number_format($seller->total_price, 2, ',', '.') }}</div>
                    <a href="https://www.cardmarket.com/it/Pokemon/Users/{{ urlencode($seller->seller_name) }}/Offers/Singles"
                        target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        Acquista queste {{ count($seller->cards) }} carte
                    </a>
                </div>
            </div>

            <!-- Griglia Figurine -->
            <div class="card-body p-4 bg-dark">
                <h6 class="text-white-50 text-uppercase fw-bold mb-3 d-flex align-items-center gap-2">
                    Carte fornite da questo venditore ({{ count($seller->cards) }})
                </h6>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
                    @foreach ($seller->cards as $offer)
                        @php
                            $variantColor = match ($offer->variant_name) {
                                'holo' => 'bg-warning text-dark',
                                'reverse' => 'bg-info text-dark',
                                'firstedition' => 'bg-danger text-white',
                                default => 'bg-secondary text-white',
                            };
                            $variantLabel = match ($offer->variant_name) {
                                'holo' => 'Holo',
                                'reverse' => 'Reverse',
                                'firstedition' => '1ª Ed.',
                                default => 'Normal',
                            };
                        @endphp
                        <div class="col">
                            <div class="card h-100 border-0 bg-transparent"
                                style="cursor: pointer; transition: transform 0.2s;"
                                onmouseover="this.style.transform='scale(1.05)';"
                                onmouseout="this.style.transform='scale(1)';">
                                <div class="position-relative rounded-3 overflow-hidden shadow-sm"
                                    style="padding-top: 139.6%; background: url('{{ $offer->card_image ?: 'https://via.placeholder.com/240x330?text=No+Image' }}/low.png') center/cover no-repeat;">
                                    <div class="position-absolute bottom-0 start-0 w-100 p-2"
                                        style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);">
                                        <div class="d-flex justify-content-between align-items-end">
                                            <span
                                                class="badge {{ $variantColor }} border border-dark shadow-sm">{{ $variantLabel }}</span>
                                            <span
                                                class="badge bg-success border border-dark shadow-sm fs-6">€{{ number_format($offer->price_eur, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 text-center text-truncate text-white small fw-bold"
                                    title="{{ $offer->card_name }}">
                                    {{ $offer->card_name }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="card bg-secondary bg-opacity-10 border-secondary border rounded-4 p-5 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor"
                class="bi bi-emoji-frown text-secondary mb-3" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                <path
                    d="M4.285 12.433a.5.5 0 0 0 .683-.183A3.498 3.498 0 0 1 8 10.5c1.295 0 2.426.703 3.032 1.75a.5.5 0 0 0 .866-.5A4.498 4.498 0 0 0 8 9.5a4.5 4.5 0 0 0-3.898 2.25.5.5 0 0 0 .183.683zM7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5zm4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5z" />
            </svg>
            <h4 class="mb-2 text-white fw-bold">Nessun venditore disponibile</h4>
            <p class="mb-0 text-secondary fs-5">Sembra che nessuno venda le carte mancanti richieste, oppure le hai già
                tutte!</p>
        </div>
    </div>
@endforelse

@if (!empty($uncoveredCards))
    <div class="col-12 mt-4">
        <div class="card border-danger border bg-danger bg-opacity-10 rounded-4 overflow-hidden">
            <div class="card-header border-bottom border-danger text-danger fw-bold p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-exclamation-triangle-fill me-2" viewBox="0 0 16 16">
                    <path
                        d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                </svg>
                Carte introvabili ({{ count($uncoveredCards) }})
            </div>
            <div class="card-body p-4">
                <p class="text-danger-emphasis mb-3">Le seguenti carte mancanti non sono state trovate presso alcun
                    venditore su Cardmarket con i parametri attuali:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($uncoveredCards as $unc)
                        <span
                            class="badge bg-dark border border-danger text-light px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                            <div
                                style="width: 20px; height: 28px; background: url('{{ $unc->image ?: 'https://via.placeholder.com/20x28' }}') center/cover; border-radius: 2px;">
                            </div>
                            {{ $unc->name }}
                            <span class="text-danger fw-normal">({{ $unc->variant }})</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
