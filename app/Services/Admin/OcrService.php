<?php

namespace App\Services\Admin;

use App\Services\Admin\DocumentAiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OcrService
 * ----------
 * Public-facing OCR facade used by OcrScannerController.
 *
 * ── Architecture ──────────────────────────────────────────────────────
 *
 *   OcrScannerController
 *        ↓
 *   OcrService              ← YOU ARE HERE (public API, unchanged interface)
 *        ↓
 *   DocumentAiService       ← Business prompt layer
 *        ↓
 *   GeminiVisionService     ← Raw Gemini Vision HTTP client
 *        ↓
 *   Gemini Vision API       ← Google AI (replaces OCR.space entirely)
 *
 * ── Migration notes ───────────────────────────────────────────────────
 *   OCR.space has been REMOVED. All OCR now goes through Gemini Vision.
 *   Public interface is IDENTICAL — controllers need zero changes.
 *
 * Usage:
 *   $result = $this->ocrService->scan($request->file('image'), 'invoice');
 *   // $result['success']        — bool
 *   // $result['raw_text']       — Gemini Vision raw response string
 *   // $result['extracted_data'] — structured JSON fields array
 *   // $result['error']          — string|null
 */
class OcrService
{


    public function __construct(
        private readonly DocumentAiService $documentAi
    ) {}

    // ═════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Main OCR entry point — identical interface to the old OCR.space version.
     *
     * @param  UploadedFile  $file
     * @param  string  $scanType   business_card | invoice | receipt | expense | gst_bill | general
     * @return array{success:bool, raw_text:string|null, extracted_data:array, error:string|null}
     */
    public function scan(UploadedFile $file, string $scanType = 'business_card'): array
    {
        try {
            // 1. Server-side file size guard. config/ocr.php is the single
            // source of truth — the controller's max: rule reads the same key.
            // This used to be a hardcoded 4 MB const behind a max:1024 rule,
            // so it was unreachable and the config value was never read.
            $maxBytes = (int) config('ocr.max_image_bytes', 4_000_000);

            if ($file->getSize() > $maxBytes) {
                $mb = round($maxBytes / 1_000_000, 1);
                return $this->error("Image too large (max {$mb} MB). Please use the in-browser compressor before uploading.");
            }

            // 2. Delegate to DocumentAiService → GeminiVisionService
            $result = $this->documentAi->parse($file, $scanType);

            // Attach engine identifier so callers never hardcode it
            $result['ocr_engine'] = 'GeminiVision';

            return $result;

        } catch (Throwable $e) {
            Log::error('OcrService::scan failed', [
                'error'     => $e->getMessage(),
                'scan_type' => $scanType,
            ]);

            // The raw exception text goes to the log only. It was previously
            // returned to the browser, where a misconfigured key or a Gemini
            // endpoint path would be shown verbatim to the user.
            return $this->error('Could not read this document. Please try a clearer image.');
        }
    }

    /**
     * Store an uploaded OCR image to disk.
     * Returns the relative storage path (e.g. ocr/1/2025/06/xyz.jpg).
     * Unchanged from original — no OCR.space dependency here.
     */
    public function storeImage(UploadedFile $file, int $companyId): string
    {
        $folder = 'ocr/' . $companyId . '/' . now()->format('Y/m');

        // Private disk, not public. These are invoices, GST bills and business
        // cards — on the public disk every one of them was reachable by URL
        // with no authentication and no tenant check at all.
        return $file->store($folder, 'local');
    }

    // ═════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═════════════════════════════════════════════════════════════════════

    private function error(string $message): array
    {
        return [
            'success'        => false,
            'raw_text'       => null,
            'extracted_data' => [],
            'ocr_engine'     => 'GeminiVision',
            'error'          => $message,
        ];
    }
}