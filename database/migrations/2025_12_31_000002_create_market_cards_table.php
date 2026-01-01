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
        Schema::create('market_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('product_name');
            $table->string('card_number');
            $table->string('set_name');
            $table->string('set_abbreviation');
            $table->string('rarity');
            $table->string('type');
            $table->string('game')->default('Pokemon');
            $table->boolean('is_supplemental')->default(false);
            $table->timestamps();

            $table->index(['card_number', 'set_abbreviation']);
            $table->index('product_name');
            $table->index('set_abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_cards');
    }
};
