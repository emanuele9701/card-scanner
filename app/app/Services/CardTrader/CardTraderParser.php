<?php

namespace App\Services\CardTrader;

use App\Services\CardTrader\DTO\GameDto;
use App\Services\CardTrader\DTO\CategoryDto;
use App\Services\CardTrader\DTO\ExpansionDto;
use App\Services\CardTrader\DTO\BlueprintDto;

class CardTraderParser
{
    /**
     * @return GameDto[]
     */
    public function parseGames(array $data): array
    {
        return array_map(fn($item) => GameDto::fromArray($item), $data['array']);
    }

    /**
     * @return CategoryDto[]
     */
    public function parseCategories(array $data): array
    {
        return array_map(fn($item) => CategoryDto::fromArray($item), $data);
    }

    /**
     * @return ExpansionDto[]
     */
    public function parseExpansions(array $data): array
    {
        return array_map(fn($item) => ExpansionDto::fromArray($item), $data);
    }

    /**
     * @return BlueprintDto[]
     */
    public function parseBlueprints(array $data): array
    {
        $blueprints = [];
        // CardTrader usually returns blueprints inside a data array or as a list, depending on the endpoint.
        // Assuming it's an array of items here.
        $list = isset($data['data']) ? $data['data'] : (isset($data['blueprints']) ? $data['blueprints'] : $data);
        
        foreach ($list as $item) {
            $blueprints[] = BlueprintDto::fromArray($item);
        }
        return $blueprints;
    }
}
