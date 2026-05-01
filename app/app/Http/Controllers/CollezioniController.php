<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use App\Models\TCGSeries;
use App\Models\TCGSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * Mostra tutti i set disponibili raggruppati per serie.
     */
    public function disponibili(Request $request): View
    {
        $language = Auth::user()->language ?? app()->getLocale();

        $search = $request->input('search');
        $year = $request->input('year');
        $sort = $request->input('sort', 'desc');

        $setsQuery = TCGSet::where('language', $language)->with('serie');

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

        $years = TCGSet::where('language', $language)
            ->whereNotNull('release_date')
            ->selectRaw('YEAR(release_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('collezioni.disponibili', compact('sets', 'years', 'search', 'year', 'sort'));
    }

    /**
     * Mostra il dettaglio di un singolo set.
     */
    public function showSet(Request $request, TCGSet $set): View|JsonResponse
    {
        $user = Auth::user();
        $language = $user->language ?? app()->getLocale();

        if ($set->language !== $language) {
            $translated = TCGSet::where('set_id', $set->set_id)
                ->where('language', $language)
                ->first();

            if ($translated) {
                $set = $translated;
            }
        }

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 15, 20];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $stageFilter = $request->input('stage');
        $sort = $request->input('sort', 'dex_asc');

        $cardsQuery = $set->cards()->with('prices');

        if ($search) {
            $cardsQuery->where('name', 'like', '%' . $search . '%');
        }

        if ($typeFilter) {
            $cardsQuery->whereJsonContains('types', $typeFilter);
        }

        if ($stageFilter) {
            $cardsQuery->where('level_stage', $stageFilter);
        }

        match ($sort) {
            'name_desc' => $cardsQuery->orderBy('name', 'desc'),
            'name_asc' => $cardsQuery->orderBy('name', 'asc'),
            'rarity_desc' => $cardsQuery->orderBy('rarity', 'desc'),
            'rarity_asc' => $cardsQuery->orderBy('rarity', 'asc'),
            'dex_desc' => $cardsQuery->orderBy('dexId', 'desc'),
            default => $cardsQuery->orderBy('dexId', 'asc'),
        };

        $cards = $cardsQuery->paginate($perPage)->withQueryString();

        $allCardsForOptions = $set->cards()->get();
        $typeOptions = $allCardsForOptions->flatMap(fn ($card) => is_array($card->types) ? $card->types : [])->unique()->sort()->values();
        $stageOptions = $allCardsForOptions->pluck('level_stage')->filter()->unique()->values();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.cards-grid', compact('cards'))->render(),
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'per_page' => $cards->perPage(),
            ]);
        }

        return view('collezioni.set-detail', compact('set', 'cards', 'typeOptions', 'stageOptions'));
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
            return response()->json(['esito' => true, 'message' => 'Carta aggiunta']);
        }
        
        return response()->json(['esito' => false, 'message' => 'Carta già inserita']);
    }

    /**
     * Mostra il dettaglio di un singolo set della collezione dell'utente.
     */
    public function showMySet(Request $request, TCGSet $set): View|JsonResponse
    {
        $user = Auth::user();
        $language = $user->language ?? app()->getLocale();

        if ($set->language !== $language) {
            $translated = TCGSet::where('set_id', $set->set_id)
                ->where('language', $language)
                ->first();

            if ($translated) {
                $set = $translated;
            }
        }

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 15, 20];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $stageFilter = $request->input('stage');
        $sort = $request->input('sort', 'dex_asc');

        // Get user's cards for this set (distinct cards)
        $userCardsQuery = \App\Models\TCGCard::where('set_id', $set->id)
            ->whereHas('collectors', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['prices', 'collectors' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

        if ($search) {
            $userCardsQuery->where('name', 'like', '%' . $search . '%');
        }

        if ($typeFilter) {
            $userCardsQuery->whereJsonContains('types', $typeFilter);
        }

        if ($stageFilter) {
            $userCardsQuery->where('level_stage', $stageFilter);
        }

        match ($sort) {
            'name_desc' => $userCardsQuery->orderBy('name', 'desc'),
            'name_asc' => $userCardsQuery->orderBy('name', 'asc'),
            'rarity_desc' => $userCardsQuery->orderBy('rarity', 'desc'),
            'rarity_asc' => $userCardsQuery->orderBy('rarity', 'asc'),
            'dex_desc' => $userCardsQuery->orderBy('dexId', 'desc'),
            default => $userCardsQuery->orderBy('dexId', 'asc'),
        };

        $userCards = $userCardsQuery->paginate($perPage)->withQueryString();

        $allUserCardsForOptions = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->with('card')
            ->get();
            
        $typeOptions = $allUserCardsForOptions->flatMap(fn ($c) => $c->card && is_array($c->card->types) ? $c->card->types : [])->unique()->sort()->values();
        $stageOptions = $allUserCardsForOptions->map(fn ($c) => $c->card ? $c->card->level_stage : null)->filter()->unique()->values();

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('collezioni.partials.my-cards-grid', ['userCards' => $userCards])->render(),
                'current_page' => $userCards->currentPage(),
                'last_page' => $userCards->lastPage(),
                'per_page' => $userCards->perPage(),
            ]);
        }

        return view('collezioni.mie-set-detail', compact('set', 'userCards', 'typeOptions', 'stageOptions'));
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
            'variants' => 'array',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $user = Auth::user();
        $variants = $request->input('variants', []);
        
        $existing = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->where('condition', $request->condition)
            ->get()
            ->filter(function($col) use ($variants) {
                $colVariants = is_array($col->variants) ? $col->variants : [];
                $reqVariants = is_array($variants) ? $variants : [];
                sort($colVariants);
                sort($reqVariants);
                return $colVariants === $reqVariants;
            })->first();

        if ($existing) {
            $existing->quantity += $request->quantity;
            $existing->save();
        } else {
            \App\Models\UserCardCollection::create([
                'user_id' => $user->id,
                'card_id' => $card->id,
                'set_id' => $card->set_id,
                'serie_id' => $card->set->serie_id,
                'condition' => $request->condition,
                'variants' => $variants,
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
