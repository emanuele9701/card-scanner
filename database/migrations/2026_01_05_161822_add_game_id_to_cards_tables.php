<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pokemon_cards', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
        });

        Schema::table('market_cards', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemon_cards', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropColumn('game_id');
        });

        Schema::table('market_cards', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropColumn('game_id');
        });
    }
};
