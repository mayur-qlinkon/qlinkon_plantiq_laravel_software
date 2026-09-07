<?php

namespace App\Exports;

use App\Models\CrmLead;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    private array $filters;

    private int $companyId;

    public function __construct(int $companyId, array $filters = [])
    {
        $this->companyId = $companyId;
        $this->filters = $filters;
    }

    // ════════════════════════════════════════════════════
    //  QUERY — applies same filters as leads index
    // ════════════════════════════════════════════════════

    public function query(): Builder
    {
        // Same visibility rule as the leads list and dashboard. Without it the
        // export was the widest hole in the module: a rep restricted to four
        // leads on screen could still download the entire company pipeline as
        // a spreadsheet.
        $query = CrmLead::where('company_id', $this->companyId)
            ->visibleToCurrentUser()
            ->with([
                'stage:id,name',
                'pipeline:id,name',
                'source:id,name',
                'tags:id,name',
                'assignees:id,name',
            ])
            ->orderBy('created_at', 'desc');

        $f = $this->filters;

        if (! empty($f['q'])) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$f['q']}%")
                ->orWhere('phone', 'like', "%{$f['q']}%")
                ->orWhere('email', 'like', "%{$f['q']}%")
            );
        }

        if (! empty($f['pipeline_id'])) {
            $query->where('crm_pipeline_id', $f['pipeline_id']);
        }
        if (! empty($f['stage_id'])) {
            $query->where('crm_stage_id', $f['stage_id']);
        }
        if (! empty($f['priority'])) {
            $query->where('priority', $f['priority']);
        }
        if (! empty($f['source_id'])) {
            $query->where('crm_lead_source_id', $f['source_id']);
        }
        if (! empty($f['from'])) {
            $query->whereDate('created_at', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $query->whereDate('created_at', '<=', $f['to']);
        }

        if (isset($f['converted'])) {
            $f['converted']
                ? $query->where('is_converted', true)
                : $query->where('is_converted', false);
        }

        if (! empty($f['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('crm_tags.id', $f['tag_id'])
            );
        }

        return $query;
    }

    // ════════════════════════════════════════════════════
    //  HEADINGS — column headers in Excel
    // ════════════════════════════════════════════════════

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'Company',
            'Pipeline',
            'Stage',
            'Source',
            'Priority',
            'Score',
            'Lead Value (₹)',
            'Tags',
            'Assigned To',
            'City',
            'State',
            'Country',
            'Address',
            'Instagram',
            'Website',
            'Is Converted',
            'Converted At',
            'Last Contacted',
            'Next Follow-up',
            'Notes',
            'Created At',
        ];
    }

    // ════════════════════════════════════════════════════
    //  MAP — transform each lead to row array
    // ════════════════════════════════════════════════════

    public function map($lead): array
    {
        return [
            $lead->name,
            $lead->phone,
            $lead->email,
            $lead->company_name,
            $lead->pipeline?->name,
            $lead->stage?->name,
            $lead->source?->name,
            ucfirst($lead->priority),
            $lead->score,
            $lead->lead_value,
            $lead->tags->pluck('name')->implode(', '),
            $lead->assignees->pluck('name')->implode(', '),
            $lead->city,
            $lead->state,
            $lead->country,
            $lead->address,
            $lead->instagram_id,
            $lead->website,
            $lead->is_converted ? 'Yes' : 'No',
            $lead->converted_at?->format('d M Y'),
            $lead->last_contacted_at?->format('d M Y'),
            $lead->next_followup_at?->format('d M Y'),
            $lead->description,
            $lead->created_at->format('d M Y'),
        ];
    }

    // ════════════════════════════════════════════════════
    //  STYLES — header row bold + colored
    // ════════════════════════════════════════════════════

    // public function styles(Worksheet $sheet): array
    // {
    //     return [
    //         1 => [
    //             'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    //             'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e293b']],
    //             'alignment' => ['horizontal' => 'center'],
    //         ],
    //     ];
    // }
}
