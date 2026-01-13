<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service per recuperare prezzi storici da BitGet e MEXC API
 */
class BitGetPriceService
{
    private const API_BASE = 'https://api.bitget.com';
    private const MEXC_BASE = 'https://api.mexc.com';
    // private const CACHE_TTL = 86400; // Removed in favor of rememberForever

    /**
     * Recupera il prezzo di chiusura al timestamp specificato
     * 
     * @param string $symbol Symbol (es. BTC, ETH, ONDO)
     * @param int $timestamp Unix timestamp in secondi
     * @return float|null Prezzo in USDT o null se non trovato
     */
    public function getHistoricalPrice(string $symbol, int $timestamp): ?float
    {
        // Cache key based on symbol and minute
        $minute = floor($timestamp / 60) * 60;
        $cacheKey = "price_{$symbol}_{$minute}";

        return Cache::rememberForever($cacheKey, function () use ($symbol, $timestamp) {
            return $this->fetchPriceFromAPI($symbol, $timestamp);
        });
    }

    /**
     * Recupera il prezzo da API multiple (BitGet -> MEXC)
     */
    private function fetchPriceFromAPI(string $symbol, int $timestamp): ?float
    {
        // Prova prima con BitGet (dati recenti, più veloce)
        $price = $this->fetchFromBitGet($symbol, $timestamp);

        if ($price) {
            return $price;
        }

        // Se BitGet non ha dati, prova con MEXC (storico più esteso)
        // Log::info("BitGet no data, trying MEXC for {$symbol}");
        return $this->fetchFromMEXC($symbol, $timestamp);
    }

    /**
     * Recupera da BitGet API
     */
    private function fetchFromBitGet(string $symbol, int $timestamp): ?float
    {
        try {
            $pair = $symbol . 'USDT';

            // Determine interval based on timestamp age
            // Use 1h for timestamps older than 6 months, 1m otherwise
            $sixMonthsAgo = now()->subMonths(3)->timestamp;
            $useHourlyInterval = $timestamp < $sixMonthsAgo;

            $granularity = $useHourlyInterval ? '1h' : '1m';
            $intervalSeconds = $useHourlyInterval ? 3600 : 60;

            // Align timestamp to the interval boundary
            $alignedTimestamp = $timestamp - ($timestamp % $intervalSeconds);
            $startTime = $alignedTimestamp * 1000;
            $endTime = ($alignedTimestamp + $intervalSeconds) * 1000;

            $response = Http::timeout(10)->get(self::API_BASE . '/api/v2/spot/market/candles', [
                'symbol' => $pair,
                'granularity' => $granularity,
                'startTime' => (string) $startTime,
                'endTime' => (string) $endTime,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] === '00000' && !empty($data['data'])) {
                $closePrice = (float) ($data['data'][0][4] ?? 0);

                if ($closePrice > 0) {
                    Log::info("BitGet price fetched", [
                        'symbol' => $symbol,
                        'price' => $closePrice,
                    ]);
                    return $closePrice;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Recupera da MEXC API (fallback per dati storici)
     */
    private function fetchFromMEXC(string $symbol, int $timestamp): ?float
    {
        try {
            $pair = $symbol . 'USDT';

            // Arrotonda all'ora precedente (es. 10:32 -> 10:00)
            $hourStart = floor($timestamp / 3600) * 3600;
            $startTime = $hourStart * 1000;
            $endTime = ($hourStart + 3600) * 1000; // +1 ora

            $dataMexc = [
                'symbol' => $pair,
                'interval' => '60m', // Intervallo orario
                'startTime' => $startTime,
                'endTime' => $endTime,
                'limit' => 1,
            ];
            $response = Http::timeout(10)->get(self::MEXC_BASE . '/api/v3/klines', $dataMexc);

            // Log::info("MEXC POINT: " . self::MEXC_BASE . '/api/v3/klines' . "\n" . http_build_query($dataMexc));

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            // MEXC response: [[timestamp, open, high, low, close, volume, ...]]
            if (!empty($data) && is_array($data[0])) {
                $closePrice = (float) ($data[0][4] ?? 0);

                if ($closePrice > 0) {
                    // Log::info("MEXC price fetched", [
                    //     'symbol' => $symbol,
                    //     'price' => $closePrice,
                    // ]);
                    return $closePrice;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error fetching from MEXC for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
