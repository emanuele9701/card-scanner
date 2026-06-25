<?php

namespace App\Observers;

use App\Models\UserCardCollection;
use App\Services\CollectionCacheService;
use App\Services\DashboardCacheService;

class UserCardCollectionObserver
{
    public function created(UserCardCollection $item): void
    {
        $this->invalidateUserCache($item->user_id);
    }

    public function updated(UserCardCollection $item): void
    {
        $this->invalidateUserCache($item->user_id);
    }

    public function deleted(UserCardCollection $item): void
    {
        $this->invalidateUserCache($item->user_id);
    }

    private function invalidateUserCache(int $userId): void
    {
        app(DashboardCacheService::class)->invalidateForUser($userId);
        app(CollectionCacheService::class)->invalidateForUser($userId);
    }
}
