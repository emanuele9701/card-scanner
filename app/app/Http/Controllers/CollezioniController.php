<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use App\Models\TCGSeries;
use App\Models\TCGSet;
use App\Models\UserCardCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollezioniController extends Controller
{

    public function index() {
        $user = Auth::user();

        $collections = $user->collection()
            ->with(['set.serie', 'card.prices'])
            ->get();

        if ($collections->isEmpty()) {
            return view('collezioni.mie', [
                'collezioni' => [],
            ]);
        }

        return view('collezioni.mie', [
            'collezioni' => $collections,
        ]);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $collections = \App\Models\UserCardCollection::with(['card', 'card.set', 'card.prices'])
            ->where('user_id', $user->id)
            ->get();
            
        $csvHeader = ['Card Name', 'Set', 'Rarity', 'Condition', 'Variants', 'Quantity', 'Estimated Unit Value', 'Estimated Total Value'];
        $csvData = [];
        
        foreach ($collections as $item) {
            $card = $item->card;
            if (!$card) continue;
            
            $price = 0;
            $priceModel = $card->prices->first();
            if ($priceModel) {
                $isHoloOrReverse = false;
                if (is_array($item->variants)) {
                    $variantsLower = array_map('strtolower', $item->variants);
                    $isHoloOrReverse = in_array('holo', $variantsLower) || in_array('reverse', $variantsLower);
                }
                if ($isHoloOrReverse) {
                    $price = $priceModel->trend_holo ?? $priceModel->avg_holo ?? $priceModel->trend ?? $priceModel->avg ?? 0;
                } else {
                    $price = $priceModel->trend ?? $priceModel->avg ?? 0;
                }
            }
            
            $csvData[] = [
                $card->name,
                $card->set->name ?? '',
                $card->rarity ?? '',
                $item->condition ?? 'NM',
                is_array($item->variants) ? implode(', ', $item->variants) : '',
                $item->quantity,
                number_format((float)$price, 2, '.', ''),
                number_format((float)$price * $item->quantity, 2, '.', ''),
            ];
        }
        
        $filename = "pokestash_collection_" . date('Y-m-d') . ".csv";
        
        return response()->streamDownload(function() use ($csvHeader, $csvData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function missingGlobal(Request $request)
    {
        $user = Auth::user();
        $perPage = 20;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        
        $collectedSetIds = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->select('set_id')
            ->distinct()
            ->pluck('set_id');

        $allCardsQuery = \App\Models\TCGCard::whereIn('tcg_cards.set_id', $collectedSetIds)
            ->with(['set', 'prices', 'collectors' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
            
        $typeFilter = $request->input('type');
        $stageFilter = $request->input('stage');
        $setFilter = $request->input('set');
        $serieFilter = $request->input('serie');

        if ($search = $request->input('search')) {
            $allCardsQuery->where('tcg_cards.name', 'like', '%' . $search . '%');
        }

        if ($typeFilter) {
            $allCardsQuery->whereJsonContains('tcg_cards.types', $typeFilter);
        }

        if ($stageFilter) {
            $allCardsQuery->where('tcg_cards.level_stage', $stageFilter);
        }

        if ($setFilter) {
            $allCardsQuery->where('tcg_cards.set_id', $setFilter);
        }

        if ($serieFilter) {
            $allCardsQuery->where('tcg_sets.serie_id', $serieFilter);
        }

        // Ordinamento globale per data di rilascio del set o dexId
        $allCardsQuery->join('tcg_sets', 'tcg_cards.set_id', '=', 'tcg_sets.id')
            ->orderBy('tcg_sets.release_date', 'desc')
            ->orderBy('tcg_cards.dexId', 'asc')
            ->select('tcg_cards.*');

        // To extract available filter options across collected sets, we do a fast query on TCGCard
        // We do this before the get() to get ALL options regardless of the current search filter
        $optionsCards = \App\Models\TCGCard::whereIn('set_id', $collectedSetIds)->get();
        $typeOptions = $optionsCards->flatMap(fn ($c) => is_array($c->types) ? $c->types : [])->unique()->sort()->values();
        $stageOptions = $optionsCards->map(fn ($c) => $c->level_stage)->filter()->unique()->values();

        $setOptions = \App\Models\TCGSet::whereIn('id', $collectedSetIds)->orderBy('release_date', 'desc')->get();
        $serieIds = $setOptions->pluck('serie_id')->unique();
        $serieOptions = \App\Models\TCGSeries::whereIn('id', $serieIds)->orderBy('id', 'desc')->get();

        $allCards = $allCardsQuery->get();
        $missingCards = collect();

        foreach ($allCards as $card) {
            $produced = $card->produced_variants;
            if (empty($produced)) $produced = ['normal'];

            $ownedVariants = [];
            foreach ($card->collectors as $coll) {
                if ($coll->foil_type) $ownedVariants[] = strtolower(trim($coll->foil_type));
                if ($coll->is_first_edition) $ownedVariants[] = 'firstedition';
            }
            if (empty($ownedVariants)) {
                $ownedVariants[] = 'normal';
            }
            $ownedVariantsUnique = array_unique($ownedVariants);
            $producedUnique = array_unique(array_map('strtolower', $produced));

            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));

            if (count($missingVariants) > 0) {
                $card->missing_variants = $missingVariants;
                $missingCards->push($card);
            }
        }

        $total = $missingCards->count();
        $paginatedCards = $missingCards->forPage($page, $perPage)->values();
        
        $cardIds = $paginatedCards->pluck('id')->toArray();


        $userCards = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedCards,
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.mancanti-global-grid', ['userCards' => $userCards])->render(),
                'current_page' => $userCards->currentPage(),
                'last_page' => $userCards->lastPage(),
                'per_page' => $userCards->perPage(),
            ]);
        }

        return view('collezioni.mancanti-global', compact('userCards', 'total', 'typeOptions', 'stageOptions', 'setOptions', 'serieOptions'));
    }

    /**
     * Mostra tutti i set disponibili raggruppati per serie.
     */
    public function disponibili(Request $request): View
    {
        $language = Auth::user()->language ?? app()->getLocale();

        $search = $request->input('search');
        $year = $request->input('year');
        $sort = $request->input('sort', 'desc');

        $allSets = TCGSet::has('cards')->get(['id', 'set_id', 'language']);
        
        $selectedIds = [];
        $grouped = $allSets->groupBy('set_id');
        foreach ($grouped as $setId => $versions) {
            $preferred = $versions->firstWhere('language', $language);
            if ($preferred) {
                $selectedIds[] = $preferred->id;
            } else {
                $en = $versions->firstWhere('language', 'en');
                $selectedIds[] = $en ? $en->id : $versions->first()->id;
            }
        }

        $setsQuery = TCGSet::whereIn('id', $selectedIds)->with('serie');

        if ($search) {
            $setsQuery->where('name', 'like', '%' . $search . '%');
        }

        if ($year) {
            $setsQuery->whereYear('release_date', $year);
        }

        if ($sort === 'asc') {
            $setsQuery->orderBy('release_date', 'asc');
        } else {
            $setsQuery->orderBy('release_date', 'desc');
        }

        $sets = $setsQuery->paginate(30)->withQueryString();

        $years = TCGSet::whereIn('id', $selectedIds)
            ->whereNotNull('release_date')
            ->selectRaw('YEAR(release_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('collezioni.disponibili', compact('sets', 'years', 'search', 'year', 'sort', 'language'));
    }

    /**
     * Mostra il dettaglio di un singolo set.
     */
    public function showSet(Request $request, TCGSet $set): View|JsonResponse
    {
        $user = Auth::user();
        $language = $user->language ?? app()->getLocale();

        $allCards = $set->cards()->with(['prices', 'collectors' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->orderBy('dexId', 'asc')->get();

        $allCards->transform(function($card) {
            $card->isCollected = $card->collectors->isNotEmpty();
            return $card;
        });

        $typeOptions = $allCards->flatMap(fn ($card) => is_array($card->types) ? $card->types : [])->unique()->sort()->values();
        $stageOptions = $allCards->pluck('level_stage')->filter()->unique()->values();

        // Build lightweight JSON for client-side rendering
        $allCardsJson = $allCards->map(function($card) {
            $latestPrice = $card->prices->sortByDesc('updated_at')->first();
            return [
                'id'          => $card->id,
                'name'        => $card->name,
                'dexId'       => $card->dexId,
                'rarity'      => $card->rarity ?? '',
                'type'        => $card->type ?? '',
                'types'       => $card->types ?? [],
                'level_stage' => $card->level_stage ?? '',
                'url_image'   => $card->url_image,
                'language'    => $card->language ?? '',
                'illustrator' => $card->illustrator ?? '',
                'evolve_from' => $card->evolve_from ?? '',
                'isCollected' => $card->isCollected,
                'price'       => $latestPrice ? ($latestPrice->avg ?? 0) : 0,
                'set_name'    => optional($card->set)->name ?? '',
                'set_symbol'  => optional($card->set)->symbol ?? '',
                'set_abbr'    => optional($card->set)->abbreviation_official ?? '',
            ];
        })->values();

        return view('collezioni.set-detail', compact('set', 'typeOptions', 'stageOptions', 'allCardsJson'));
    }

    public function addCardToCollection(TCGCard $card) {
        $user = Auth::user();

        if(!$card->collectors()->where('user_id',$user->id)->first()) {
            // Aggiungo
            $card->collectors()->create([
                'user_id' => $user->id,
                'set_id' => $card->set_id,
                'serie_id' => $card->set->serie_id
            ]);            
            return response()->json(['esito' => true, 'message' => __('Carta aggiunta')]);
        }
        
        return response()->json(['esito' => false, 'message' => __('Carta già inserita')]);
    }

    /**
     * Mostra il dettaglio di un singolo set della collezione dell'utente.
     */
    public function showMySet(Request $request, TCGSet $set): View|JsonResponse
    {
        $user = Auth::user();
        $language = $user->language ?? app()->getLocale();

        $perPage = (int) $request->input('per_page', 100);
        $allowedPerPage = [100, 200, 300];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 100;
        }

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $stageFilter = $request->input('stage');
        $sort = $request->input('sort', 'name_desc');

        $tab = $request->input('tab', 'owned');

        // Fetch all matching cards from DB based on filters
        $allCardsQuery = \App\Models\TCGCard::where('set_id', $set->id)
            ->with(['prices', 'collectors' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

        if ($search) {
            $allCardsQuery->where('name', 'like', '%' . $search . '%');
        }

        if ($typeFilter) {
            $allCardsQuery->whereJsonContains('types', $typeFilter);
        }

        if ($stageFilter) {
            $allCardsQuery->where('level_stage', $stageFilter);
        }

        match ($sort) {
            'name_desc' => $allCardsQuery->orderBy('name', 'desc'),
            'name_asc' => $allCardsQuery->orderBy('name', 'asc'),
            'rarity_desc' => $allCardsQuery->orderBy('rarity', 'desc'),
            'rarity_asc' => $allCardsQuery->orderBy('rarity', 'asc'),
            'dex_desc' => $allCardsQuery->orderBy('dexId', 'desc'),
            default => $allCardsQuery->orderBy('dexId', 'asc'),
        };

        $allCards = $allCardsQuery->get();

        $ownedCards = collect();
        $missingCards = collect();
        $doppieCards = collect();

        foreach ($allCards as $card) {
            $produced = $card->produced_variants;
            if (empty($produced)) {
                $produced = ['normal'];
            }

            $ownedVariants = [];
            $variantCounts = [];
            foreach ($card->collectors as $coll) {
                $feats = [];
                if ($coll->foil_type) $feats[] = strtolower(trim($coll->foil_type));
                if ($coll->is_first_edition) $feats[] = 'firstedition';
                if (empty($feats)) $feats[] = 'normal';

                foreach ($feats as $variantItemLow) {
                    $ownedVariants[] = $variantItemLow;
                    if (!isset($variantCounts[$variantItemLow])) {
                        $variantCounts[$variantItemLow] = 0;
                    }
                    $variantCounts[$variantItemLow] += $coll->quantity;
                }
            }
            // Normalize variants to lowercase to avoid duplicates like 'Holo' and 'holo'
            $ownedVariantsUnique = array_unique($ownedVariants);
            $producedUnique = array_unique(array_map('strtolower', $produced));

            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));

            $doppieVariants = [];
            foreach ($variantCounts as $var => $count) {
                if ($count > 1) {
                    $doppieVariants[$var] = $count;
                }
            }

            $card->owned_variants = $ownedVariantsUnique;
            $card->missing_variants = $missingVariants;
            $card->doppie_variants = $doppieVariants;

            if (count($ownedVariantsUnique) > 0) {
                $ownedCards->push($card);
            }
            
            if (count($missingVariants) > 0) {
                $missingCards->push($card);
            }

            if (count($doppieVariants) > 0) {
                $doppieCards->push($card);
            }
        }

        $collectionToPaginate = match($tab) {
            'missing' => $missingCards,
            'doppie' => $doppieCards,
            default => $ownedCards,
        };

        $userCards = null;
        $sellers = null;

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $userCards = new \Illuminate\Pagination\LengthAwarePaginator(
            $collectionToPaginate->forPage($currentPage, $perPage)->values(),
            $collectionToPaginate->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $allUserCardsForOptions = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->with('card')
            ->get();
            
        $typeOptions = $allUserCardsForOptions->flatMap(fn ($c) => $c->card && is_array($c->card->types) ? $c->card->types : [])->unique()->sort()->values();
        $stageOptions = $allUserCardsForOptions->map(fn ($c) => $c->card ? $c->card->level_stage : null)->filter()->unique()->values();

        $ownedTotal = $ownedCards->count();
        $missingTotal = $missingCards->count();
        $doppieTotal = $doppieCards->count();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.my-cards-grid', ['userCards' => $userCards, 'tab' => $tab])->render(),
                'current_page' => $userCards->currentPage(),
                'last_page' => $userCards->lastPage(),
                'per_page' => $userCards->perPage(),
            ]);
        }

        return view('collezioni.mie-set-detail', compact(
            'set', 
            'userCards', 
            'typeOptions', 
            'stageOptions',
            'tab',
            'ownedTotal',
            'missingTotal',
            'doppieTotal'
        ));
    }

    public function getCardCopies(TCGCard $card) {
        $user = Auth::user();
        $copies = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->get();
        return response()->json($copies);
    }

    public function addCardCopy(Request $request, TCGCard $card) {
        $request->validate([
            'condition' => 'required|in:NM,LP,MP,HP,DMG',
            'language' => 'nullable|string',
            'foil_type' => 'nullable|in:normal,holo,reverse',
            'is_first_edition' => 'boolean',
            'is_signed' => 'boolean',
            'is_altered' => 'boolean',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $user = Auth::user();
        
        $existing = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('condition', $request->condition)
            ->where('language', $request->language ?: 'en')
            ->where('foil_type', $request->foil_type ?: 'normal')
            ->where('is_first_edition', $request->boolean('is_first_edition'))
            ->where('is_signed', $request->boolean('is_signed'))
            ->where('is_altered', $request->boolean('is_altered'))
            ->first();

        if ($existing) {
            $existing->quantity += $request->quantity;
            $existing->save();
        } else {
            \App\Models\UserCardCollection::create([
                'user_id' => $user->id,
                'card_id' => $card->id,
                'set_id' => $card->set_id,
                'serie_id' => $card->set->serie_id ?? $card->set?->serie_id,
                'condition' => $request->condition,
                'language' => $request->language ?: 'en',
                'foil_type' => $request->foil_type ?: 'normal',
                'is_first_edition' => $request->boolean('is_first_edition'),
                'is_signed' => $request->boolean('is_signed'),
                'is_altered' => $request->boolean('is_altered'),
                'quantity' => $request->quantity,
            ]);
        }
        
        return response()->json(['success' => true]);
    }

    public function updateCardCopy(Request $request, \App\Models\UserCardCollection $copy) {
        if ($copy->user_id !== Auth::id()) {
            abort(403);
        }
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);
        
        if ($request->quantity == 0) {
            $copy->delete();
        } else {
            $copy->quantity = $request->quantity;
            $copy->save();
        }
        
        return response()->json(['success' => true]);
    }

    public function deleteCardCopy(\App\Models\UserCardCollection $copy) {
        if ($copy->user_id !== Auth::id()) {
            abort(403);
        }
        $copy->delete();
        return response()->json(['success' => true]);
    }

    public function removeCardFromCollection(TCGCard $card) {
        $user = Auth::user();
        \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->delete();
        
        return response()->json(['success' => true]);
    }

    public function getMassCardCopies(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer|exists:tcg_cards,id'
        ]);

        $userId = Auth::id();
        
        $copies = UserCardCollection::with(['card', 'card.set'])
            ->where('user_id', $userId)
            ->whereIn('card_id', $request->card_ids)
            ->get();
            
        // Group by card_id for frontend convenience
        $grouped = [];
        foreach($copies as $copy) {
            $cid = $copy->card_id;
            if(!isset($grouped[$cid])) {
                $grouped[$cid] = [
                    'card' => $copy->card,
                    'copies' => []
                ];
            }
            $grouped[$cid]['copies'][] = $copy;
        }

        return response()->json(array_values($grouped));
    }

    public function massAddCopies(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer|exists:tcg_cards,id',
            'condition' => 'required|in:NM,LP,MP,HP,DMG',
            'language' => 'nullable|string',
            'foil_type' => 'nullable|in:normal,holo,reverse',
            'is_first_edition' => 'boolean',
            'is_signed' => 'boolean',
            'is_altered' => 'boolean',
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $condition = $request->condition;
        
        $language = $request->language ?: 'en';
        $foil_type = $request->foil_type ?: 'normal';
        $is_first_edition = $request->boolean('is_first_edition');
        $is_signed = $request->boolean('is_signed');
        $is_altered = $request->boolean('is_altered');

        DB::beginTransaction();
        try {
            foreach ($request->card_ids as $cardId) {
                // Check if copy already exists
                $existing = UserCardCollection::where('user_id', $userId)
                    ->where('card_id', $cardId)
                    ->where('condition', $condition)
                    ->where('language', $language)
                    ->where('foil_type', $foil_type)
                    ->where('is_first_edition', $is_first_edition)
                    ->where('is_signed', $is_signed)
                    ->where('is_altered', $is_altered)
                    ->first();

                if ($existing) {
                    $existing->quantity += $request->quantity;
                    $existing->save();
                } else {
                    $card = \App\Models\TCGCard::find($cardId);
                    if ($card) {
                        UserCardCollection::create([
                            'user_id' => $userId,
                            'card_id' => $cardId,
                            'set_id' => $card->set_id,
                            'serie_id' => $card->set->serie_id ?? $card->set?->serie_id,
                            'condition' => $condition,
                            'language' => $language,
                            'foil_type' => $foil_type,
                            'is_first_edition' => $is_first_edition,
                            'is_signed' => $is_signed,
                            'is_altered' => $is_altered,
                            'quantity' => $request->quantity,
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function massUpdateQuantities(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*' => 'integer|min:0'
        ]);

        $userId = Auth::id();

        DB::beginTransaction();
        try {
            foreach ($request->updates as $copyId => $newQty) {
                $copy = UserCardCollection::where('id', $copyId)
                    ->where('user_id', $userId)
                    ->first();
                
                if ($copy) {
                    if ($newQty == 0) {
                        $copy->delete();
                    } else {
                        $copy->quantity = $newQty;
                        $copy->save();
                    }
                }
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function massRemoveCards(Request $request) {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer'
        ]);
        
        $user = Auth::user();
        \App\Models\UserCardCollection::where('user_id', $user->id)
            ->whereIn('card_id', $request->card_ids)
            ->delete();
            
        return response()->json(['success' => true]);
    }

    public function missingCards(TCGSet $set) {
        $user = Auth::user();
        $missingCards = \App\Models\TCGCard::where('set_id', $set->id)
            ->whereDoesntHave('collectors', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('dexId', 'asc')
            ->get(['id', 'name', 'url_image', 'dexId', 'rarity']);
            
        return response()->json($missingCards);
    }
}
