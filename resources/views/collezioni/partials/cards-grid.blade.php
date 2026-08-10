@forelse ($cards as $card)
    @include('collezioni.singles.cards', ['card' => $card])
@empty
    <div class="col-12">
        <div class="card bg-secondary bg-opacity-10 border-secondary border rounded-4 p-4 text-center">
            <p class="mb-2 text-white fw-semibold">Nessuna carta trovata</p>
            <p class="mb-0 text-secondary">Prova a modificare i filtri o la ricerca per visualizzare altre carte.</p>
        </div>
    </div>
@endforelse
