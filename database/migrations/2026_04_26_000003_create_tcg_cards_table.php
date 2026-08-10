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
        Schema::create('tcg_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_id');
            $table->foreignId('set_id')->constrained('tcg_sets')->cascadeOnDelete();
            $table->string('name');
            $table->string('url_image')->nullable();
            $table->string('illustrator')->nullable();
            $table->string('rarity')->nullable();
            $table->json('variants')->nullable();
            $table->string('dexId')->nullable()->comment('Numero di carta nel mazzo');
            $table->json('types')->nullable()->comment('Tipo della carta');
            $table->string('evolve_from')->nullable()->comment('Carta da cui si evolve');
            $table->string('level_stage')->nullable()->comment('Stato di evoluzione');
            $table->string('language')->default('it');
            $table->timestamps();

            $table->unique(['card_id', 'set_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_cards');
    }
};
