<?php

namespace App\Services\Admin;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class LedgerService
{
    // -------------------------------------------------------------------------
    // PUBLIC: Customer List Summary (for Ledger Index)
    // -------------------------------------------------------------------------

    /**
     * Returns a paginated list of customers with their financial summary.
     *
     * @param  array  $filters 
     * @param  array|null  $storeIds
     * @param  int    $perPage
     */
    public function getCustomerSummaryList(array $filters = [], ?array $storeIds = null, int $perPage = 15)
    {
        // Safely extract store_id for transactional relationship filtering
        $storeId = $filters['store_id'] ?? null;

        // 🛡️ Backend guard: if user picked a specific store from UI, validate it's within active stores
        // Otherwise fall back to all active_store_ids() (already passed from controller)
        $effectiveStoreIds = $storeId ? [$storeId] : ($storeIds ?? []);

        return Client::query()
            ->where('is_active', true)
            // 🛡️ Global Clients: Do not filter the actual client record by store
            ->when(! empty($filters['search']), fn ($q) => $q->where(function ($inner) use ($filters) {
                $inner->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('phone', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%")
                    ->orWhere('company_name', 'like', "%{$filters['search']}%");
            }))
            // 🛡️ Store Scoping: Backend guard always applied — frontend filter just narrows further
            ->withCount([
                'invoices as invoices_count' => function ($q) use ($effectiveStoreIds) {
                    $q->where('status', '!=', 'cancelled')
                      ->when(! empty($effectiveStoreIds), fn ($sq) => $sq->whereIn('store_id', $effectiveStoreIds));
                }
            ])
            ->withSum([
                'invoices as total_invoiced' => function ($q) use ($effectiveStoreIds) {
                    $q->where('status', '!=', 'cancelled')
                      ->when(! empty($effectiveStoreIds), fn ($sq) => $sq->whereIn('store_id', $effectiveStoreIds));
                }
            ], 'grand_total') // ✅ Second argument correctly placed outside the array
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    // -------------------------------------------------------------------------
    // PUBLIC: Paid totals map for the index page  (customer_id → paid_amount)
    // -------------------------------------------------------------------------

    /**
     * Returns a keyed collection: [ customer_id => total_paid ]
     * Pulled in ONE query — zero N+1 on the index page.
     *
     * @param  \Illuminate\Support\Collection  $customerIds
     * @return \Illuminate\Support\Collection
     */
    public function getPaidTotalsForCustomers(Collection $customerIds, ?array $storeIds = null): Collection
    {
        return Invoice::whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->when(! empty($storeIds), fn ($q) => $q->whereIn('store_id', $storeIds)) // 🛡️ Store filter
            ->with(['payments' => fn ($q) => $q->where('status', 'completed')])
            ->get()
            ->groupBy('customer_id')
            ->map(fn ($invoices) => $invoices->flatMap->payments->sum('amount'));
    }

    // -------------------------------------------------------------------------
    // PUBLIC: Single Customer Ledger
    // -------------------------------------------------------------------------

    /**
     * Build the full ledger for one customer.
     * Returns entries (sorted chronologically) + financial summary.
     *
     * @param  Client  $client
     * @param  array   $filters  ['store_id', 'from_date', 'to_date']
     * @return array{entries: \Illuminate\Support\Collection, summary: array}
     */
    public function buildLedger(Client $client, array $filters = [], ?array $storeIds = null): array
    {
        // One query: invoices + completed payments, eagerly loaded
        // 🛡️ Backend guard: always restrict to active store(s), frontend filter narrows further
        $effectiveStoreIds = ! empty($filters['store_id'])
            ? [$filters['store_id']]
            : ($storeIds ?? []);

        $invoices = Invoice::where('customer_id', $client->id)
            ->where('status', '!=', 'cancelled')
            ->when(! empty($effectiveStoreIds), fn ($q) => $q->whereIn('store_id', $effectiveStoreIds))
            ->when(! empty($filters['from_date']), fn ($q) => $q->whereDate('invoice_date', '>=', $filters['from_date']))
            ->when(! empty($filters['to_date']), fn ($q) => $q->whereDate('invoice_date', '<=', $filters['to_date']))
            ->with([
                'payments' => fn ($q) => $q->where('status', 'completed')->orderBy('payment_date')->orderBy('id'),
                'payments.paymentMethod',
            ])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        // Flatten invoices + payments into a single raw entry list
        $rawEntries = collect();

        foreach ($invoices as $invoice) {
            // ── Debit row: Invoice ──
            $rawEntries->push([
                'sort_date'      => $invoice->invoice_date->toDateString(),
                'sort_seq'       => 0, // invoice sorts before same-day payments
                'date'           => $invoice->invoice_date,
                'type'           => 'invoice',
                'reference'      => $invoice->invoice_number,
                'invoice_id'     => $invoice->id,
                'invoice_amount' => (float) $invoice->grand_total,
                'payment_amount' => 0.0,
                'running_balance' => 0.0,    // computed in pass below
                'payment_status'  => $invoice->payment_status,
                'due_date'        => $invoice->due_date,
                'notes'           => $invoice->notes,
                'payment_method'  => null,
            ]);

            // ── Credit rows: Payments against this invoice ──
            foreach ($invoice->payments as $payment) {
                $rawEntries->push([
                    'sort_date'       => optional($payment->payment_date)->toDateString() ?? $invoice->invoice_date->toDateString(),
                    'sort_seq'        => 1, // payments come after invoices on the same date
                    'date'            => $payment->payment_date ?? $invoice->invoice_date,
                    'type'            => 'payment',
                    'reference'       => $payment->payment_number ?? ('RCP-'.str_pad($payment->id, 5, '0', STR_PAD_LEFT)),
                    'invoice_id'      => $invoice->id,
                    'invoice_ref'     => $invoice->invoice_number,
                    'invoice_amount'  => 0.0,
                    'payment_amount'  => (float) $payment->amount,
                    'running_balance' => 0.0,
                    'payment_status'  => 'payment',
                    'due_date'        => null,
                    'notes'           => $payment->notes,
                    'payment_method'  => $payment->paymentMethod?->name,
                ]);
            }
        }

        // Sort: date ASC → invoice before payment on same date
        $sorted = $rawEntries->sortBy([
            ['sort_date', 'asc'],
            ['sort_seq', 'asc'],
        ])->values();

        // Single-pass running balance
        $runningBalance = 0.0;
        $ledgerEntries  = $sorted->map(function (array $entry) use (&$runningBalance) {
            $runningBalance += $entry['invoice_amount'];
            $runningBalance -= $entry['payment_amount'];
            $entry['running_balance'] = round($runningBalance, 2);

            return $entry;
        });

        // Summary aggregates (computed from already-loaded collections — zero extra queries)
        $totalInvoiced = (float) $invoices->sum(fn ($inv) => $inv->grand_total);
        $totalPaid     = (float) $invoices->flatMap->payments->sum('amount');
        $outstanding   = $totalInvoiced - $totalPaid;

        $overdue = (float) $invoices
            ->filter(fn ($inv) =>
                $inv->payment_status !== 'paid'
                && $inv->due_date !== null
                && $inv->due_date->isPast()
            )
            ->sum(fn ($inv) => max(0.0, (float) $inv->grand_total - (float) $inv->payments->sum('amount')));

        return [
            'entries' => $ledgerEntries,
            'summary' => [
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid'     => round($totalPaid, 2),
                'outstanding'    => round($outstanding, 2),
                'overdue'        => round($overdue, 2),
            ],
        ];
    }
}