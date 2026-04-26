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
        Schema::create('scraping_card_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')
                  ->constrained('scraping_card_price_snapshots')
                  ->cascadeOnDelete();
            $table->string('article_id')->index();
            $table->string('seller_name');
            $table->text('seller_url')->nullable();
            $table->string('language', 30)->nullable();
            $table->text('comment')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['snapshot_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_card_offers');
    }
};
