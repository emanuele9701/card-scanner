<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleDriveFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pokemon_card_id',
        'user_id',
        'drive_id',
        'name',
        'mime_type',
        'size',
        'is_public',
        'is_shared',
        'web_view_link',
        'web_content_link',
        'thumbnail_link',
        'parent_folder_id',
        'drive_created_at',
        'drive_modified_at',
        'owners',
        'metadata',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_shared' => 'boolean',
        'owners' => 'array',
        'metadata' => 'array',
        'drive_created_at' => 'datetime',
        'drive_modified_at' => 'datetime',
        'size' => 'integer',
    ];

    /**
     * Relazione con PokemonCard
     */
    public function pokemonCard(): BelongsTo
    {
        return $this->belongsTo(PokemonCard::class);
    }

    /**
     * Relazione con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formatta la dimensione del file in formato leggibile
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the file upload was successful
     */
    public function isUploaded(): bool
    {
        return $this->status === 'uploaded';
    }

    /**
     * Check if the file upload failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as uploaded successfully
     */
    public function markAsUploaded(): void
    {
        $this->update([
            'status' => 'uploaded',
            'error_message' => null,
        ]);
    }

    /**
     * Mark as failed with error message
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
