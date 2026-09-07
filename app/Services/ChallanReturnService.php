<?php

namespace App\Services;

use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\ChallanReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ChallanReturnService
{
    public function __construct() {}

    // ──────────────────────────────────────────────────────────────
    // 🚀 CORE API
    // ──────────────────────────────────────────────────────────────

    /**
     * Safely creates a new Challan Return, updates parent challan item quantities,
     * recalculates the main challan status, and processes inventory movements.
     *
     * @param  array  $data  Validated data from StoreChallanReturnRequest
     *
     * @throws \Exception
     */
    public function processReturn(array $data): ChallanReturn
    {
        return DB::transaction(function () use ($data) {
            $companyId = Auth::user()->company_id;
            $userId = Auth::id();

            // 1. Isolate Items Data
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            // 2. Fetch Parent Challan
            $challan = Challan::where('id', $data['challan_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            // 🛡️ Pre-flight Check: Cannot return against an invalid challan type
            if (! $challan->is_returnable) {
                throw new LogicException("Challan #{$challan->challan_number} is not marked as returnable.");
            }

            // 3. Create Return Header
            $data['return_number'] = ChallanReturn::generateNumber($companyId);
            $data['company_id'] = $companyId;
            $data['created_by'] = $userId;

            $challanReturn = ChallanReturn::create($data);

            // 4. Process Line Items
            foreach ($itemsData as $itemData) {
                $qtyReturned = (float) $itemData['qty_returned'];
                $qtyDamaged = (float) ($itemData['qty_damaged'] ?? 0);

                if ($qtyReturned <= 0) {
                    continue; // Skip empty rows
                }

                // Fetch the specific Challan Item being returned
                $challanItem = ChallanItem::where('id', $itemData['challan_item_id'])
                    ->where('challan_id', $challan->id)
                    ->firstOrFail();

                // 🛡️ Integrity Check: Cannot return more than what is pending
                if ($qtyReturned > $challanItem->qty_pending) {
                    throw new InvalidArgumentException(
                        "Cannot return {$qtyReturned} for [{$challanItem->display_name}]. Only {$challanItem->qty_pending} pending."
                    );
                }

                // Create the Return Line Item
                $challanReturn->items()->create([
                    'challan_item_id' => $challanItem->id,
                    'qty_returned' => $qtyReturned,
                    'qty_damaged' => $qtyDamaged,
                    'damage_note' => $itemData['damage_note'] ?? null,
                ]);
            }

            // 5. Trigger the Model's Business Logic (Updates parent items and recalculates status)
            $challanReturn->refresh();
            $challanReturn->process();

            return $challanReturn->load('items.challanItem');
        });
    }

    /**
     * Updates editable fields on a Challan Return.
     * Note: Quantities and structural relationships are strictly locked.
     *
     * @param  array  $data  Validated data from UpdateChallanReturnRequest
     */
    public function updateReturn(ChallanReturn $return, array $data): ChallanReturn
    {
        return DB::transaction(function () use ($return, $data) {

            // 1. Update Header (Only allowed fields)
            $headerUpdates = array_intersect_key($data, array_flip(['received_by', 'notes', 'condition']));
            if (! empty($headerUpdates)) {
                $return->update($headerUpdates);
            }

            // 2. Update Item Notes (If provided)
            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['id']) && isset($itemData['damage_note'])) {
                        $return->items()
                            ->where('id', $itemData['id'])
                            ->update(['damage_note' => $itemData['damage_note']]);
                    }
                }
            }

            return $return->fresh(['items.challanItem']);
        });
    }
}
