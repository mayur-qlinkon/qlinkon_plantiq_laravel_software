<?php

namespace App\Models\Platform;
use App\Models\Plan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionPaymentLog
 *
 * Tracks every subscription payment attempt (success or failure) made
 * through Cashfree. Separate from the tenant-level Payment model which
 * tracks business transactions (invoices, POS, etc.).
 *
 * @property int         $id
 * @property int         $company_id
 * @property int|null    $plan_id
 * @property string      $cf_order_id        Our generated order_id sent to Cashfree
 * @property string|null $cf_payment_id      Cashfree's payment ID (from webhook/fetch)
 * @property string|null $cf_transaction_id  Cashfree's internal transaction ID
 * @property float       $amount
 * @property string      $currency
 * @property string      $status             pending | paid | failed | refunded
 * @property string|null $payment_method     upi | card | netbanking | wallet | etc.
 * @property string|null $failure_reason
 * @property array|null  $gateway_response   Full Cashfree response stored as JSON
 * @property string      $initiated_by       web | webhook
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SubscriptionPaymentLog extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'coupon_code',
        'cf_order_id',
        'cf_payment_id',
        'cf_transaction_id',
        'amount',
        'discount_amount',
        'currency',
        'status',
        'payment_method',
        'failure_reason',
        'gateway_response',
        'initiated_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'payment_method' => 'array',
        'gateway_response' => 'array',
    ];

    // -------------------------------------------------------------------------
    // RELATIONSHIPS
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // -------------------------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------------------------

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}