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
        Schema::table('user_card_collections', function (Blueprint $table) {
            // Add a simple index on user_id so the foreign key constraint is satisfied
            $table->index('user_id');
            // Now we can safely drop the unique constraint
            $table->dropUnique('user_card_collections_user_id_card_id_condition_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_card_collections', function (Blueprint $table) {
            $table->unique(['user_id', 'card_id', 'condition'], 'user_card_collections_user_id_card_id_condition_unique');
            $table->dropIndex(['user_id']);
        });
    }
};
