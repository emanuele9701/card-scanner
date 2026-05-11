<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserCardCollection;
use App\Models\TCGSet;
use Illuminate\Support\Facades\DB;

class FindBestSellersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:find-best-sellers {user_id=1} {--limit=15 : Numero massimo di venditori da mostrare} {--set= : Filtra per un set specifico (ID numerico o stringa es. base1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un report dei venditori che hanno il maggior numero di carte mancanti per la collezione dell\'utente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("Utente con ID {$userId} non trovato!");
            return self::FAILURE;
        }

        $this->info("Elaborazione report per l'utente: {$user->name}...");

        $setOption = $this->option('set');

        if ($setOption) {
            $set = TCGSet::where('set_id', $setOption)->orWhere('id', $setOption)->first();
            
            if (!$set) {
                $this->error("Set '{$setOption}' non trovato nel database.");
                return self::FAILURE;
            }
            
            $collectedSetIds = collect([$set->id]);
            $this->info("Ricerca limitata al set: {$set->name} ({$set->set_id})");
        } else {
            // 1. Trova i set che l'utente sta attualmente collezionando (ha almeno una carta)
            $collectedSetIds = UserCardCollection::where('user_id', $userId)
                ->select('set_id')
                ->distinct()
                ->pluck('set_id');

            if ($collectedSetIds->isEmpty()) {
                $this->warn("L'utente non ha alcuna carta nella sua collezione. Impossibile determinare quali set colleziona.");
                return self::SUCCESS;
            }
        }

        // 2. Trova gli ID delle carte che l'utente possiede già
        $ownedCardIds = UserCardCollection::where('user_id', $userId)
            ->select('card_id')
            ->distinct()
            ->pluck('card_id');

        $this->info("Set collezionati: {$collectedSetIds->count()} | Carte uniche possedute: {$ownedCardIds->count()}");
        $this->info("Ricerca carte e varianti mancanti in corso...");
        $allCards = \App\Models\TCGCard::whereIn('set_id', $collectedSetIds)
            ->with(['collectors' => function($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->get();

        $missingCards = collect();
        foreach ($allCards as $card) {
            $produced = $card->produced_variants;
            if (empty($produced)) $produced = ['normal'];

            $ownedVariants = [];
            foreach ($card->collectors as $coll) {
                $v = is_array($coll->variants) && count($coll->variants) > 0 ? $coll->variants : ['normal'];
                $ownedVariants = array_merge($ownedVariants, $v);
            }
            $ownedVariantsUnique = array_unique(array_map('strtolower', $ownedVariants));
            $producedUnique = array_unique(array_map('strtolower', $produced));

            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));
            if (count($missingVariants) > 0) {
                $card->missing_variants = $missingVariants;
                $missingCards->push($card);
            }
        }

        $this->info("Varianti mancanti trovate: " . $missingCards->sum(fn($c) => count($c->missing_variants)));

        // 3. Trova le offerte per le carte mancanti all'utente nei set che colleziona
        // Raggruppiamo per venditore per trovare chi ha più carte uniche mancanti
        $sellersQuery = DB::table('tcg_card_offers')
            ->join('tcg_cards', 'tcg_card_offers.card_id', '=', 'tcg_cards.id')
            ->whereIn('tcg_cards.set_id', $collectedSetIds);

        $sellersQuery->where(function($q) use ($missingCards) {
            $addedConditions = 0;
            foreach ($missingCards as $card) {
                foreach ($card->missing_variants as $variant) {
                    $q->orWhere(function($subQ) use ($card, $variant) {
                        $subQ->where('tcg_card_offers.card_id', $card->id);
                        if ($variant === 'holo') {
                            $subQ->where('tcg_card_offers.is_holo', 1);
                        } elseif ($variant === 'reverse') {
                            $subQ->where('tcg_card_offers.is_reverse_holo', 1);
                        } elseif ($variant === 'firstedition') {
                            $subQ->where('tcg_card_offers.card_special_type', 'like', '%First Edition%');
                        } elseif ($variant === 'normal') {
                            $subQ->where(function($sq) {
                                $sq->whereNull('tcg_card_offers.is_holo')->orWhere('tcg_card_offers.is_holo', 0);
                            })->where(function($sq) {
                                $sq->whereNull('tcg_card_offers.is_reverse_holo')->orWhere('tcg_card_offers.is_reverse_holo', 0);
                            });
                        }
                    });
                    $addedConditions++;
                }
            }
            if ($addedConditions === 0) {
                $q->whereRaw('1 = 0');
            }
        });

        $sellers = $sellersQuery->select(
                'tcg_card_offers.seller_name',
                'tcg_card_offers.seller_country',
                DB::raw('COUNT(DISTINCT tcg_card_offers.card_id) as missing_cards_available'),
                // Calcola un prezzo minimo stimato prendendo l'offerta più bassa per ogni carta di questo venditore
                // (approssimazione rapida per dare un'idea del costo)
                DB::raw('SUM(tcg_card_offers.price_eur) as total_price_sum')
            )
            ->groupBy('tcg_card_offers.seller_name', 'tcg_card_offers.seller_country')
            ->orderByDesc('missing_cards_available')
            ->limit($this->option('limit'))
            ->get();

        if ($sellers->isEmpty()) {
            $this->info("Nessun venditore trovato con carte mancanti per l'utente nei set collezionati.");
            return self::SUCCESS;
        }

        $this->info("Trovati i venditori con più carte mancanti:");
        
        $headers = ['Venditore', 'Paese', 'Carte Uniche Mancanti', 'Somma Prezzi Offerte (€)'];
        $rows = $sellers->map(function ($seller) {
            return [
                $seller->seller_name,
                $seller->seller_country,
                $seller->missing_cards_available,
                number_format($seller->total_price_sum, 2)
            ];
        })->toArray();

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
