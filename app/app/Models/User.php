<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserSetting;

#[Fillable(['name', 'email', 'password', 'fcm_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Collezione di carte dell'utente.
     */
    public function collection()
    {
        return $this->hasMany(UserCardCollection::class, 'user_id', 'id');
    }

    /**
     * Impostazioni dell'utente.
     */
    public function settings()
    {
        return $this->hasMany(UserSetting::class, 'user_id', 'id');
    }

    public function cardWatchlists()
    {
        return $this->hasMany(UserCardWatchlist::class, 'user_id', 'id');
    }

    public function setWatchlists()
    {
        return $this->hasMany(UserSetWatchlist::class, 'user_id', 'id');
    }

    /**
     * Restituisce il valore di una specifica impostazione.
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Salva un'impostazione dell'utente.
     */
    public function setSetting(string $key, string $value)
    {
        return $this->settings()->updateOrCreate(
            ['user_id' => $this->id, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Lingua preferita dell'utente.
     */
    public function getLanguageAttribute(): string
    {
        return $this->getSetting('language', config('app.locale'));
    }
}
