@if(request()->has('selected_cards') && !empty(request('selected_cards')))
    <div class="col-12 mb-3">
        <div class="alert alert-info border-info bg-info bg-opacity-10 text-info d-flex align-items-center gap-3 rounded-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle-fill flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
            </svg>
            <div>
                <strong>Ricerca mirata attiva!</strong> Stiamo calcolando i migliori venditori esclusivamente per le carte specifiche che hai selezionato.
                <a href="{{ request()->fullUrlWithQuery(['selected_cards' => null]) }}" class="alert-link ms-2">Torna a cercare su tutto il set</a>
            </div>
        </div>
    </div>
@endif

@forelse ($sellers as $seller)
    <div class="col-12">
        <div class="card bg-dark border-secondary border rounded-4 overflow-hidden" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex justify-content-center align-items-center flex-shrink-0" style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" class="bi bi-person-fill" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="card-title text-white mb-1 fw-bold">{{ $seller->seller_name }}</h5>
                        <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 0.85rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                            </svg>
                            {{ $seller->seller_country ?: 'N/D' }}
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div class="text-md-end">
                        <div class="text-secondary" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Carte mancanti disponibili</div>
                        <div class="text-info fw-bold fs-5">{{ $seller->missing_cards_available }} <span class="text-secondary fs-6 fw-normal">carte uniche</span></div>
                    </div>
                    <div class="text-md-end" style="min-width: 120px;">
                        <div class="text-secondary" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Stima Totale</div>
                        <div class="text-warning fw-bold fs-5">€ {{ number_format($seller->total_price_sum, 2, ',', '.') }}</div>
                    </div>
                    <a href="https://www.cardmarket.com/it/Pokemon/Users/{{ urlencode($seller->seller_name) }}/Offers/Singles" target="_blank" class="btn btn-outline-info rounded-pill px-4 fw-bold">
                        Vedi su Cardmarket
                    </a>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="card bg-secondary bg-opacity-10 border-secondary border rounded-4 p-4 text-center">
            <p class="mb-2 text-white fw-semibold">Nessuna offerta trovata</p>
            <p class="mb-0 text-secondary">Sembra che tu non abbia carte mancanti in questo set, oppure non ci sono venditori su Cardmarket al momento.</p>
        </div>
    </div>
@endforelse
