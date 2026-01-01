@extends('layouts.app')

@section('title', 'My Cards')

@section('content')
<div class="container">
    <div class="text-center mb-5">
        <h1 class="page-title">La Mia Collezione</h1>
        <p class="page-subtitle">Tutte le tue carte Pokemon scansionate</p>
    </div>

    @if($cards-&gt;count() &gt; 0)
    <div class="row g-4">
        @foreach($cards as $card)
        <div class="col-md-6 col-lg-4">
            <div class="glass-card p-3 h-100">
                <div class="card-pokemon-display">
                    @if($card->storage_path)
                    <img src="{{ Storage::url($card->storage_path) }}" 
                         alt="{{ $card->card_name ?? 'Pokemon Card' }}"
                         class="img-fluid rounded mb-3"
                         style="width: 100%; aspect-ratio: 0.70; object-fit: cover;">
                    @endif
                    
                    <div class="card-details">
                        <h5 class="text-pokemon-yellow mb-2">
                            {{ $card->card_name ?? 'Carta Sconosciuta' }}
                        </h5>
                        
                        @if($card->hp || $card->type)
                        <div class="d-flex gap-2 mb-2">
                            @if($card->hp)
                            <span class="badge bg-danger">HP {{ $card->hp }}</span>
                            @endif
                            @if($card->type)
                            <span class="type-badge {{ 'type-' . strtolower($card->type) }}">
                                {{ $card->type }}
                            </span>
                            @endif
                        </div>
                        @endif

                        @if($card->set_number)
                        <p class="small text-white-50 mb-2">
                            <i class="bi bi-hash"></i> {{ $card->set_number }}
                        </p>
                        @endif

                        @if($card->rarity)
                        <p class="small text-white-50 mb-2">
                            <i class="bi bi-star-fill"></i> {{ $card->rarity }}
                        </p>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-white-50">
                                <i class="bi bi-calendar"></i> {{ $card->created_at->format('d/m/Y') }}
                            </small>
                            
                            <button class="btn btn-sm btn-danger-pokemon" 
                                    onclick="deleteCard({{ $card->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt- d-flex justify-content-center">
        {{ $cards->links() }}
    </div>
    @else
    <div class="glass-card p-5 text-center">
        <i class="bi bi-inbox" style="font-size: 64px; color: rgba(255, 255, 255, 0.3);"></i>
        <h3 class="mt-3">Nessuna Carta Trovata</h3>
        <p class="text-white-50">Inizia a scansionare le tue carte!</p>
        <a href="{{ route('cards.upload') }}" class="btn btn-pokemon mt-3">
            <i class="bi bi-camera-fill"></i> Carica Carte
        </a>
    </div>
    @endif
</div>

<style>
.text-pokemon-yellow {
    color: var(--pokemon-yellow);
}

.card-pokemon-display {
    transition: transform 0.3s ease;
}

.glass-card:hover .card-pokemon-display {
    transform: translateY(-5px);
}
</style>

<script>
async function deleteCard(cardId) {
    if (!confirm('Sei sicuro di voler eliminare questa carta?')) return;
    
    try {
        const response = await fetch(`/cards/${cardId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Carta eliminata con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Errore durante l\'eliminazione', 'error');
        }
    } catch (error) {
        showToast('Errore durante l\'eliminazione', 'error');
    }
}
</script>
@endsection
