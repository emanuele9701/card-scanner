<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('google_drive_files', function (Blueprint $table) {
            $table->string('drive_id')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_drive_files', function (Blueprint $table) {
            $table->string('drive_id')->nullable(false)->change();
            $table->string('mime_type')->nullable(false)->change();
        });
    }
};
