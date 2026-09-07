<?php

namespace App\Models\Hrm;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySlip extends Model
{
    use  SoftDeletes, Tenantable;

    // ── Constants ──

    const STATUS_DRAFT = 'draft';

    const STATUS_GENERATED = 'generated';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    const PAYMENT_CASH = 'cash';

    const PAYMENT_CHEQUE = 'cheque';

    const PAYMENT_UPI = 'upi';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_GENERATED => 'Generated',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_PAID => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        self::STATUS_GENERATED => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
        self::STATUS_APPROVED => ['bg' => '#fffbeb', 'text' => '#92400e', 'dot' => '#f59e0b'],
        self::STATUS_PAID => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
        self::STATUS_CANCELLED => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];

    // ── Fillable ──

    protected $fillable = [
        'company_id', 'employee_id', 'slip_number', 'month', 'year',
        'working_days', 'present_days', 'absent_days', 'leave_days', 'overtime_hours',
        'gross_earnings', 'total_deductions', 'net_salary', 'round_off',
        'payment_mode', 'payment_method_id', 'payment_method_name', 'payment_reference', 'payment_date',
        'status', 'generated_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'working_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'leave_days' => 'decimal:1',
        'overtime_hours' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'round_off' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];
  

    // ── Boot ──

    protected static function booted(): void
    {
        static::creating(function (SalarySlip $slip) {
            if (empty($slip->slip_number)) {
                $slip->slip_number = static::generateSlipNumber($slip->company_id, $slip->month, $slip->year);
            }
        });
    }

    public static function generateSlipNumber(int $companyId, int $month, int $year): string
    {
        $prefix = 'SAL-'.$year.str_pad($month, 2, '0', STR_PAD_LEFT);
        $latest = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('slip_number', 'like', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->value('slip_number');

        $sequence = $latest ? ((int) last(explode('-', $latest))) + 1 : 1;

        return "{$prefix}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalarySlipItem::class)->orderBy('sort_order');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(SalarySlipItem::class)->where('type', SalaryComponent::TYPE_EARNING)->orderBy('sort_order');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(SalarySlipItem::class)->where('type', SalaryComponent::TYPE_DEDUCTION)->orderBy('sort_order');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ──

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where('month', $month)->where('year', $year);
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): array
    {
        return self::STATUS_COLORS[$this->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'];
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    // ── Accessors ──

    /**
     * Human-readable payment label for display in UI and PDF.
     * Priority: new payment_method_name → legacy payment_mode → null.
     */
    public function getPaymentLabelAttribute(): ?string
    {
        if ($this->payment_method_name) {
            return $this->payment_method_name;
        }

        if ($this->payment_mode) {
            return ucfirst(str_replace('_', ' ', $this->payment_mode));
        }

        return null;
    }

    // ── Helpers ──

    /**
     * A slip is editable only in pre-approval states. Once approved or paid
     * it becomes immutable so downstream reports/PDFs stay consistent.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_GENERATED], true);
    }
}
