<?php

namespace App\Policies;

use App\Models\CardInventory;
use App\Models\User;

class CardInventoryPolicy
{
    /**
     * Determine if the user can update the inventory item.
     */
    public function update(User $user, CardInventory $inventory): bool
    {
        return $user->id === $inventory->user_id;
    }

    /**
     * Determine if the user can delete the inventory item.
     */
    public function delete(User $user, CardInventory $inventory): bool
    {
        return $user->id === $inventory->user_id;
    }
}
