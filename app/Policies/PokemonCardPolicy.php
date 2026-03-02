<?php

namespace App\Policies;

use App\Models\PokemonCard;
use App\Models\User;

class PokemonCardPolicy
{
    public function view(User $user, PokemonCard $card): bool
    {
        return $user->id === $card->user_id;
    }

    public function update(User $user, PokemonCard $card): bool
    {
        return $user->id === $card->user_id;
    }

    public function delete(User $user, PokemonCard $card): bool
    {
        return $user->id === $card->user_id;
    }
}
