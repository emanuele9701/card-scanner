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
        Schema::create('tcg_card_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('tcg_cards')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->json('cost')->nullable();
            $table->string('name')->nullable();
            $table->text('effect')->nullable();
            $table->string('damage')->nullable();
            $table->string('language')->default('it');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_card_abilities');
    }
};
