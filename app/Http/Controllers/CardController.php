<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCardRequest;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\AssignSetRequest;
use App\Models\CardSet;
use App\Models\PokemonCard;
use App\Services\GoogleDriveService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get all scanned cards with server-side pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');
        $game = $request->input('game', '');
        $set = $request->input('set', '');
        $withoutSet = $request->input('without_set', false);
        $withoutRarity = $request->input('without_rarity', false);
        $onlyDuplicates = $request->input('only_duplicates', false);
        $rarityVariant = $request->input('rarity_variant', '');
        $sortColumn = $request->input('sort_column', '');
        $sortDirection = in_array(strtolower($request->input('sort_direction', 'asc')), ['asc', 'desc'])
            ? strtolower($request->input('sort_direction', 'asc'))
            : 'asc';

        $query = PokemonCard::with('cardSet')
            ->withSum('inventory', 'quantity')
            ->where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('card_name', 'like', '%' . $search . '%')
                    ->orWhere('set_number', 'like', '%' . $search . '%');
            });
        }

        if ($game) {
            $query->where('game', $game);
        }

        if ($set) {
            $query->whereHas('cardSet', function ($q) use ($set) {
                $q->where('name', $set);
            });
        }

        if ($withoutSet) {
            $query->whereNull('card_set_id');
        }

        if ($withoutRarity) {
            $query->where(function ($q) {
                $q->whereNull('rarity')->orWhere('rarity', '');
            });
        }

        if ($onlyDuplicates) {
            $query->whereIn('id', function ($subquery) {
                $subquery->select('pokemon_card_id')
                    ->from('card_inventory')
                    ->where('user_id', auth()->id())
                    ->groupBy('pokemon_card_id')
                    ->havingRaw('SUM(quantity) > 1');
            });
        }

        if ($rarityVariant) {
            $query->whereHas('inventory', function ($q) use ($rarityVariant) {
                $q->where('rarity_variant', $rarityVariant);
            });
        }

        // Apply sorting — whitelist columns to prevent injection
        $allowedSortColumns = ['card_name', 'set_number', 'rarity', 'hp', 'type', 'created_at'];
        if ($sortColumn === 'set_number') {
            $query->orderByRaw("CAST(SUBSTRING_INDEX(set_number, '/', 1) AS UNSIGNED) " . $sortDirection)
                ->orderBy('set_number', $sortDirection);
        } elseif ($sortColumn && in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('card_name');
        }

        $cards = $query->paginate($perPage);

        $availableGames = PokemonCard::where('user_id', auth()->id())
            ->whereNotNull('game')
            ->distinct()
            ->pluck('game')
            ->sort()
            ->values();

        $availableSets = CardSet::whereHas('pokemonCards', function ($q) {
            $q->where('user_id', auth()->id());
        })
            ->pluck('name')
            ->sort()
            ->values();

        $availableVariants = \App\Models\CardInventory::where('user_id', auth()->id())
            ->distinct()
            ->pluck('rarity_variant')
            ->sort()
            ->values();

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
            'availableGames' => $availableGames,
            'availableSets' => $availableSets,
            'availableVariants' => $availableVariants,
            'filters' => [
                'search' => $search,
                'game' => $game,
                'set' => $set,
                'without_set' => $withoutSet,
                'without_rarity' => $withoutRarity,
                'only_duplicates' => $onlyDuplicates,
                'rarity_variant' => $rarityVariant,
                'sort_column' => $sortColumn,
                'sort_direction' => $sortDirection,
            ]
        ]);
    }

    /**
     * Get single card data for modal display
     */
    public function show(PokemonCard $card)
    {
        $this->authorize('view', $card);
        $card->load(['cardSet', 'inventory']);

        return response()->json([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'card_name' => $card->card_name,
                'hp' => $card->hp,
                'type' => $card->type,
                'evolution_stage' => $card->evolution_stage,
                'weakness' => $card->weakness,
                'resistance' => $card->resistance,
                'retreat_cost' => $card->retreat_cost,
                'set_number' => $card->set_number,
                'rarity' => $card->rarity,
                'illustrator' => $card->illustrator,
                'flavor_text' => $card->flavor_text,
                'condition' => $card->condition,
                'acquisition_price' => $card->acquisition_price,
                'image_url' => $card->image_url,
                'card_set_id' => $card->card_set_id,
                'card_set' => $card->cardSet ? ['name' => $card->cardSet->name] : null,
                'estimated_value' => $card->formatted_estimated_value,
                'inventory' => $card->inventory,
                'total_quantity' => $card->getTotalQuantity(),
            ]
        ]);
    }

    /**
     * Update a card
     */
    public function update(UpdateCardRequest $request, PokemonCard $card)
    {
        $this->authorize('update', $card);

        $data = $request->only([
            'card_name',
            'hp',
            'type',
            'evolution_stage',
            'rarity',
            'set_number',
            'illustrator',
            'card_set_id',
            'retreat_cost',
            'weakness',
            'resistance',
            'game'
        ]);

        if ($request->has('game')) {
            $gameModel = \App\Models\Game::firstOrCreate(['name' => $request->game]);
            $data['game_id'] = $gameModel->id;
        }

        $card->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Carta aggiornata con successo!',
            'data' => $card->load('cardSet')
        ]);
    }

    /**
     * Delete a card
     */
    public function destroy(PokemonCard $card)
    {
        $this->authorize('delete', $card);

        if ($card->driveFile && $card->driveFile->isUploaded()) {
            $driveService = app(GoogleDriveService::class);
            $driveService->deleteFile($card->driveFile->drive_id);
        } elseif ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carta eliminata con successo!'
        ]);
    }

    /**
     * Delete multiple cards at once
     */
    public function bulkDestroy(BulkDestroyRequest $request)
    {

        $cards = PokemonCard::with('driveFile')
            ->where('user_id', auth()->id())
            ->whereIn('id', $request->card_ids)
            ->get();

        $count = $cards->count();
        $driveService = app(GoogleDriveService::class);

        foreach ($cards as $card) {
            if ($card->driveFile && $card->driveFile->isUploaded()) {
                try {
                    $driveService->deleteFile($card->driveFile->drive_id);
                } catch (Exception $e) {
                    Log::error("Failed to delete file from Drive for card #{$card->id}: " . $e->getMessage());
                }
            } elseif ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
                Storage::disk('public')->delete($card->storage_path);
            }
            $card->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} carte eliminate con successo!"
        ]);
    }

    /**
     * Assign set to one or more cards
     */
    public function assignSet(AssignSetRequest $request)
    {

        PokemonCard::where('user_id', auth()->id())
            ->whereIn('id', $request->card_ids)
            ->update(['card_set_id' => $request->card_set_id]);

        return response()->json([
            'success' => true,
            'message' => 'Set assegnato con successo!'
        ]);
    }

    /**
     * Get all card sets for dropdown
     */
    public function getCardSets()
    {
        $sets = CardSet::orderBy('name')->get(['id', 'name', 'abbreviation']);

        return response()->json([
            'success' => true,
            'data' => $sets
        ]);
    }

    /**
     * Get all available games for dropdown
     */
    public function getAvailableGames()
    {
        $games = \App\Models\Game::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $games
        ]);
    }
}
