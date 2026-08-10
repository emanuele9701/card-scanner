<?php

namespace App\Services\CardTrader\DTO;

class BlueprintDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $expansionId,
        public readonly int $categoryId,
        public readonly int $gameId,
        public readonly ?string $collectorNumber
    ) {}

    public static function fromArray(array $data): self
    {
        $collectorNumber = null;
        if (isset($data['fixed_properties']) && is_array($data['fixed_properties'])) {
            $collectorNumber = $data['fixed_properties']['collector_number'] ?? null;
        }
        
        if ($collectorNumber === null && !empty($data['version'])) {
            $version = trim($data['version']);
            if (str_contains($version, '|')) {
                $pipeParts = explode('|', $version);
                $version = trim(array_pop($pipeParts));
            }
            $parts = explode('/', $version);
            if (!empty($parts[0])) {
                $collectorNumber = trim($parts[0]);
            }
        }
        return new self(
            id: $data['id'],
            name: $data['name'],
            expansionId: $data['expansion_id'],
            categoryId: $data['category_id'],
            gameId: $data['game_id'],
            collectorNumber: $collectorNumber !== null ? (string) $collectorNumber : null
        );
    }
}
