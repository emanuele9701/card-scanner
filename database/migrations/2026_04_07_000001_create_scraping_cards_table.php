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
        Schema::create('scraping_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraping_provider_id')
                  ->constrained('scraping_providers')
                  ->cascadeOnDelete();
            $table->foreignId('scraping_expansion_id')
                  ->nullable()
                  ->constrained('scraping_expansions')
                  ->nullOnDelete();
            $table->string('card_number', 20);
            $table->string('name');
            $table->string('rarity')->nullable();
            $table->string('species')->nullable();
            $table->text('product_url');
            $table->text('reprint_url')->nullable();
            $table->text('reprint_offers_url')->nullable();
            $table->timestamps();

            $table->unique(
                ['scraping_provider_id', 'scraping_expansion_id', 'card_number'],
                'scraping_cards_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_cards');
    }
};
