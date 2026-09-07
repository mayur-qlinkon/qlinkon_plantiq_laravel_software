<?php

namespace App\Services\Production;

use App\Models\Production\GrowingSpaceType;
use Illuminate\Support\Facades\DB;

class GrowingSpaceTypeService
{
    public function create(array $data): GrowingSpaceType
    {
        return DB::transaction(fn () => GrowingSpaceType::create($data));
    }

    public function update(GrowingSpaceType $type, array $data): GrowingSpaceType
    {
        return DB::transaction(function () use ($type, $data) {
            $type->update($data);

            return $type->fresh();
        });
    }

    /**
     * @throws \RuntimeException  When Growing Spaces still reference this
     *                            type (a friendly guard in front of the
     *                            DB's restrictOnDelete FK constraint)
     */
    public function delete(GrowingSpaceType $type): void
    {
        if ($type->growingSpaces()->withTrashed()->exists()) {
            throw new \RuntimeException('Cannot delete a Growing Space Type that is still in use. Reassign or permanently remove its Growing Spaces first.');
        }

        DB::transaction(fn () => $type->delete());
    }
}