<?php

namespace App\Models\Production;

use App\Enums\Production\ActivityType;
use App\Models\Product;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityTemplate extends Model
{
    use SoftDeletes, Tenantable;

    protected $table = 'production_activity_templates';

    protected $fillable = [
        'company_id',
        'product_id',
        'activity_type',
        'frequency_type',
        'frequency_value',
        'start_after_days',
        'is_required',
        'is_active',
        'sort_order',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'activity_type' => ActivityType::class,
        'frequency_value' => 'integer',
        'start_after_days' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeRequired(Builder $q): Builder
    {
        return $q->where('is_required', true);
    }

    // Global Default templates — product_id NULL, fallback for all species
    public function scopeGlobalDefault(Builder $q): Builder
    {
        return $q->whereNull('product_id');
    }

    public function scopeForProduct(Builder $q, int $productId): Builder
    {
        return $q->where('product_id', $productId);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order', 'asc');
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function isGlobalDefault(): bool
    {
        return $this->product_id === null;
    }

    public function getFormattedFrequencyAttribute(): string
    {
        if ($this->frequency_type === 'interval') {
            return $this->frequency_value === 1 
                ? 'Every Day' 
                : "Every {$this->frequency_value} Days";
        }

        return ucfirst($this->frequency_type);
    }
}