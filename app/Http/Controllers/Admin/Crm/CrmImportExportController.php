<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Exports\LeadsExport;
use App\Exports\LeadsSampleTemplate;
use App\Http\Controllers\Controller;
use App\Imports\LeadsImport;
use App\Models\CrmPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Throwable;

class CrmImportExportController extends Controller
{
    // ════════════════════════════════════════════════════
    //  IMPORT PAGE
    //  GET /admin/crm/leads/import
    // ════════════════════════════════════════════════════

    public function importPage()
    {
        $companyId = Auth::user()->company_id;

        $pipelines = CrmPipeline::query()
            ->active()
            ->ordered()
            ->with(['stages' => fn ($q) => $q->active()->ordered()])
            ->get();

        // 🌟 Fetch users for the quick-assign dropdown
        $users = \App\Models\User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.crm.leads.import', compact('pipelines', 'users'));
    }

    // ════════════════════════════════════════════════════
    //  PROCESS IMPORT
    //  POST /admin/crm/leads/import
    // ════════════════════════════════════════════════════

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // 10MB limit
            'pipeline_id' => ['nullable', 'exists:crm_pipelines,id'],
            'stage_id' => ['nullable', 'exists:crm_stages,id'],
            'assigned_to' => ['nullable', 'exists:users,id'], // 🌟 Validate assignee
        ]);

        $companyId = Auth::user()->company_id;

        // ── Validate pipeline belongs to company ──
        if ($request->pipeline_id) {
            $belongs = CrmPipeline::where('id', $request->pipeline_id)
                ->where('company_id', $companyId)
                ->exists();

            if (! $belongs) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected pipeline does not belong to your company.',
                ], 422);
            }
        }

        try {
            // 🌟 1. Instantiate ONCE with the assigned_to parameter
            $importer = new LeadsImport(
                $companyId, 
                $request->pipeline_id, 
                $request->stage_id, 
                $request->assigned_to
            );

            // 🌟 2. Pass the EXACT SAME instance to Excel so getResult() works
            Excel::import(
                $importer,
                $request->file('file')
            );

            $result = $importer->getResult();

            Log::info('[CrmImport] Import completed', [
                'company_id' => $companyId,
                'by' => Auth::id(),
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'duplicates' => $result['duplicates'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Import complete — {$result['imported']} lead(s) imported.",
                'result' => $result,
            ]);

        } catch (ValidationException $e) {
            // Laravel Excel validation errors (from rules())
            $failures = collect($e->failures())->map(fn ($f) => "Row {$f->row()}: ".implode(', ', $f->errors())
            )->toArray();

            return response()->json([
                'success' => false,
                'message' => 'File has validation errors.',
                'errors' => $failures,
            ], 422);

        } catch (Throwable $e) {
            Log::error('[CrmImport] Import failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  EXPORT
    //  GET /admin/crm/leads/export
    // ════════════════════════════════════════════════════

    public function export(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $filters = $request->only([
            'q', 'pipeline_id', 'stage_id', 'priority',
            'source_id', 'tag_id', 'converted',
            'from', 'to',
        ]);

        $filename = 'crm-leads-'.now()->format('Y-m-d').'.xlsx';

        Log::info('[CrmExport] Export triggered', [
            'company_id' => $companyId,
            'by' => Auth::id(),
            'filters' => $filters,
        ]);

        return Excel::download(
            new LeadsExport($companyId, $filters),
            $filename
        );
    }

    // ════════════════════════════════════════════════════
    //  SAMPLE TEMPLATE DOWNLOAD
    //  GET /admin/crm/leads/import/template
    // ════════════════════════════════════════════════════

    public function template()
    {
        return Excel::download(
            new LeadsSampleTemplate,
            'crm-leads-import-template.xlsx'
        );
    }
}
