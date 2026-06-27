<?php

namespace App\Http\Controllers;

use App\Models\UserCardCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MassActionController extends Controller
{
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
}
