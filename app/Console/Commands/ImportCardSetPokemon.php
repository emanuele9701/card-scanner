<?php

namespace App\Console\Commands;

use App\Models\CardSet;
use App\Models\Game;
use App\Models\User;
use Illuminate\Console\Command;
use Log;
use TCGdex\Model\Set;
use TCGdex\TCGdex;

class ImportCardSetPokemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-card-set-pokemon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Create global Pokemon Game
        $pokemonGame = Game::firstOrCreate(
            ['name' => 'Pokemon']
        );

        $sets = file_get_contents('https://mpapi.tcgplayer.com/v2/Catalog/SetNames?categoryId=3&active=true');

        if (!($allSets = json_decode($sets, true))) {
            Log::alert("Errore riconoscimento json: $sets -> " . json_last_error_msg());
            die;
        }

        if (!isset($allSets['results']) || empty($allSets['results'])) {
            Log::alert("Errore riconoscimento json: $sets -> " . json_last_error_msg());
            die;
        }

        foreach ($allSets['results'] as $key => $set) {
            $abbreviation = explode("-", $set['urlName'])[0];

            CardSet::updateOrCreate(
                [
                    'abbreviation' => $abbreviation,
                ],
                [
                    'name' => $set['name'],
                    'card_set_abbreviation' => $set['abbreviation'],
                    'lang' => 'en',
                    'game_id' => $pokemonGame->id,
                    'release_date' => str_replace("T", " ", $set['releaseDate']),
                    'external_set_id' => $set['setNameId'],
                    'external_category_id' => $set['categoryId'],
                    'is_supplemental' => $set['isSupplemental'],
                    'is_active' => $set['active'],
                ]
            );
        }

        $this->info("Card Sets imported successfully.");
    }
}
