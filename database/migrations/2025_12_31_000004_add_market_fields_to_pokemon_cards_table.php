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
            $table->foreignId('card_set_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->foreignId('market_card_id')->nullable()->after('card_set_id')->constrained()->onDelete('set null');
            $table->enum('condition', [
                'Damaged',
                'Heavily Played',
                'Moderately Played',
                'Lightly Played',
                'Near Mint'
            ])->nullable()->after('market_card_id');
            $table->enum('printing', [
                'Normal',
                'Reverse Holofoil',
                'Holofoil'
            ])->default('Normal')->after('condition');
            $table->decimal('acquisition_price', 10, 2)->nullable()->after('printing');
            $table->date('acquisition_date')->nullable()->after('acquisition_price');

            $table->index('card_set_id');
            $table->index('market_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemon_cards', function (Blueprint $table) {
            $table->dropForeign(['card_set_id']);
            $table->dropForeign(['market_card_id']);
            $table->dropIndex(['card_set_id']);
            $table->dropIndex(['market_card_id']);

            $table->dropColumn([
                'card_set_id',
                'market_card_id',
                'condition',
                'printing',
                'acquisition_price',
                'acquisition_date'
            ]);
        });
    }
};
