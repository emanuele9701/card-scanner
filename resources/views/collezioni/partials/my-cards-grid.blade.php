@forelse ($userCards as $card)
    @php
        $cardIncoming = isset($incomingByCard) && isset($incomingByCard[$card->id]) ? $incomingByCard[$card->id] : collect();
    @endphp
    @include('collezioni.singles.my-card', ['card' => $card, 'tab' => $tab ?? 'owned', 'cardIncoming' => $cardIncoming, 'watchlistedCardIds' => $watchlistedCardIds ?? []])
@empty
    <div class="col-12">
        <div class="card bg-secondary bg-opacity-10 border-secondary border rounded-4 p-4 text-center">
            <p class="mb-2 text-white fw-semibold">Nessuna carta trovata in questa sezione</p>
            <p class="mb-0 text-secondary">Prova a cambiare i filtri o seleziona l'altra tab.</p>
        </div>
    </div>
@endforelse
