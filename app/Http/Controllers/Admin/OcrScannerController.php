<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\OcrScan;
use App\Notifications\AppNotification;

use App\Services\Admin\OcrService;
use App\Services\NotificationDispatcher;
use App\Mail\DynamicMail;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * OcrScannerController
 * --------------------
 * Handles the standalone OCR Scanner module.
 *
 * Routes (all gated by module:ocr_scanner middleware):
 *   GET  /ocr-scanner              → scan page (camera + upload UI)
 *   POST /ocr-scanner/process      → receive image, run OCR, return JSON
 *   POST /ocr-scanner/save         → save confirmed edited data to ocr_scans
 *   GET  /ocr-scanner/history      → history / previous scans list
 *   GET  /ocr-scanner/{scan}       → view single scan detail
 *   DELETE /ocr-scanner/{scan}     → soft-delete (archive) a scan
 */
class OcrScannerController extends Controller
{
    public function __construct(
        protected OcrService $ocrService,
        protected NotificationDispatcher $dispatcher,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    //  SCAN PAGE  (GET /ocr-scanner)
    // ════════════════════════════════════════════════════════════════════

    public function index(): View
    {
        return view('admin.ocr-scanner.scan');
    }

    // ════════════════════════════════════════════════════════════════════
    //  PROCESS IMAGE  (POST /ocr-scanner/process)
    //  Called via AJAX. Returns JSON.
    // ════════════════════════════════════════════════════════════════════

    public function process(Request $request): JsonResponse
    {
        $maxKb = (int) round(((int) config('ocr.max_image_bytes', 4_000_000)) / 1024);

        $request->validate([
            'image'     => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:' . $maxKb],
            'scan_type' => ['nullable', 'string', 'in:business_card,invoice,receipt,expense,gst_bill,general'],
        ]);

        $file     = $request->file('image');
        $scanType = $request->input('scan_type', 'business_card');
        // ↑ Accepted: business_card | invoice | receipt | expense | gst_bill | general
        $user     = Auth::user();

        // ── Reserve one scan slot atomically ─────────────────────────────
        // The limit check reads a COUNT and this method writes a row. Without
        // a lock, two uploads landing together both read the same count, both
        // pass, and both insert — the daily cap is silently exceeded and the
        // extra Gemini calls are billed.
        //
        // The row is inserted INSIDE the lock and BEFORE the Gemini call, so
        // the next request counts it immediately and a scan that later fails
        // still spends its quota. Failing after the API call was already paid
        // for used to leave the tenant free to retry forever.
        $lock = Cache::lock("ocr_quota_{$user->company_id}", 10);

        try {
            $scan = $lock->block(5, function () use ($user, $scanType, $file) {
                if (! check_plan_limit('ocr_scans')) {
                    return null;
                }

                return OcrScan::create([
                    'company_id'        => $user->company_id,
                    'user_id'           => $user->id,
                    'scan_type'         => $scanType,
                    'original_filename' => $file->getClientOriginalName(),
                    'status'            => 'pending',
                    'ocr_engine'        => 'GeminiVision',
                ]);
            });
        } catch (LockTimeoutException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Another scan is being processed right now. Please try again in a moment.',
            ], 429);
        }

        if (! $scan) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached your daily OCR scan limit. Please upgrade your plan to continue scanning.',
            ], 403);
        }

        try {
            $result = $this->ocrService->scan($file, $scanType);

            if (! $result['success']) {
                $scan->update(['status' => 'failed']);

                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }

            // Stored on the private disk and served through image() — scanned
            // GST bills and invoices must never sit on a public URL.
            $imagePath = null;
            if ($request->boolean('store_image', true)) {
                $imagePath = $this->ocrService->storeImage($file, $user->company_id);
            }

            $scan->update([
                'image_path'     => $imagePath,
                'raw_ocr_text'   => $result['raw_text'],
                'extracted_data' => $result['extracted_data'],
                'status'         => 'completed',
                'ocr_engine'     => $result['ocr_engine'] ?? 'GeminiVision',
            ]);

            return response()->json([
                'success'        => true,
                'scan_id'        => $scan->id,
                'raw_text'       => $result['raw_text'],
                'extracted_data' => $result['extracted_data'],
                'image_url'      => $scan->fresh()->image_url,
            ]);

        } catch (Throwable $e) {
            $scan->update(['status' => 'failed']);

            // The old handler discarded the exception entirely, leaving a 500
            // with no trace anywhere.
            Log::error('OCR processing failed', [
                'scan_id'    => $scan->id,
                'company_id' => $user->company_id,
                'user_id'    => $user->id,
                'exception'  => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error during OCR processing.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    //  SAVE CONFIRMED DATA  (POST /ocr-scanner/save)
    //  Called after the user reviews & edits extracted fields.
    // ════════════════════════════════════════════════════════════════════

    public function save(Request $request): JsonResponse
    {
        // No exists: rule here. It ran unscoped, so a foreign scan id returned
        // 404 while a nonexistent one returned 422 — enough of a difference to
        // enumerate which ids exist across every tenant. firstOrFail() below
        // answers 404 either way.
        $request->validate([
            'scan_id'      => ['required', 'integer'],
            'edited_data'  => ['required', 'array'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();

        $scan = OcrScan::withoutGlobalScope('tenant')
            ->where('id', $request->scan_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Captured before the update so the notification fires on the first
        // save only. Re-posting the same scan_id used to send a fresh in-app
        // notification and a fresh email to every recipient, every time.
        $alreadySaved = $scan->status === 'saved';

        $scan->update([
            'edited_data' => $request->edited_data,
            'notes'       => $request->notes,
            'status'      => 'saved',
        ]);

        // Recipients come from Settings > Notifications, not from a local
        // toggle. The old flag emailed one fixed company address and offered
        // no in-app notification at all, so a scan saved by a worker was
        // invisible to everyone who was not reading that inbox.
        if (! $alreadySaved) {
            $this->notifyScanSaved($scan);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Scan saved successfully.',
            'scan_id'  => $scan->id,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  HISTORY  (GET /ocr-scanner/history)
    // ════════════════════════════════════════════════════════════════════

    public function history(Request $request): View
    {
        $user  = Auth::user();

        $query = OcrScan::forCompany($user->company_id)
            ->active()
            ->latest();

        if ($request->filled('scan_type')) {
            $query->ofType($request->scan_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('raw_ocr_text', 'like', "%{$search}%")
                  ->orWhere('extracted_data', 'like', "%{$search}%")
                  ->orWhere('edited_data', 'like', "%{$search}%");
            });
        }

        $scans = $query->paginate(20)->withQueryString();

        return view('admin.ocr-scanner.history', compact('scans'));
    }

    // ════════════════════════════════════════════════════════════════════
    //  SHOW SINGLE  (GET /ocr-scanner/{scan})
    // ════════════════════════════════════════════════════════════════════

    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $scan = OcrScan::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'scan'    => [
                'id'             => $scan->id,
                'scan_type'      => $scan->scan_type,
                'raw_text'       => $scan->raw_ocr_text,
                'extracted_data' => $scan->extracted_data,
                'edited_data'    => $scan->edited_data,
                'final_data'     => $scan->final_data,
                'image_url'      => $scan->image_url,
                'status'         => $scan->status,
                'notes'          => $scan->notes,
                'created_at'     => $scan->created_at->diffForHumans(),
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Announce a saved scan to whoever the tenant has configured.
     *
     * The email greets the company rather than the reader: one Mailable is
     * built here and delivered to every recipient who wants mail, so there is
     * no single addressee to greet by name.
     */
    private function notifyScanSaved(OcrScan $scan): void
    {
        $scanType = ucfirst(str_replace('_', ' ', $scan->scan_type));
        $companyName = $scan->company?->name ?? '';

        $this->dispatcher->dispatch(
            NotificationEvent::OcrScanCompleted,
            $scan->company_id,
            notification: new AppNotification(
                title: 'Scan Saved',
                message: "A {$scanType} document was scanned and saved.",
                link: route('admin.ocr-scanner.history'),
                icon: 'scan-text',
                color: 'blue',
                type: 'ocr_scan_saved',
                extra: ['scan_id' => $scan->id],
            ),
            mailable: new DynamicMail(
                "New OCR scan processed: {$scanType}",
                'emails.ocr-scan-completed',
                [
                    'ownerName'     => $companyName,
                    'scanType'      => $scanType,
                    'extractedHtml' => $this->buildOcrEmailTable($scan->edited_data ?? $scan->extracted_data ?? []),
                    'rawText'       => $scan->raw_ocr_text,
                    'scanDate'      => $scan->created_at->format('d M Y, h:i A'),
                    'viewUrl'       => route('admin.ocr-scanner.history'),
                ],
            ),
        );
    }

    /**
     * Build a plain HTML table from flat OCR data for email notifications.
     * Nested arrays (line items) are skipped to keep emails readable.
     */
    private function buildOcrEmailTable(array $data): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;font-size:14px;background:#fff;border:1px solid #e2e8f0;">';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue; // Skip nested arrays (line items etc.)
            }
            $label   = ucwords(str_replace('_', ' ', $key));
            $display = ($value === null || $value === '')
                ? '<span style="color:#9ca3af;font-style:italic;">Not found</span>'
                : htmlspecialchars((string) $value);
            $html .= "<tr>
                <td style='padding:12px 15px;border-bottom:1px solid #e2e8f0;color:#64748b;font-weight:600;width:40%;'>{$label}</td>
                <td style='padding:12px 15px;border-bottom:1px solid #e2e8f0;color:#1e293b;font-weight:500;'>{$display}</td>
            </tr>";
        }
        $html .= '</table>';
        return $html;
    }

    // ════════════════════════════════════════════════════════════════════
    //  SERVE IMAGE  (GET /ocr-scanner/{id}/image)
    // ════════════════════════════════════════════════════════════════════

    /**
     * Stream a scan image from the private disk after checking ownership.
     */
    public function image(int $id): StreamedResponse
    {
        $user = Auth::user();

        $scan = OcrScan::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        abort_if(! $scan->image_path, 404);
        abort_unless(Storage::disk('local')->exists($scan->image_path), 404);

        return Storage::disk('local')->response($scan->image_path);
    }

    // ════════════════════════════════════════════════════════════════════
    //  ARCHIVE  (DELETE /ocr-scanner/{id})
    // ════════════════════════════════════════════════════════════════════

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $scan = OcrScan::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $scan->update(['is_archived' => true]);

        return response()->json(['success' => true, 'message' => 'Scan archived.']);
    }
}