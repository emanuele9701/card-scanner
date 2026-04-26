<?php

namespace App\Models;

use App\Enums\ProviderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScraperProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scraping_providers';

    /**
     * Gli attributi assegnabili tramite mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'base_url',
        'search_url_pattern',
        'path_starting_point',
        'status',
    ];

    /**
     * I cast degli attributi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ProviderStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pagesQueue()
    {
        return $this->hasMany(ScrapingPageQueue::class, 'provider_id');
    }
}
