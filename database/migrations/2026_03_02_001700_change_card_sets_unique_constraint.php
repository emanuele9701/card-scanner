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
            // Rimuovi il vecchio vincolo unique solo su abbreviation
            $table->dropUnique(['abbreviation']);

            // Crea il nuovo vincolo composito
            $table->unique(
                ['abbreviation', 'release_date', 'name', 'external_set_id'],
                'card_sets_composite_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_sets', function (Blueprint $table) {
            // Rimuovi il vincolo composito
            $table->dropUnique('card_sets_composite_unique');

            // Ripristina il vecchio vincolo su abbreviation
            $table->unique('abbreviation');
        });
    }
};
