<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserCardRequest;
use App\Http\Requests\UpdateUserCardRequest;
use App\Models\UserCardCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserCardCollectionController extends Controller
{
    /**
     * Lista tutte le carte nella collezione dell'utente autenticato.
     * Supporta filtri per set_id, condition, e paginazione.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserCardCollection::where('user_id', $request->user()->id)
            ->with(['card.set', 'media']);

        // Filtro per set
        if ($request->has('set_id')) {
            $query->whereHas('card', function ($q) use ($request) {
                $q->where('set_id', $request->input('set_id'));
            });
        }

        // Filtro per condizione
        if ($request->has('condition')) {
            $query->where('condition', $request->input('condition'));
        }

        // Filtro per lingua della carta
        if ($request->has('language')) {
            $query->whereHas('card', function ($q) use ($request) {
                $q->where('language', $request->input('language'));
            });
        }

        $collection = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $collection->items(),
            'meta' => [
                'current_page' => $collection->currentPage(),
                'last_page' => $collection->lastPage(),
                'per_page' => $collection->perPage(),
                'total' => $collection->total(),
            ],
        ]);
    }

    /**
     * Mostra un singolo elemento della collezione.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $item = UserCardCollection::where('user_id', $request->user()->id)
            ->with(['card.set', 'card.abilities', 'card.prices', 'media'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Aggiunge una carta alla collezione dell'utente.
     */
    public function store(StoreUserCardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $item = UserCardCollection::create($validated);

        // Upload foto se presenti
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $item->addMedia($photo)
                    ->toMediaCollection('photos');
            }
        }

        $item->load(['card.set', 'media']);

        return response()->json([
            'success' => true,
            'message' => 'Carta aggiunta alla collezione.',
            'data' => $item,
        ], 201);
    }

    /**
     * Aggiorna un elemento della collezione.
     */
    public function update(UpdateUserCardRequest $request, int $id): JsonResponse
    {
        $item = UserCardCollection::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $item->update($request->validated());

        // Upload nuove foto se presenti
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $item->addMedia($photo)
                    ->toMediaCollection('photos');
            }
        }

        $item->load(['card.set', 'media']);

        return response()->json([
            'success' => true,
            'message' => 'Collezione aggiornata.',
            'data' => $item,
        ]);
    }

    /**
     * Rimuove una carta dalla collezione.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = UserCardCollection::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carta rimossa dalla collezione.',
        ]);
    }

    /**
     * Elimina una specifica foto dalla collezione.
     */
    public function deletePhoto(Request $request, int $id, int $mediaId): JsonResponse
    {
        $item = UserCardCollection::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $media = $item->media()->where('id', $mediaId)->firstOrFail();
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminata.',
        ]);
    }

    /**
     * Statistiche della collezione dell'utente.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totalCards = UserCardCollection::where('user_id', $userId)->sum('quantity');
        $uniqueCards = UserCardCollection::where('user_id', $userId)->distinct('card_id')->count('card_id');
        $byCondition = UserCardCollection::where('user_id', $userId)
            ->selectRaw('`condition`, COUNT(*) as count, SUM(quantity) as total_quantity')
            ->groupBy('condition')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_cards' => (int) $totalCards,
                'unique_cards' => $uniqueCards,
                'by_condition' => $byCondition,
            ],
        ]);
    }
}
