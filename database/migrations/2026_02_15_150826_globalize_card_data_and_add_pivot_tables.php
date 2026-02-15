<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Pivot Tables
        Schema::create('game_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['game_id', 'user_id']);
        });

        Schema::create('card_set_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_set_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['card_set_id', 'user_id']);
        });

        // 2. DATA MIGRATION & DEDUPLICATION

        // --- GAMES ---
        $games = DB::table('games')->get();
        $processedGames = []; // name -> master_id

        foreach ($games as $game) {
            // Populate pivot table for existing relationship
            DB::table('game_user')->insertOrIgnore([
                'game_id' => $game->id,
                'user_id' => $game->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($processedGames[$game->name])) {
                // This is a duplicate. Remap references to the master ID.
                $masterId = $processedGames[$game->name];

                // Update PokemonCards
                DB::table('pokemon_cards')
                    ->where('game_id', $game->id)
                    ->update(['game_id' => $masterId]);

                // Update CardSets
                DB::table('card_sets')
                    ->where('game_id', $game->id)
                    ->update(['game_id' => $masterId]);

                // Update MarketCards
                DB::table('market_cards')
                    ->where('game_id', $game->id)
                    ->update(['game_id' => $masterId]);

                // Update pivot table entries for this duplicate game to point to master
                // If the user already has the master game, this update will fail unique constraint (handled by ignore/delete)
                $exists = DB::table('game_user')
                    ->where('game_id', $masterId)
                    ->where('user_id', $game->user_id)
                    ->exists();

                if (!$exists) {
                    DB::table('game_user')
                        ->where('game_id', $game->id)
                        ->update(['game_id' => $masterId]);
                } else {
                    // User already has master game, delete pivot for duplicate
                    DB::table('game_user')->where('game_id', $game->id)->delete();
                }

                // Delete the duplicate game
                DB::table('games')->where('id', $game->id)->delete();

            } else {
                // This is the first time we see this game name. It becomes the master.
                $processedGames[$game->name] = $game->id;
            }
        }


        // --- CARD SETS ---
        $sets = DB::table('card_sets')->get();
        $processedSets = []; // abbreviation -> master_id

        foreach ($sets as $set) {
            // Populate pivot
            DB::table('card_set_user')->insertOrIgnore([
                'card_set_id' => $set->id,
                'user_id' => $set->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Re-fetch sets to process deduplication (game_ids might have changed in component update above)
        // Note: Logic above updated card_sets.game_id, so we are good.
        // We use a fresh query to process deduplication.
        $sets = DB::table('card_sets')->get();

        foreach ($sets as $set) {
            $key = $set->abbreviation;

            if (isset($processedSets[$key])) {
                $masterId = $processedSets[$key];

                // Update PokemonCards
                DB::table('pokemon_cards')
                    ->where('card_set_id', $set->id)
                    ->update(['card_set_id' => $masterId]);

                // MarketCards do not have card_set_id FK, they use string abbreviation. No update needed.

                // Update Pivot
                $exists = DB::table('card_set_user')
                    ->where('card_set_id', $masterId)
                    ->where('user_id', $set->user_id)
                    ->exists();

                if (!$exists) {
                    DB::table('card_set_user')
                        ->where('card_set_id', $set->id)
                        ->update(['card_set_id' => $masterId]);
                } else {
                    DB::table('card_set_user')->where('card_set_id', $set->id)->delete();
                }

                // Delete duplicate
                DB::table('card_sets')->where('id', $set->id)->delete();
            } else {
                $processedSets[$key] = $set->id;
            }
        }


        // --- MARKET CARDS ---
        // Deduplicate by product_id
        $marketCards = DB::table('market_cards')->get();
        $processedProducts = []; // product_id -> master_id

        foreach ($marketCards as $card) {
            if (isset($processedProducts[$card->product_id])) {
                $masterId = $processedProducts[$card->product_id];

                // Remap PokemonCards
                DB::table('pokemon_cards')
                    ->where('market_card_id', $card->id)
                    ->update(['market_card_id' => $masterId]);

                // Remap MarketPrices
                DB::table('market_prices')
                    ->where('market_card_id', $card->id)
                    ->update(['market_card_id' => $masterId]);

                // Delete duplicate
                DB::table('market_cards')->where('id', $card->id)->delete();
            } else {
                $processedProducts[$card->product_id] = $card->id;
            }
        }


        // 3. SCHEMA UPDATES (Drop columns and update constraints)

        // Games
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn('user_id');
            $table->unique('name');
        });

        // Card Sets
        Schema::table('card_sets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            // Constraint names vary, dropping by array usually works if name standard
            $table->dropUnique(['user_id', 'abbreviation', 'name']);
            $table->dropColumn('user_id');
            // Add new unique constraint
            $table->unique('abbreviation');
        });

        // Market Cards
        Schema::table('market_cards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('market_cards_product_user_unique');
            $table->dropColumn('user_id');
            $table->unique('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add columns back
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        });
        Schema::table('card_sets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        });
        Schema::table('market_cards', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Drop pivot tables
        Schema::dropIfExists('game_user');
        Schema::dropIfExists('card_set_user');
    }
};
