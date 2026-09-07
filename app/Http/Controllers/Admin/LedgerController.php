<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Admin\LedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LedgerController extends Controller
{
    public function __construct(protected LedgerService $ledgerService) {}

    // -------------------------------------------------------------------------
    // INDEX — Customer list with balance summary
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $storeIds  = auth_store_ids(); // 🛡️ Fetch authorized stores
        $filters   = $request->only(['search', 'store_id']);

        // 🛡️ Security Guard: Prevent staff from viewing stores they don't have access to
        if (!empty($filters['store_id']) && $storeIds !== null && !in_array($filters['store_id'], $storeIds)) {
            abort(403, 'Access denied to this store ledger.');
        }

        $stores    = auth_stores()->get();
        
        // 🌟 Pass $storeIds to the service so it can filter invoices/payments correctly
        $customers = $this->ledgerService->getCustomerSummaryList($filters, $storeIds);

        // Single query for paid totals — no N+1
        $paidTotals = $this->ledgerService->getPaidTotalsForCustomers(
            $customers->pluck('id'),
            $storeIds // 🌟 Pass to paid totals too
        );

        return view('admin.invoices.ledger', compact('customers', 'paidTotals', 'stores', 'filters'));
    }

    // -------------------------------------------------------------------------
    // SHOW — Individual customer ledger (HTML + PDF + Excel)
    // -------------------------------------------------------------------------

    public function show(Request $request, Client $client)
    {
        // Multi-store + company tenant safety
        abort_if($client->company_id !== Auth::user()->company_id, 403);

        $storeIds = active_store_ids(); // 🛡️ Fetch authorized stores
        $filters = $request->only(['store_id', 'from_date', 'to_date']);

        // 🛡️ Security Guard: Prevent staff from viewing stores they don't have access to
        if (!empty($filters['store_id']) && $storeIds !== null && !in_array($filters['store_id'], $storeIds)) {
            abort(403, 'Access denied to this store ledger.');
        }

        $stores  = auth_stores()->get();

        // 🌟 Pass $storeIds to the service
        ['entries' => $entries, 'summary' => $summary] = $this->ledgerService->buildLedger($client, $filters, $storeIds);

        // ── PDF Export ──
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('admin.invoices.ledger-pdf', compact('client', 'entries', 'summary', 'filters'))
                ->setPaper('A4', 'portrait')
                ->setOption(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $filename = 'Ledger-'.str_replace([' ', '/'], '-', $client->name).'.pdf';

            return $pdf->download($filename);
        }

        // ── Excel Export ──
        if ($request->get('export') === 'excel') {
            // Check if maatwebsite/excel is installed; if not, return a graceful error
            if (! class_exists(Excel::class)) {
                return back()->with('error', 'Excel export requires the maatwebsite/excel package. Run: composer require maatwebsite/excel');
            }

            $filename = 'Ledger-'.str_replace([' ', '/'], '-', $client->name).'.xlsx';

            return Excel::download(
                new \App\Exports\CustomerLedgerExport($client, $entries, $summary),
                $filename
            );
        }

        return view('admin.invoices.ledger-show', compact('client', 'entries', 'summary', 'stores', 'filters'));
    }
}