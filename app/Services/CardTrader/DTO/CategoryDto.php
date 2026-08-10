<?php

namespace App\Services\CardTrader\DTO;

class CategoryDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $gameId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            gameId: $data['game_id']
        );
    }
}
