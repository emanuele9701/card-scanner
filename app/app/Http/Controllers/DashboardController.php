<?php

namespace App\Http\Controllers;

use App\Models\UserCardCollection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Mostra la dashboard con le statistiche della collezione dell'utente.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        // Ottieni i set posseduti e il conteggio di carte uniche per ogni set
        $userSets = UserCardCollection::where('user_id', $userId)
            ->selectRaw('set_id, COUNT(DISTINCT card_id) as unique_cards, SUM(quantity) as total_quantity')
            ->groupBy('set_id')
            ->with('set')
            ->get();

        $totalSetsOwned = $userSets->count();

        // Ottieni tutte le carte della collezione con i relativi prezzi
        $collections = UserCardCollection::where('user_id', $userId)
            ->with(['card.prices' => function($q) {
                $q->latest('updated_at'); // Assumiamo che l'ultimo prezzo sia il più aggiornato
            }])
            ->get();

        $totalEstimatedValue = 0;
        $totalCardsOwned = 0;
        $setValue = [];

        foreach ($collections as $item) {
            $totalCardsOwned += $item->quantity;
            $priceModel = $item->card?->prices->first();
            $price = 0;
            
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

            $itemValue = $price * $item->quantity;
            $totalEstimatedValue += $itemValue;

            if (!isset($setValue[$item->set_id])) {
                $setValue[$item->set_id] = 0;
            }
            $setValue[$item->set_id] += $itemValue;
        }

        // Prepara le statistiche per ogni set posseduto
        $setsStats = $userSets->map(function ($userSet) use ($setValue) {
            $cardTotal = $userSet->set->card_total ?? 0;
            $completionPercentage = $cardTotal > 0 
                ? min(100, round(($userSet->unique_cards / $cardTotal) * 100, 1)) 
                : 0;

            return [
                'set_id' => $userSet->set_id,
                'name' => $userSet->set->name ?? 'Sconosciuto',
                'symbol' => ($userSet->set->logo ?? $userSet->set->symbol) ?? null,
                'unique_cards' => $userSet->unique_cards,
                'total_quantity' => $userSet->total_quantity,
                'official_cards' => $cardTotal,
                'completion_percentage' => $completionPercentage,
                'estimated_value' => $setValue[$userSet->set_id] ?? 0,
            ];
        })->sortByDesc('completion_percentage')->values();

        return view('dashboard', compact(
            'totalSetsOwned', 
            'totalCardsOwned', 
            'totalEstimatedValue', 
            'setsStats'
        ));
    }
}
