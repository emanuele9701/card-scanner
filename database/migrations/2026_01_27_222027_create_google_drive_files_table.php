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
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();

            // Relazione con la carta (nullable perché potrebbe essere usato per altri scopi)
            $table->foreignId('pokemon_card_id')->nullable()->constrained('pokemon_cards')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Informazioni di Google Drive
            $table->string('drive_id')->unique(); // L'ID del file su Google Drive
            $table->string('name'); // Nome del file
            $table->string('mime_type'); // MIME Type (image/jpeg, etc.)
            $table->bigInteger('size')->nullable(); // Dimensione in bytes

            // Permessi e condivisione
            $table->boolean('is_public')->default(false); // Se il file è pubblico
            $table->boolean('is_shared')->default(false); // Se il file è condiviso

            // Links
            $table->text('web_view_link')->nullable(); // Link per visualizzare
            $table->text('web_content_link')->nullable(); // Link per scaricare
            $table->text('thumbnail_link')->nullable(); // Link thumbnail

            // Metadati Google Drive
            $table->string('parent_folder_id')->nullable(); // ID della cartella padre
            $table->timestamp('drive_created_at')->nullable(); // Data creazione su Drive
            $table->timestamp('drive_modified_at')->nullable(); // Data modifica su Drive

            // Informazioni aggiuntive
            $table->json('owners')->nullable(); // Array di proprietari
            $table->json('metadata')->nullable(); // Altri metadati JSON

            // Stato del file
            $table->enum('status', ['uploading', 'uploaded', 'failed', 'deleted'])->default('uploading');
            $table->text('error_message')->nullable(); // Messaggio di errore se fallito

            $table->timestamps();
            $table->softDeletes(); // Per soft delete

            // Indexes
            $table->index('drive_id');
            $table->index(['user_id', 'status']);
            $table->index('pokemon_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_files');
    }
};
