<?php

use App\Models\MarketPrice;
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
        Schema::table('market_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('market_prices', 'trend')) {
                $table->decimal('trend', 10, 2)->nullable()->after('market_price');
            }
            if (!Schema::hasColumn('market_prices', 'unit_divisa')) {
                $table->enum('unit_divisa', array_keys(MarketPrice::UNITS_DIVISA))->default('eur');
            }
            if (!Schema::hasColumn('market_prices', 'high_price')) {
                $table->decimal('high_price', 10, 2)->nullable()->after('market_price');
            }
            if (!Schema::hasColumn('market_prices', 'mid_price')) {
                $table->decimal('mid_price', 10, 2)->nullable()->after('market_price');
            }
            if (!Schema::hasColumn('market_prices', 'avg1')) {
                $table->decimal('avg1', 10, 2)->nullable()->after('trend');
            }
            if (!Schema::hasColumn('market_prices', 'avg7')) {
                $table->decimal('avg7', 10, 2)->nullable()->after('avg1');
            }
            if (!Schema::hasColumn('market_prices', 'avg30')) {
                $table->decimal('avg30', 10, 2)->nullable()->after('avg7');
            }


            if (!Schema::hasColumn('market_prices', 'external_product_id')) {
                $table->string('external_product_id')->nullable();
            }

            $table->string('printing', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->dropColumn(['trend', 'avg1', 'avg7', 'avg30', 'unit_divisa', 'high_price', 'mid_price', 'external_product_id']);
            $table->enum('printing', ['Normal', 'Reverse Holofoil', 'Holofoil'])->change();
        });
    }
};
