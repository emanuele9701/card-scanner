<?php

namespace App\Http\Controllers;

use App\Services\MarketDataImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketDataController extends Controller
{
    public function __construct(
        private MarketDataImportService $importService
    ) {
    }

    /**
     * Show the market data management page
     */
    public function index(): Response
    {
        $stats = $this->importService->getStats();

        return Inertia::render('MarketData/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Import market data from uploaded JSON file or raw JSON string
     */
    public function import(Request $request)
    {
        // Validate: either file or raw_json must be provided
        $request->validate([
            'json_file' => 'nullable|file|mimetypes:application/json,text/plain|max:10240', // Max 10MB
            'raw_json' => 'nullable|string|max:10485760', // Max 10MB as string
        ]);

        // Ensure at least one input is provided
        if (!$request->hasFile('json_file') && !$request->filled('raw_json')) {
            return back()->withErrors([
                'import' => 'Please provide either a JSON file or raw JSON data.'
            ]);
        }

        $jsonContent = null;

        // Get JSON content from file or raw input
        if ($request->hasFile('json_file')) {
            $file = $request->file('json_file');
            $jsonContent = $file->get();
        } else {
            $jsonContent = $request->input('raw_json');
        }

        // Validate JSON syntax
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors([
                'import' => 'Invalid JSON: ' . json_last_error_msg()
            ]);
        }

        // Validate JSON structure
        if (!isset($jsonData['result']) || !is_array($jsonData['result'])) {
            return back()->withErrors([
                'import' => 'Invalid JSON structure. Expected "result" array.'
            ]);
        }

        // Validate result array is not empty
        if (empty($jsonData['result'])) {
            return back()->withErrors([
                'import' => 'The "result" array is empty. No data to import.'
            ]);
        }

        try {
            $stats = $this->importService->importFromJson($jsonData['result']);

            return back()->with([
                'success' => 'Market data imported successfully!',
                'import_stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'import' => 'Import failed: ' . $e->getMessage()
            ]);
        }
    }
}
