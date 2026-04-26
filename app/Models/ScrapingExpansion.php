<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\BelongsToManyRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScrapingExpansion extends Model
{
    use HasFactory;

    /**
     * Poiché stiamo inserendo manualmente gli ID (presi dalla select),
     * dobbiamo disabilitare l'auto-incremento di Eloquent.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Il tipo di dato della chiave primaria.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * Gli attributi assegnabili in massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'abbreviation',
        'scraper_provider_id',
    ];

    /**
     * Relazione: Un'espansione appartiene a uno Scraper Provider.
     */
    public function scraperProvider(): BelongsTo
    {
        return $this->belongsTo(ScraperProvider::class);
    }

    /**
     * Mutator per assicurarsi che l'abbreviazione contenga solo il codice del set
     * e venga ripulita da eventuali suffissi di lingua (es. "BRS-EN" o "BRS IT" diventa "BRS").
     */
    protected function abbreviation(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if (!$value) {
                    return null;
                }

                // Rimuove eventuali suffissi di lingua come -EN, -IT, _JP, o spaziati " FR"
                // Modifica la regex in base al formato esatto che ricevi dallo scraper
                return trim(preg_replace('/[\s\-_]+[a-zA-Z]{2}$/', '', $value));
            }
        );
    }

    /**
     * Gli utenti che sono associati a questa espansione.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getUrlEncodedName(): string
    {
        return str_replace(" ", "-", $this->name);
    }
}
