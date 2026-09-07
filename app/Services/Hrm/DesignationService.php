<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Designation;
use Illuminate\Support\Facades\DB;

class DesignationService
{
    public function create(array $data): Designation
    {
        return DB::transaction(fn () => Designation::create($data));
    }

    public function update(Designation $designation, array $data): Designation
    {
        return DB::transaction(function () use ($designation, $data) {
            $designation->update($data);

            return $designation->fresh();
        });
    }

    public function delete(Designation $designation): void
    {
        if ($designation->employees()->exists()) {
            throw new \RuntimeException('Cannot delete designation with assigned employees.');
        }
        DB::transaction(fn () => $designation->delete());
    }
}
