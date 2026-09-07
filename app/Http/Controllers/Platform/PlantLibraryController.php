<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlantLibrary;
use App\Models\Platform\PlantLibraryMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlantLibraryController extends Controller
{
    /** Columns the import CSV must contain. */
    private const REQUIRED_HEADERS = ['name', 'category_name'];

    /** Columns the import CSV may contain, guide pairs excluded. */
    private const OPTIONAL_HEADERS = ['type', 'product_type', 'unit_short_name', 'description', 'is_active', 'sort_order'];

    /**
     * How many title{n}/value{n} pairs the CSV may carry.
     * Same convention as ProductWithSkuImporter, so one CSV can feed both.
     */
    private const GUIDE_PAIRS = 10;

    /**
     * Rows accepted per file. Shared hosting has no queue worker, so a single
     * request has to finish the whole file before max_execution_time.
     */
    private const MAX_ROWS = 2000;

    /** Errors returned to the browser. Beyond this the payload stops being useful. */
    private const MAX_REPORTED_ERRORS = 100;

    /**
     * Display a listing of the plant library entries.
     */
    public function index(Request $request)
    {
        $plants = PlantLibrary::withCount('media')
            ->when($request->filled('search'), function ($query) use ($request) {
                $searchTerm = '%'.$request->string('search').'%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('category_name', 'like', $searchTerm);
                });
            })
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('platform.plant-library.index', compact('plants'));
    }

    /**
     * Show the form for creating a new plant library entry.
     */
    public function create()
    {
        return view('platform.plant-library.create');
    }

    /**
     * Store a newly created plant library entry.
     * Media is added afterwards on the Edit screen (needs the record's id first).
     */
    public function store(Request $request)
    {
        $validated = $this->validatePlant($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['product_guide'] = $this->buildProductGuide($request);

        $plant = PlantLibrary::create($validated);

        return redirect()->route('platform.plant-library.edit', $plant)
            ->with('success', 'Plant added to library. You can now add photos/videos below.');
    }

    /**
     * Show the form for editing the specified plant library entry.
     */
    public function edit(PlantLibrary $plantLibrary)
    {
        $plantLibrary->load('media');

        return view('platform.plant-library.edit', compact('plantLibrary'));
    }

    /**
     * Update the specified plant library entry.
     */
    public function update(Request $request, PlantLibrary $plantLibrary)
    {
        $validated = $this->validatePlant($request, $plantLibrary->id);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['product_guide'] = $this->buildProductGuide($request);

        $plantLibrary->update($validated);

        return redirect()->route('platform.plant-library.edit', $plantLibrary)
            ->with('success', 'Plant Library entry updated successfully.');
    }

    /**
     * Display the specified plant library entry.
     */
    public function show(PlantLibrary $plantLibrary)
    {
        // Load all associated media files for the library entry
        $plantLibrary->load('media');

        return view('platform.plant-library.show', compact('plantLibrary'));
    }

    /**
     * Remove the specified plant library entry (and its media files).
     */
    public function destroy(PlantLibrary $plantLibrary)
    {
        foreach ($plantLibrary->media as $media) {
            $this->deleteMediaFile($media);
        }

        // plant_library_media rows cascade-delete via FK; explicit file cleanup above.
        $plantLibrary->delete();

        return redirect()->route('platform.plant-library.index')
            ->with('success', 'Plant Library entry deleted successfully.');
    }

    /**
     * Add one media item (image upload or YouTube link) to a plant.
     */
    public function storeMedia(Request $request, PlantLibrary $plantLibrary)
    {
        $validated = $request->validate([
            'media_type' => ['required', Rule::in(['image', 'youtube'])],
            'file'       => ['required_if:media_type,image', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'media_path' => ['required_if:media_type,youtube', 'nullable', 'string', 'max:500', 'url'],
            'is_primary' => ['boolean'],
        ]);

        $mediaPath = $validated['media_type'] === 'youtube'
            ? $validated['media_path']
            : $request->file('file')->store($this->categoryFolder($plantLibrary), 'public');

        if ($request->boolean('is_primary')) {
            $plantLibrary->media()->update(['is_primary' => false]);
        }

        $plantLibrary->media()->create([
            'media_type' => $validated['media_type'],
            'media_path' => $mediaPath,
            'is_primary' => $request->boolean('is_primary'),
            'sort_order' => $plantLibrary->media()->max('sort_order') + 1,
        ]);

        return redirect()->route('platform.plant-library.edit', $plantLibrary)
            ->with('success', 'Media added successfully.');
    }

    /**
     * Remove one media item from a plant.
     */
    public function destroyMedia(PlantLibrary $plantLibrary, PlantLibraryMedia $media)
    {
        abort_unless($media->plant_library_id === $plantLibrary->id, 404);

        $this->deleteMediaFile($media);
        $media->delete();

        return redirect()->route('platform.plant-library.edit', $plantLibrary)
            ->with('success', 'Media removed successfully.');
    }

    /**
     * Mark one media item as the primary/cover image.
     */
    public function setPrimaryMedia(PlantLibrary $plantLibrary, PlantLibraryMedia $media)
    {
        abort_unless($media->plant_library_id === $plantLibrary->id, 404);

        $plantLibrary->media()->update(['is_primary' => false]);
        $media->update(['is_primary' => true]);

        return redirect()->route('platform.plant-library.edit', $plantLibrary)
            ->with('success', 'Primary media updated.');
    }

    /**
     * Stream a sample CSV showing every supported column.
     */
    public function downloadSample()
    {
        $headers = [...self::REQUIRED_HEADERS, ...self::OPTIONAL_HEADERS];

        for ($i = 1; $i <= self::GUIDE_PAIRS; $i++) {
            $headers[] = "title{$i}";
            $headers[] = "value{$i}";
        }

        $rows = [
            ['Areca Palm', 'Indoor Plants', 'single', 'sellable', 'pcs', 'A hardy indoor palm that tolerates low light.', '1', '1', 'Light', 'Bright indirect light', 'Water', 'Twice a week'],
            ['Rose Desiree', 'Flowering Plants', 'variable', 'sellable', 'pcs', 'Repeat-flowering hybrid tea rose.', '1', '2', 'Light', 'Full sun', 'Soil', 'Well drained loam'],
            ['Neem Sapling', 'Trees', 'single', 'catalog', 'pcs', 'Fast growing native shade tree.', '1', '3'],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_pad($row, count($headers), ''));
            }

            fclose($out);
        }, 'plant-library-sample.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk import plant library entries from a CSV.
     *
     * The whole file runs inside one transaction: a half-imported library is
     * worse than a rejected file, and the row cap keeps that transaction small
     * enough to be safe on shared hosting.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.(int) (max_upload_bytes() / 1024)],
            'duplicate_mode' => ['nullable', 'in:skip,update'],
        ]);

        $duplicateMode = $request->input('duplicate_mode', 'skip');

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if (! $handle) {
            return response()->json(['message' => 'Could not read the uploaded file.'], 422);
        }

        $headerRow = fgetcsv($handle);

        if (! $headerRow) {
            fclose($handle);

            return response()->json(['message' => 'The CSV file is empty.'], 422);
        }

        // Strip the UTF-8 BOM Excel writes into the first header.
        $headers = array_map(
            fn ($h) => strtolower(trim(preg_replace('/\x{FEFF}/u', '', (string) $h))),
            $headerRow
        );

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                fclose($handle);

                return response()->json(['message' => "Missing required column: {$required}"], 422);
            }
        }

        // Count first, so an oversized file is rejected before anything is written.
        $dataRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) > 0) {
                $dataRows++;
            }
        }

        if ($dataRows === 0) {
            fclose($handle);

            return response()->json(['message' => 'The CSV has no data rows.'], 422);
        }

        if ($dataRows > self::MAX_ROWS) {
            fclose($handle);

            return response()->json([
                'message' => 'This file has '.$dataRows.' rows. The limit is '.self::MAX_ROWS.' rows per file — please split it and upload again.',
            ], 422);
        }

        rewind($handle);
        fgetcsv($handle); // Skip the header on the second pass.

        // Read once. Querying per row would turn 2000 rows into 4000 round trips.
        $existing = PlantLibrary::pluck('id', 'slug')->all();

        $knownCategories = PlantLibrary::whereNotNull('category_name')
            ->distinct()
            ->pluck('category_name')
            ->mapWithKeys(fn ($name) => [mb_strtolower($name) => $name])
            ->all();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $newCategories = [];
        $seen = [];
        $rowNumber = 1;

        try {
            DB::transaction(function () use (
                $handle, $headers, $duplicateMode,
                &$existing, &$knownCategories, &$created, &$updated, &$skipped,
                &$errors, &$newCategories, &$seen, &$rowNumber
            ) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                        continue;
                    }

                    $data = [];

                    foreach ($headers as $index => $header) {
                        $data[$header] = trim((string) ($row[$index] ?? ''));
                    }

                    $name = $data['name'] ?? '';
                    $category = $data['category_name'] ?? '';

                    if ($name === '' || $category === '') {
                        $skipped++;
                        $this->addImportError($errors, $rowNumber, 'Name and category name are both required.');

                        continue;
                    }

                    $slug = Str::slug($name);

                    if ($slug === '') {
                        $skipped++;
                        $this->addImportError($errors, $rowNumber, 'Name must contain at least one letter or number.');

                        continue;
                    }

                    $type = strtolower($data['type'] ?? '') ?: 'single';

                    if (! in_array($type, ['single', 'variable'], true)) {
                        $skipped++;
                        $this->addImportError($errors, $rowNumber, "Type must be 'single' or 'variable'.");

                        continue;
                    }

                    $productType = strtolower($data['product_type'] ?? '') ?: 'sellable';

                    if (! in_array($productType, ['sellable', 'catalog'], true)) {
                        $skipped++;
                        $this->addImportError($errors, $rowNumber, "Product type must be 'sellable' or 'catalog'.");

                        continue;
                    }

                    // Two rows whose names slugify identically would collide on
                    // the unique index — caught here with a useful message.
                    if (isset($seen[$slug])) {
                        $skipped++;
                        $this->addImportError($errors, $rowNumber, "Duplicate of row {$seen[$slug]} in this file (slug: {$slug}).");

                        continue;
                    }

                    $seen[$slug] = $rowNumber;

                    $categoryKey = mb_strtolower($category);

                    if (! isset($knownCategories[$categoryKey])) {
                        $knownCategories[$categoryKey] = $category;
                        $newCategories[] = $category;
                    }

                    $payload = [
                        'name' => $name,
                        'slug' => $slug,
                        'category_name' => $category,
                        'type' => $type,
                        'product_type' => $productType,
                        'unit_short_name' => ($data['unit_short_name'] ?? '') !== '' ? $data['unit_short_name'] : null,
                        'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
                        'is_active' => $this->csvBool($data['is_active'] ?? ''),
                        'sort_order' => is_numeric($data['sort_order'] ?? '') ? (int) $data['sort_order'] : 0,
                        'product_guide' => $this->guideFromRow($data),
                    ];

                    if (isset($existing[$slug])) {
                        if ($duplicateMode !== 'update') {
                            $skipped++;

                            continue;
                        }

                        PlantLibrary::find($existing[$slug])?->fill($payload)->save();
                        $updated++;

                        continue;
                    }

                    $plant = PlantLibrary::create($payload);
                    $existing[$slug] = $plant->id;
                    $created++;
                }
            });
        } finally {
            fclose($handle);
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $dataRows,
            'new_categories' => $newCategories,
            'errors' => $errors,
        ]);
    }

    private function addImportError(array &$errors, int $rowNumber, string $message): void
    {
        if (count($errors) < self::MAX_REPORTED_ERRORS) {
            $errors[] = ['row' => $rowNumber, 'message' => $message];
        }
    }

    /**
     * Blank is_active means "not specified", which should follow the model
     * default of active — not silently deactivate the plant.
     */
    private function csvBool(string $value): bool
    {
        if (trim($value) === '') {
            return true;
        }

        return in_array(strtolower(trim($value)), ['1', 'yes', 'y', 'true', 'active'], true);
    }

    /**
     * Zip title1/value1 … title10/value10 into the same product_guide shape
     * buildProductGuide() produces from the create/edit form.
     */
    private function guideFromRow(array $data): ?array
    {
        $guide = [];

        for ($i = 1; $i <= self::GUIDE_PAIRS; $i++) {
            $title = trim((string) ($data["title{$i}"] ?? ''));
            $value = trim((string) ($data["value{$i}"] ?? ''));

            if ($title !== '' && $value !== '') {
                $guide[] = ['title' => $title, 'description' => $value];
            }
        }

        return $guide !== [] ? $guide : null;
    }

    /**
     * Centralized validation rules. $ignoreId excludes the current record
     * from the slug-uniqueness check when updating.
     */
    private function validatePlant(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'category_name'    => ['required', 'string', 'max:255'],
            'type'             => ['required', Rule::in(['single', 'variable'])],
            'product_type'     => ['required', Rule::in(['sellable', 'catalog'])],
            'unit_short_name'  => ['nullable', 'string', 'max:50'],
            'description'      => ['nullable', 'string'],
            'is_active'        => ['boolean'],
            'sort_order'       => ['nullable', 'integer'],
            'guide_title'          => ['nullable', 'array'],
            'guide_title.*'        => ['nullable', 'string', 'max:255'],
            'guide_description'    => ['nullable', 'array'],
            'guide_description.*'  => ['nullable', 'string'],
        ]);

        // Slug is derived, not user-entered — check for a collision up front
        // so we return a friendly validation error instead of a DB exception.
        $slug = Str::slug($validated['name']);
        $slugTaken = PlantLibrary::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($slugTaken) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => "A plant with a similar name already exists (slug: {$slug}). Please use a different name.",
            ]);
        }

        unset($validated['guide_title'], $validated['guide_description']);

        return $validated;
    }

    /**
     * Zip guide_title[] / guide_description[] pairs into the same
     * [{ "title": ..., "description": ... }] shape ProductWithSkuImporter
     * produces from title1/value1 … title16/value16 — incomplete pairs
     * (one side filled, other empty) are silently skipped, same as import.
     */
    private function buildProductGuide(Request $request): ?array
    {
        $titles       = $request->input('guide_title', []);
        $descriptions = $request->input('guide_description', []);
        $guide        = [];

        foreach ($titles as $i => $title) {
            $title       = trim((string) $title);
            $description = trim((string) ($descriptions[$i] ?? ''));

            if ($title !== '' && $description !== '') {
                $guide[] = ['title' => $title, 'description' => $description];
            }
        }

        return ! empty($guide) ? $guide : null;
    }

    private function deleteMediaFile(PlantLibraryMedia $media): void
    {
        if ($media->media_type === 'image' && $media->media_path) {
            Storage::disk('public')->delete($media->media_path);
        }
    }

    /**
     * Category-based storage folder, e.g. "Indoor Plants" -> plant-library/indoor_plants.
     * Same category name always resolves to the same folder — different
     * category names get their own new folder automatically.
     */
    private function categoryFolder(PlantLibrary $plantLibrary): string
    {
        $categorySlug = Str::slug($plantLibrary->category_name ?: 'uncategorized', '_');

        return 'plant-library/'.$categorySlug;
    }
}