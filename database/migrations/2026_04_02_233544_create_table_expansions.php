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
        Schema::create('scraping_expansions', function (Blueprint $table) {
            // Utilizziamo unsignedBigInteger senza auto_increment poiché gli ID sono predefiniti
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->foreignId('scraper_provider_id')->constrained('scraping_providers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_expansions');
    }
};
