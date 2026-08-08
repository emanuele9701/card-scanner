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

    /**
     * Bulk add missing cards to a collection with per-row attributes.
     */
    public function bulkAddMissingCards(Request $request, \App\Models\TCGSet $set)
    {
        $request->validate([
            'cards' => 'required|array|min:1',
            'cards.*.card_id' => 'required|integer|exists:tcg_cards,id',
            'cards.*.quantity' => 'required|integer|min:1|max:999',
            'cards.*.language' => 'required|string|in:it,en,jp,fr,de,es,pt',
            'cards.*.condition' => 'required|string|in:NM,LP,MP,HP,DMG',
            'cards.*.foil_type' => 'nullable|in:normal,holo,reverse',
            'cards.*.is_first_edition' => 'boolean',
            'cards.*.is_signed' => 'boolean',
            'cards.*.is_altered' => 'boolean',
        ]);

        $userId = Auth::id();
        $added = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->cards as $entry) {
                $card = \App\Models\TCGCard::where('id', $entry['card_id'])
                    ->where('set_id', $set->id)
                    ->first();

                if (!$card) {
                    $failed++;
                    $errors[] = [
                        'card_id' => $entry['card_id'],
                        'message' => 'Carta non trovata nel set.',
                    ];
                    continue;
                }

                $language = $entry['language'] ?? 'it';
                $condition = $entry['condition'] ?? 'NM';
                $foilType = $entry['foil_type'] ?? 'normal';
                $isFirstEdition = !empty($entry['is_first_edition']);
                $isSigned = !empty($entry['is_signed']);
                $isAltered = !empty($entry['is_altered']);

                $existing = UserCardCollection::where('user_id', $userId)
                    ->where('card_id', $card->id)
                    ->where('condition', $condition)
                    ->where('language', $language)
                    ->where('foil_type', $foilType)
                    ->where('is_first_edition', $isFirstEdition)
                    ->where('is_signed', $isSigned)
                    ->where('is_altered', $isAltered)
                    ->first();

                if ($existing) {
                    $existing->quantity += $entry['quantity'];
                    $existing->save();
                } else {
                    UserCardCollection::create([
                        'user_id' => $userId,
                        'card_id' => $card->id,
                        'set_id' => $set->id,
                        'serie_id' => $set->serie_id,
                        'condition' => $condition,
                        'language' => $language,
                        'foil_type' => $foilType,
                        'is_first_edition' => $isFirstEdition,
                        'is_signed' => $isSigned,
                        'is_altered' => $isAltered,
                        'quantity' => $entry['quantity'],
                    ]);
                }

                $added++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'added' => $added,
                'failed' => $failed,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
