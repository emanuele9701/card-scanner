<?php

namespace App\Services\CardTrader\DTO;

class GameDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $displayName
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            displayName: $data['display_name'] ?? $data['name']
        );
    }
}
