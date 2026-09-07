<?php

namespace App\Services\Import;

use App\Models\Import;
use App\Models\ImportLog;
use App\Models\State;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * CSV bulk importer for Suppliers.
 *
 * Expected columns (only "name" is required):
 *   name, phone, email, gstin, pan, registration_type,
 *   address, city, state, pincode, credit_days, credit_limit, notes
 *
 * Duplicate detection:
 *   - FIRST check GSTIN.
 *   - If GSTIN is empty, fall back to phone.
 *   - If both are empty, allow create.
 */
class SupplierImporter
{
    /**
     * In-file duplicate key follows the same priority as the DB-level check:
     * GSTIN first, phone second.
     */
    public function extractUniqueKey(array $row): ?string
    {
        $gstin = strtoupper($this->normalize($row['gstin'] ?? ''));
        if ($gstin !== '') {
            return 'gstin:'.$gstin;
        }

        $phone = $this->normalize($row['phone'] ?? '');
        if ($phone !== '') {
            return 'phone:'.$phone;
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array{success: int, failed: int, skipped: int, created: int, updated: int}
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

        $states = State::query()
            ->where('is_active', true)
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['_row_number'] ?? ($startRow + $index));

            try {
                $errors = $this->validateRow($row, $states);

                if (! empty($errors)) {
                    $this->logError($import, $rowNumber, $row, implode(' ', $errors));
                    $failed++;

                    continue;
                }

                $rowSkipped = false;
                $rowAction = null;

                try {
                    DB::transaction(function () use ($row, $companyId, $importMode, $isDryRun, $states, &$rowSkipped, &$rowAction) {
                        $gstin = strtoupper($this->normalize($row['gstin'] ?? ''));
                        $phone = $this->normalize($row['phone'] ?? '');

                        // DB-level duplicate: GSTIN first, phone fallback.
                        $existing = null;
                        if ($gstin !== '') {
                            $existing = Supplier::withoutGlobalScopes()
                                ->where('company_id', $companyId)
                                ->where('gstin', $gstin)
                                ->first();
                        } elseif ($phone !== '') {
                            $existing = Supplier::withoutGlobalScopes()
                                ->where('company_id', $companyId)
                                ->where('phone', $phone)
                                ->first();
                        }

                        $data = $this->mapRowToData($row, $states);

                        if ($existing) {
                            if ($importMode === 'create_only') {
                                $rowSkipped = true;

                                return;
                            }
                            
                            $existing->update($data);
                            $rowAction = 'updated';
                        } else {
                            if ($importMode === 'update_only') {
                                $rowSkipped = true;

                                return;
                            }

                            $supplier = new Supplier;
                            $supplier->company_id = $companyId;                            
                            $supplier->fill($data);
                            $supplier->is_active = true;
                            $supplier->save();

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
                $this->logError($import, $rowNumber, $row, 'Something went wrong with this row. Please check the values and try again.');
                $failed++;
            }
        }

        return compact('success', 'failed', 'skipped', 'created', 'updated');
    }

    private function mapRowToData(array $row, array $states): array
    {
        $data = [
            'name' => trim($row['name'] ?? ''),
        ];

        $simpleFields = [
            'phone' => 'phone',
            'email' => 'email',
            'pan' => 'pan',
            'registration_type' => 'registration_type',
            'address' => 'address',
            'city' => 'city',
            'pincode' => 'pincode',
            'notes' => 'notes',
        ];

        foreach ($simpleFields as $csv => $col) {
            $value = trim($row[$csv] ?? '');
            if ($value !== '') {
                $data[$col] = $value;
            }
        }

        // GSTIN / PAN — normalize to uppercase (GST/PAN rules).
        $gstin = strtoupper($this->normalize($row['gstin'] ?? ''));
        if ($gstin !== '') {
            $data['gstin'] = $gstin;
        }
        if (isset($data['pan'])) {
            $data['pan'] = strtoupper($data['pan']);
        }
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        // Numeric fields — skip when blank so defaults apply.
        $creditDays = trim($row['credit_days'] ?? '');
        if ($creditDays !== '' && is_numeric($creditDays)) {
            $data['credit_days'] = (int) $creditDays;
        }

        $creditLimit = trim($row['credit_limit'] ?? '');
        if ($creditLimit !== '' && is_numeric($creditLimit)) {
            $data['credit_limit'] = (float) $creditLimit;
        }

        $stateName = trim($row['state'] ?? '');
        if ($stateName !== '') {
            $key = strtolower($stateName);
            if (isset($states[$key])) {
                $data['state_id'] = $states[$key];
            }
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function validateRow(array $row, array $states): array
    {
        $errors = [];

        if (trim($row['name'] ?? '') === '') {
            $errors[] = 'Name is required. Please fill it in.';
        }

        $stateName = trim($row['state'] ?? '');
        if ($stateName !== '' && ! isset($states[strtolower($stateName)])) {
            $errors[] = "State '{$stateName}' not found. Please correct the spelling.";
        }

        return $errors;
    }

    private function normalize(?string $value): string
    {
        return trim((string) $value);
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
