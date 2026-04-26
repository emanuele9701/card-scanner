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
        Schema::create('scraping_card_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraping_card_id')
                  ->constrained('scraping_cards')
                  ->cascadeOnDelete();
            $table->timestamp('scraped_at');
            $table->unsignedInteger('available_items')->default(0);
            $table->decimal('price_from', 10, 2)->nullable();
            $table->decimal('price_trend', 10, 2)->nullable();
            $table->decimal('avg_price_1d', 10, 2)->nullable();
            $table->decimal('avg_price_7d', 10, 2)->nullable();
            $table->decimal('avg_price_30d', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['scraping_card_id', 'scraped_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_card_price_snapshots');
    }
};
