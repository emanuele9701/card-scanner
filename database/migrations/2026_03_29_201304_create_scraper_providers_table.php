<?php

use App\Models\ScraperProvider;
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
        Schema::create('scraping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url');
            $table->string('search_url_pattern');
            $table->string('path_starting_point');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        ScraperProvider::create([
            'name' => 'CardTrader',
            'base_url' => 'https://www.cardtrader.com',
            'search_url_pattern' => '',
            'path_starting_point' => '/it/games/pokemon/categories/pokemon-singles/blueprints_search',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_providers');
    }
};
