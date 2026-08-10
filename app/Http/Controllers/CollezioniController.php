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
        $data = app(\App\Services\CollectionCacheService::class)->getForUser($user->id);

        return view('collezioni.mie', $data);
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

            $producedUnique = array_unique(array_map('strtolower', $produced));

            $ownedVariants = [];
            foreach ($card->collectors as $coll) {
                $foil = $coll->foil_type ? strtolower(trim($coll->foil_type)) : null;
                if (!$foil || $foil === 'normal') {
                    if (!in_array('normal', $producedUnique) && in_array('holo', $producedUnique)) {
                        $foil = 'holo';
                    } elseif (!in_array('normal', $producedUnique) && in_array('reverse', $producedUnique)) {
                        $foil = 'reverse';
                    } else {
                        $foil = 'normal';
                    }
                }
                $ownedVariants[] = $foil;
                if ($coll->is_first_edition) {
                    $ownedVariants[] = 'firstedition';
                }
            }
            $ownedVariantsUnique = array_unique($ownedVariants);

            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));

            if (count($missingVariants) > 0) {
                $card->missing_variants = $missingVariants;
                $missingCards->push($card);
            }
        }

        $total = $missingCards->count();
        $paginatedCards = $missingCards->forPage($page, $perPage)->values();
        
        $cardIds = $paginatedCards->pluck('id')->toArray();

        // Load incoming cards for the paginated missing cards
        $incomingCards = \App\Models\UserIncomingCard::where('user_id', $user->id)
            ->whereIn('card_id', $cardIds)
            ->get();
        $incomingByCard = $incomingCards->groupBy('card_id');


        $userCards = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedCards,
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.mancanti-global-grid', ['userCards' => $userCards, 'incomingByCard' => $incomingByCard])->render(),
                'current_page' => $userCards->currentPage(),
                'last_page' => $userCards->lastPage(),
                'per_page' => $userCards->perPage(),
            ]);
        }

        return view('collezioni.mancanti-global', compact('userCards', 'total', 'typeOptions', 'stageOptions', 'setOptions', 'serieOptions', 'incomingByCard'));
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

        $isSetWatchlisted = false;
        $watchlistedCardIds = [];
        if ($user) {
            $isSetWatchlisted = \Illuminate\Support\Facades\DB::table('user_set_watchlists')
                ->where('user_id', $user->id)
                ->where('set_id', $set->id)
                ->exists();
                
            $watchlistedCardIds = \Illuminate\Support\Facades\DB::table('user_card_watchlists')
                ->where('user_id', $user->id)
                ->whereIn('card_id', $allCards->pluck('id'))
                ->pluck('card_id')
                ->toArray();
        }

        $typeOptions = $allCards->flatMap(fn ($card) => is_array($card->types) ? $card->types : [])->unique()->sort()->values();
        $stageOptions = $allCards->pluck('level_stage')->filter()->unique()->values();

        // Build lightweight JSON for client-side rendering
        $allCardsJson = $allCards->map(function($card) use ($watchlistedCardIds) {
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
                'is_watchlisted' => in_array($card->id, $watchlistedCardIds),
            ];
        })->values();

        return view('collezioni.set-detail', compact('set', 'typeOptions', 'stageOptions', 'allCardsJson', 'isSetWatchlisted', 'watchlistedCardIds'));
    }
    public function showMySet(Request $request, TCGSet $set): View|JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 24);
        $tab = $request->input('tab', 'owned');
        
        $filterLanguage = $request->input('filter_language');
        $filterCondition = $request->input('filter_condition');
        $filterVariant = $request->input('filter_variant');
        $filterIncoming = $request->input('filter_incoming');

        // Pre-fetch incoming cards for this set
        $incomingCardsSet = \App\Models\UserIncomingCard::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->get()
            ->groupBy('card_id');

        // Base Query
        $query = \App\Models\TCGCard::where('set_id', $set->id)->orderBy('dexId', 'asc');

        // Apply Tab Filter at DB level for memory efficiency
        if ($tab === 'owned') {
            $query->whereHas('collectors', function($q) use ($user, $filterLanguage, $filterCondition) {
                $q->where('user_id', $user->id);
                if ($filterLanguage) $q->where('language', $filterLanguage);
                if ($filterCondition) $q->where('condition', $filterCondition);
            });
        } elseif ($tab === 'missing') {
            $query->whereDoesntHave('collectors', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
            if ($filterIncoming === 'only_incoming') {
                $query->whereIn('id', $incomingCardsSet->keys());
            } elseif ($filterIncoming === 'only_missing') {
                $query->whereNotIn('id', $incomingCardsSet->keys());
            }
        } elseif ($tab === 'doppie') {
            // Simplified DB doppie check: has copies > 1
            $query->whereHas('collectors', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('quantity', '>', 1);
            });
        }

        // Apply variant filter roughly at DB level if specified
        if ($filterVariant && $tab !== 'missing') {
            $query->whereHas('collectors', function($q) use ($user, $filterVariant) {
                $q->where('user_id', $user->id);
                if ($filterVariant === 'firstedition') {
                    $q->where('is_first_edition', true);
                } else {
                    $q->where('foil_type', $filterVariant);
                }
            });
        }

        // Paginate directly from DB
        $userCards = $query->paginate($perPage)->withQueryString();

        // Eager load collectors and prices ONLY for the paginated items
        $cardIds = $userCards->pluck('id')->toArray();
        $cardsWithRelations = \App\Models\TCGCard::with([
            'prices',
            'collectors' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }
        ])->whereIn('id', $cardIds)->get()->keyBy('id');

        // Compute variant badges ONLY for the paginated items
        foreach ($userCards as $card) {
            $fullCard = $cardsWithRelations->get($card->id);
            $card->prices = $fullCard ? $fullCard->prices : collect();
            $card->collectors = $fullCard ? $fullCard->collectors : collect();

            $produced = $card->produced_variants;
            if (empty($produced)) {
                $produced = ['normal'];
            }
            $producedUnique = array_unique(array_map('strtolower', $produced));

            $ownedVariants = [];
            $variantCounts = [];

            foreach ($card->collectors as $coll) {
                $feats = [];
                $foil = $coll->foil_type ? strtolower(trim($coll->foil_type)) : null;
                if (!$foil || $foil === 'normal') {
                    if (!in_array('normal', $producedUnique) && in_array('holo', $producedUnique)) {
                        $foil = 'holo';
                    } elseif (!in_array('normal', $producedUnique) && in_array('reverse', $producedUnique)) {
                        $foil = 'reverse';
                    } else {
                        $foil = 'normal';
                    }
                }
                $feats[] = $foil;
                if ($coll->is_first_edition) $feats[] = 'firstedition';

                foreach ($feats as $variantItemLow) {
                    $ownedVariants[] = $variantItemLow;
                    if (!isset($variantCounts[$variantItemLow])) {
                        $variantCounts[$variantItemLow] = 0;
                    }
                    $variantCounts[$variantItemLow] += $coll->quantity;
                }
            }

            $ownedVariantsUnique = array_unique($ownedVariants);
            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));

            $incomingVariants = [];
            if ($incomingCardsSet->has($card->id)) {
                foreach ($incomingCardsSet->get($card->id) as $inc) {
                    $foil = $inc->foil_type ? strtolower(trim($inc->foil_type)) : null;
                    if (!$foil || $foil === 'normal') {
                        if (!in_array('normal', $producedUnique) && in_array('holo', $producedUnique)) {
                            $foil = 'holo';
                        } elseif (!in_array('normal', $producedUnique) && in_array('reverse', $producedUnique)) {
                            $foil = 'reverse';
                        } else {
                            $foil = 'normal';
                        }
                    }
                    $incomingVariants[] = $foil;
                    if ($inc->is_first_edition) $incomingVariants[] = 'firstedition';
                }
            }
            $incomingVariantsUnique = array_unique($incomingVariants);

            $missingIncoming = array_values(array_intersect($missingVariants, $incomingVariantsUnique));
            $pureMissing = array_values(array_diff($missingVariants, $incomingVariantsUnique));

            $doppieVariants = [];
            foreach ($variantCounts as $var => $count) {
                if ($count > 1) {
                    $doppieVariants[$var] = $count;
                }
            }

            $card->owned_variants = $ownedVariantsUnique;
            $card->missing_variants = $missingVariants;
            $card->missing_incoming_variants = $missingIncoming;
            $card->pure_missing_variants = $pureMissing;
            $card->doppie_variants = $doppieVariants;
        }

        // Totals for Tabs via Fast DB Aggregates
        $ownedTotal = \App\Models\TCGCard::where('set_id', $set->id)->whereHas('collectors', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        $missingTotal = \App\Models\TCGCard::where('set_id', $set->id)->whereDoesntHave('collectors', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        $doppieTotal = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->where('quantity', '>', 1)
            ->distinct('card_id')
            ->count('card_id');

        // Setup filter options
        $allUserCardsForOptions = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->with('card')
            ->get();
            
        $typeOptions = $allUserCardsForOptions->flatMap(fn ($c) => $c->card && is_array($c->card->types) ? $c->card->types : [])->unique()->sort()->values();
        $stageOptions = $allUserCardsForOptions->map(fn ($c) => $c->card ? $c->card->level_stage : null)->filter()->unique()->values();

        $incomingByCard = collect();
        if ($tab === 'missing') {
            $incomingByCard = $incomingCardsSet->filter(function($val, $key) use ($cardIds) {
                return in_array($key, $cardIds);
            });
        }

        $isSetWatchlisted = false;
        $watchlistedCardIds = [];
        if ($user) {
            $isSetWatchlisted = \Illuminate\Support\Facades\DB::table('user_set_watchlists')
                ->where('user_id', $user->id)
                ->where('set_id', $set->id)
                ->exists();
                
            $watchlistedCardIds = \Illuminate\Support\Facades\DB::table('user_card_watchlists')
                ->where('user_id', $user->id)
                ->whereIn('card_id', $cardIds)
                ->pluck('card_id')
                ->toArray();
        }

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.my-cards-grid', ['userCards' => $userCards, 'tab' => $tab, 'incomingByCard' => $incomingByCard, 'watchlistedCardIds' => $watchlistedCardIds])->render(),
                'current_page' => $userCards->currentPage(),
                'last_page' => $userCards->lastPage(),
                'per_page' => $userCards->perPage(),
                'total' => $userCards->total(),
                'ownedTotal' => $ownedTotal,
                'missingTotal' => $missingTotal,
                'doppieTotal' => $doppieTotal
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
            'doppieTotal',
            'incomingByCard',
            'isSetWatchlisted',
            'watchlistedCardIds'
        ));
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
