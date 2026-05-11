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
        Schema::create('tcg_card_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('tcg_cards')->cascadeOnDelete();
            $table->string('article_id')->nullable()->index();
            $table->string('seller_name')->nullable();
            $table->string('seller_profile_url')->nullable();
            $table->string('seller_country')->nullable();
            $table->integer('seller_sales_count')->nullable();
            $table->integer('seller_available_items')->nullable();
            $table->string('card_condition')->nullable();
            $table->string('card_condition_code')->nullable();
            $table->string('card_language')->nullable();
            $table->boolean('is_reverse_holo')->default(false);
            $table->boolean('is_holo')->default(false);
            $table->string('card_special_type')->nullable();
            $table->text('seller_comment')->nullable();
            $table->decimal('price_eur', 10, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_card_offers');
    }
};
