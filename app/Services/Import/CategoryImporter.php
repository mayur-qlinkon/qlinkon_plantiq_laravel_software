<?php

namespace App\Services\Import;

use App\Models\Category;
use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryImporter
{
    /**
     * Extract the unique key used to detect in-file duplicates for a row.
     * Returns null for rows without a usable key (those will be handled by normal validation).
     */
    public function extractUniqueKey(array $row): ?string
    {
        $slug = trim($row['slug'] ?? '');
        if ($slug !== '') {
            return Str::slug($slug);
        }

        $name = trim($row['name'] ?? '');
        if ($name === '') {
            return null;
        }

        return Str::slug($name);
    }

    /**
     * Process a chunk of CSV rows for the given import.
     *
     * @param  array<int, array<string, string>>  $rows  Associative rows (header => value)
     * @param  int  $startRow  The 1-based row number of the first row in this chunk (for error reporting)
     * @return array{success: int, failed: int, skipped: int}
     */
    public function processChunk(Import $import, array $rows, int $startRow, int $companyId): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;
        $created = 0;
        $updated = 0;
        $importMode = $import->import_mode ?? 'create_or_update';
        $isDryRun = (bool) ($import->is_dry_run ?? false);

        // Pre-load existing categories for this company (slug => id) for parent lookup
        $existingSlugs = Category::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('id', 'slug')
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['_row_number'] ?? ($startRow + $index));

            try {
                $errors = $this->validateRow($row, $existingSlugs, $companyId);

                if (! empty($errors)) {
                    $this->logError($import, $rowNumber, $row, implode('; ', $errors));
                    $failed++;

                    continue;
                }

                $rowSkipped = false;
                $skipReason = null;
                $rowAction = null; // 'created' | 'updated'

                try {
                    DB::transaction(function () use ($row, $companyId, $importMode, $isDryRun, &$existingSlugs, &$rowSkipped, &$skipReason, &$rowAction) {
                        $slug = ! empty($row['slug'])
                            ? Str::slug(trim($row['slug']))
                            : Str::slug(trim($row['name']));                        

                        $existing = Category::withoutGlobalScopes()
                            ->where('company_id', $companyId)
                            ->where('slug', $slug)
                            ->first();

                        if ($existing) {
                            if ($importMode === 'create_only') {
                                $rowSkipped = true;
                                $skipReason = "Duplicate: a category with slug '{$slug}' already exists. This import only creates new records, so the existing one was left unchanged.";

                                return;
                            }
                            // Update only name and parent
                            $existing->update([
                                'name' => trim($row['name']),                                
                            ]);
                            $rowAction = 'updated';
                        } else {
                            if ($importMode === 'update_only') {
                                $rowSkipped = true;
                                $skipReason = "Not found: no category with slug '{$slug}' exists to update. This import only updates existing records.";

                                return;
                            }
                            $category = new Category;
                            $category->company_id = $companyId;
                            $category->name = trim($row['name']);
                            $category->slug = $slug;                            
                            $category->is_active = true;
                            $category->save();

                            // Update cache so subsequent rows in this chunk can reference it
                            // (Even in dry-run, this lets subsequent rows validate against would-be-created records.)
                            $existingSlugs[$slug] = $category->id;
                            $rowAction = 'created';
                        }

                        if ($isDryRun && $rowAction !== null) {
                            throw DryRunRollback::for($rowAction);
                        }
                    });
                } catch (DryRunRollback $e) {
                    // Expected — DB rolled back; counters still reflect what would happen.
                    $rowAction = $e->action;
                }

                if ($rowSkipped) {
                    // Skipped rows were counted but never logged, so the error
                    // report showed a count in its badge and nothing inside it.
                    $this->logError($import, $rowNumber, $row, $skipReason ?? 'Skipped.');
                    $skipped++;
                } else {
                    $success++;
                    if ($rowAction === 'created') {
                        $created++;
                    } elseif ($rowAction === 'updated') {
                        $updated++;
                    }
                }
            } catch (\Throwable $e) {
                $this->logError($import, $rowNumber, $row, 'Unexpected error: '.$e->getMessage());
                $failed++;
            }
        }

        return compact('success', 'failed', 'skipped', 'created', 'updated');
    }

    /**
     * Validate a single row. Returns array of error messages (empty = valid).
     */
    private function validateRow(array $row, array $existingSlugs, int $companyId): array
    {
        $errors = [];

        // Required: name
        if (empty(trim($row['name'] ?? ''))) {
            $errors[] = 'Name is required';
        }       

        return $errors;
    }

    private function logError(Import $import, int $rowNumber, array $rowData, string $message): void
    {
        ImportLog::create([
            'import_id' => $import->id,
            'row_number' => $rowNumber,
            'row_data' => array_filter($rowData, fn ($v, $k) => ! str_starts_with((string) $k, '_'), ARRAY_FILTER_USE_BOTH),
            'error_message' => $message,
        ]);
    }
}
