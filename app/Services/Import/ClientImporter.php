<?php

namespace App\Services\Import;

use App\Models\Client;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\State;
use Illuminate\Support\Facades\DB;

/**
 * CSV bulk importer for Clients.
 *
 * Expected columns (only "name" is required — all other columns are optional):
 *   name, company_name, phone, email, gst_number, registration_type,
 *   address, city, state, zip_code, country, notes
 *
 * Duplicate detection:
 *   - DB level: matched if phone OR email matches any existing client.
 *   - In-file:  uses the first-filled of (phone, email) as a coarse key —
 *               the authoritative duplicate gate is the DB-level check below.
 */
class ClientImporter
{
    /**
     * Build an in-file duplicate key. The DB-level check in processChunk()
     * remains the source of truth — this key only catches the common case
     * of the same phone/email appearing twice inside one CSV.
     */
    public function extractUniqueKey(array $row): ?string
    {
        $phone = $this->normalize($row['phone'] ?? '');
        $email = $this->normalize($row['email'] ?? '');

        if ($phone !== '') {
            return 'phone:'.$phone;
        }

        if ($email !== '') {
            return 'email:'.strtolower($email);
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

        // Pre-load states as [lowercase_name => id] for fast case-insensitive lookup.
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
                        $phone = $this->normalize($row['phone'] ?? '');
                        $email = $this->normalize($row['email'] ?? '');

                        // DB-level duplicate check: match on phone OR email.
                        $existing = null;
                        if ($phone !== '' || $email !== '') {
                            $existing = Client::withoutGlobalScopes()
                                ->where('company_id', $companyId)
                                ->where(function ($q) use ($phone, $email) {
                                    if ($phone !== '') {
                                        $q->orWhere('phone', $phone);
                                    }
                                    if ($email !== '') {
                                        $q->orWhere('email', $email);
                                    }
                                })
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

                            $client = new Client;
                            $client->company_id = $companyId;                            
                            $client->fill($data);
                            $client->is_active = true;
                            $client->save();

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

    /**
     * Map a CSV row to a model-fillable array. Blank fields are skipped so
     * they don't overwrite existing values on updates.
     */
    private function mapRowToData(array $row, array $states): array
    {
        $data = [
            'name' => trim($row['name'] ?? ''),
        ];

        $simpleFields = [
            'company_name' => 'company_name',
            'phone' => 'phone',
            'email' => 'email',
            'gst_number' => 'gst_number',
            'registration_type' => 'registration_type',
            'address' => 'address',
            'city' => 'city',
            'zip_code' => 'zip_code',
            'country' => 'country',
            'notes' => 'notes',
        ];

        foreach ($simpleFields as $csv => $col) {
            $value = trim($row[$csv] ?? '');
            if ($value !== '') {
                $data[$col] = $value;
            }
        }

        // Normalize email for consistency.
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        // Normalize registration_type to match invoices.gst_treatment enum.
        if (isset($data['registration_type'])) {
            $rt = strtolower(trim($data['registration_type']));
            if ($rt === 'regular') {
                $rt = 'registered';
            }
            $allowed = ['registered', 'unregistered', 'composition', 'overseas', 'sez'];
            $data['registration_type'] = in_array($rt, $allowed, true) ? $rt : 'unregistered';
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
