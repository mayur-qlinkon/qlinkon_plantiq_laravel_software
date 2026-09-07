<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Platform\ModuleSeeder::class,
            Platform\PermissionSeeder::class,                  
            // Platform\SuperAdminSeeder::class,
            // Platform\StateSeeder::class,
            
            // HRM\DepartmentSeeder::class,
            // HRM\DesignationSeeder::class,
            // HRM\RoleSeeder::class,
            // HRM\LeaveTypeSeeder::class,
            
            // Inventory\UnitsSeeder::class,
            // Inventory\CategoriesSeeder::class,
            // Inventory\WarehousesSeeder::class,
            // Inventory\AttributesSeeder::class,
            // Inventory\ProductsSeeder::class,
            
            // Production\ProductionDemoSeeder::class,
            // Projects\ProjectDemoSeeder::class,
            // Projects\RenewalBoardDemoSeeder::class,
        ]);
    }
}
