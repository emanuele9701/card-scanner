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
    public function disponibili(): View
    {
        $language = Auth::user()->language ?? app()->getLocale();

        $series = TCGSeries::where('language', $language)
            ->with(['sets' => function ($query) use ($language) {
                $query->where('language', $language)->orderBy('release_date', 'desc');
            }])
            ->whereHas('sets')
            ->orderBy('name')
            ->get();

        return view('collezioni.disponibili', compact('series'));
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
}
