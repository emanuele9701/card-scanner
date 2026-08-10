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
        Schema::table('tcg_card_prices', function (Blueprint $table) {
            $table->enum('provider', ['cardmarket', 'tcgplayer'])->default('cardmarket')->after('card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tcg_card_prices', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
