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
        Schema::table('market_cards', function (Blueprint $table) {
            // Drop the existing unique constraint on product_id only
            $table->dropUnique('market_cards_product_id_unique');

            // Add a composite unique constraint on product_id + user_id
            $table->unique(['product_id', 'user_id'], 'market_cards_product_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_cards', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('market_cards_product_user_unique');

            // Restore the original unique constraint on product_id only
            $table->unique('product_id', 'market_cards_product_id_unique');
        });
    }
};
