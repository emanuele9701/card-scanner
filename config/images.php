<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Resize Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the automatic image resizing behavior.
    | When enabled, uploaded images will be automatically resized if they
    | exceed the maximum dimensions specified below.
    |
    */

    // Enable or disable automatic image resizing
    'enabled' => env('IMAGE_RESIZE_ENABLED', true),

    // Maximum width in pixels
    'max_width' => env('IMAGE_RESIZE_MAX_WIDTH', 1920),

    // Maximum height in pixels
    'max_height' => env('IMAGE_RESIZE_MAX_HEIGHT', 1080),

    // Image quality (1-100, higher is better quality but larger file size)
    'quality' => env('IMAGE_RESIZE_QUALITY', 85),

    // Maintain aspect ratio when resizing
    'maintain_aspect_ratio' => env('IMAGE_RESIZE_MAINTAIN_RATIO', true),

];
