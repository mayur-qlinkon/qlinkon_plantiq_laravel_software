<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Expense extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'store_id',
        'expense_category_id',
        'expense_number',
        // Merchant & Tax
        'currency_code',
        'exchange_rate',
        'tax_type',
        'tax_percent',
        'merchant_name',
        'merchant_gstin',
        'reference_number',
        'expense_date',
        // Financials
        'payment_status', // 'unpaid', 'partial', 'paid'
        'round_off',
        'base_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        // Attributes
        'is_reimbursable',
        'is_billable',
        'status', // 'draft', 'pending_approval', 'approved', 'rejected', 'reimbursed'
        'attachment',
        'source',
        'notes',

        'approved_by',
        'approved_at',
    ];
    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'total_paid',
        'due_amount',
        'receipt_url',
        'receipt_name',
        'receipt_is_image',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expense_date' => 'date',
        'approved_at' => 'datetime',

        'exchange_rate' => 'decimal:4',
        'tax_percent' => 'decimal:2',
        'round_off' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',

        'is_reimbursable' => 'boolean',
        'is_billable' => 'boolean',
    ];    

    /**
     * The media collection lives on a private disk with no public URL, but
     * Spatie's Media model appends original_url, which resolves through
     * Storage::url(). Serialising a loaded media relation would therefore
     * throw. Nothing in the Alpine components reads media directly — they use
     * the receipt_url accessor — so keep it out of the array form entirely.
     */
    protected $hidden = ['media'];

    // ════════════════════════════════════════════════════
    //  MEDIA LIBRARY (Spatie)
    // ════════════════════════════════════════════════════

    public function registerMediaCollections(): void
    {
        // Private disk. Receipts are real vendor bills and GST invoices, and
        // Spatie's default path is /storage/{media_id}/{file_name} — the id is
        // sequential, so on the public disk another tenant's receipts were
        // reachable by walking the ids. Served through ExpenseController@receipt.
        $this->addMediaCollection('receipts')
            ->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The employee/user who incurred or submitted the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * The manager/admin who approved the expense.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 🌟 THE UNIFIED PAYMENTS LINK
     * Link this expense to your unified payments ledger.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }
    /**
     * grand_total accessor — PaymentService::recordPayment() and
     * syncDocumentPaymentStatus() read $document->grand_total on every
     * document type. Expense stores it as total_amount, so bridge the gap
     * here exactly as Purchase and Order already do.
     *
     * Without this the property resolved to null and cast to 0.0, so every
     * expense payment was written with amount = 0 and the full sum parked in
     * change_returned.
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) $this->total_amount;
    }

    /**
     * Computed: Total amount paid so far.
     *
     * Sums `amount` — the value actually applied to the document. The old
     * version summed `amount_received`, which is cash tendered at a POS
     * counter and carries change on top of it; for an expense it is simply
     * the wrong column.
     *
     * A withSum()/loadSum() alias is reused when the query supplied one.
     * An accessor takes precedence over that alias, so without this check the
     * eager-loaded sum was silently discarded and one query fired per row.
     */
    public function getTotalPaidAttribute(): float
    {
        if (array_key_exists('total_paid', $this->attributes)) {
            return (float) $this->attributes['total_paid'];
        }

        return (float) $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Computed: Remaining balance.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->total_paid);
    }

        /**
     * Authorised URL for the receipt, or null when none is attached.
     * Never expose Media::getUrl() directly — the collection lives on the
     * private disk now.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        return $this->getFirstMedia('receipts')
            ? route('admin.expenses.receipt', $this->id)
            : null;
    }
        /**
     * Original filename of the receipt, for display next to the link.
     */
    public function getReceiptNameAttribute(): ?string
    {
        return $this->getFirstMedia('receipts')?->file_name;
    }

    /**
     * Whether the receipt can be rendered inline as an image.
     */
    public function getReceiptIsImageAttribute(): bool
    {
        $mime = $this->getFirstMedia('receipts')?->mime_type;

        return $mime ? str_starts_with($mime, 'image/') : false;
    }
}
