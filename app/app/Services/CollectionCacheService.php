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
        $ownedCards = 0;
        $totalSlots = 0;
        $totalValue = 0;
        $setCount = 0;
        $completedSets = 0;

        $setsData = [];

        UserCardCollection::where('user_id', $userId)
            ->with(['set.serie', 'card.prices' => function($q) {
                $q->latest('updated_at');
            }])
            ->lazy(500)
            ->each(function ($item) use (&$setsData, &$ownedCards, &$totalValue) {
                $ownedCards += $item->quantity;
                $price = $item->getCalculatedPrice();
                $itemValue = $price * $item->quantity;
                $totalValue += $itemValue;
                
                $setId = $item->set_id;
                if (!isset($setsData[$setId])) {
                    $setsData[$setId] = [
                        'set' => $item->set,
                        'uniqueCardIds' => [],
                        'totalValue' => 0
                    ];
                }
                $setsData[$setId]['uniqueCardIds'][$item->card_id] = true;
                $setsData[$setId]['totalValue'] += $itemValue;
            });

        if (empty($setsData)) {
            return [
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

        $seriesGroups = [];
        foreach ($setsData as $setId => $data) {
            $set = $data['set'];
            if (!$set) continue;
            
            $setCount++;
            $cardTotal = $set->card_official ?? $set->card_total ?? 0;
            $totalSlots += $cardTotal;
            
            $uniqueOwnedQty = count($data['uniqueCardIds']);
            $progress = $cardTotal > 0 ? min(100, round($uniqueOwnedQty / $cardTotal * 100)) : 0;
            
            if ($progress >= 100) {
                $completedSets++;
            }
            
            $serieName = $set->serie->name ?? __('Senza serie');
            if (!isset($seriesGroups[$serieName])) {
                $seriesGroups[$serieName] = [];
            }
            
            $seriesGroups[$serieName][] = [
                'set_id' => $set->id,
                'name' => $set->name,
                'logo' => $set->logo,
                'symbol' => $set->symbol,
                'unique_owned' => $uniqueOwnedQty,
                'card_total' => $cardTotal,
                'progress' => $progress,
                'total_value' => $data['totalValue'],
            ];
        }

        $seriesData = [];
        foreach ($seriesGroups as $serieName => $setsInSerie) {
            $seriesData[] = [
                'name' => $serieName,
                'sets' => $setsInSerie
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
