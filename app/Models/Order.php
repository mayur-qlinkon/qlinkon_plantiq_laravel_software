<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity,SoftDeletes,Tenantable;

    protected $fillable = [
        'company_id',
        'order_number',
        'order_type',        // ← add
        'source',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_id',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_pincode',
        'delivery_country',
        'supply_state',
        'subtotal',
        'discount_amount',
        'cgst_amount',       // ← add
        'sgst_amount',       // ← add
        'igst_amount',       // ← add
        'tax_amount',
        'shipping_amount',
        'round_off',         // ← add
        'total_amount',
        'refunded_amount',   // ← add
        'currency',
        'coupon_code',
        'coupon_discount',
        'payment_method',
        'payment_status',
        'payment_id',        // ← add
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'paid_at',
        'store_id',          // ← add
        'warehouse_id',      // ← add
        'delivery_type',
        'tracking_number',
        'courier_name',
        'shipped_at',
        'delivered_at',
        'expected_delivery_date',
        'whatsapp_sent',
        'confirmation_sms_sent',
        'last_notified_at',
        'customer_notes',
        'admin_notes',
        'cancellation_reason',
        'items_count',
        'items_qty',
        'invoice_id',
        'created_by',
        'confirmed_by',
        'cancelled_by',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',   // ← add
        'sgst_amount' => 'decimal:2',   // ← add
        'igst_amount' => 'decimal:2',   // ← add
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'round_off' => 'decimal:2',   // ← add
        'total_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',   // ← add
        'coupon_discount' => 'decimal:2',
        'whatsapp_sent' => 'boolean',
        'confirmation_sms_sent' => 'boolean',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'items_count' => 'integer',
        'items_qty' => 'integer',
        'warehouse_id' => 'integer',
    ];

    // ── Status badge colors for blade ──
    public const STATUS_COLORS = [
        'inquiry' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#3b82f6'],
        'confirmed' => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#22c55e'],
        'processing' => ['bg' => '#fefce8', 'text' => '#a16207', 'dot' => '#eab308'],
        'shipped' => ['bg' => '#f5f3ff', 'text' => '#6d28d9', 'dot' => '#8b5cf6'],
        'out_for_delivery' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'dot' => '#f97316'],
        'delivered' => ['bg' => '#f0fdf4', 'text' => '#166534', 'dot' => '#16a34a'],
        'cancelled' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'dot' => '#ef4444'],
        'refunded' => ['bg' => '#f8fafc', 'text' => '#475569', 'dot' => '#64748b'],
        'failed' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'dot' => '#ef4444'],
    ];

    public const PAYMENT_STATUS_COLORS = [
        'pending' => ['bg' => '#fefce8', 'text' => '#a16207'],
        'partial' => ['bg' => '#fff7ed', 'text' => '#c2410c'],
        'paid' => ['bg' => '#f0fdf4', 'text' => '#15803d'],
        'failed' => ['bg' => '#fef2f2', 'text' => '#dc2626'],
        'refunded' => ['bg' => '#f8fafc', 'text' => '#475569'],
        'waived' => ['bg' => '#f0fdf4', 'text' => '#166534'],
    ];

    public const PAYMENT_STATUSES = ['pending', 'partial', 'paid', 'failed', 'refunded', 'waived'];

    // ════════════════════════════════════════════════════
    //  BOOT
    // ════════════════════════════════════════════════════

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber($order->company_id);
            }
            if (empty($order->created_by) && Auth::check()) {
                $order->created_by = Auth::id();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Logs every fillable attribute
            ->logOnlyDirty() // ONLY logs attributes that actually changed
            ->dontSubmitEmptyLogs() // Prevents logging if nothing was actually modified
            ->setDescriptionForEvent(fn (string $eventName) => "Invoice has been {$eventName}");
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeInquiries(Builder $q): Builder
    {
        return $q->where('status', 'inquiry');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['cancelled', 'failed', 'refunded']);
    }

    public function scopeByStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeRecent(Builder $q): Builder
    {
        return $q->orderBy('created_at', 'desc');
    }

    public function scopeFromStorefront(Builder $q): Builder
    {
        return $q->where('source', 'storefront');
    }

    public function scopeByType(Builder $q, string $type): Builder
    {
        return $q->where('order_type', $type);
    }

    public function scopeRetail(Builder $q): Builder
    {
        return $q->where('order_type', 'retail');
    }

    public function scopeForStore(Builder $q, array $storeIds): Builder
    {
        return $q->whereIn('store_id', $storeIds);
    }

    public function scopeAccessibleByUser(Builder $q): Builder
    {
        $storeIds = active_store_ids();
        return empty($storeIds) ? $q : $q->whereIn('store_id', $storeIds);
    }

    public function scopeWholesale(Builder $q): Builder
    {
        return $q->where('order_type', 'wholesale');
    }

    public function scopePaidOrders(Builder $q): Builder
    {
        return $q->where('payment_status', 'paid');
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->whereDate('created_at', today());
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    public function getReceiptUrlAttribute(): string
    {
        return URL::signedRoute(
            'storefront.receipt',
            [
                'slug' => $this->company->slug,
                'orderNumber' => $this->order_number,
            ],
            now()->addDays(30)
        );
    }

    public function getStatusColorAttribute(): array
    {
        return self::STATUS_COLORS[$this->status] ?? ['bg' => '#f8fafc', 'text' => '#64748b', 'dot' => '#94a3b8'];
    }

    public function getPaymentStatusColorAttribute(): array
    {
        return self::PAYMENT_STATUS_COLORS[$this->payment_status] ?? ['bg' => '#f8fafc', 'text' => '#64748b'];
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'retail' => 'Retail',
            'wholesale' => 'Wholesale',
            'inquiry' => 'Inquiry',
            'sample' => 'Sample',
            'subscription' => 'Subscription',
            'repair' => 'Repair',
            default => ucfirst($this->order_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'inquiry' => 'New Inquiry',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            default => ucfirst($this->status),
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₹'.number_format($this->total_amount, 2);
    }

    public function getGrandTotalAttribute(): float
    {
        // Reload from DB if total_amount is null/zero — safety for service context
        if (! $this->total_amount && $this->id) {
            return (float) static::where('id', $this->id)->value('total_amount');
        }

        return (float) $this->total_amount;
    }
    // ── PaymentService compatibility ──

    public function getWhatsappMessageAttribute(): string
    {
        $items = $this->items->map(fn ($i) => "• {$i->product_name}".
            ($i->sku_label ? " ({$i->sku_label})" : '').
            " x{$i->qty} = ₹".number_format($i->line_total, 2)
        )->join("\n");

        return urlencode(
            "🛒 *New Order #{$this->order_number}*\n\n".
            "*Customer:* {$this->customer_name}\n".
            "*Phone:* {$this->customer_phone}\n".
            "*Address:* {$this->delivery_address}, {$this->delivery_city}\n\n".
            "*Items:*\n{$items}\n\n".
            '*Total: ₹'.number_format($this->total_amount, 2)."*\n\n".
            '*Payment:* '.strtoupper($this->payment_method)."\n".
            '*Notes:* '.($this->customer_notes ?: 'None')
        );
    }

    public function getIsPayableAttribute(): bool
    {
        return in_array($this->status, ['inquiry', 'confirmed', 'processing'])
            && $this->payment_status === 'pending';
    }

    public function getIsCancellableAttribute(): bool
    {
        return in_array($this->status, ['inquiry', 'confirmed']);
    }

    // ════════════════════════════════════════════════════
    //  STATUS TRANSITION HELPERS
    // ════════════════════════════════════════════════════

    public function transitionTo(string $newStatus, ?string $notes = null, string $changedByType = 'admin'): bool
    {
        $oldStatus = $this->status;

        try {
            $this->status = $newStatus;

            // Set timestamps on specific transitions
            match ($newStatus) {
                'confirmed' => $this->confirmed_at = now(),
                'shipped' => $this->shipped_at = now(),
                'delivered' => $this->delivered_at = now(),
                'cancelled' => $this->cancelled_at = now(),
                default => null,
            };

            if ($newStatus === 'confirmed' && Auth::check()) {
                $this->confirmed_by = Auth::id();
            }
            if ($newStatus === 'cancelled' && Auth::check()) {
                $this->cancelled_by = Auth::id();
            }

            $this->save();

            // Log the transition
            OrderStatusHistory::create([
                'order_id' => $this->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'notes' => $notes,
                'changed_by_type' => $changedByType,
                'changed_by' => Auth::id(),
            ]);

            Log::info('[Order] Status transitioned', [
                'order_id' => $this->id,
                'order_no' => $this->order_number,
                'from' => $oldStatus,
                'to' => $newStatus,
                'changed_by' => Auth::id(),
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('[Order] Transition failed', [
                'order_id' => $this->id,
                'from' => $oldStatus,
                'to' => $newStatus,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ════════════════════════════════════════════════════
    //  STATIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Generate unique order number: ORD-YYYYMM-XXXXX
     * e.g. ORD-202401-00042
     */
    public static function generateOrderNumber(int $companyId): string
    {
        $prefix = 'ORD-'.date('Ym').'-';
        // withTrashed is required — see PaymentService::generatePaymentNumber().
        // The unique index does not respect soft deletes, so a deleted order's
        // number remains permanently taken.
        $last = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('order_number');

        $nextNum = $last
            ? (intval(substr($last, -5)) + 1)
            : 1;

        return $prefix.str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Quick stats for admin dashboard.
     */
    public static function getStats(int $companyId, ?array $storeIds = null): array
    {
        $base = static::where('company_id', $companyId);

        if (!empty($storeIds)) {
            $base->whereIn('store_id', $storeIds);
        }

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->today()->count(),
            'inquiries' => (clone $base)->inquiries()->count(),
            'confirmed' => (clone $base)->byStatus('confirmed')->count(),
            'processing' => (clone $base)->byStatus('processing')->count(),
            'shipped' => (clone $base)->byStatus('shipped')->count(),
            'delivered' => (clone $base)->byStatus('delivered')->count(),
            'cancelled' => (clone $base)->byStatus('cancelled')->count(),
            'revenue' => (clone $base)->paidOrders()->sum('total_amount'),
            'pending_revenue' => (clone $base)->active()->sum('total_amount'),
            'refunded_total' => (clone $base)->sum('refunded_amount'),
            'retail_count' => (clone $base)->retail()->count(),
            'wholesale_count' => (clone $base)->wholesale()->count(),
        ];
    }
}
