<?php

/**
 * OCR Configuration
 * -----------------
 * OCR.space has been removed. All OCR now uses Gemini Vision.
 *
 * The Gemini API key and base URL are shared with the main AI service
 * via config/services.php (services.gemini.*).
 *
 * This file retains OCR-specific settings only:
 *   - Max upload size enforced before sending to Gemini
 *   - Storage path config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Max Upload Size (bytes)
    |--------------------------------------------------------------------------
    | Uploads larger than this are rejected before hitting Gemini.
    | Gemini Vision supports up to 20 MB inline, but 4 MB is safe on
    | shared hosting (Hostinger) to avoid memory exhaustion.
    |
    | Default: 4 MB = 4_000_000 bytes
    */
    'max_image_bytes' => env('OCR_MAX_IMAGE_BYTES', 4_000_000),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    | Disk to store scanned images. Must be configured in config/filesystems.php
    */
    'storage_disk' => env('OCR_STORAGE_DISK', 'public'),

];