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
        Schema::create('tcg_external_providers_games', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('external_id')->index();
            $table->string('name');
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
        });

        Schema::create('tcg_external_providers_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_game_id')->constrained('tcg_external_providers_games')->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->string('name');
            $table->string('abbreviation')->nullable()->index();
            $table->timestamps();

            $table->unique(['external_game_id', 'external_id']);
        });

        Schema::create('tcg_external_providers_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('tcg_cards')->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('external_id')->index();
            $table->timestamps();

            $table->unique(['card_id', 'provider']);
            $table->unique(['provider', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_external_providers_mappings');
        Schema::dropIfExists('tcg_external_providers_sets');
        Schema::dropIfExists('tcg_external_providers_games');
    }
};
