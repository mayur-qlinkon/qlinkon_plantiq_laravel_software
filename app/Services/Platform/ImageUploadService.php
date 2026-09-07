<?php

namespace App\Services\Platform;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

class ImageUploadService
{
    protected ImageManager $manager;

    // ── Allowed MIME types ──
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
    ];

    // ── Max upload size: 10MB ──
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    // ════════════════════════════════════════════════════
    //  UPLOAD  — main public method
    // ════════════════════════════════════════════════════
    /**
     * Upload, resize, format, compress and store an image.
     *
     * Options:
     *   disk      string   Storage disk (default: 'public')
     *   old_file  string   Path to old file — deleted AFTER successful upload
     *   width     int      Max width in pixels (proportional scale)
     *   height    int      Max height in pixels (proportional scale)
     *   crop      bool     true = cover crop to exact width×height
     *   format    string   Output format: webp|jpg|jpeg|png|gif (default: webp)
     *   quality   int      Compression quality 1–100 (default: 80)
     *
     * @throws InvalidArgumentException on invalid file type or size
     * @throws Throwable on processing/storage failure
     */
    public function upload(UploadedFile $file, string $path, array $options = []): string
    {
        // ── 1. Extract options ──
        $disk = $options['disk'] ?? 'public';
        $oldFile = $options['old_file'] ?? null;
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $crop = $options['crop'] ?? false;
        $quality = (int) ($options['quality'] ?? 80);
        $format = strtolower($options['format'] ?? 'webp');

        // ── 2. Validate ──
        $this->validate($file);

        // ── 3. SVG shortcut — Intervention cannot process SVG ──
        if ($file->getMimeType() === 'image/svg+xml') {
            return $this->storeSvg($file, $path, $disk, $oldFile);
        }

        // ── 4. Process and store raster image ──
        try {
            $filename = $this->generateFilename($format);
            $fullPath = trim($path, '/').'/'.$filename;

            // Read into memory
            $image = $this->manager->read($file);

            // Resize / crop
            if ($width || $height) {
                if ($crop && $width && $height) {
                    // Exact crop — fills the box, no whitespace
                    $image->cover($width, $height);
                } else {
                    // Proportional scale — no distortion
                    $image->scale(width: $width, height: $height);
                }
            }

            // Encode
            $encoded = $this->encode($image, $format, $quality);

            // Store
            Storage::disk($disk)->put($fullPath, (string) $encoded);

            // Delete old file ONLY after successful save
            $this->deleteOld($oldFile, $disk);

            Log::info('[ImageUpload] Success', [
                'path' => $fullPath,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
                'original_name' => $file->getClientOriginalName(),
                'size_kb' => round($file->getSize() / 1024, 1),
            ]);

            return $fullPath;

        } catch (Throwable $e) {
            Log::error('[ImageUpload] Processing failed', [
                'path' => $path,
                'format' => $format,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size_kb' => round($file->getSize() / 1024, 1),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw so BannerService / SettingController transactions can rollback
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  DELETE — safe single file delete
    // ════════════════════════════════════════════════════
    /**
     * Delete a file from storage. Never throws.
     */
    public function delete(?string $path, string $disk = 'public'): void
    {
        if (! $path) {
            return;
        }

        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);

                Log::info('[ImageUpload] File deleted', [
                    'path' => $path,
                    'disk' => $disk,
                ]);
            }
        } catch (Throwable $e) {
            // Never crash the calling code over a file delete failure
            Log::warning('[ImageUpload] Delete failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════
    //  DELETE MULTIPLE — bulk cleanup
    // ════════════════════════════════════════════════════
    /**
     * Delete multiple files in one call.
     * Useful when a model has image + mobile_image + thumbnail etc.
     */
    public function deleteMany(array $paths, string $disk = 'public'): void
    {
        foreach ($paths as $path) {
            $this->delete($path, $disk);
        }
    }

    // ════════════════════════════════════════════════════
    //  EXISTS
    // ════════════════════════════════════════════════════
    public function exists(?string $path, string $disk = 'public'): bool
    {
        if (! $path) {
            return false;
        }

        try {
            return Storage::disk($disk)->exists($path);
        } catch (Throwable $e) {
            Log::warning('[ImageUpload] Exists check failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — VALIDATE
    // ════════════════════════════════════════════════════
    /**
     * Validate MIME type and file size.
     *
     * @throws InvalidArgumentException
     */
    private function validate(UploadedFile $file): void
    {
        $mime = $file->getMimeType();
        $size = $file->getSize();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException(
                "[ImageUpload] Invalid MIME type [{$mime}]. ".
                'Allowed: '.implode(', ', self::ALLOWED_MIMES)
            );
        }

        if ($size > self::MAX_SIZE_BYTES) {
            $sizeMb = round($size / 1024 / 1024, 2);
            $maxMb = self::MAX_SIZE_BYTES / 1024 / 1024;
            throw new InvalidArgumentException(
                "[ImageUpload] File too large [{$sizeMb}MB]. Maximum allowed: {$maxMb}MB."
            );
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — SVG STORE
    // ════════════════════════════════════════════════════
    /**
     * Store SVG directly — Intervention Image cannot process vector files.
     */
    private function storeSvg(
        UploadedFile $file,
        string $path,
        string $disk,
        ?string $oldFile
    ): string {
        try {
            $filename = $this->generateFilename('svg');
            $fullPath = trim($path, '/').'/'.$filename;

            Storage::disk($disk)->put($fullPath, $file->getContent());

            $this->deleteOld($oldFile, $disk);

            Log::info('[ImageUpload] SVG stored directly', [
                'path' => $fullPath,
                'original_name' => $file->getClientOriginalName(),
            ]);

            return $fullPath;

        } catch (Throwable $e) {
            Log::error('[ImageUpload] SVG store failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — ENCODE
    // ════════════════════════════════════════════════════
    /**
     * Encode the processed image to the desired format.
     */
    private function encode(mixed $image, string $format, int $quality): mixed
    {
        return match ($format) {
            'jpg', 'jpeg' => $image->toJpeg($quality),
            'png' => $image->toPng(),   // lossless — quality param ignored
            'gif' => $image->toGif(),
            default => $image->toWebp($quality), // webp default
        };
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — GENERATE FILENAME
    // ════════════════════════════════════════════════════
    /**
     * Generate a unique, collision-proof filename.
     */
    private function generateFilename(string $extension): string
    {
        return uniqid('img_', true).'_'.time().'.'.$extension;
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — DELETE OLD (after successful upload)
    // ════════════════════════════════════════════════════
    /**
     * Delete old file only after new upload has succeeded.
     * Keeps old file intact if upload fails.
     */
    private function deleteOld(?string $oldFile, string $disk): void
    {
        if (! $oldFile) {
            return;
        }
        $this->delete($oldFile, $disk);
    }
}
