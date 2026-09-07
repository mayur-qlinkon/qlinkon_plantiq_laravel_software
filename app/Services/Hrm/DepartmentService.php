<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Department;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function create(array $data): Department
    {
        return DB::transaction(fn () => Department::create($data));
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department->update($data);

            return $department->fresh();
        });
    }

    public function delete(Department $department): void
    {
        if ($department->employees()->exists()) {
            throw new \RuntimeException('Cannot delete department with assigned employees.');
        }
        DB::transaction(fn () => $department->delete());
    }
}
