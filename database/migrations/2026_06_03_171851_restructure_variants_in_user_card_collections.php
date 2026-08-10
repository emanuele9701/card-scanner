<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_card_collections', function (Blueprint $table) {
            $table->string('language', 10)->default('en')->after('quantity');
            $table->enum('foil_type', ['normal', 'holo', 'reverse'])->default('normal')->after('language');
            $table->boolean('is_first_edition')->default(false)->after('foil_type');
            $table->boolean('is_signed')->default(false)->after('is_first_edition');
            $table->boolean('is_altered')->default(false)->after('is_signed');
        });

        // Migrate existing data
        $collections = DB::table('user_card_collections')->whereNotNull('variants')->get();
        foreach ($collections as $c) {
            // variants was stored as a JSON array of strings
            $variants = json_decode($c->variants, true);
            if (!is_array($variants)) continue;
            
            $language = 'en'; // default fallback
            $foil_type = 'normal';
            $is_first_edition = false;
            $is_signed = false;
            $is_altered = false;

            $knownLanguages = ['it', 'en', 'jp', 'fr', 'de', 'es', 'pt'];

            foreach ($variants as $v) {
                $vLow = strtolower($v);
                if (in_array($vLow, $knownLanguages)) {
                    $language = $vLow;
                } elseif ($vLow === 'holo') {
                    $foil_type = 'holo';
                } elseif ($vLow === 'reverse') {
                    $foil_type = 'reverse';
                } elseif ($vLow === 'firstedition' || strpos($vLow, '1') !== false) {
                    $is_first_edition = true;
                } elseif ($vLow === 'signed') {
                    $is_signed = true;
                } elseif ($vLow === 'altered') {
                    $is_altered = true;
                }
            }

            DB::table('user_card_collections')->where('id', $c->id)->update([
                'language' => $language,
                'foil_type' => $foil_type,
                'is_first_edition' => $is_first_edition,
                'is_signed' => $is_signed,
                'is_altered' => $is_altered
            ]);
        }

        Schema::table('user_card_collections', function (Blueprint $table) {
            $table->dropColumn('variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_card_collections', function (Blueprint $table) {
            $table->json('variants')->nullable();
        });

        // Rollback data
        $collections = DB::table('user_card_collections')->get();
        foreach ($collections as $c) {
            $variants = [];
            if ($c->language && $c->language !== 'en') $variants[] = $c->language;
            if ($c->foil_type && $c->foil_type !== 'normal') $variants[] = $c->foil_type;
            if ($c->is_first_edition) $variants[] = 'firstEdition';
            if ($c->is_signed) $variants[] = 'signed';
            if ($c->is_altered) $variants[] = 'altered';

            DB::table('user_card_collections')->where('id', $c->id)->update([
                'variants' => empty($variants) ? null : json_encode($variants)
            ]);
        }

        Schema::table('user_card_collections', function (Blueprint $table) {
            $table->dropColumn(['language', 'foil_type', 'is_first_edition', 'is_signed', 'is_altered']);
        });
    }
};
