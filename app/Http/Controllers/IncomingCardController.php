<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use App\Models\UserCardCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomingCardController extends Controller
{
    /**
     * Aggiungi carte "in arrivo" (acquisto/scambio).
     */
    public function addIncoming(Request $request): JsonResponse
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer|exists:tcg_cards,id',
            'language' => 'nullable|string',
            'foil_type' => 'nullable|in:normal,holo,reverse',
            'is_first_edition' => 'boolean',
            'is_signed' => 'boolean',
            'is_altered' => 'boolean',
            'quantity' => 'integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();
        $language = $request->language ?: 'en';
        $foilType = $request->foil_type ?: 'normal';
        $isFirstEdition = $request->boolean('is_first_edition');
        $isSigned = $request->boolean('is_signed');
        $isAltered = $request->boolean('is_altered');
        $quantity = $request->quantity ?? 1;
        $notes = $request->notes;

        DB::beginTransaction();
        try {
            foreach ($request->card_ids as $cardId) {
                $card = TCGCard::find($cardId);
                if (!$card) continue;

                // Check for existing identical incoming entry
                $existing = \App\Models\UserIncomingCard::where('user_id', $userId)
                    ->where('card_id', $cardId)
                    ->where('language', $language)
                    ->where('foil_type', $foilType)
                    ->where('is_first_edition', $isFirstEdition)
                    ->where('is_signed', $isSigned)
                    ->where('is_altered', $isAltered)
                    ->first();

                if ($existing) {
                    $existing->quantity += $quantity;
                    if ($notes) $existing->notes = $notes;
                    $existing->save();
                } else {
                    \App\Models\UserIncomingCard::create([
                        'user_id' => $userId,
                        'card_id' => $cardId,
                        'set_id' => $card->set_id,
                        'language' => $language,
                        'foil_type' => $foilType,
                        'is_first_edition' => $isFirstEdition,
                        'is_signed' => $isSigned,
                        'is_altered' => $isAltered,
                        'quantity' => $quantity,
                        'notes' => $notes,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Le carte "in arrivo" sono arrivate: spostale in collezione.
     */
    public function arrivedIncoming(Request $request): JsonResponse
    {
        $request->validate([
            'incoming_ids' => 'required|array',
            'incoming_ids.*' => 'integer|exists:user_incoming_cards,id',
            'condition' => 'required|in:NM,LP,MP,HP,DMG',
        ]);

        $userId = Auth::id();

        DB::beginTransaction();
        try {
            foreach ($request->incoming_ids as $incomingId) {
                $incoming = \App\Models\UserIncomingCard::where('id', $incomingId)
                    ->where('user_id', $userId)
                    ->first();

                if (!$incoming) continue;

                $card = TCGCard::find($incoming->card_id);
                if (!$card) continue;

                // Check if identical copy exists in collection
                $existing = UserCardCollection::where('user_id', $userId)
                    ->where('card_id', $incoming->card_id)
                    ->where('condition', $request->condition)
                    ->where('language', $incoming->language)
                    ->where('foil_type', $incoming->foil_type)
                    ->where('is_first_edition', $incoming->is_first_edition)
                    ->where('is_signed', $incoming->is_signed)
                    ->where('is_altered', $incoming->is_altered)
                    ->first();

                if ($existing) {
                    $existing->quantity += $incoming->quantity;
                    $existing->save();
                } else {
                    UserCardCollection::create([
                        'user_id' => $userId,
                        'card_id' => $incoming->card_id,
                        'set_id' => $incoming->set_id,
                        'serie_id' => $card->set->serie_id ?? null,
                        'condition' => $request->condition,
                        'language' => $incoming->language,
                        'foil_type' => $incoming->foil_type,
                        'is_first_edition' => $incoming->is_first_edition,
                        'is_signed' => $incoming->is_signed,
                        'is_altered' => $incoming->is_altered,
                        'quantity' => $incoming->quantity,
                    ]);
                }

                // Remove the incoming record
                $incoming->delete();
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Rimuovi lo status "in arrivo" (annulla ordine).
     */
    public function removeIncoming(Request $request): JsonResponse
    {
        $request->validate([
            'incoming_ids' => 'nullable|array',
            'incoming_ids.*' => 'integer',
            'card_ids' => 'nullable|array',
            'card_ids.*' => 'integer',
        ]);

        $userId = Auth::id();

        $query = \App\Models\UserIncomingCard::where('user_id', $userId);
        
        if ($request->has('incoming_ids') && !empty($request->incoming_ids)) {
            $query->whereIn('id', $request->incoming_ids);
        } elseif ($request->has('card_ids') && !empty($request->card_ids)) {
            $query->whereIn('card_id', $request->card_ids);
        } else {
            return response()->json(['success' => false, 'message' => 'Nessun ID fornito']);
        }
        
        $query->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Restituisci le carte in arrivo per i card_id specificati.
     */
    public function getIncomingCards(Request $request): JsonResponse
    {
        $request->validate([
            'card_ids' => 'nullable|array',
            'card_ids.*' => 'integer',
        ]);

        $userId = Auth::id();

        if (!$request->has('card_ids') || empty($request->card_ids)) {
            return response()->json([]); // Fix for 422 error
        }

        $incoming = \App\Models\UserIncomingCard::with('card')
            ->where('user_id', $userId)
            ->whereIn('card_id', $request->card_ids)
            ->get();

        return response()->json($incoming);
    }
}
