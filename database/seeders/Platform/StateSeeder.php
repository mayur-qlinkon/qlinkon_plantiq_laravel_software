<?php

namespace Database\Seeders\Platform;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['name' => 'Jammu and Kashmir', 'code' => '01', 'type' => 'union_territory'],
            ['name' => 'Himachal Pradesh', 'code' => '02', 'type' => 'state'],
            ['name' => 'Punjab', 'code' => '03', 'type' => 'state'],
            ['name' => 'Chandigarh', 'code' => '04', 'type' => 'union_territory'],
            ['name' => 'Uttarakhand', 'code' => '05', 'type' => 'state'],
            ['name' => 'Haryana', 'code' => '06', 'type' => 'state'],
            ['name' => 'Delhi', 'code' => '07', 'type' => 'union_territory'],
            ['name' => 'Rajasthan', 'code' => '08', 'type' => 'state'],
            ['name' => 'Uttar Pradesh', 'code' => '09', 'type' => 'state'],
            ['name' => 'Bihar', 'code' => '10', 'type' => 'state'],
            ['name' => 'Sikkim', 'code' => '11', 'type' => 'state'],
            ['name' => 'Arunachal Pradesh', 'code' => '12', 'type' => 'state'],
            ['name' => 'Nagaland', 'code' => '13', 'type' => 'state'],
            ['name' => 'Manipur', 'code' => '14', 'type' => 'state'],
            ['name' => 'Mizoram', 'code' => '15', 'type' => 'state'],
            ['name' => 'Tripura', 'code' => '16', 'type' => 'state'],
            ['name' => 'Meghalaya', 'code' => '17', 'type' => 'state'],
            ['name' => 'Assam', 'code' => '18', 'type' => 'state'],
            ['name' => 'West Bengal', 'code' => '19', 'type' => 'state'],
            ['name' => 'Jharkhand', 'code' => '20', 'type' => 'state'],
            ['name' => 'Odisha', 'code' => '21', 'type' => 'state'],
            ['name' => 'Chhattisgarh', 'code' => '22', 'type' => 'state'],
            ['name' => 'Madhya Pradesh', 'code' => '23', 'type' => 'state'],
            ['name' => 'Gujarat', 'code' => '24', 'type' => 'state'],
            ['name' => 'Dadra and Nagar Haveli and Daman and Diu', 'code' => '26', 'type' => 'union_territory'],
            ['name' => 'Maharashtra', 'code' => '27', 'type' => 'state'],
            ['name' => 'Karnataka', 'code' => '29', 'type' => 'state'],
            ['name' => 'Goa', 'code' => '30', 'type' => 'state'],
            ['name' => 'Lakshadweep', 'code' => '31', 'type' => 'union_territory'],
            ['name' => 'Kerala', 'code' => '32', 'type' => 'state'],
            ['name' => 'Tamil Nadu', 'code' => '33', 'type' => 'state'],
            ['name' => 'Puducherry', 'code' => '34', 'type' => 'union_territory'],
            ['name' => 'Andaman and Nicobar Islands', 'code' => '35', 'type' => 'union_territory'],
            ['name' => 'Telangana', 'code' => '36', 'type' => 'state'],
            ['name' => 'Andhra Pradesh', 'code' => '37', 'type' => 'state'],
            ['name' => 'Ladakh', 'code' => '38', 'type' => 'union_territory'],
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(['code' => $state['code']], $state);
        }
    }
}
