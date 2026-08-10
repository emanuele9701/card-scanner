<?php

namespace App\Services\CardTrader\DTO;

class ExpansionDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $gameId,
        public readonly string $name,
        public readonly ?string $code
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            gameId: $data['game_id'],
            name: $data['name'],
            code: $data['code'] ?? null
        );
    }
}
