<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketDataImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketDataApiController extends Controller
{
    protected $importService;

    public function __construct(MarketDataImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import market data from JSON
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        // Validate input
        $request->validate([
            'result' => 'required|array|min:1',
        ]);

        try {
            $stats = $this->importService->importFromJson($request->input('result'));

            return response()->json([
                'success' => true,
                'message' => 'Market data imported successfully',
                'stats' => $stats
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
