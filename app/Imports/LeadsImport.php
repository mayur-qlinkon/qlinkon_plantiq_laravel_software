<?php

namespace App\Imports;

use App\Models\CrmActivity;
use App\Models\CrmLead;
use App\Models\CrmLeadSource;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\CrmTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Throwable;

class LeadsImport implements SkipsEmptyRows, SkipsOnError, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    // ── Result tracking ──
    public int $imported = 0;

    public int $skipped = 0;

    public int $duplicates = 0;

    public array $errors = [];

    public array $skippedRows = [];

    private int $companyId;

    private ?int $pipelineId;

    private ?int $stageId;
    
    private ?int $assignedTo; // 🌟 Add this property

    private array $sourceMap = [];

    private array $tagMap = [];

    public function __construct(
        int $companyId,
        ?int $pipelineId = null,
        ?int $stageId = null,
        ?int $assignedTo = null // 🌟 Accept it in constructor
    ) {
        $this->companyId = $companyId;
        $this->pipelineId = $pipelineId;
        $this->stageId = $stageId;
        $this->assignedTo = $assignedTo; // 🌟 Assign it

        // ── Pre-load source and tag maps for fast lookup ──
        CrmLeadSource::where('company_id', $companyId)
            ->get(['id', 'name'])
            ->each(fn ($s) => $this->sourceMap[strtolower($s->name)] = $s->id);

        CrmTag::where('company_id', $companyId)
            ->get(['id', 'name'])
            ->each(fn ($t) => $this->tagMap[strtolower($t->name)] = $t->id);
    }

    // ════════════════════════════════════════════════════
    //  COLLECTION HANDLER — called per chunk
    // ════════════════════════════════════════════════════

    public function collection(Collection $rows): void
    {
        // Resolve pipeline/stage once for the whole import
        $pipelineId = $this->pipelineId ?? $this->resolveDefaultPipeline();
        $stageId = $this->stageId ?? $this->resolveFirstStage($pipelineId);

        if (! $pipelineId || ! $stageId) {
            $this->errors[] = 'No active pipeline or stage found. Set up your CRM pipeline first.';

            return;
        }

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because row 1 is heading

            try {
                $name = trim($row['name'] ?? $row['full_name'] ?? '');
                $phone = trim($row['phone'] ?? $row['mobile'] ?? '');
                $email = trim($row['email'] ?? '');

                // ── Skip if name is empty ──
                if (empty($name)) {
                    $this->skipped++;
                    $this->skippedRows[] = "Row {$rowNum}: Name is empty — skipped.";

                    continue;
                }

                // ── Duplicate detection by phone ──
                if ($phone) {
                    $exists = CrmLead::where('company_id', $this->companyId)
                        ->where('phone', $phone)
                        ->exists();

                    if ($exists) {
                        $this->duplicates++;
                        $this->skippedRows[] = "Row {$rowNum}: {$name} ({$phone}) — duplicate phone, skipped.";

                        continue;
                    }
                }

                // ── Duplicate detection by email ──
                if ($email && ! $phone) {
                    $exists = CrmLead::where('company_id', $this->companyId)
                        ->where('email', $email)
                        ->exists();

                    if ($exists) {
                        $this->duplicates++;
                        $this->skippedRows[] = "Row {$rowNum}: {$name} ({$email}) — duplicate email, skipped.";

                        continue;
                    }
                }

                // ── Resolve source by name ──
                $sourceId = null;
                $sourceName = strtolower(trim($row['source'] ?? $row['lead_source'] ?? ''));
                if ($sourceName) {
                    $sourceId = $this->sourceMap[$sourceName] ?? null;
                }

                // ── Resolve priority ──
                $priority = in_array(
                    strtolower($row['priority'] ?? ''),
                    ['low', 'medium', 'high', 'hot']
                ) ? strtolower($row['priority']) : 'medium';

                // ── Create lead ──
                $lead = CrmLead::create([
                    'company_id' => $this->companyId,
                    'crm_pipeline_id' => $pipelineId,
                    'crm_stage_id' => $stageId,
                    'crm_lead_source_id' => $sourceId,
                    'name' => $name,
                    'phone' => $phone ?: null,
                    'email' => $email ?: null,
                    'company_name' => trim($row['company'] ?? $row['company_name'] ?? '') ?: null,
                    'address' => trim($row['address'] ?? '') ?: null,
                    'city' => trim($row['city'] ?? '') ?: null,
                    'state' => trim($row['state'] ?? '') ?: null,
                    'zip_code' => trim($row['pin'] ?? $row['zip_code'] ?? $row['pincode'] ?? '') ?: null,
                    'country' => trim($row['country'] ?? 'India') ?: 'India',
                    'instagram_id' => trim($row['instagram'] ?? $row['instagram_id'] ?? '') ?: null,
                    'website' => trim($row['website'] ?? '') ?: null,
                    'priority' => $priority,
                    'lead_value' => is_numeric($row['value'] ?? $row['lead_value'] ?? '') ? $row['value'] ?? $row['lead_value'] : null,
                    'description' => trim($row['notes'] ?? $row['description'] ?? '') ?: null,
                    'created_by' => Auth::id(),
                ]);

                // 🌟 NEW: Attach the assigned user immediately after lead creation
                if ($this->assignedTo) {
                    // Goes through assignTo() rather than the pivot directly, or
                    // imported leads land with no assignment history and never
                    // appear in anyone's performance report.
                    $lead->assignTo($this->assignedTo);
                }

                // ── Sync tags if provided ──
                $tagsRaw = trim($row['tags'] ?? '');
                if ($tagsRaw) {
                    $tagIds = [];
                    foreach (explode(',', $tagsRaw) as $tagName) {
                        $tagName = strtolower(trim($tagName));
                        if (isset($this->tagMap[$tagName])) {
                            $tagIds[] = $this->tagMap[$tagName];
                        } else {
                            // Create tag on the fly
                            $newTag = CrmTag::firstOrCreate(
                                ['company_id' => $this->companyId, 'name' => ucfirst($tagName)],
                                ['color' => '#6b7280']
                            );
                            $tagIds[] = $newTag->id;
                            $this->tagMap[$tagName] = $newTag->id;
                        }
                    }
                    $lead->tags()->sync($tagIds);
                }

                // ── Activity log ──
                CrmActivity::logAuto(
                    leadId: $lead->id,
                    type: 'lead_created',
                    description: 'Lead imported via CSV/Excel',
                    meta: ['imported_by' => Auth::id()],
                    companyId: $this->companyId
                );

                $this->imported++;

            } catch (Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNum}: ".$e->getMessage();
                Log::error('[LeadsImport] Row failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════
    //  PRE-VALIDATION FORMATTING (The Excel Trap Fix)
    // ════════════════════════════════════════════════════
    public function prepareForValidation($data, $index)
    {
        // Force Excel numbers back into strings so Laravel validation passes
        if (isset($data['phone'])) {
            $data['phone'] = (string) $data['phone'];
        }

        // Do the same for 'mobile' since you use it as a fallback in your collection
        if (isset($data['mobile'])) {
            $data['mobile'] = (string) $data['mobile'];
        }

        // Do the same for 'value' and 'pin' just to be safe from scientific notation limits
        if (isset($data['value'])) {
            $data['value'] = (string) $data['value'];
        }

        if (isset($data['pin'])) {
            $data['pin'] = (string) $data['pin'];
        }

        return $data;
    }

    // ════════════════════════════════════════════════════
    //  VALIDATION RULES — applied before collection()
    // ════════════════════════════════════════════════════

    public function rules(): array
    {
        return [
            // Only name is required — everything else optional
            '*.name' => ['nullable', 'string', 'max:150'],
            '*.full_name' => ['nullable', 'string', 'max:150'],
            '*.phone' => ['nullable', 'string', 'max:20'],
            '*.email' => ['nullable', 'email', 'max:150'],
            '*.website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.email.email' => 'Row :attribute: Invalid email address.',
            '*.website.url' => 'Row :attribute: Website must be a valid URL.',
        ];
    }

    // ════════════════════════════════════════════════════
    //  SkipsOnError — never abort entire import on one bad row
    // ════════════════════════════════════════════════════

    public function onError(Throwable $e): void
    {
        $this->skipped++;
        $this->errors[] = $e->getMessage();
        Log::warning('[LeadsImport] Row skipped due to error', ['error' => $e->getMessage()]);
    }

    // ════════════════════════════════════════════════════
    //  PERFORMANCE — batch + chunk settings
    // ════════════════════════════════════════════════════

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    // ════════════════════════════════════════════════════
    //  RESULT SUMMARY
    // ════════════════════════════════════════════════════

    public function getResult(): array
    {
        return [
            'imported' => $this->imported,
            'duplicates' => $this->duplicates,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'skipped_rows' => $this->skippedRows,
            'total' => $this->imported + $this->skipped + $this->duplicates,
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Pipeline/Stage resolution
    // ════════════════════════════════════════════════════

    private function resolveDefaultPipeline(): ?int
    {
        return CrmPipeline::where('company_id', $this->companyId)
            ->where('is_default', true)->where('is_active', true)->value('id')
            ?? CrmPipeline::where('company_id', $this->companyId)
                ->where('is_active', true)->orderBy('sort_order')->value('id');
    }

    private function resolveFirstStage(?int $pipelineId): ?int
    {
        if (! $pipelineId) {
            return null;
        }

        return CrmStage::where('crm_pipeline_id', $pipelineId)
            ->where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('is_won', false)
            ->where('is_lost', false)
            ->orderBy('sort_order')
            ->value('id');
    }
}
