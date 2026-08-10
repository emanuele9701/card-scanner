<?php

namespace App\Services\CardTrader\DTO;

class CT0BoxItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $expansion,
        public readonly int $blueprintId,
        public readonly int $gameId,
        public readonly int $quantityOk,
        public readonly int $quantityPending,
        public readonly int $quantityMissing,
        public readonly ?string $condition,
        public readonly string $language,
        public readonly bool $isFoil,
        public readonly bool $isSigned,
        public readonly bool $isAltered,
        public readonly bool $isFirstEdition,
        public readonly bool $isReverse,
        public readonly int $priceCents,
        public readonly string $currency,
        public readonly string $formattedPrice,
        public readonly ?string $scryfallId,
        public readonly ?string $cancelledAt,
    ) {}

    public static function fromArray(array $data): self
    {
        $props = $data['properties'] ?? [];
        $quantity = $data['quantity'] ?? [];
        $price = $data['buyer_price'] ?? [];

        // Condition mapping
        $condition = $props['condition'] ?? null;

        // Language fallback
        $language = $props['pokemon_language'] ?? ($props['mtg_language'] ?? ($props['language'] ?? 'en'));

        // Foil / Reverse logic
        $isFoil = !empty($props['pokemon_foil']) || !empty($props['mtg_foil']);
        $isReverse = !empty($props['pokemon_reverse']);

        return new self(
            id: $data['id'],
            name: $data['name'] ?? '',
            expansion: $data['expansion'] ?? '',
            blueprintId: $data['blueprint_id'] ?? 0,
            gameId: $data['game_id'] ?? 0,
            quantityOk: $quantity['ok'] ?? 0,
            quantityPending: $quantity['pending'] ?? 0,
            quantityMissing: $quantity['missing'] ?? 0,
            condition: $condition,
            language: $language,
            isFoil: $isFoil,
            isSigned: !empty($props['signed']),
            isAltered: !empty($props['altered']),
            isFirstEdition: !empty($props['first_edition']),
            isReverse: $isReverse,
            priceCents: $price['cents'] ?? 0,
            currency: $price['currency'] ?? 'EUR',
            formattedPrice: $data['formatted_price'] ?? '',
            scryfallId: $data['scryfall_id'] ?? null,
            cancelledAt: $data['cancelled_at'] ?? null,
        );
    }
}
