<?php

namespace App\Models;

use App\Enums\UrlMappingStatus;
use App\Enums\UrlMappingType;
use Illuminate\Database\Eloquent\Model;

class UrlMapping extends Model
{
    protected $table = 'url_mappings';

    protected $fillable = [
        'site_name',
        'url_path',
        'status',
        'last_scraped_at',
        'attempts_ok',
        'attempts_failed',
        'type',
    ];

    protected $casts = [
        'status'          => UrlMappingStatus::class,
        'type'            => UrlMappingType::class,
        'last_scraped_at' => 'datetime',
        'attempts_ok'     => 'integer',
        'attempts_failed' => 'integer',
    ];

    // ─── Scope ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', UrlMappingStatus::Pending);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', UrlMappingStatus::Failed);
    }

    public function scopeDone($query)
    {
        return $query->where('status', UrlMappingStatus::Done);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Registra un tentativo andato a buon fine.
     */
    public function markSuccess(): void
    {
        $this->update([
            'status'          => UrlMappingStatus::Done,
            'last_scraped_at' => now(),
            'attempts_ok'     => $this->attempts_ok + 1,
        ]);
    }

    /**
     * Registra un tentativo fallito.
     */
    public function markFailed(): void
    {
        $this->update([
            'status'         => UrlMappingStatus::Failed,
            'attempts_failed' => $this->attempts_failed + 1,
        ]);
    }

    /**
     * Rimette l'URL in coda per il prossimo scraping.
     */
    public function resetToPending(): void
    {
        $this->update(['status' => UrlMappingStatus::Pending]);
    }
}
