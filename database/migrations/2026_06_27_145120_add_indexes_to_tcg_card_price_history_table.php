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
        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->index(['card_id', 'provider', 'created_at'], 'idx_card_prov_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->dropIndex('idx_card_prov_date');
        });
    }
};
