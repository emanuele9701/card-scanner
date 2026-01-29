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
        Schema::table('card_sets', function (Blueprint $table) {
            // Drop existing unique constraint if exists (e.g. on abbreviation only) to avoid conflicts if needed,
            // but assuming no previous unique constraint conflicts with this new specific one.
            $table->dropUnique('card_sets_abbreviation_unique');
            $table->unique(['user_id', 'abbreviation', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_sets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'abbreviation', 'name']);
            $table->unique('abbreviation', 'card_sets_abbreviation_unique');
        });
    }
};
