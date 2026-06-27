<?php

namespace App\Services;

use App\Models\UserCardCollection;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    private const CACHE_TTL = 86400; // 24 hours

    public function getForUser(int $userId): array
    {
        return Cache::remember(
            $this->cacheKey($userId),
            self::CACHE_TTL,
            fn () => $this->computeDashboardData($userId)
        );
    }

    public function invalidateForUser(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    public function invalidateAll(): void
    {
        $userIds = UserCardCollection::distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $this->invalidateForUser($userId);
        }
    }

    private function cacheKey(int $userId): string
    {
        return "dashboard:stats:{$userId}";
    }

    private function computeDashboardData(int $userId): array
    {
        $userSets = UserCardCollection::where('user_id', $userId)
            ->selectRaw('set_id, COUNT(DISTINCT card_id) as unique_cards, SUM(quantity) as total_quantity')
            ->groupBy('set_id')
            ->with('set')
            ->get();

        $totalSetsOwned = $userSets->count();

        $totalEstimatedValue = 0;
        $totalCardsOwned = 0;
        $setValue = [];
        $topCardsCandidate = [];

        UserCardCollection::where('user_id', $userId)
            ->with(['card.set', 'card.prices' => function($q) {
                $q->latest('updated_at');
            }])
            ->lazy(500)
            ->each(function ($item) use (&$totalCardsOwned, &$totalEstimatedValue, &$setValue, &$topCardsCandidate) {
                $totalCardsOwned += $item->quantity;
                $price = $item->getCalculatedPrice();
                $itemValue = $price * $item->quantity;
                $totalEstimatedValue += $itemValue;

                if (!isset($setValue[$item->set_id])) {
                    $setValue[$item->set_id] = 0;
                }
                $setValue[$item->set_id] += $itemValue;

                if ($itemValue > 0) {
                    $topCardsCandidate[] = [
                        'card' => $item->card ? [
                            'id' => $item->card->id,
                            'name' => $item->card->name,
                            'url_image' => $item->card->url_image,
                            'rarity' => $item->card->rarity,
                            'dexId' => $item->card->dexId,
                            'set_name' => $item->card->set->name ?? '',
                        ] : null,
                        'quantity' => $item->quantity,
                        'unit_price' => $price,
                        'total_price' => $itemValue,
                    ];
                    
                    if (count($topCardsCandidate) > 50) {
                        usort($topCardsCandidate, fn($a, $b) => $b['total_price'] <=> $a['total_price']);
                        $topCardsCandidate = array_slice($topCardsCandidate, 0, 10);
                    }
                }
            });

        $setsStats = $userSets->map(function ($userSet) use ($setValue) {
            $cardTotal = $userSet->set->card_total ?? 0;
            $completionPercentage = $cardTotal > 0
                ? min(100, round(($userSet->unique_cards / $cardTotal) * 100, 1))
                : 0;

            return [
                'set_id' => $userSet->set_id,
                'name' => $userSet->set->name ?? __('Sconosciuto'),
                'symbol' => ($userSet->set->logo ?? $userSet->set->symbol) ?? null,
                'unique_cards' => $userSet->unique_cards,
                'total_quantity' => $userSet->total_quantity,
                'official_cards' => $cardTotal,
                'completion_percentage' => $completionPercentage,
                'estimated_value' => $setValue[$userSet->set_id] ?? 0,
            ];
        })->sortByDesc('completion_percentage')->values()->toArray();

        usort($topCardsCandidate, fn($a, $b) => $b['total_price'] <=> $a['total_price']);
        $topCards = array_slice($topCardsCandidate, 0, 5);

        return compact(
            'totalSetsOwned',
            'totalCardsOwned',
            'totalEstimatedValue',
            'setsStats',
            'topCards'
        );
    }
}
