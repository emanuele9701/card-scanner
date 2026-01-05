<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImageResizeService
{
    protected $manager;

    public function __construct()
    {
        // Initialize ImageManager with GD driver (Intervention/Image v3)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Helper function to log memory usage
     */
    private function logMemoryUsage(string $step, array $extraData = []): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        Log::info("MEMORY TRACKING [RESIZE] - {$step}", array_merge([
            'memory_current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_limit' => $memoryLimit,
            'memory_current_bytes' => $memoryUsage,
            'memory_peak_bytes' => $memoryPeak,
        ], $extraData));
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
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('memory_limit', '1G');

        Log::info("Memory php impostata: " . ini_get('memory_limit'));

        $this->logMemoryUsage('START - Resize check');

        Log::info('ImageResizeService: Starting resize check', [
            'path' => $path,
            'disk' => $disk
        ]);

        // Check if resize is enabled
        if (!config('images.enabled', true)) {
            Log::info('ImageResizeService: Resize disabled in configuration');
            return false;
        }

        try {
            // Get full path to the image
            $fullPath = Storage::disk($disk)->path($path);

            Log::info('ImageResizeService: Full path resolved', [
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath)
            ]);

            $this->logMemoryUsage('AFTER - Path resolved');

            if (!file_exists($fullPath)) {
                Log::warning("ImageResizeService: Image file not found for resize", [
                    'full_path' => $fullPath
                ]);
                return false;
            }

            $originalFileSize = filesize($fullPath);
            $this->logMemoryUsage('BEFORE - Loading image', [
                'file_size_mb' => round($originalFileSize / 1024 / 1024, 2)
            ]);

            try {
                // Load image using v3 syntax
                $image = $this->manager->read($fullPath);

                $this->logMemoryUsage('AFTER - Image loaded into memory');

                $width = $image->width();
                $height = $image->height();

                $maxWidth = config('images.max_width', 1920);
                $maxHeight = config('images.max_height', 1080);
            } catch (RuntimeException $ex) {
                Log::error('ImageResizeService: Error loading image', [
                    'full_path' => $fullPath,
                    'error_message' => $ex->getMessage(),
                    'error_line' => $ex->getLine(),
                    'error_file' => $ex->getFile()
                ]);
                $this->logMemoryUsage('ERROR - Loading image failed');
                return false;
            }


            Log::info('ImageResizeService: Image loaded', [
                'original_width' => $width,
                'original_height' => $height,
                'max_width' => $maxWidth,
                'max_height' => $maxHeight,
                'file_size_kb' => round($originalFileSize / 1024, 2)
            ]);

            // Check if resize is needed
            if ($width <= $maxWidth && $height <= $maxHeight) {
                Log::info('ImageResizeService: No resize needed - image within limits');
                $this->logMemoryUsage('END - No resize needed');
                // Free memory before returning
                unset($image);
                gc_collect_cycles();
                return false; // No resize needed
            }

            Log::info('ImageResizeService: Resize required - calculating new dimensions');

            $this->logMemoryUsage('BEFORE - Resize operation');

            // Calculate new dimensions maintaining aspect ratio
            if (config('images.maintain_aspect_ratio', true)) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int) ($width * $ratio);
                $newHeight = (int) ($height * $ratio);

                Log::info('ImageResizeService: Maintaining aspect ratio', [
                    'ratio' => round($ratio, 4),
                    'new_width' => $newWidth,
                    'new_height' => $newHeight
                ]);

                // Scale down the image
                $image->scale($newWidth, $newHeight);

                $this->logMemoryUsage('AFTER - Image scaled');
            } else {
                Log::info('ImageResizeService: Using cover mode (crop)');
                // Cover (crop to exact dimensions)
                $image->cover($maxWidth, $maxHeight);

                $this->logMemoryUsage('AFTER - Image covered');
            }

            // Save with configured quality
            $quality = config('images.quality', 85);

            $this->logMemoryUsage('BEFORE - Saving image');

            $image->save($fullPath, $quality);

            $this->logMemoryUsage('AFTER - Image saved');

            $newFileSize = filesize($fullPath);

            Log::info("ImageResizeService: Image resized successfully", [
                'path' => $path,
                'original_size' => "{$width}x{$height}",
                'new_size' => "{$image->width()}x{$image->height()}",
                'quality' => $quality,
                'original_file_size_kb' => round($originalFileSize / 1024, 2),
                'new_file_size_kb' => round($newFileSize / 1024, 2),
                'size_reduction_percent' => round((($originalFileSize - $newFileSize) / $originalFileSize) * 100, 2)
            ]);

            // Free memory explicitly
            unset($image);
            gc_collect_cycles();

            $this->logMemoryUsage('END - After cleanup');

            return true;
        } catch (\Exception $e) {
            $this->logMemoryUsage('ERROR - Exception in resize');

            Log::error("ImageResizeService: Error resizing image", [
                'path' => $path,
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
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
