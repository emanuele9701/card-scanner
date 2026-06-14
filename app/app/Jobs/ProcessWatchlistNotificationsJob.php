<?php

namespace App\Jobs;

use App\Models\TCGCard;
use App\Models\User;
use App\Models\UserCardWatchlist;
use App\Models\UserSetWatchlist;
use App\Notifications\PriceTrendNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWatchlistNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $priceChanges;

    /**
     * Create a new job instance.
     *
     * @param array $priceChanges
     */
    public function __construct(array $priceChanges)
    {
        $this->priceChanges = $priceChanges;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->priceChanges)) {
            return;
        }

        // Raccogliamo tutti i card_id e set_id coinvolti
        $cardIds = array_unique(array_column($this->priceChanges, 'card_id'));
        $setIds = array_unique(array_column($this->priceChanges, 'set_id'));

        // 1. Notifiche per le Singole Carte
        $cardWatchlists = UserCardWatchlist::whereIn('card_id', $cardIds)
            ->with('user', 'card')
            ->get()
            ->groupBy('card_id');

        foreach ($this->priceChanges as $change) {
            $cId = $change['card_id'];
            if (!$cardWatchlists->has($cId)) continue;

            $diff = $change['new_trend'] - $change['old_trend'];
            $diffStr = ($diff > 0 ? '+' : '') . number_format($diff, 2) . '€';
            
            $cardName = TCGCard::find($cId)?->name ?? "Carta #{$cId}";
            $message = "Il prezzo di {$cardName} ({$change['condition']}, {$change['language']}" . ($change['is_reverse'] ? ', Foil' : '') . ") è cambiato: {$change['new_trend']}€ ({$diffStr}).";

            $usersToNotify = $cardWatchlists->get($cId)->pluck('user')->unique('id');

            foreach ($usersToNotify as $user) {
                if ($user) {
                    $user->notify(new PriceTrendNotification($message, 'card', $change));
                }
            }
        }

        // 2. Notifiche Aggregate per i Set
        // Raggruppiamo i cambiamenti per set
        $changesBySet = collect($this->priceChanges)->groupBy('set_id');

        $setWatchlists = UserSetWatchlist::whereIn('set_id', $setIds)
            ->with('user', 'set')
            ->get()
            ->groupBy('set_id');

        foreach ($changesBySet as $sId => $changes) {
            if (!$setWatchlists->has($sId)) continue;

            $count = count($changes);
            $firstChange = $changes->first();
            $setName = $firstChange['set_name'] ?? "Espansione #{$sId}";
            
            $message = "{$count} carte dell'espansione {$setName} hanno subito una variazione di prezzo.";

            $usersToNotify = $setWatchlists->get($sId)->pluck('user')->unique('id');

            foreach ($usersToNotify as $user) {
                if ($user) {
                    // Aggreghiamo i dati per la notifica
                    $data = [
                        'set_id' => $sId,
                        'set_name' => $setName,
                        'changes_count' => $count,
                        'changes' => $changes->take(5)->toArray() // Includiamo solo le prime 5 per evitare payload enormi
                    ];
                    $user->notify(new PriceTrendNotification($message, 'set', $data));
                }
            }
        }
    }
}
