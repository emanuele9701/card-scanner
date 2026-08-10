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
        Schema::table('user_incoming_cards', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('notes');
            $table->string('external_id', 100)->nullable()->after('source');
            $table->string('condition', 30)->nullable()->after('external_id');
            
            // Indice univoco per garantire l'idempotenza durante l'import (source, external_id)
            $table->unique(['source', 'external_id'], 'user_incoming_cards_source_ext_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_incoming_cards', function (Blueprint $table) {
            $table->dropUnique('user_incoming_cards_source_ext_id_unique');
            $table->dropColumn(['source', 'external_id', 'condition']);
        });
    }
};
