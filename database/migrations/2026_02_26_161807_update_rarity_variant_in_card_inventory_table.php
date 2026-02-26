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
        Schema::table('card_inventory', function (Blueprint $table) {
            $table->string('rarity_variant')->default('Standard')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_inventory', function (Blueprint $table) {
            $table->enum('rarity_variant', [
                'Standard',
                'Reverse Holo',
                'Holo',
                'First Edition',
                'Shadowless',
                'Error Card',
                'Promo',
                'Altro'
            ])->default('Standard')->change();
        });
    }
};
