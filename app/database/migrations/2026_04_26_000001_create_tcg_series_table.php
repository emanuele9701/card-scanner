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
        Schema::create('tcg_series', function (Blueprint $table) {
            $table->id();
            $table->string('serie_id');
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('language')->default('it');
            $table->timestamps();

            $table->unique(['serie_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcg_series');
    }
};
