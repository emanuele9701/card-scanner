<?php

namespace App\Services\CardTrader;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CardTraderClient
{
    private string $baseUrl = 'https://api.cardtrader.com/api/v2';
    private string $token;
    
    // Rate limit parameters
    private const RATE_LIMIT = 200;
    private const RATE_LIMIT_WINDOW_SECONDS = 10;
    
    // Array to store timestamps of the last 200 requests (static to persist across instances in the same process)
    private static array $requestTimestamps = [];

    public function __construct()
    {
        $this->token = config('services.cardtrader.token', env('CARDTRADER_API_TOKEN', ''));
    }

    private function applyRateLimit(): void
    {
        $now = microtime(true);
        
        // Remove timestamps older than the window
        self::$requestTimestamps = array_filter(self::$requestTimestamps, function ($timestamp) use ($now) {
            return ($now - $timestamp) < self::RATE_LIMIT_WINDOW_SECONDS;
        });

        // If we reached the limit
        if (count(self::$requestTimestamps) >= self::RATE_LIMIT) {
            // Get the oldest timestamp in the current window
            $oldestTimestamp = min(self::$requestTimestamps);
            
            // Calculate how much time we need to wait
            $timeToWait = self::RATE_LIMIT_WINDOW_SECONDS - ($now - $oldestTimestamp);
            
            if ($timeToWait > 0) {
                // Add a tiny buffer (10ms) to be safe
                $sleepMicroseconds = (int)(($timeToWait + 0.01) * 1000000);
                usleep($sleepMicroseconds);
            }
            
            // Recalculate 'now' after sleeping
            $now = microtime(true);
        }

        // Add the current request timestamp
        self::$requestTimestamps[] = $now;
    }

    public function get(string $endpoint, array $query = []): array
    {
        $this->applyRateLimit();

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get("{$this->baseUrl}{$endpoint}", $query);

        if ($response->failed()) {
            Log::error("CardTrader API error on {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            $response->throw();
        }

        return $response->json() ?? [];
    }
}
