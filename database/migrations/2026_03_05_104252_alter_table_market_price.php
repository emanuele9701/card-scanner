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

        if (!Schema::hasTable('provider_prices')) {
            Schema::create('provider_prices', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique('unic_provider');
            });
        }

        if (!Schema::hasColumn('market_prices', 'provider_id')) {
            Schema::table('market_prices', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('id');
                $table->foreign('provider_id')->references('id')->on('provider_prices')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
        Schema::dropIfExists('provider_prices');
    }
};
