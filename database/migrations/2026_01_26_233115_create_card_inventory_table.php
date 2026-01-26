<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_card_id')->constrained('pokemon_cards')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->enum('rarity_variant', [
                'Standard',
                'Reverse Holo',
                'Holo',
                'First Edition',
                'Shadowless',
                'Error Card',
                'Promo',
                'Altro'
            ])->default('Standard');
            $table->enum('condition', [
                'Mint',
                'Near Mint',
                'Excellent',
                'Good',
                'Light Played',
                'Played',
                'Poor'
            ])->default('Near Mint');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure unique combination of card, user, variant, and condition
            $table->unique(['pokemon_card_id', 'user_id', 'rarity_variant', 'condition'], 'card_inventory_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_inventory');
    }
};
