<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GoogleDriveToken extends Model
{
    protected $fillable = [
        'service',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scopes',
        'refresh_count',
        'last_refreshed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'scopes' => 'array',
        'refresh_count' => 'integer',
    ];

    /**
     * Get the token for Google Drive service
     */
    public static function getGoogleDriveToken(): ?self
    {
        return self::where('service', 'google_drive')->first();
    }

    /**
     * Check if the access token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the access token is valid (not expired)
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Check if the token will expire soon (within 5 minutes)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at->diffInMinutes(now()) < 5;
    }

    /**
     * Update the access token
     */
    public function updateAccessToken(string $accessToken, int $expiresIn): void
    {
        $this->update([
            'access_token' => $accessToken,
            'expires_at' => now()->addSeconds($expiresIn),
            'refresh_count' => $this->refresh_count + 1,
            'last_refreshed_at' => now(),
        ]);
    }

    /**
     * Create or update the Google Drive token
     */
    public static function createOrUpdateToken(array $tokenData): self
    {
        $expiresAt = isset($tokenData['expires_in'])
            ? now()->addSeconds($tokenData['expires_in'])
            : now()->addHour(); // Default 1 hour if not specified

        return self::updateOrCreate(
            ['service' => 'google_drive'],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => $expiresAt,
                'token_type' => $tokenData['token_type'] ?? 'Bearer',
                'scopes' => $tokenData['scope'] ?? null,
            ]
        );
    }

    /**
     * Get the token in array format suitable for Google Client
     */
    public function toGoogleClientArray(): array
    {
        return [
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_at->diffInSeconds(now()),
            'created' => $this->expires_at->subSeconds($this->expires_at->diffInSeconds(now()))->timestamp,
        ];
    }
}
