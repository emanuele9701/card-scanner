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
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_card_id')->constrained()->onDelete('cascade');
            $table->enum('condition', [
                'Damaged',
                'Heavily Played',
                'Moderately Played',
                'Lightly Played',
                'Near Mint'
            ]);
            $table->enum('printing', [
                'Normal',
                'Reverse Holofoil',
                'Holofoil'
            ]);
            $table->decimal('low_price', 10, 2);
            $table->decimal('market_price', 10, 2);
            $table->integer('sales_count')->default(0);
            $table->date('import_date');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['market_card_id', 'import_date']);
            $table->index('import_date');
            $table->index(['market_card_id', 'condition', 'printing']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
