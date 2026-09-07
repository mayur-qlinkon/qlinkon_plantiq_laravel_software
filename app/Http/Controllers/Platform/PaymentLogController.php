<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\SubscriptionPaymentLog;
use Illuminate\Http\Request;

class PaymentLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'q', 'from', 'to']);

        $logs = SubscriptionPaymentLog::query()
            ->with([
                'company:id,name,slug,email',
                'plan:id,name,company_id',
            ])
            // Status filter (pending | paid | failed | refunded)
            ->when($filters['status'] ?? null, fn ($q, $status) =>
                $q->where('status', $status)
            )
            // Search by company name/email or cf_order_id
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('cf_order_id', 'like', "%{$term}%")
                        ->orWhere('cf_payment_id', 'like', "%{$term}%")
                        ->orWhereHas('company', fn ($c) =>
                            $c->where('name', 'like', "%{$term}%")
                              ->orWhere('email', 'like', "%{$term}%")
                        );
                });
            })
            // Date range (on created_at)
            ->when($filters['from'] ?? null, fn ($q, $from) =>
                $q->whereDate('created_at', '>=', $from)
            )
            ->when($filters['to'] ?? null, fn ($q, $to) =>
                $q->whereDate('created_at', '<=', $to)
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Summary cards (respect current filters except status, so totals stay useful)
        $base = SubscriptionPaymentLog::query()
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total_paid'    => (clone $base)->where('status', 'paid')->sum('amount'),
            'count_paid'    => (clone $base)->where('status', 'paid')->count(),
            'count_pending' => (clone $base)->where('status', 'pending')->count(),
            'count_failed'  => (clone $base)->where('status', 'failed')->count(),
        ];

        return view('platform.payments.index', compact('logs', 'stats', 'filters'));
    }

    public function show(SubscriptionPaymentLog $payment)
    {
        $payment->load(['company:id,name,slug,email,phone', 'plan']);

        return view('platform.payments.show', compact('payment'));
    }
}