<?php

namespace Database\Seeders\HRM;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Dynamically grab the company ID injected by the Visual Seeder Platform
        $companyId = request()->attributes->get('seeder_company_id', 1);

        // Standard Corporate Leave Policy Matrix
        $leaveTypes = [
            [
                'name' => 'Casual Leave',
                'code' => 'CL',
                'description' => 'Granted for unforeseen situations or personal matters.',
                'default_days_per_year' => 12.0,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0.0,
                'is_encashable' => false,
                'requires_document' => false,
                'min_days_before_apply' => 0,
                'max_consecutive_days' => 3.0,
                'applicable_gender' => 'all',
                'sort_order' => 1,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave on the grounds of sickness or health issues. Medical certificate required for extended leaves.',
                'default_days_per_year' => 12.0,
                'is_paid' => true,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 12.0,
                'is_encashable' => false,
                'requires_document' => true, // Often required if taken for > 2 days
                'min_days_before_apply' => 0,
                'max_consecutive_days' => 12.0,
                'applicable_gender' => 'all',
                'sort_order' => 2,
            ],
            [
                'name' => 'Privilege / Earned Leave',
                'code' => 'PL',
                'description' => 'Planned long leaves. Needs prior approval.',
                'default_days_per_year' => 18.0,
                'is_paid' => true,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 30.0,
                'is_encashable' => true,
                'requires_document' => false,
                'min_days_before_apply' => 15, // Requires 15 days notice
                'max_consecutive_days' => 15.0,
                'applicable_gender' => 'all',
                'sort_order' => 3,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Statutory leave for expecting mothers.',
                'default_days_per_year' => 180.0,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0.0,
                'is_encashable' => false,
                'requires_document' => true,
                'min_days_before_apply' => 30,
                'max_consecutive_days' => 180.0,
                'applicable_gender' => 'female',
                'sort_order' => 4,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PTL',
                'description' => 'Leave granted to expecting fathers.',
                'default_days_per_year' => 7.0,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0.0,
                'is_encashable' => false,
                'requires_document' => true,
                'min_days_before_apply' => 15,
                'max_consecutive_days' => 7.0,
                'applicable_gender' => 'male',
                'sort_order' => 5,
            ],
            [
                'name' => 'Leave Without Pay',
                'code' => 'LWP',
                'description' => 'Unpaid leave taken when no leave balance is available.',
                'default_days_per_year' => 0.0,
                'is_paid' => false,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0.0,
                'is_encashable' => false,
                'requires_document' => false,
                'min_days_before_apply' => 0,
                'max_consecutive_days' => 365.0,
                'applicable_gender' => 'all',
                'sort_order' => 6,
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($leaveTypes as $leave) {
                DB::table('leave_types')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'code' => $leave['code'], // Unique constraint
                    ],
                    array_merge($leave, [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();
            if (isset($this->command)) {
                $this->command->info("✅ HRM Leave Types seeded successfully for Company ID: {$companyId}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed leave types: '.$e->getMessage());
            }
        }
    }
}
