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
     * Import market data from uploaded JSON file
     */
    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimetypes:application/json,text/plain|max:10240', // Max 10MB
        ]);

        $file = $request->file('json_file');
        $jsonContent = $file->get();
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors([
                'json_file' => 'Invalid JSON file: ' . json_last_error_msg()
            ]);
        }

        if (!isset($jsonData['result']) || !is_array($jsonData['result'])) {
            return back()->withErrors([
                'json_file' => 'Invalid JSON structure. Expected "result" array.'
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
