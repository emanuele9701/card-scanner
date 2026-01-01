<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the column type without needing doctrine/dbal
        // This is safe since we know the driver is MySQL from the user's error log
        DB::statement("ALTER TABLE pokemon_cards MODIFY status VARCHAR(255) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemon_cards', function (Blueprint $table) {
            // Revert back to enum if necessary (though usually not recommended to revert to stricter types with potentially incompatible data)
            // For safety in down(), we might just leave it or try to revert if data allows
            // $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->change();
        });
    }
};
