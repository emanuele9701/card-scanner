<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Services\GoogleDriveService;
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
            'has_drive_file' => $card->driveFile !== null,
            'storage_path' => $card->storage_path
        ]);

        // Authorization check
        if ($card->user_id !== auth()->id()) {
            \Log::warning('ImageController: Unauthorized access attempt', [
                'card_id' => $card->id,
                'card_user_id' => $card->user_id,
                'auth_user_id' => auth()->id()
            ]);
            abort(403, 'Non autorizzato');
        }

        // Google Drive Path - Most efficient is to redirect
        if ($card->driveFile && $card->driveFile->isUploaded()) {
            $driveService = app(GoogleDriveService::class);
            return $driveService->streamFile($card->driveFile->drive_id);
        }

        // Local Storage Path
        if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
            return $this->serveLocalImage($card);
        }

        // No image found
        \Log::error('ImageController: No image found', [
            'card_id' => $card->id,
            'has_drive_file' => $card->driveFile !== null,
            'storage_path' => $card->storage_path
        ]);
        abort(404, 'Immagine non trovata');
    }

    /**
     * Serve image from Google Drive (redirect to direct link)
     * This is more efficient than downloading and re-serving
     */
    private function serveGoogleDriveImage(PokemonCard $card)
    {
        $driveFile = $card->driveFile;

        \Log::info('ImageController: Serving from Google Drive', [
            'card_id' => $card->id,
            'drive_id' => $driveFile->drive_id,
            'is_public' => $driveFile->is_public
        ]);

        // Use web_content_link if available, otherwise construct public URL
        $imageUrl = $driveFile->web_content_link
            ?? "https://drive.google.com/uc?export=view&id={$driveFile->drive_id}";

        // Redirect to Google Drive direct link
        // This is much more efficient than downloading and re-serving
        return redirect($imageUrl);
    }

    /**
     * Serve image from local storage (stream)
     */
    private function serveLocalImage(PokemonCard $card)
    {
        $filePath = Storage::disk('public')->path($card->storage_path);
        $mimeType = $this->getMimeType($filePath);
        $fileSize = Storage::disk('public')->size($card->storage_path);

        \Log::info('ImageController: Serving from local storage', [
            'card_id' => $card->id,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize
        ]);

        // Stream the file for efficiency
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
