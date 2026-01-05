<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Reset specified database tables and storage.
     * Protected by a configurable password.
     */
    public function resetDatabase(Request $request)
    {
        Log::info('Resetting database...');
        // 1. Validate Password
        $configuredPassword = env('DB_RESET_PASSWORD');

        if (empty($configuredPassword)) {
            Log::error('DB_RESET_PASSWORD is not configured in .env');
            return response()->json([
                'success' => false,
            ], 500);
        }

        $inputPassword = $request->input('password');

        if ($inputPassword !== $configuredPassword) {
            Log::error('Invalid password: ' . $inputPassword);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid password.'
            ], 403);
        }

        // 2. Define tables to reset
        // User requested: pokemon_cards, market_prices, market_cards, card_set
        // Actual tables (based on migrations): pokemon_cards, market_prices, market_cards, card_sets
        $tables = [
            'market_prices', // Child of market_cards usually?
            'market_cards',
            'pokemon_cards',
            'card_sets'
        ];

        $resetLog = [];

        try {
            Log::info('Disabling foreign key constraints...');
            // 3. Disable Foreign Key Constraints
            Schema::disableForeignKeyConstraints();
            Log::info('Foreign key constraints disabled.');

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $resetLog[] = "Truncated table: $table";
                } else {
                    $resetLog[] = "Table not found (skipped): $table";
                }
            }

            // 4. Clean Storage
            // Deletes the 'pokemon_cards' directory in public disk
            if (Storage::disk('public')->exists('pokemon_cards')) {
                Storage::disk('public')->deleteDirectory('pokemon_cards');
                $resetLog[] = "Deleted storage directory: pokemon_cards";
            }

            // 5. Re-enable Foreign Key Constraints
            Schema::enableForeignKeyConstraints();
            Log::info('Foreign key constraints enabled.');

            return response()->json([
                'success' => true,
                'message' => 'Database tables reset successfully.',
                'details' => $resetLog
            ]);
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints(); // Ensure checks are re-enabled
            Log::error('An error occurred during reset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during reset: ' . $e->getMessage()
            ], 500);
        }
    }
}
