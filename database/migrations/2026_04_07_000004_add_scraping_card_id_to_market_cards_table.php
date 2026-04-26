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
        Schema::table('market_cards', function (Blueprint $table) {
            $table->foreignId('scraping_card_id')
                  ->nullable()
                  ->after('game_id')
                  ->constrained('scraping_cards')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scraping_card_id');
        });
    }
};
