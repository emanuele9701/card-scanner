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
        Schema::create('scraping_pages_queues', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed','disabled'])->default('pending');
            $table->enum('type', ['search', 'product'])->default('search');
            $table->text('last_error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('provider_id')->constrained('scraping_providers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_pages_queues');
    }
};
