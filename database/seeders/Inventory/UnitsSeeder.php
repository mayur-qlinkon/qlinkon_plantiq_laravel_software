<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $units = [
            ['name' => 'Piece', 'short_name' => 'pcs'],
            ['name' => 'Kilogram', 'short_name' => 'kg'],
            ['name' => 'Liter', 'short_name' => 'ltr'],
            ['name' => 'Box', 'short_name' => 'box'],
        ];

        DB::beginTransaction();

        try {
            foreach ($units as $unit) {
                DB::table('units')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'name' => $unit['name'],
                    ],
                    array_merge($unit, [
                        'company_id' => $companyId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
