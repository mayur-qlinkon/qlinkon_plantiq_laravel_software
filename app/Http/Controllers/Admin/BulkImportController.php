<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductImageGuideExport;
use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\ProductSku;
use App\Models\ProductSkuValue;
use App\Models\Attribute;

use App\Imports\ImportResult;
use App\Imports\Contracts\ImportType;
use App\Imports\ImportContext;
use App\Imports\ImportTypeRegistry;
use App\Services\Import\ProductImageImporter;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkImportController extends Controller
{
    private const CHUNK_SIZE = 50;

    public function __construct(private ImportTypeRegistry $registry) {}

    /**
     * Resolve the {type} route segment.
     *
     * URLs use hyphens ('product-with-skus') because that reads better and is
     * what the existing frontend already builds; registry keys use underscores
     * to match Import::$type in the database. Normalising here means neither
     * side has to know about the other.
     */
    private function resolveType(string $segment): ImportType
    {
        $key = str_replace('-', '_', $segment);

        abort_unless($this->registry->has($key), 404, "Unknown import type: {$segment}");

        $type = $this->registry->get($key);

        abort_if(
            $type->permission() !== null && ! has_permission($type->permission()),
            403,
            'You do not have permission to run this import.'
        );

        return $type;
    }

    public function index()
    {
        $imports = Import::with('logs')
            ->latest()
            ->limit(20)
            ->get();

        $companyId = Auth::user()->company_id;
        $productCount = Product::withoutGlobalScopes()->where('company_id', $companyId)->count();
        $plan = Auth::user()->company->subscription?->plan;
        $productLimit = $plan?->product_limit; // null = no plan / unlimited
        $productLimitReached = $productLimit !== null && $productCount >= $productLimit;

        // Only types this user may actually run — the same filter the modal
        // will use, so a permission cannot be enforced in one place and
        // forgotten in the other.
        $importTypes = $this->registry->availableToUser();

        return view('admin.bulk-import.index', compact('imports', 'productCount', 'productLimit', 'productLimitReached', 'importTypes'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Generic CSV upload + process — one pair for every registered type
    // ══════════════════════════════════════════════════════════════════════════

    public function upload(Request $request, string $type): JsonResponse
    {
        return $this->handleUpload($request, $this->resolveType($type));
    }

    public function process(Request $request, string $type): JsonResponse
    {
        return $this->handleProcess($request, $this->resolveType($type));
    }



    public function reference()
    {
        $companyId = Auth::user()->company_id;

        $categories = Category::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        
        $categories = $categories->map(fn ($c) => [
            'name'        => $c->name,
            'slug'        => $c->slug,            
        ])->values();

        $units = Unit::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name'])
            ->map(fn ($u) => ['name' => $u->name, 'short_name' => $u->short_name])
            ->values();

        $warehouses = Warehouse::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($w) => ['name' => $w->name])
            ->values();

        $productRows = Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $skus = ProductSku::withoutGlobalScopes()
            ->whereIn('product_id', $productRows->pluck('id'))
            ->get(['id', 'product_id', 'sku', 'price']);

        $skuValues = ProductSkuValue::whereIn('product_sku_id', $skus->pluck('id'))
            ->with(['attribute:id,name', 'attributeValue:id,value'])
            ->get();

        $attrsBySkuId = $skuValues->groupBy('product_sku_id')->map(
            fn ($vals) => $vals
                ->map(fn ($v) => trim(($v->attribute?->name ?? '') . ': ' . ($v->attributeValue?->value ?? '')))
                ->filter()->implode(' | ')
        );

        $skusByProduct = $skus->map(fn ($s) => [
            'product_id' => $s->product_id,
            'sku'        => $s->sku,
            'price'      => $s->price,
            'attrs'      => $attrsBySkuId[$s->id] ?? '',
        ])->groupBy('product_id');

        $products = $productRows->map(fn ($p) => [
            'name' => $p->name,
            'slug' => $p->slug,
            'skus' => $skusByProduct->get($p->id, collect())->values()->all(),
        ])->values();

        $attributes = Attribute::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['values' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'name'   => $a->name,
                'type'   => $a->type,
                'values' => $a->values->map(fn ($v) => [
                    'value'      => $v->value,
                    'color_code' => $v->color_code,
                ])->values()->all(),
            ])->values();

        return view('admin.bulk-import.reference', compact('categories', 'units', 'warehouses', 'products', 'attributes'));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Product Images — ZIP upload flow (non-CSV)
    // ══════════════════════════════════════════════════════════════════════════

    public function uploadProductImages(Request $request, ProductImageImporter $importer): JsonResponse
    {
        $request->validate([
            // Kilobytes, matching what the server can actually accept — the
            // client checks the same number before sending.
            'file' => ['required', 'file', 'mimes:zip', 'mimetypes:application/zip,application/x-zip-compressed,multipart/x-zip', 'max:' . (int) (max_upload_bytes() / 1024)],
            'import_mode' => ['nullable', 'in:create_only,update_only,create_or_update'],
        ]);

        $importMode = $request->input('import_mode', 'create_or_update');

        $file = $request->file('file');
        $storedPath = $file->store('imports/product-images', 'local');
        $absoluteZip = Storage::disk('local')->path($storedPath);

        $result = $importer->extractZip($absoluteZip);

        if (! $result['valid']) {
            // Cleanup uploaded ZIP on rejection
            Storage::disk('local')->delete($storedPath);

            return response()->json(['error' => $result['message']], 422);
        }

        $import = Import::create([
            'user_id' => $request->user()->id,
            'type' => 'product_images',
            'file_path' => $storedPath,
            'temp_path' => $result['temp_path'],
            'total_rows' => $result['total'],
            'status' => 'pending',
            'duplicate_mode' => 'skip',
            'import_mode' => $importMode,
            'is_dry_run' => false,
        ]);

        return response()->json([
            'import_id' => $import->id,
            'total_rows' => $result['total'],
            'import_mode' => $importMode,
            'is_dry_run' => false,
            'message' => "ZIP extracted. {$result['total']} image(s) ready to import.",
        ]);
    }

    public function processProductImages(Request $request, ProductImageImporter $importer): JsonResponse
    {
        $import = Import::find($request->input('import_id'));

        if (! $import) {
            return response()->json(['error' => 'Import session not found. Please start a new import.'], 404);
        }

        if ($import->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($import->type !== 'product_images') {
            return response()->json(['error' => 'Invalid import type for this endpoint.'], 422);
        }

        $tempDir = $import->temp_path;
        if (! $tempDir || ! is_dir($tempDir)) {
            $import->markFailed();

            return response()->json(['error' => 'Temporary extraction directory missing. Please re-upload the ZIP.'], 422);
        }

        $companyId = $request->user()->company_id;
        $offset = (int) $request->input('offset', 0);

        if ($offset === 0 && $import->isPending()) {
            $import->markProcessing();
        }

        $allFiles = $importer->listFiles($tempDir);
        $chunk = array_slice($allFiles, $offset, self::CHUNK_SIZE);

        if (empty($chunk)) {
            $import->markCompleted();
            $importer->cleanup($tempDir);

            return response()->json([
                'done' => true,
                'processed' => $import->processed_rows,
                'success' => $import->success_rows,
                'created' => $import->created_rows,
                'updated' => $import->updated_rows,
                'failed' => $import->failed_rows,
                'skipped' => $import->skipped_rows,
                'total' => $import->total_rows,
                'is_dry_run' => false,
            ]);
        }

        $result = $importer->processChunk($import, $tempDir, $chunk, $companyId);

        // ProductImageImporter still returns the legacy array shape — it is
        // not an ImportType yet, so array access is correct here.
        $chunkCount = count($chunk);
        $import->increment('processed_rows', $chunkCount);
        $import->increment('success_rows', $result['success']);
        $import->increment('failed_rows', $result['failed']);
        if (! empty($result['created'])) {
            $import->increment('created_rows', $result['created']);
        }
        if (! empty($result['updated'])) {
            $import->increment('updated_rows', $result['updated']);
        }
        if (! empty($result['skipped'])) {
            $import->increment('skipped_rows', $result['skipped']);
        }

        $nextOffset = $offset + self::CHUNK_SIZE;
        $done = $nextOffset >= $import->total_rows;

        if ($done) {
            $import->markCompleted();
            // Cleanup temp dir only after final chunk
            $importer->cleanup($tempDir);
            // Also remove the uploaded ZIP — we no longer need it
            if ($import->file_path && Storage::disk('local')->exists($import->file_path)) {
                Storage::disk('local')->delete($import->file_path);
            }
        }

        return response()->json([
            'done' => $done,
            'next_offset' => $done ? null : $nextOffset,
            'processed' => $import->processed_rows,
            'success' => $import->success_rows,
            'created' => $import->created_rows,
            'updated' => $import->updated_rows,
            'failed' => $import->failed_rows,
            'skipped' => $import->skipped_rows,
            'total' => $import->total_rows,
            'is_dry_run' => false,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Shared: Upload + Process + CSV helpers
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Generic chunk processor — resolves the correct importer by type.
     */
    private function handleProcess(Request $request, ImportType $type): JsonResponse
    {
        $import = Import::find($request->input('import_id'));

        if (! $import) {
            return response()->json(['error' => 'Import session not found. Please start a new import.'], 404);
        }

        if ($import->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $companyId = $request->user()->company_id;
        $storeId = session('store_id') ?? $request->user()->store_id; // Extracted safely in the HTTP context
        $offset = (int) $request->input('offset', 0);

        $filePath = Storage::disk('local')->path($import->file_path);
        if (! file_exists($filePath)) {
            return response()->json(['error' => 'Import file not found.'], 404);
        }

        // Mark as processing on first chunk
        if ($offset === 0 && $import->isPending()) {
            $import->markProcessing();
        }

        $rows = $this->readCsvChunk($filePath, $offset, self::CHUNK_SIZE);

        if (empty($rows['data'])) {
            $import->markCompleted();

            return response()->json([
                'done' => true,
                'processed' => $import->processed_rows,
                'success' => $import->success_rows,
                'created' => $import->created_rows,
                'updated' => $import->updated_rows,
                'failed' => $import->failed_rows,
                'skipped' => $import->skipped_rows,
                'total' => $import->total_rows,
                'is_dry_run' => (bool) $import->is_dry_run,
            ]);
        }

        // Apply duplicate-detection metadata: silently skip or log-error duplicates
        // before sending remaining rows to the importer.
        $meta = $import->duplicate_meta ?? [];
        $skipSet = array_flip($meta['skip_rows'] ?? []);
        $errorSet = array_flip($meta['error_rows'] ?? []);
        $skipReasons = $meta['skip_reasons'] ?? [];

        $rowsToProcess = [];
        $skippedInChunk = 0;
        $duplicateErrorsInChunk = 0;

        foreach ($rows['data'] as $row) {
            $rowNumber = (int) ($row['_row_number'] ?? 0);

            if (isset($skipSet[$rowNumber])) {
                $keptRow = $skipReasons[$rowNumber] ?? null;
                ImportLog::create([
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'row_data' => $this->stripInternalKeys($row),
                    'error_message' => $keptRow
                        ? "Duplicate row in file (duplicate_mode = skip). Row {$keptRow} was kept instead; this row was skipped."
                        : 'Duplicate row in file (duplicate_mode = skip).',
                ]);
                $skippedInChunk++;

                continue;
            }

            if (isset($errorSet[$rowNumber])) {
                ImportLog::create([
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'row_data' => $this->stripInternalKeys($row),
                    'error_message' => 'Duplicate row in file (duplicate_mode = error).',
                ]);
                $duplicateErrorsInChunk++;

                continue;
            }

            $rowsToProcess[] = $row;
        }

        $result = ImportResult::empty();

        if (! empty($rowsToProcess)) {
            $result = $type->processChunk($rowsToProcess, $rows['start_row'], new ImportContext(
                import: $import,
                companyId: $companyId,
                storeId: $storeId,
                remainingSlots: $type->remainingSlots(
                    $companyId,
                    (bool) $import->is_dry_run,
                    (int) ($import->created_rows ?? 0)
                ),
            ));
        }

        // Every counter always exists on ImportResult, so the old
        // "! empty(...)" guards are gone — a zero increment is a no-op.
        $chunkCount = count($rows['data']);
        $totalSkipped = $skippedInChunk + $result->skipped;

        $import->increment('processed_rows', $chunkCount);
        $import->increment('success_rows', $result->success);
        $import->increment('failed_rows', $result->failed + $duplicateErrorsInChunk);
        $import->increment('created_rows', $result->created);
        $import->increment('updated_rows', $result->updated);
        $import->increment('limit_skipped_rows', $result->limitSkipped);

        if ($totalSkipped > 0) {
            $import->increment('skipped_rows', $totalSkipped);
        }

        $nextOffset = $offset + self::CHUNK_SIZE;
        $done = $nextOffset >= $import->total_rows;

        if ($done) {
            $import->markCompleted();
        }

        return response()->json([
            'done' => $done,
            'next_offset' => $done ? null : $nextOffset,
            'processed' => $import->processed_rows,
            'success' => $import->success_rows,
            'created' => $import->created_rows,
            'updated' => $import->updated_rows,
            'failed' => $import->failed_rows,
            'skipped' => $import->skipped_rows,
            'limit_skipped' => $import->limit_skipped_rows,
            'created_refs' => $result->createdRefs,
            'total' => $import->total_rows,
            'is_dry_run' => (bool) $import->is_dry_run,
        ]);
    }

    /**
     * Strip keys prefixed with _ (internal metadata like _row_number) before persisting row_data.
     */
    private function stripInternalKeys(array $row): array
    {
        return array_filter($row, fn ($v, $k) => ! str_starts_with((string) $k, '_'), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Handle CSV upload, validate headers, create Import record.
     */
    private function handleUpload(Request $request, ImportType $type): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:' . (int) (max_upload_bytes() / 1024)],
            'duplicate_mode' => ['nullable', 'in:skip,update,error'],
            'import_mode' => ['nullable', 'in:create_only,update_only,create_or_update'],
            'is_dry_run' => ['nullable'],
        ]);

        $duplicateMode = $request->input('duplicate_mode', 'skip');
        $importMode = $request->input('import_mode', 'create_or_update');
        $isDryRun = filter_var($request->input('is_dry_run', false), FILTER_VALIDATE_BOOLEAN);

        $file = $request->file('file');
        $path = $file->store('imports/' . $type->key(), 'local');

        $fullPath = Storage::disk('local')->path($path);

        // Read and validate headers
        $handle = fopen($fullPath, 'r');
        if (! $handle) {
            Storage::disk('local')->delete($path);

            return response()->json(['error' => 'Could not read uploaded file.'], 422);
        }

        $headerRow = fgetcsv($handle);
        if (! $headerRow) {
            fclose($handle);
            Storage::disk('local')->delete($path);

            return response()->json(['error' => 'CSV file is empty.'], 422);
        }

        // Clean BOM and whitespace from headers
        $headers = array_map(fn ($h) => strtolower(trim(preg_replace('/\x{FEFF}/u', '', $h))), $headerRow);

        // Validate headers per import type
        $headerCheck = $type->validateHeaders($headers);
        if (! $headerCheck['valid']) {
            fclose($handle);
            Storage::disk('local')->delete($path);

            return response()->json(['error' => $headerCheck['message']], 422);
        }

        // Walk data rows: count total + build duplicate metadata based on selected mode.
        $totalRows = 0;
        $dataRowIndex = 0; // 0-based data row index
        $keyFirstSeen = [];        // unique_key => csv row_number of first occurrence
        $keyLastSeen = [];         // unique_key => csv row_number of last occurrence
        $duplicateOccurrences = []; // unique_key => [row_numbers of all occurrences in order]

        // Pre-flight observer — NullScanner for types with no upload-time rules.
        $scanner = $type->newScanner();

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim($v ?? '') !== '')) === 0) {
                $dataRowIndex++;

                continue;
            }

            $totalRows++;
            $csvRowNumber = $dataRowIndex + 2; // +1 for header, +1 for 1-based

            $mapped = [];
            foreach ($headers as $i => $header) {
                $mapped[$header] = $row[$i] ?? '';
            }

            $scanner->observe($mapped);

            $key = $type->extractUniqueKey($mapped);
            if ($key !== null && $key !== '') {
                if (! isset($keyFirstSeen[$key])) {
                    $keyFirstSeen[$key] = $csvRowNumber;
                }
                $keyLastSeen[$key] = $csvRowNumber;
                $duplicateOccurrences[$key][] = $csvRowNumber;
            }

            $dataRowIndex++;
        }
        fclose($handle);

        if ($totalRows === 0) {
            Storage::disk('local')->delete($path);

            return response()->json(['error' => 'CSV has no data rows.'], 422);
        }

                // ── Pre-flight rejection (before any record is created) ────────
        if ($rejection = $scanner->reject($importMode, $request->user()->company_id)) {
            Storage::disk('local')->delete($path);

            return response()->json($rejection, 422);
        }

        // Build skip_rows and error_rows from duplicate occurrences per mode.
        $skipRows = [];
        $errorRows = [];
        $skipReasons = []; // row_number => row_number of the occurrence that was kept
        foreach ($duplicateOccurrences as $key => $occurrences) {
            if (count($occurrences) < 2) {
                continue;
            }

            if ($duplicateMode === 'skip') {
                // Keep first, skip the rest silently
                $kept = $occurrences[0];
                foreach (array_slice($occurrences, 1) as $rowNum) {
                    $skipReasons[$rowNum] = $kept;
                }
                $skipRows = array_merge($skipRows, array_slice($occurrences, 1));
            } elseif ($duplicateMode === 'update') {
                // Keep last (most recent wins), skip earlier ones silently
                $kept = end($occurrences);
                foreach (array_slice($occurrences, 0, -1) as $rowNum) {
                    $skipReasons[$rowNum] = $kept;
                }
                $skipRows = array_merge($skipRows, array_slice($occurrences, 0, -1));
            } else { // error
                // Keep first, log error for the rest
                $errorRows = array_merge($errorRows, array_slice($occurrences, 1));
            }
        }

        $duplicateMeta = [
            'skip_rows' => array_values(array_unique($skipRows)),
            'error_rows' => array_values(array_unique($errorRows)),
            'skip_reasons' => $skipReasons,
            'duplicate_groups' => count(array_filter($duplicateOccurrences, fn ($o) => count($o) > 1)),
        ];

        $import = Import::create([
            'user_id' => $request->user()->id,
            'type' => $type->key(),
            'file_path' => $path,
            'total_rows' => $totalRows,
            'status' => 'pending',
            'duplicate_mode' => $duplicateMode,
            'duplicate_meta' => $duplicateMeta,
            'import_mode' => $importMode,
            'is_dry_run' => $isDryRun,
        ]);

        $dupCount = count($duplicateMeta['skip_rows']) + count($duplicateMeta['error_rows']);
        $message = "File uploaded. {$totalRows} rows ready to import.";
        if ($isDryRun) {
            $message = "Dry run ready. {$totalRows} rows will be validated without saving.";
        }
        if ($dupCount > 0) {
            $message .= " Detected {$dupCount} duplicate row(s) (mode: {$duplicateMode}).";
        }

        return response()->json([
            'import_id' => $import->id,
            'total_rows' => $totalRows,
            'duplicate_mode' => $duplicateMode,
            'duplicate_count' => $dupCount,
            'import_mode' => $importMode,
            'is_dry_run' => $isDryRun,
            'message' => $message,
        ]);
    }

    /**
     * Read a chunk of CSV rows starting at the given offset (0-based, data rows only, skips header).
     *
     * @return array{data: array, start_row: int}
     */
    private function readCsvChunk(string $filePath, int $offset, int $limit): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return ['data' => [], 'start_row' => 0];
        }

        // Read header row
        $headerRow = fgetcsv($handle);
        if (! $headerRow) {
            fclose($handle);

            return ['data' => [], 'start_row' => 0];
        }

        $headers = array_map(fn ($h) => strtolower(trim(preg_replace('/\x{FEFF}/u', '', $h))), $headerRow);

        // $fileRow  — every row read after the header (includes empty rows), used for _row_number.
        // $dataRow  — only non-empty rows, used to honour the data-row offset correctly.
        // Previously the skip loop advanced $currentRow for every file row (including empties),
        // which caused the offset to drift when the CSV contained blank lines.
        $fileRow = 0;
        $dataRow = 0;

        // Skip exactly $offset DATA rows
        while ($dataRow < $offset) {
            $row = fgetcsv($handle);
            if ($row === false) {
                break;
            }
            $fileRow++;
            if (count(array_filter($row, fn ($v) => trim($v ?? '') !== '')) > 0) {
                $dataRow++;
            }
        }

        // Read up to $limit DATA rows
        $data = [];
        $read = 0;
        while ($read < $limit && ($row = fgetcsv($handle)) !== false) {
            $fileRow++;

            // Skip completely empty rows (don't count toward the chunk limit)
            if (count(array_filter($row, fn ($v) => trim($v ?? '') !== '')) === 0) {
                continue;
            }

            // Map columns to headers
            $mapped = [];
            foreach ($headers as $i => $header) {
                $mapped[$header] = $row[$i] ?? '';
            }

            // 1-based row number: header = 1, so first data row = fileRow + 1.
            // Using the real file position keeps _row_number consistent with the
            // duplicate-detection row numbers calculated during handleUpload.
            $mapped['_row_number'] = $fileRow + 1;

            $data[] = $mapped;
            $read++;
        }

        fclose($handle);

        // start_row is 1-based (row 1 = header, row 2 = first data row)
        return [
            'data' => $data,
            'start_row' => $offset + 2, // +1 for header, +1 for 1-based
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Error report download
    // ══════════════════════════════════════════════════════════════════════════

    public function downloadErrors(Request $request, Import $import)
    {
        if ($import->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $logs = $import->logs()->orderBy('row_number')->get();

        $filename = "import-{$import->type}-errors-{$import->id}.csv";

        // Column set is the union of every logged row. Rows in one file can
        // carry different keys, and deriving the header from the first row
        // alone produced a CSV whose data columns did not line up with it.
        $dataColumns = [];
        foreach ($logs as $log) {
            foreach (array_keys($log->row_data ?? []) as $key) {
                $dataColumns[$key] = true;
            }
        }
        $dataColumns = array_keys($dataColumns);

        $callback = function () use ($logs, $dataColumns) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM so Excel does not mangle non-ASCII values.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_merge(['row_number'], $dataColumns, ['error_message']));

            // Skipped rows are counted but never logged, so a run with only
            // skips arrives here with nothing to write. Returning back() at
            // this point downloaded nothing in the button's target="_blank"
            // tab, which read as a broken button. Always send a file.
            if ($logs->isEmpty()) {
                fputcsv($handle, array_merge(
                    [''],
                    array_fill(0, count($dataColumns), ''),
                    ['No row-level errors were logged for this import.']
                ));
                fclose($handle);

                return;
            }

            foreach ($logs as $log) {
                $rowData = $log->row_data ?? [];
                $line = [$log->row_number];

                foreach ($dataColumns as $column) {
                    $line[] = $rowData[$column] ?? '';
                }

                $line[] = $log->error_message;

                fputcsv($handle, $line);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Sample CSV download
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Stream rows as a CSV download. The BOM keeps Excel from mangling
     * non-ASCII characters in sample data.
     */
    private function streamCsv(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadSample(string $type)
    {
        $importType = $this->resolveType($type);

        // Product+SKU orders its sample columns for readability rather than
        // required-then-optional, so it overrides sampleHeaders().
        $headers = method_exists($importType, 'sampleHeaders')
            ? $importType->sampleHeaders()
            : $importType->allHeaders();

        return $this->streamCsv(
            $importType->key() . '-sample.csv',
            $headers,
            $importType->sampleRows()
        );
    }

    /**
     * Download a pre-filled Excel guide that lists every product slug with
     * example image filenames — helps non-technical users rename their photos
     * correctly before zipping and uploading.
     */
    public function downloadImageGuide(): BinaryFileResponse
    {
        $companyId = Auth::user()->company_id;

        $products = Product::with('category:id,name')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category_id']);

        $export = new ProductImageGuideExport($products);
        $filename = 'product-image-naming-guide-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download($export, $filename);
    }

     public function exportExistingData(string $type)
    {
        $companyId = Auth::user()->company_id;

        $exports = [
            'products' => [
                'filename' => 'existing-product-slugs.csv',
                'headers'  => ['Product Name', 'slug'],
                'query'    => Product::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->select(['name', 'slug'])
                    ->orderBy('name'),
                'map'      => function ($row) {
                    return [
                        $row->name ?? '',
                        $row->slug ?? '',
                    ];
                },
            ],

            'categories' => [
                'filename' => 'existing-category-slugs.csv',
                'headers'  => ['Category Name', 'slug'],
                'query'    => Category::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->select(['name', 'slug'])
                    ->orderBy('name'),
                'map'      => function ($row) {
                    return [
                        $row->name ?? '',
                        $row->slug ?? '',
                    ];
                },
            ],

            'units' => [
                'filename' => 'existing-unit-codes.csv',
                'headers'  => ['Unit Name', 'short_name'],
                'query'    => Unit::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->select(['name', 'short_name'])
                    ->orderBy('name'),
                'map'      => function ($row) {
                    return [
                        $row->name ?? '',
                        $row->short_name ?? '',
                    ];
                },
            ],

            'warehouses' => [
                'filename' => 'existing-warehouses.csv',
                'headers'  => ['Warehouse Name'],
                'query'    => Warehouse::where('company_id', $companyId)
                    ->select(['name'])
                    ->orderBy('name'),
                'map'      => function ($row) {
                    return [
                        $row->name ?? '',
                    ];
                },
            ],
        ];

        if (! isset($exports[$type])) {
            abort(404);
        }

        $export = $exports[$type];

        return response()->streamDownload(function () use ($export) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, $export['headers']);

            // Chunked export for production safety
            $export['query']->chunk(500, function ($rows) use ($handle, $export) {
                foreach ($rows as $row) {
                    fputcsv($handle, ($export['map'])($row));
                }
            });

            fclose($handle);
        }, $export['filename'], [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }
    public function exportAllData()
    {
        $companyId = Auth::user()->company_id;

        $zip = new \ZipArchive();
        $fileName = 'existing-data.zip';
        $filePath = storage_path($fileName);

        if ($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

            // Products
            $products = Product::where('company_id', $companyId)
                ->select('name', 'slug')->get();

            $productsCsv = $this->arrayToCsv([
                ['Product Name', 'slug'],
                ...$products->map(fn($p) => [$p->name, $p->slug])->toArray()
            ]);

            $zip->addFromString('products.csv', $productsCsv);

            // Categories
            $categories = Category::where('company_id', $companyId)
                ->select('name', 'slug')->get();

            $categoriesCsv = $this->arrayToCsv([
                ['Category Name', 'slug'],
                ...$categories->map(fn($c) => [$c->name, $c->slug])->toArray()
            ]);

            $zip->addFromString('categories.csv', $categoriesCsv);

            // Units
            $units = Unit::where('company_id', $companyId)
                ->select('name', 'short_name')->get();

            $unitsCsv = $this->arrayToCsv([
                ['Unit Name', 'short_name'],
                ...$units->map(fn($u) => [$u->name, $u->short_name])->toArray()
            ]);

            $zip->addFromString('units.csv', $unitsCsv);

            // Warehouses
            $warehouses = Warehouse::where('company_id', $companyId)
                ->select('name')->get();

            $warehousesCsv = $this->arrayToCsv([
                ['Warehouse Name'],
                ...$warehouses->map(fn($w) => [$w->name])->toArray()
            ]);

            $zip->addFromString('warehouses.csv', $warehousesCsv);

            $zip->close();
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
    private function arrayToCsv(array $data)
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF" . $csv;
    }
    
}
