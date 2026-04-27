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
        Schema::create('tcg_card_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('tcg_cards')->cascadeOnDelete();
            $table->string('card_id_product')->nullable()->comment('ID prodotto Cardmarket');
            $table->string('unit')->nullable()->comment('Valuta (es. EUR)');
            $table->decimal('avg', 10, 2)->nullable();
            $table->decimal('low', 10, 2)->nullable();
            $table->decimal('trend', 10, 2)->nullable();
            $table->decimal('avg_1d', 10, 2)->nullable();
            $table->decimal('avg_7d', 10, 2)->nullable();
            $table->decimal('avg_30d', 10, 2)->nullable();
            $table->decimal('avg_holo', 10, 2)->nullable();
            $table->decimal('low_holo', 10, 2)->nullable();
            $table->decimal('trend_holo', 10, 2)->nullable();
            $table->decimal('avg_1d_holo', 10, 2)->nullable();
            $table->decimal('avg_7d_holo', 10, 2)->nullable();
            $table->decimal('avg_30d_holo', 10, 2)->nullable();
            $table->string('language')->default('it');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_card_prices');
    }
};
