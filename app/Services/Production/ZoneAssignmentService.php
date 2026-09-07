<?php

namespace App\Services\Production;

use App\Models\Production\ZoneAssignment;
use Illuminate\Support\Facades\DB;

class ZoneAssignmentService
{
    /**
     * @throws \RuntimeException  Jab employee already us zone mein actively assigned ho
     */
    public function assign(array $data): ZoneAssignment
    {
        $this->assertNotAlreadyAssigned($data['employee_id'], $data['zone_id'], $data['company_id']);

        $data['assigned_at'] = $data['assigned_at'] ?? now();
        $data['is_active'] = true;

        return DB::transaction(fn () => ZoneAssignment::create($data));
    }

    /**
     * Assignment hata do (soft delete) — record history ke liye reh jaata hai.
     */
    public function unassign(ZoneAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $assignment->update(['is_active' => false]);
            $assignment->delete();
        });
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    protected function assertNotAlreadyAssigned(int $employeeId, int $zoneId, int $companyId): void
    {
        $exists = ZoneAssignment::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('zone_id', $zoneId)
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This employee is already assigned here.');
        }
    }
}