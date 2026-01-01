<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PokemonCard;
use App\Models\CardSet;

class TestPokemonCardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the PFL set
        $pflSet = CardSet::where('abbreviation', 'PFL')->first();

        if (!$pflSet) {
            $this->command->error('PFL set not found. Please import market data first.');
            return;
        }

        // Create test cards that should match
        $testCards = [
            [
                'card_name' => 'Absol',
                'set_number' => '063/094',
                'rarity' => 'Common',
                'card_set_id' => $pflSet->id,
                'condition' => 'Near Mint',
                'printing' => 'Normal',
                'status' => 'completed',
                'storage_path' => 'test/absol.jpg',
                'original_filename' => 'absol.jpg',
            ],
            [
                'card_name' => 'Charmander',
                'set_number' => '011/094',
                'rarity' => 'Common',
                'card_set_id' => $pflSet->id,
                'condition' => 'Lightly Played',
                'printing' => 'Normal',
                'status' => 'completed',
                'storage_path' => 'test/charmander.jpg',
                'original_filename' => 'charmander.jpg',
            ],
            [
                'card_name' => 'Mega Charizard X ex',
                'set_number' => '013/094',
                'rarity' => 'Double Rare',
                'card_set_id' => $pflSet->id,
                'condition' => 'Near Mint',
                'printing' => 'Holofoil',
                'status' => 'completed',
                'storage_path' => 'test/charizard.jpg',
                'original_filename' => 'charizard.jpg',
            ],
            [
                'card_name' => 'Ambipom',
                'set_number' => '079/094',
                'rarity' => 'Rare',
                'card_set_id' => $pflSet->id,
                'condition' => 'Near Mint',
                'printing' => 'Holofoil',
                'status' => 'completed',
                'storage_path' => 'test/ambipom.jpg',
                'original_filename' => 'ambipom.jpg',
            ],
            [
                'card_name' => 'Dawn',
                'set_number' => '087/094',
                'rarity' => 'Uncommon',
                'card_set_id' => $pflSet->id,
                'condition' => 'Near Mint',
                'printing' => 'Normal',
                'status' => 'completed',
                'storage_path' => 'test/dawn.jpg',
                'original_filename' => 'dawn.jpg',
            ],
        ];

        foreach ($testCards as $cardData) {
            PokemonCard::create($cardData);
        }

        $this->command->info('Created ' . count($testCards) . ' test Pokemon cards.');
    }
}
