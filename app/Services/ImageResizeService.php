<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageResizeService
{
    protected $manager;

    public function __construct()
    {
        // Initialize ImageManager with GD driver (Intervention/Image v3)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Resize an image if it exceeds configured maximum dimensions
     *
     * @param string $path Path to the image file in storage
     * @param string $disk Storage disk (default: 'public')
     * @return bool Whether the image was resized
     */
    public function resizeIfNeeded(string $path, string $disk = 'public'): bool
    {
        // Check if resize is enabled
        if (!config('images.enabled', true)) {
            return false;
        }

        try {
            // Get full path to the image
            $fullPath = Storage::disk($disk)->path($path);

            if (!file_exists($fullPath)) {
                Log::warning("Image file not found for resize: {$fullPath}");
                return false;
            }

            // Load image using v3 syntax
            $image = $this->manager->read($fullPath);
            $width = $image->width();
            $height = $image->height();

            $maxWidth = config('images.max_width', 1920);
            $maxHeight = config('images.max_height', 1080);

            // Check if resize is needed
            if ($width <= $maxWidth && $height <= $maxHeight) {
                return false; // No resize needed
            }

            // Calculate new dimensions maintaining aspect ratio
            if (config('images.maintain_aspect_ratio', true)) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int) ($width * $ratio);
                $newHeight = (int) ($height * $ratio);

                // Scale down the image
                $image->scale($newWidth, $newHeight);
            } else {
                // Cover (crop to exact dimensions)
                $image->cover($maxWidth, $maxHeight);
            }

            // Save with configured quality
            $quality = config('images.quality', 85);
            $image->save($fullPath, $quality);

            Log::info("Image resized successfully", [
                'path' => $path,
                'original_size' => "{$width}x{$height}",
                'new_size' => "{$image->width()}x{$image->height()}",
                'quality' => $quality
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Error resizing image: " . $e->getMessage(), [
                'path' => $path,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Get current configuration as array
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'enabled' => config('images.enabled', true),
            'max_width' => config('images.max_width', 1920),
            'max_height' => config('images.max_height', 1080),
            'quality' => config('images.quality', 85),
            'maintain_aspect_ratio' => config('images.maintain_aspect_ratio', true),
        ];
    }
}
