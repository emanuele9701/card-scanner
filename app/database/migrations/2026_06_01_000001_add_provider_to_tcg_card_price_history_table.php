<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->string('provider')->nullable()->default('cardmarket')->after('card_id');
        });
    }

    public function down(): void
    {
        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
