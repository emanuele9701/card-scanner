<?php

namespace App\Services;

use App\Models\UserCardCollection;
use Illuminate\Support\Facades\Cache;

class CollectionCacheService
{
    private const CACHE_TTL = 86400; // 24 hours

    public function getForUser(int $userId): array
    {
        return Cache::remember(
            $this->cacheKey($userId),
            self::CACHE_TTL,
            fn () => $this->computeCollectionData($userId)
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
        return "collection:index:{$userId}";
    }

    private function computeCollectionData(int $userId): array
    {
        $collections = UserCardCollection::where('user_id', $userId)
            ->with(['set.serie', 'card.prices'])
            ->get();

        if ($collections->isEmpty()) {
            return [
                'collezioni' => [],
                'stats' => [
                    'totalValue' => 0,
                    'ownedCards' => 0,
                    'totalSlots' => 0,
                    'setCount' => 0,
                    'completedSets' => 0,
                ],
                'seriesData' => [],
            ];
        }

        $ownedCards = 0;
        $totalSlots = 0;
        $totalValue = 0;
        $setCount = 0;
        $completedSets = 0;

        $groupedBySet = $collections->groupBy(fn ($item) => $item->set?->id ?? 0);

        foreach ($groupedBySet as $setItems) {
            $set = $setItems->first()->set;
            if (!$set) continue;

            $setCount++;
            $ownedQty = $setItems->sum('quantity');
            $uniqueOwnedQty = $setItems->unique('card_id')->count();
            $cardTotal = $set->card_official ?? $set->card_total ?? 0;
            $totalSlots += $cardTotal;
            $ownedCards += $ownedQty;

            $progress = $cardTotal > 0 ? round($uniqueOwnedQty / $cardTotal * 100) : 0;
            if ($progress >= 100) {
                $completedSets++;
            }

            foreach ($setItems as $item) {
                $card = $item->card;
                if (!$card) continue;

                $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                $value = 0;
                if ($lastPrice) {
                    $foil = strtolower(trim($item->foil_type ?? ''));
                    $isHolo = in_array($foil, ['holo', 'reverse']);
                    $value = $isHolo
                        ? ($lastPrice->trend_holo ?? $lastPrice->avg_holo ?? $lastPrice->trend ?? $lastPrice->avg ?? 0)
                        : ($lastPrice->trend ?? $lastPrice->avg ?? 0);
                }
                $totalValue += $value * $item->quantity;
            }
        }

        // Pre-compute series data for the view
        $groupedBySerie = $collections->groupBy(fn ($item) => $item->set?->serie?->name ?? __('Senza serie'));
        $seriesData = [];

        foreach ($groupedBySerie as $serieName => $items) {
            $setsInSerie = [];
            $setGroups = $items->groupBy(fn ($item) => $item->set?->id ?? 0);

            foreach ($setGroups as $setItems) {
                $set = $setItems->first()->set;
                if (!$set) continue;

                $ownedQty = $setItems->sum('quantity');
                $uniqueOwnedQty = $setItems->unique('card_id')->count();
                $cardTotal = $set->card_total ?? 0;
                $progress = $cardTotal > 0 ? min(100, round($uniqueOwnedQty / $cardTotal * 100)) : 0;

                $totalValueSet = 0;
                foreach ($setItems as $item) {
                    $card = $item->card;
                    if (!$card) continue;
                    $lastPrice = $card->prices->sortByDesc('updated_at')->first();
                    $value = 0;
                    if ($lastPrice) {
                        $foil = strtolower(trim($item->foil_type ?? ''));
                        $isHolo = in_array($foil, ['holo', 'reverse']);
                        $value = $isHolo
                            ? ($lastPrice->trend_holo ?? $lastPrice->avg_holo ?? $lastPrice->trend ?? $lastPrice->avg ?? 0)
                            : ($lastPrice->trend ?? $lastPrice->avg ?? 0);
                    }
                    $totalValueSet += $value * $item->quantity;
                }

                $setsInSerie[] = [
                    'set_id' => $set->id,
                    'name' => $set->name,
                    'logo' => $set->logo,
                    'symbol' => $set->symbol,
                    'unique_owned' => $uniqueOwnedQty,
                    'card_total' => $cardTotal,
                    'progress' => $progress,
                    'total_value' => $totalValueSet,
                ];
            }

            $seriesData[] = [
                'name' => $serieName,
                'sets' => $setsInSerie,
            ];
        }

        return [
            'stats' => [
                'totalValue' => $totalValue,
                'ownedCards' => $ownedCards,
                'totalSlots' => $totalSlots,
                'setCount' => $setCount,
                'completedSets' => $completedSets,
            ],
            'seriesData' => $seriesData,
        ];
    }
}
