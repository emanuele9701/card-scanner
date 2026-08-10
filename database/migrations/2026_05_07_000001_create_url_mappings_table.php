<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UrlMappingStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('url_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('url_path');
            $table->string('status')->default(UrlMappingStatus::Pending->value);
            $table->timestamp('last_scraped_at')->nullable();
            $table->unsignedInteger('attempts_ok')->default(0);
            $table->unsignedInteger('attempts_failed')->default(0);
            $table->timestamps();

            $table->unique(['site_name', 'url_path']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_mappings');
    }
};
