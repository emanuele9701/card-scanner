<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tcg_cards_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('language', 10);
            $table->string('name')->nullable();
            $table->string('url_image')->nullable();
            $table->timestamps();

            $table->foreign('card_id')->references('id')->on('tcg_cards')->onDelete('cascade');
            $table->unique(['card_id', 'language']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tcg_cards_translations');
    }
};
