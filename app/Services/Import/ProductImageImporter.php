<?php

namespace App\Services\Import;

use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\ProductMedia;

use App\Services\Platform\ImageUploadService;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ProductImageImporter
{
    private const VALID_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const VALID_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Match "slug-1.jpg" style names. Captures slug, sequence, extension. */
    private const FILENAME_REGEX = '/^(.+)-(\d+)\.(jpg|jpeg|png|webp)$/i';

    public function __construct(private readonly ImageUploadService $imageService) {}

    /**
     * Extract the uploaded ZIP into a fresh temp directory and return the
     * list of valid image filenames (sorted deterministically) plus the temp path.
     *
     * @return array{valid: bool, message: string, temp_path?: string, files?: array<int,string>, total?: int}
     */
    public function extractZip(string $zipAbsolutePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            return ['valid' => false, 'message' => 'ZipArchive PHP extension is not available on this server.'];
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipAbsolutePath);
        if ($opened !== true) {
            return ['valid' => false, 'message' => 'Could not open ZIP file (error code '.$opened.').'];
        }

        $tempDir = storage_path('app/imports/product-images/tmp-'.uniqid('', true));
        if (! @mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            $zip->close();

            return ['valid' => false, 'message' => 'Could not create temporary extraction directory.'];
        }

        $files = [];

        // Stream each entry individually → avoid holding whole archive in memory.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $entryName = $stat['name'];

            // Reject absolute paths, parent-traversal, and anything weird — zip-slip guard.
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || preg_match('#(^|/)\.\.?($|/)#', $entryName)) {
                continue;
            }

            // Ignore directory entries and macOS resource-fork noise.
            if (str_ends_with($entryName, '/') || str_starts_with(basename($entryName), '.') || str_contains($entryName, '__MACOSX/')) {
                continue;
            }

            $basename = basename($entryName);
            $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
            if (! in_array($ext, self::VALID_EXTENSIONS, true)) {
                continue;
            }

            // Extract to a flat filename under temp dir (collisions disambiguated by index).
            $targetName = $i.'__'.$basename;
            $targetPath = $tempDir.DIRECTORY_SEPARATOR.$targetName;

            $stream = $zip->getStream($entryName);
            if (! $stream) {
                continue;
            }

            $out = fopen($targetPath, 'w');
            if (! $out) {
                fclose($stream);

                continue;
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);

            // Double-check MIME of the extracted file — ZIP entry extension is not trust-enough.
            $mime = @mime_content_type($targetPath);
            if (! in_array($mime, self::VALID_MIMES, true)) {
                @unlink($targetPath);

                continue;
            }

            $files[] = $targetName;
        }

        $zip->close();

        if (empty($files)) {
            $this->cleanup($tempDir);

            return ['valid' => false, 'message' => 'ZIP contains no valid image files (jpg, jpeg, png, webp).'];
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'valid' => true,
            'message' => 'ZIP extracted successfully.',
            'temp_path' => $tempDir,
            'files' => $files,
            'total' => count($files),
        ];
    }

    /**
     * Process a batch of extracted files. Safe to call repeatedly across chunks.
     *
     * @param  array<int, string>  $files  Filenames (not full paths) inside $tempDir.
     * @return array{success:int, failed:int, skipped:int, created:int, updated:int}
     */
    public function processChunk(Import $import, string $tempDir, array $files, int $companyId): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;
        $created = 0;
        $updated = 0;

        $importMode = $import->import_mode ?? 'create_or_update';

        // ── Parse all filenames first → collect slugs for a single whereIn lookup ──
        //
        // Ambiguity: a file like "aloe-vera-plant-1.jpg" is parsed by the regex as
        //   slug="aloe-vera-plant", sequence=1
        // But if the DB slug is actually "aloe-vera-plant-1" (slug ending in a number),
        // the regex strips the trailing digit as the sequence number and the lookup fails.
        //
        // Fix: collect both slug candidates per file:
        //   primary   → regex result ("aloe-vera-plant")
        //   secondary → primary + "-" + sequence ("aloe-vera-plant-1")
        // Both are queried in one whereIn. Per-file resolution prefers secondary
        // (longer / more-specific match) over primary.
        $parsed = []; // [filename => ['slug' => s, 'slug_alt' => s2, 'sequence' => n, 'ext' => e]]
        $slugs = [];
        foreach ($files as $filename) {
            $original = $this->originalName($filename);
            if (! preg_match(self::FILENAME_REGEX, $original, $m)) {
                $parsed[$filename] = ['error' => "Invalid filename format. Expected 'slug-N.ext'.", 'original' => $original];

                continue;
            }

            $slug = strtolower(trim($m[1]));
            $sequence = (int) $m[2];
            $ext = strtolower($m[3]);
            // Alternative candidate: slug that includes the trailing number.
            // Resolves ambiguity when the DB slug itself ends with "-<digit(s)>".
            $slugAlt = $slug.'-'.$sequence;

            $parsed[$filename] = [
                'slug' => $slug,
                'slug_alt' => $slugAlt,
                'sequence' => $sequence,
                'ext' => $ext,
                'original' => $original,
            ];
            $slugs[$slug] = true;
            $slugs[$slugAlt] = true;
        }

        // Single query for all slug candidates in this batch (primary + alternative).
        $products = ! empty($slugs)
            ? Product::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('slug', array_keys($slugs))
                ->get()
                ->keyBy('slug')
            : collect();

        // ── Update-mode safety: delete existing media once per product per import ──
        // Persisted in duplicate_meta.deleted_product_ids so it survives across chunks.
        $meta = $import->duplicate_meta ?? [];
        $deletedForProducts = array_flip($meta['deleted_product_ids'] ?? []);

        foreach ($files as $index => $filename) {
            $fileRowNumber = $this->fileRowNumber($import, $filename);
            $info = $parsed[$filename];
            $absolutePath = $tempDir.DIRECTORY_SEPARATOR.$filename;

            try {
                // ── Bad filename format ──
                if (isset($info['error'])) {
                    $this->log($import, $fileRowNumber, $info['original'], $info['error']);
                    $failed++;

                    continue;
                }

                // ── Sanity-check extracted file still exists ──
                if (! is_file($absolutePath)) {
                    $this->log($import, $fileRowNumber, $info['original'], 'Extracted file missing from temp directory.');
                    $failed++;

                    continue;
                }

                // ── MIME revalidation (defence-in-depth; zip-extract re-checked too) ──
                $mime = @mime_content_type($absolutePath);
                if (! in_array($mime, self::VALID_MIMES, true)) {
                    $this->log($import, $fileRowNumber, $info['original'], "Invalid MIME type '{$mime}'. Allowed: jpeg, png, webp.");
                    $failed++;

                    continue;
                }

                // ── Product lookup (two-candidate resolution) ──
                // Prefer the alternative slug (e.g. "aloe-vera-plant-1") over the
                // stripped slug ("aloe-vera-plant") so products whose DB slug ends
                // with a digit match correctly without requiring doubled suffixes.
                $product = $products->get($info['slug_alt']) ?? $products->get($info['slug']);
                if (! $product) {
                    $this->log(
                        $import,
                        $fileRowNumber,
                        $info['original'],
                        "Product not found. Tried slugs: '{$info['slug_alt']}' and '{$info['slug']}'."
                    );
                    $skipped++;

                    continue;
                }

                // ── Import-mode gating ──
                if ($importMode === 'create_only') {
                    $hasMedia = ProductMedia::where('company_id', $companyId)
                        ->where('product_id', $product->id)
                        ->exists();
                    if ($hasMedia) {
                        $this->log($import, $fileRowNumber, $info['original'], 'create_only mode: product already has images.');
                        $skipped++;

                        continue;
                    }
                }

                if ($importMode === 'update_only' && ! isset($deletedForProducts[$product->id])) {
                    $this->deleteExistingMedia($companyId, $product->id);
                    $deletedForProducts[$product->id] = true;
                }

                // ── Upload + store ──
                $uploaded = new UploadedFile(
                    $absolutePath,
                    $info['original'],
                    $mime,
                    null,
                    true // test mode — skip is_uploaded_file() check
                );

                $storedPath = $this->imageService->upload(
                    $uploaded,
                    "products/{$companyId}",
                    ['format' => 'webp', 'disk' => 'public']
                );

                // ── Primary flag logic (no post-batch correction) ──
                $isPrimary = false;
                if ($info['sequence'] === 1) {
                    $isPrimary = true;
                    // Demote any other primary for this product so there's only one.
                    ProductMedia::where('company_id', $companyId)
                        ->where('product_id', $product->id)
                        ->where('is_primary', true)
                        ->update(['is_primary' => false]);
                } else {
                    $hasPrimary = ProductMedia::where('company_id', $companyId)
                        ->where('product_id', $product->id)
                        ->where('is_primary', true)
                        ->exists();
                    if (! $hasPrimary) {
                        $isPrimary = true;
                    }
                }

                DB::transaction(function () use ($companyId, $product, $storedPath, $info, $isPrimary) {
                    ProductMedia::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'media_type' => 'image',
                        'media_path' => $storedPath,
                        'is_primary' => $isPrimary,
                        'sort_order' => $info['sequence'],
                    ]);
                });

                $success++;
                if ($importMode === 'update_only') {
                    $updated++;
                } else {
                    $created++;
                }
            } catch (\Throwable $e) {
                $this->log($import, $fileRowNumber, $info['original'] ?? $filename, 'Unexpected error: '.$e->getMessage());
                $failed++;
            }
        }

        // Persist the deleted-product set so subsequent chunks don't wipe again.
        if ($importMode === 'update_only') {
            $meta['deleted_product_ids'] = array_keys($deletedForProducts);
            $import->update(['duplicate_meta' => $meta]);
        }

        return compact('success', 'failed', 'skipped', 'created', 'updated');
    }

    /**
     * Recursively delete a temp directory. Never throws; logs via ImportLog if supplied.
     */
    public function cleanup(?string $tempDir): void
    {
        if (! $tempDir || ! is_dir($tempDir)) {
            return;
        }

        try {
            $items = @scandir($tempDir) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $tempDir.DIRECTORY_SEPARATOR.$item;
                if (is_dir($path)) {
                    $this->cleanup($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($tempDir);
        } catch (\Throwable $e) {
            // Best-effort: leave for cron/manual cleanup. Do not throw.
            Log::warning('[ProductImageImporter] Temp cleanup failed', [
                'path' => $tempDir,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete all media rows (and their files) for a product.
     */
    private function deleteExistingMedia(int $companyId, int $productId): void
    {
        $existing = ProductMedia::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->get();

        foreach ($existing as $media) {
            $this->imageService->delete($media->media_path, 'public');
            $media->delete();
        }
    }

    /**
     * Strip the "{index}__" disambiguation prefix applied during extraction.
     */
    private function originalName(string $storedFilename): string
    {
        $pos = strpos($storedFilename, '__');

        return $pos === false ? $storedFilename : substr($storedFilename, $pos + 2);
    }

    /**
     * Stable pseudo-row-number for an import file — used for ImportLog.row_number
     * so the error-report download feature works the same as CSV imports.
     */
    private function fileRowNumber(Import $import, string $filename): int
    {
        // Deterministic hash-based ordering isn't useful; caller supplies offset in $filename order.
        // We store the 1-based index within the import via a simple crc; good enough for display.
        return abs(crc32($filename)) % 1_000_000_000;
    }

    private function log(Import $import, int $rowNumber, string $filename, string $message): void
    {
        ImportLog::create([
            'import_id' => $import->id,
            'row_number' => $rowNumber,
            'row_data' => ['filename' => $filename],
            'error_message' => $message,
        ]);
    }

    /**
     * List valid image filenames inside an already-extracted temp dir, sorted naturally.
     * Used by the controller to slice chunks without re-extracting.
     *
     * @return array<int, string>
     */
    public function listFiles(string $tempDir): array
    {
        if (! is_dir($tempDir)) {
            throw new RuntimeException("Temp directory does not exist: {$tempDir}");
        }

        $entries = @scandir($tempDir) ?: [];
        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (! in_array($ext, self::VALID_EXTENSIONS, true)) {
                continue;
            }
            $files[] = $entry;
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    /**
     * Cleanup for a failed/aborted upload (also deletes the original ZIP).
     */
    public function cleanupForImport(Import $import): void
    {
        $this->cleanup($import->temp_path);
        if ($import->file_path && Storage::disk('local')->exists($import->file_path)) {
            Storage::disk('local')->delete($import->file_path);
        }
    }
}
