<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use Illuminate\Http\Request;

class PokemonCardController extends Controller
{
    /**
     * Update the condition of a card
     */
    public function updateCondition(Request $request, PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate([
            'condition' => 'required|in:Damaged,Heavily Played,Moderately Played,Lightly Played,Near Mint',
        ]);

        $card->update([
            'condition' => $request->condition,
        ]);

        return back()->with('success', 'Condition updated successfully!');
    }
}
