<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('card_sets', function (Blueprint $table) {
            $table->integer('external_set_id', $autoIncrement = false, $unsigned = true)->nullable()->after('abbreviation');
            $table->integer('external_category_id', $autoIncrement = false, $unsigned = true)->nullable()->after('abbreviation');

            $table->boolean('is_supplemental')->nullable()->after('abbreviation');
            $table->boolean('is_active')->nullable()->after('abbreviation');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_sets', function (Blueprint $table) {
            $table->dropColumn('external_set_id');
            $table->dropColumn('external_category_id');
            $table->dropColumn('is_supplemental');
            $table->dropColumn('is_active');
        });
    }
};
