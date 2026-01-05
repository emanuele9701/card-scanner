<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    /**
     * Serve a card image via stream
     * This allows for authorization checks and doesn't rely on symbolic links
     *
     * @param PokemonCard $card
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function showCardImage(PokemonCard $card)
    {
        \Log::info('ImageController: showCardImage called', [
            'card_id' => $card->id,
            'card_user_id' => $card->user_id,
            'auth_user_id' => auth()->id(),
            'storage_path' => $card->storage_path
        ]);

        if ($card->user_id !== auth()->id()) {
            \Log::warning('ImageController: Unauthorized access attempt', [
                'card_id' => $card->id,
                'card_user_id' => $card->user_id,
                'auth_user_id' => auth()->id()
            ]);
            abort(403, 'Non autorizzato');
        }

        // Check if the file exists
        if (!$card->storage_path || !Storage::disk('public')->exists($card->storage_path)) {
            \Log::error('ImageController: Image not found', [
                'card_id' => $card->id,
                'storage_path' => $card->storage_path
            ]);
            abort(404, 'Immagine non trovata');
        }

        // Get the file path
        $filePath = Storage::disk('public')->path($card->storage_path);

        // Detect MIME type
        $mimeType = $this->getMimeType($filePath);

        // Get file size
        $fileSize = Storage::disk('public')->size($card->storage_path);

        \Log::info('ImageController: Serving image', [
            'card_id' => $card->id,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize
        ]);

        // Stream the file
        return response()->stream(
            function () use ($filePath) {
                $stream = fopen($filePath, 'rb');
                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Cache-Control' => 'public, max-age=31536000', // 1 year cache
                'Content-Disposition' => 'inline',
            ]
        );
    }

    /**
     * Serve any image from storage by path (for authenticated users)
     * This is a more generic endpoint for future use
     *
     * @param Request $request
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function showImage(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            abort(400, 'Path richiesto');
        }

        // Security: prevent directory traversal
        $path = str_replace(['../', '..\\'], '', $path);

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Immagine non trovata');
        }

        $filePath = Storage::disk('public')->path($path);
        $mimeType = $this->getMimeType($filePath);
        $fileSize = Storage::disk('public')->size($path);

        return response()->stream(
            function () use ($filePath) {
                $stream = fopen($filePath, 'rb');
                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Cache-Control' => 'public, max-age=31536000',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    /**
     * Get MIME type of a file
     *
     * @param string $filePath
     * @return string
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
