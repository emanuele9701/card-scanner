<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserCardWatchlist;
use App\Models\UserSetWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    /**
     * Aggiorna l'FCM Token del dispositivo Android.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM Token aggiornato con successo.']);
    }

    /**
     * Ritorna tutte le watchlist dell'utente.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'cards' => $user->cardWatchlists()->with('card')->get(),
            'sets' => $user->setWatchlists()->with('set')->get(),
        ]);
    }

    /**
     * Attiva/Disattiva il monitoraggio di una singola carta.
     */
    public function toggleCard(Request $request, $cardId)
    {
        $user = Auth::user();

        $existing = UserCardWatchlist::where('user_id', $user->id)
            ->where('card_id', $cardId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['status' => 'removed', 'message' => 'Carta rimossa dalla watchlist.']);
        }

        UserCardWatchlist::create([
            'user_id' => $user->id,
            'card_id' => $cardId,
        ]);

        return response()->json(['status' => 'added', 'message' => 'Carta aggiunta alla watchlist.']);
    }

    /**
     * Attiva/Disattiva il monitoraggio di carte in modo massivo.
     */
    public function massToggle(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer',
            'action' => 'required|in:add,remove'
        ]);

        $user = Auth::user();
        $action = $request->action;
        $cardIds = $request->card_ids;

        if ($action === 'add') {
            $existing = UserCardWatchlist::where('user_id', $user->id)
                ->whereIn('card_id', $cardIds)
                ->pluck('card_id')
                ->toArray();

            $toInsert = array_diff($cardIds, $existing);
            $inserts = [];
            foreach ($toInsert as $id) {
                $inserts[] = [
                    'user_id' => $user->id,
                    'card_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            if (!empty($inserts)) {
                UserCardWatchlist::insert($inserts);
            }
            return response()->json(['status' => 'added', 'message' => 'Carte aggiunte alla watchlist.']);
        } else {
            UserCardWatchlist::where('user_id', $user->id)
                ->whereIn('card_id', $cardIds)
                ->delete();
            return response()->json(['status' => 'removed', 'message' => 'Carte rimosse dalla watchlist.']);
        }
    }

    /**
     * Attiva/Disattiva il monitoraggio di un'espansione.
     */
    public function toggleSet(Request $request, $setId)
    {
        $user = Auth::user();

        $existing = UserSetWatchlist::where('user_id', $user->id)
            ->where('set_id', $setId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['status' => 'removed', 'message' => 'Set rimosso dalla watchlist.']);
        }

        UserSetWatchlist::create([
            'user_id' => $user->id,
            'set_id' => $setId,
        ]);

        return response()->json(['status' => 'added', 'message' => 'Set aggiunto alla watchlist.']);
    }
}
