<?php

namespace App\Models\Production;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlanItem extends Model
{
    protected $fillable = [
        'production_plan_id',
        'product_id',
        'target_quantity',
        'target_date',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}