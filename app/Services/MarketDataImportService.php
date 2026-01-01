<?php

namespace App\Services;

use App\Models\CardSet;
use App\Models\MarketCard;
use App\Models\MarketPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketDataImportService
{
    /**
     * Import market data from JSON array
     *
     * @param array $jsonData Array of card data from JSON
     * @return array Statistics about the import
     * @throws \Exception
     */
    public function importFromJson(array $jsonData): array
    {
        $stats = [
            'total' => count($jsonData),
            'sets_created' => 0,
            'cards_created' => 0,
            'cards_updated' => 0,
            'prices_created' => 0,
            'errors' => [],
        ];

        $importDate = now()->toDateString();

        DB::beginTransaction();
        try {
            // Group variants by product ID
            $groupedByProduct = collect($jsonData)->groupBy('productID');

            foreach ($groupedByProduct as $productId => $variants) {
                $firstVariant = $variants->first();

                // Ensure set exists
                $setCreated = $this->ensureSetExists($firstVariant);
                if ($setCreated) {
                    $stats['sets_created']++;
                }

                // Create or update market card
                $marketCard = MarketCard::updateOrCreate(
                    ['product_id' => $productId],
                    [
                        'product_name' => $firstVariant['productName'],
                        'card_number' => $firstVariant['number'],
                        'set_name' => $firstVariant['set'],
                        'set_abbreviation' => $firstVariant['setAbbrv'],
                        'rarity' => $firstVariant['rarity'],
                        'type' => $firstVariant['type'],
                        'game' => $firstVariant['game'],
                        'is_supplemental' => $firstVariant['isSupplemental'],
                    ]
                );

                $marketCard->wasRecentlyCreated
                    ? $stats['cards_created']++
                    : $stats['cards_updated']++;

                // Create price records for each condition/printing variant
                foreach ($variants as $variant) {
                    try {
                        MarketPrice::create([
                            'market_card_id' => $marketCard->id,
                            'condition' => $this->normalizeCondition($variant['condition']),
                            'printing' => $this->normalizePrinting($variant['printing']),
                            'low_price' => $variant['lowPrice'],
                            'market_price' => $variant['marketPrice'],
                            'sales_count' => $variant['sales'],
                            'import_date' => $importDate,
                            'created_at' => now(),
                        ]);
                        $stats['prices_created']++;
                    } catch (\Exception $e) {
                        $stats['errors'][] = "Error creating price for product {$productId}: " . $e->getMessage();
                        Log::warning('Price creation failed', [
                            'product_id' => $productId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            DB::commit();
            Log::info('Market data import successful', $stats);
        } catch (\Exception $e) {
            DB::rollBack();
            $stats['errors'][] = $e->getMessage();
            Log::error('Market data import failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Ensure a card set exists, create if not
     *
     * @param array $cardData Card data containing set information
     * @return bool True if set was created, false if it already existed
     */
    private function ensureSetExists(array $cardData): bool
    {
        $set = CardSet::firstOrCreate(
            ['abbreviation' => $cardData['setAbbrv']],
            ['name' => $cardData['set']]
        );

        return $set->wasRecentlyCreated;
    }

    /**
     * Normalize condition string by removing printing type suffixes
     *
     * @param string $condition Raw condition string from JSON
     * @return string Normalized condition
     */
    private function normalizeCondition(string $condition): string
    {
        // Remove all printing type suffixes from condition
        // Order matters: remove longer strings first to avoid partial matches
        $normalized = str_replace(
            [' Reverse Holofoil', ' Holofoil', ' Reverse'],
            '',
            $condition
        );
        return trim($normalized);
    }

    /**
     * Normalize printing type string
     *
     * @param string $printing Raw printing string from JSON
     * @return string Normalized printing type
     */
    private function normalizePrinting(string $printing): string
    {
        return $printing;
    }

    /**
     * Get statistics about existing market data
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_sets' => CardSet::count(),
            'total_cards' => MarketCard::count(),
            'total_prices' => MarketPrice::count(),
            'latest_import' => MarketPrice::max('import_date'),
            'unique_import_dates' => MarketPrice::distinct('import_date')->count('import_date'),
        ];
    }

    /**
     * Clean up old price records, keeping only the most recent N imports
     *
     * @param int $keepImports Number of recent imports to keep
     * @return int Number of deleted records
     */
    public function cleanupOldPrices(int $keepImports = 12): int
    {
        // Get the dates of the most recent imports
        $datesToKeep = MarketPrice::select('import_date')
            ->distinct()
            ->orderBy('import_date', 'desc')
            ->limit($keepImports)
            ->pluck('import_date');

        if ($datesToKeep->isEmpty()) {
            return 0;
        }

        // Delete prices not in the dates to keep
        $deleted = MarketPrice::whereNotIn('import_date', $datesToKeep)->delete();

        Log::info('Cleaned up old market prices', [
            'deleted' => $deleted,
            'kept_imports' => $keepImports,
        ]);

        return $deleted;
    }
}
