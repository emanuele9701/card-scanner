<?php

namespace App\Models;

use App\Enums\ScraperStatus;
use App\Enums\ScraperPageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapingPageQueue extends Model
{
    use HasFactory;

    /**
     * Il nome della tabella associata al modello.
     */
    protected $table = 'scraping_pages_queues';

    /**
     * Gli attributi assegnabili tramite mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'provider_id',
        'type',
        'status',
        'last_error_message',
        'processed_at',
    ];

    /**
     * I cast degli attributi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'       => ScraperStatus::class,
        'processed_at' => 'datetime',
        'type'         => ScraperPageType::class,
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(ScraperProvider::class, 'provider_id');
    }
}
