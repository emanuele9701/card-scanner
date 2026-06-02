<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tcg_card_prices', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('language');
            $table->boolean('is_first_edition')->default(false)->after('condition');
            $table->boolean('is_altered')->default(false)->after('is_first_edition');
            $table->boolean('is_signed')->default(false)->after('is_altered');
            $table->boolean('is_reverse')->default(false)->after('is_signed');
        });

        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('avg_holo');
            $table->boolean('is_first_edition')->default(false)->after('condition');
            $table->boolean('is_altered')->default(false)->after('is_first_edition');
            $table->boolean('is_signed')->default(false)->after('is_altered');
            $table->boolean('is_reverse')->default(false)->after('is_signed');
        });
    }

    public function down()
    {
        Schema::table('tcg_card_prices', function (Blueprint $table) {
            $table->dropColumn(['condition', 'is_first_edition', 'is_altered', 'is_signed', 'is_reverse']);
        });

        Schema::table('tcg_card_price_history', function (Blueprint $table) {
            $table->dropColumn(['condition', 'is_first_edition', 'is_altered', 'is_signed', 'is_reverse']);
        });
    }
};
