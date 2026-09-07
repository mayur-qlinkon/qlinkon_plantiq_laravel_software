<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlipItem extends Model
{
    protected $fillable = [
        'salary_slip_id', 'salary_component_id',
        'component_name', 'component_code', 'type',
        'amount', 'calculation_detail', 'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
