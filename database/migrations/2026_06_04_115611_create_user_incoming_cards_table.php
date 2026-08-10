<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_incoming_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('set_id')->nullable();
            $table->string('language', 10)->default('en');
            $table->enum('foil_type', ['normal', 'holo', 'reverse'])->default('normal');
            $table->boolean('is_first_edition')->default(false);
            $table->boolean('is_signed')->default(false);
            $table->boolean('is_altered')->default(false);
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('card_id')->references('id')->on('tcg_cards')->onDelete('cascade');
            $table->foreign('set_id')->references('id')->on('tcg_sets')->onDelete('set null');

            $table->index(['user_id', 'card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_incoming_cards');
    }
};
