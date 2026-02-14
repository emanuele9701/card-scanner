<?php

namespace App\Console\Commands;

use App\Models\CardSet;
use App\Models\Game;
use App\Models\User;
use Illuminate\Console\Command;
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
        $tcg = new TCGdex('it');
        $sets = $tcg->set->list();
        $allUsers = User::all();
        $pokemonGame = Game::where('name', 'Pokemon')->first();
        foreach ($allUsers as $user) {
            foreach ($sets as $set) {
                /**
                 * @var Set $set
                 */

                if (CardSet::where('user_id', $user->id)->where('name', $set->name)->where('abbreviation', $set->id)->where('lang', 'it')->where('game_id', $pokemonGame->id)->exists()) {
                    continue;
                }

                CardSet::create([
                    'user_id' => $user->id,
                    'name' => $set->name,
                    'abbreviation' => $set->id,
                    'lang' => 'it',
                    'game_id' => $pokemonGame->id
                ]);
            }
        }
    }
}
