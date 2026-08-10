<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tcg_sets', function (Blueprint $table) {
            $table->string('abbreviation_official', 20)->nullable()->after('abbreviation');
            $table->index('abbreviation_official');
        });

        // Popola dalla colonna JSON esistente
        DB::statement("
            UPDATE tcg_sets 
            SET abbreviation_official = UPPER(JSON_UNQUOTE(JSON_EXTRACT(abbreviation, '$.official')))
            WHERE abbreviation IS NOT NULL 
              AND JSON_UNQUOTE(JSON_EXTRACT(abbreviation, '$.official')) != 'null'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tcg_sets', function (Blueprint $table) {
            $table->dropIndex(['abbreviation_official']);
            $table->dropColumn('abbreviation_official');
        });
    }
};
