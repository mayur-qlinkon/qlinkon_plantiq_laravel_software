<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by_type',
        'changed_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getDescriptionAttribute(): string
    {
        $from = $this->from_status ? ucfirst($this->from_status) : 'Created';
        $to = ucfirst($this->to_status);

        return "{$from} → {$to}";
    }
}
