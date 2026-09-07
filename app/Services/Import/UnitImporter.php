<?php

namespace App\Services\Import;

use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitImporter
{


    /**
     * Extract the unique key used to detect in-file duplicates for a row.
     * Units use short_name (case-insensitive) as the natural key within a company.
     */
    public function extractUniqueKey(array $row): ?string
    {
        $shortName = trim($row['short_name'] ?? '');
        if ($shortName === '') {
            return null;
        }

        return strtolower($shortName);
    }

    /**
     * Process a chunk of CSV rows for the given import.
     *
     * @param  array<int, array<string, string>>  $rows  Associative rows (header => value)
     * @param  int  $startRow  1-based row number of the first row in this chunk
     * @return array{success: int, failed: int}
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

        // Pre-load existing units for duplicate detection (short_name => id)
        $existingByShortName = Unit::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('short_name')
            ->pluck('id', 'short_name')
            ->toArray();

        // Also keep name-based lookup for updates
        $existingByName = Unit::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('id', 'name')
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['_row_number'] ?? ($startRow + $index));

            try {
                $errors = $this->validateRow($row, $existingByShortName, $existingByName, $companyId);

                if (! empty($errors)) {
                    $this->logError($import, $rowNumber, $row, implode('; ', $errors));
                    $failed++;

                    continue;
                }

                $rowSkipped = false;
                $rowAction = null;

                try {
                    DB::transaction(function () use ($row, $companyId, $importMode, $isDryRun, &$existingByShortName, &$existingByName, &$rowSkipped, &$rowAction) {
                        $name = trim($row['name']);
                        $shortName = trim($row['short_name']);

                        // Match by short_name first (unique per company), then by name
                        $existing = Unit::withoutGlobalScopes()
                            ->where('company_id', $companyId)
                            ->where('short_name', $shortName)
                            ->first();

                        if ($existing) {
                            if ($importMode === 'create_only') {
                                $rowSkipped = true;

                                return;
                            }
                            // Update only the name
                            $existing->update([
                                'name' => $name,
                            ]);
                            $rowAction = 'updated';
                        } else {
                            if ($importMode === 'update_only') {
                                $rowSkipped = true;

                                return;
                            }
                            $unit = new Unit;
                            $unit->company_id = $companyId;
                            $unit->name = $name;
                            $unit->short_name = $shortName;
                            $unit->is_active = true;
                            $unit->save();

                            // Update caches
                            $existingByShortName[$shortName] = $unit->id;
                            $existingByName[$name] = $unit->id;
                            $rowAction = 'created';
                        }

                        if ($isDryRun && $rowAction !== null) {
                            throw DryRunRollback::for($rowAction);
                        }
                    });
                } catch (DryRunRollback $e) {
                    $rowAction = $e->action;
                }

                if ($rowSkipped) {
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
    private function validateRow(array $row, array $existingByShortName, array $existingByName, int $companyId): array
    {
        $errors = [];

        if (empty(trim($row['name'] ?? ''))) {
            $errors[] = 'Name is required';
        }

        if (empty(trim($row['short_name'] ?? ''))) {
            $errors[] = 'Short name is required';
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
