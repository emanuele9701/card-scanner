<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardCopyController extends Controller
{
    public function getCardCopies(TCGCard $card) {
        $user = Auth::user();
        $copies = \App\Models\UserCardCollection::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->get();
        return response()->json($copies);
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
}
