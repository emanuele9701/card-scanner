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
        Schema::create('google_drive_tokens', function (Blueprint $table) {
            $table->id();

            // Identificatore del servizio (es: 'google_drive', 'google_photos', etc.)
            // Permette di usare la stessa tabella per più servizi Google se necessario
            $table->string('service')->default('google_drive');

            // Token OAuth 2.0
            $table->text('access_token'); // Access token (può essere lungo)
            $table->text('refresh_token')->nullable(); // Refresh token

            // Scadenza
            $table->timestamp('expires_at'); // Quando scade l'access token

            // Metadata del token
            $table->string('token_type')->default('Bearer');
            $table->json('scopes')->nullable(); // Scopes autorizzati

            // Statistiche
            $table->integer('refresh_count')->default(0); // Quante volte è stato rinnovato
            $table->timestamp('last_refreshed_at')->nullable(); // Ultima volta che è stato rinnovato

            $table->timestamps();

            // Indexes
            $table->unique('service'); // Un solo token per servizio
            $table->index('expires_at'); // Per query veloci sulla scadenza
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_tokens');
    }
};
