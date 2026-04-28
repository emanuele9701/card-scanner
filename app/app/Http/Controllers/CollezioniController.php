<?php

namespace App\Http\Controllers;

use App\Models\TCGSeries;
use App\Models\TCGSet;
use Illuminate\View\View;

class CollezioniController extends Controller
{
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
}
