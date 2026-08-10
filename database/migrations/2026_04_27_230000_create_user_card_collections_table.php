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
        Schema::create('user_card_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('card_id')->constrained('tcg_cards')->cascadeOnDelete();
            $table->foreignId('set_id')->constrained('tcg_sets')->cascadeOnDelete();
            $table->foreignId('serie_id')->constrained('tcg_series')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('variants')->nullable()->comment('Varianti possedute (normal, holo, reverse, ecc.)');
            $table->string('condition')->default('NM')->comment('Condizione: NM, LP, MP, HP, DMG');
            $table->text('notes')->nullable()->comment('Note personali sulla carta');
            $table->timestamps();

            $table->unique(['user_id', 'card_id', 'condition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_card_collections');
    }
};
