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
        $allUsers = User::all();
        $pokemonGame = Game::where('name', 'Pokemon')->first();

        $sets = file_get_contents('https://mpapi.tcgplayer.com/v2/Catalog/SetNames?categoryId=3&active=true');

        if (!($allSets = json_decode($sets, true))) {
            Log::alert("Errore riconoscimento json: $sets -> " . json_last_error_msg());
            die;
        }

        if (!isset($allSets['results']) || empty($allSets['results'])) {
            Log::alert("Errore riconoscimento json: $sets -> " . json_last_error_msg());
            die;
        }


        foreach ($allUsers as $user) {
            foreach ($allSets['results'] as $key => $set) {

                if (CardSet::where('user_id', $user->id)->where('name', $set['name'])->where('abbreviation', $set['abbreviation'])->where('lang', 'en')->where('game_id', $pokemonGame->id)->exists()) {
                    continue;
                }

                CardSet::create([
                    'user_id' => $user->id,
                    'name' => $set['name'],
                    'card_set_abbreviation' => $set['abbreviation'],
                    'abbreviation' => explode("-", $set['urlName'])[0],
                    'lang' => 'en',
                    'game_id' => $pokemonGame->id,
                    'release_date' => str_replace("T", " ", $set['releaseDate']),
                    'external_set_id' => $set['setNameId'],
                    'external_category_id' => $set['categoryId'],
                    'is_supplemental' => $set['isSupplemental'],
                    'is_active' => $set['active'],
                ]);
            }
        }
    }
}
