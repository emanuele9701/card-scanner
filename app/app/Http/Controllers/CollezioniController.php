<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use App\Models\TCGSeries;
use App\Models\TCGSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollezioniController extends Controller
{

    public function index() {
        $user = Auth::user();

        $collections = $user->collection;
        if($collections->count() <= 0) {
            return view('collezioni.mie',[
                'collezioni' => []
            ]);
        }

        $collections->load('set','serie');

        return view('collezioni.mie',[
                'collezioni' => $collections
            ]
        );
    }

    /**
     * Mostra tutti i set disponibili raggruppati per serie.
     */
    public function disponibili(): View
    {
        $series = TCGSeries::with(['sets' => function ($query) {
            $query->orderBy('release_date', 'desc');
        }])
            ->whereHas('sets')
            ->orderBy('name')
            ->get();

        return view('collezioni.disponibili', compact('series'));
    }

    /**
     * Mostra il dettaglio di un singolo set.
     */
    public function showSet(TCGSet $set): View
    {
        $set->load([
            'serie',
            'cards' => fn($query) => $query->orderBy('dexId', 'asc'),
        ]);
        return view('collezioni.set-detail', compact('set'));
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
