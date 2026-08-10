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
        Schema::create('tcg_sets', function (Blueprint $table) {
            $table->id();
            $table->string('set_id');
            $table->foreignId('serie_id')->constrained('tcg_series')->cascadeOnDelete();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('symbol')->nullable();
            $table->unsignedInteger('card_total')->nullable();
            $table->unsignedInteger('card_official')->nullable();
            $table->unsignedInteger('card_normal')->nullable();
            $table->unsignedInteger('card_reverse')->nullable();
            $table->unsignedInteger('card_holo')->nullable();
            $table->unsignedInteger('card_first_edition')->nullable();
            $table->date('release_date')->nullable();
            $table->json('variants')->nullable();
            $table->json('abbreviation')->nullable();
            $table->string('language')->default('it');
            $table->timestamps();

            $table->unique(['set_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_sets');
    }
};
