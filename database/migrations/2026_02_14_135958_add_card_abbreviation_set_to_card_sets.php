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
            $table->string("card_set_abbreviation")->comment("L'identificatore del set che si trova sulla carta.")->after('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_sets', function (Blueprint $table) {
            $table->dropColumn('card_set_abbreviation');
        });
    }
};
