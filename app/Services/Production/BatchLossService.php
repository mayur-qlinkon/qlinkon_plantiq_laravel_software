<?php

namespace App\Services\Production;

use App\Models\Production\BatchLoss;
use App\Models\Production\PlantBatch;
use Illuminate\Support\Facades\DB;

class BatchLossService
{
    public function __construct(protected PlantBatchService $plantBatchService)
    {
    }

    // ════════════════════════════════════════════════════
    //  RECORD — worker "Report Loss" action ka core write path
    //  Decrement + ledger row ek hi transaction me — dono ya to
    //  saath honge ya bilkul nahi honge.
    // ════════════════════════════════════════════════════

    public function record(
        PlantBatch $batch,
        int $quantityLost,
        string $reason,
        ?string $notes,
        int $companyId,
        int $userId
    ): BatchLoss {
        return DB::transaction(function () use ($batch, $quantityLost, $reason, $notes, $companyId, $userId) {
            // Row-locks the batch, validates against current_quantity, auto-closes
            // the batch at zero. Throws RuntimeException on insufficient quantity —
            // let it bubble up so the controller can turn it into a friendly 422.
            $this->plantBatchService->adjustQuantity($batch, -$quantityLost);

            return BatchLoss::create([
                'company_id' => $companyId,
                'plant_batch_id' => $batch->id,
                'quantity_lost' => $quantityLost,
                'loss_date' => today()->toDateString(),
                'reason' => $reason,
                'recorded_by' => $userId,
                'notes' => $notes,
            ]);
        });
    }
}