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
        Schema::create('card_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation')->unique();
            $table->date('release_date')->nullable();
            $table->integer('total_cards')->nullable();
            $table->timestamps();

            $table->index('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_sets');
    }
};
