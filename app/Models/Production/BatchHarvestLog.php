<?php

namespace App\Models\Production;

use App\Enums\Production\BatchHarvestLogAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchHarvestLog extends Model
{
    // Insert-only audit trail — no updates, no deletes, no SoftDeletes needed.

    protected $table = 'production_batch_harvest_logs';

    protected $fillable = [
        'harvest_id',
        'action',
        'old_quantity',
        'new_quantity',
        'remarks',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'action'       => BatchHarvestLogAction::class,
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
        'performed_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(BatchHarvest::class, 'harvest_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}