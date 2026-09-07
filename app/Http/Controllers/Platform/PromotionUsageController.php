<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PromotionUsage;

use Illuminate\Http\Request;

class PromotionUsageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['promotion_id', 'q', 'from', 'to']);

        $usages = PromotionUsage::query()
            ->with([
                'promotion:id,code,name',
                'company:id,name,slug,email',
            ])
            // Filter by a specific promotion
            ->when($filters['promotion_id'] ?? null, fn ($q, $id) =>
                $q->where('promotion_id', $id)
            )
            // Search by coupon code or company name
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('promotion', fn ($p) =>
                            $p->where('code', 'like', "%{$term}%")
                              ->orWhere('name', 'like', "%{$term}%")
                        )
                        ->orWhereHas('company', fn ($c) =>
                            $c->where('name', 'like', "%{$term}%")
                        );
                });
            })
            ->when($filters['from'] ?? null, fn ($q, $from) =>
                $q->whereDate('created_at', '>=', $from)
            )
            ->when($filters['to'] ?? null, fn ($q, $to) =>
                $q->whereDate('created_at', '<=', $to)
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $base = PromotionUsage::query()
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total_redemptions' => (clone $base)->count(),
            'total_discount'    => (clone $base)->sum('discount_amount'),
        ];

        return view('platform.promotions.usages', compact('usages', 'stats', 'filters'));
    }
}