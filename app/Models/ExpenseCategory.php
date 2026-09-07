<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'parent_id',
        'name',
        'color',
        'icon',
        'description',
        'type',             // 'direct', 'indirect', 'asset'
        'gst_type',         // 'taxable', 'non_taxable', 'exempt'
        'account_code',     // e.g., EXP-001
        'hsn_sac_code',     // Indian Market: GST Compliance
        'default_tax_rate', // e.g., 18.00
        'position',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'default_tax_rate' => 'decimal:2',
        'position' => 'integer',
    ];
    

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent category (if this is a sub-category).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Get the sub-categories belonging to this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get all expenses logged under this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by position.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position', 'asc');
    }

    /**
     * Scope a query to only get root categories (no parents).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
