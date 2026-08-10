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
        Schema::table('url_mappings', function (Blueprint $table) {
            $table->enum('type', ['list_card', 'single_card'])->default('list_card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('url_mappings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
