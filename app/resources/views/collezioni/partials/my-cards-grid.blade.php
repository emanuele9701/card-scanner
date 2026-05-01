@forelse ($userCards as $card)
    @include('collezioni.singles.my-card', ['card' => $card])
@empty
    <div class="col-12">
        <div class="card bg-secondary bg-opacity-10 border-secondary border rounded-4 p-4 text-center">
            <p class="mb-2 text-white fw-semibold">Nessuna carta trovata nella tua collezione per questo set</p>
            <p class="mb-0 text-secondary">Aggiungi nuove carte dalla pagina "Collezioni disponibili".</p>
        </div>
    </div>
@endforelse
