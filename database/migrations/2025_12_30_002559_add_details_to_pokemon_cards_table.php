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
        Schema::table('pokemon_cards', function (Blueprint $table) {
            $table->string('card_name')->nullable()->after('status');
            $table->string('hp')->nullable()->after('card_name');
            $table->string('type')->nullable()->after('hp');
            $table->string('evolution_stage')->nullable()->after('type');
            $table->text('attacks')->nullable()->after('evolution_stage'); // Storing JSON or text blob
            $table->string('weakness')->nullable()->after('attacks');
            $table->string('resistance')->nullable()->after('weakness');
            $table->string('retreat_cost')->nullable()->after('resistance');
            $table->string('rarity')->nullable()->after('retreat_cost');
            $table->string('set_number')->nullable()->after('rarity');
            $table->string('illustrator')->nullable()->after('set_number');
            $table->text('flavor_text')->nullable()->after('illustrator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemon_cards', function (Blueprint $table) {
            $table->dropColumn([
                'card_name',
                'hp',
                'type',
                'evolution_stage',
                'attacks',
                'weakness',
                'resistance',
                'retreat_cost',
                'rarity',
                'set_number',
                'illustrator',
                'flavor_text'
            ]);
        });
    }
};
