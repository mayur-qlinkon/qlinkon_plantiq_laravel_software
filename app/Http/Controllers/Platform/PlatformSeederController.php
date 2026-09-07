<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;

use Database\Seeders\CRM\ClientsSeeder;
use Database\Seeders\CRM\SuppliersSeeder;
use Database\Seeders\CRM\CrmDefaultSeeder;

use Database\Seeders\HRM\DepartmentSeeder;
use Database\Seeders\HRM\DesignationSeeder;
use Database\Seeders\HRM\LeaveTypeSeeder;

use Database\Seeders\Inventory\AttributesSeeder;
use Database\Seeders\Inventory\CategoriesSeeder;
use Database\Seeders\Inventory\ProductsSeeder;
use Database\Seeders\Inventory\UnitsSeeder;
use Database\Seeders\Inventory\WarehousesSeeder;

use Database\Seeders\Platform\ModuleSeeder;
use Database\Seeders\Platform\PaymentMethodSeeder;
use Database\Seeders\Platform\PermissionSeeder;
use Database\Seeders\Platform\StateSeeder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlatformSeederController extends Controller
{
    /**
     * THE SECURE REGISTRY
     * Only seeders explicitly listed here can be executed via the UI.
     */    
    private array $seeders = [

        'payment_methods' => [
            'name' => 'Payment Methods',
            'description' => 'Generates default offline and online payment gateways (Cash, UPI, Razorpay).',
            'class' => PaymentMethodSeeder::class,
            'requires_company' => true,
            'icon' => 'credit-card',
            'color' => 'blue',
        ],

        'units' => [
            'name' => 'Units',
            'description' => 'Basic measurement units like pcs, kg, ltr.',
            'class' => UnitsSeeder::class,
            'requires_company' => true,
            'icon' => 'ruler-combined',
            'color' => 'teal',
        ],

        'categories' => [
            'name' => 'Categories',
            'description' => 'Product categories for classification.',
            'class' => CategoriesSeeder::class,
            'requires_company' => true,
            'icon' => 'folder',
            'color' => 'orange',
        ],

        'attributes' => [
            'name' => 'Attributes & Values',
            'description' => 'Product attributes with their possible values (Size, Color, etc.).',
            'class' => AttributesSeeder::class,
            'requires_company' => true,
            'icon' => 'tags',
            'color' => 'emerald',
        ],

        'products' => [
            'name' => 'Products',
            'description' => 'Dummy products with SKUs and variants.',
            'class' => ProductsSeeder::class,
            'requires_company' => true,
            'icon' => 'box-open',
            'color' => 'purple',
        ],

        'warehouses' => [
            'name' => 'Warehouses',
            'description' => 'Dummy warehouses with codes and locations.',
            'class' => WarehousesSeeder::class,
            'requires_company' => true,
            'icon' => 'warehouse',
            'color' => 'blue',
        ],

        'clients' => [
            'name' => 'Customers',
            'description' => 'Dummy Clients.',
            'class' => ClientsSeeder::class,
            'requires_company' => true,
            'icon' => 'users',
            'color' => 'purple',
        ],

        'suppliers' => [
            'name' => 'Suppliers',
            'description' => 'Dummy Suppliers.',
            'class' => SuppliersSeeder::class,
            'requires_company' => true,
            'icon' => 'truck',
            'color' => 'purple',
        ],        

        'hrm_departments' => [
            'name' => 'HRM Departments',
            'description' => 'Seeds standard corporate departments (HR, IT, Sales, etc).',
            'class' => DepartmentSeeder::class,
            'requires_company' => true,
            'icon' => 'building',
            'color' => 'blue',
        ],

        'hrm_designations' => [
            'name' => 'HRM Designations',
            'description' => 'Seeds a corporate hierarchy of designations from Trainee to CEO.',
            'class' => DesignationSeeder::class,
            'requires_company' => true,
            'icon' => 'user-tie',
            'color' => 'purple',
        ],

        'hrm_leave_types' => [
            'name' => 'HRM Leave Policies',
            'description' => 'Seeds standard Paid, Sick, Casual, and statutory Maternity/Paternity leaves.',
            'class' => LeaveTypeSeeder::class,
            'requires_company' => true,
            'icon' => 'calendar-xmark',
            'color' => 'blue',
        ],
    ];


    public function index()
    {
        $companies = Company::orderBy('name')->get(['id', 'name', 'slug']);
        $seeders = $this->seeders;

        return view('platform.seeders', compact('companies', 'seeders'));
    }

    public function execute(Request $request)
    {
        // 1. Basic validation
        $request->validate([
            'seeder_key' => ['required', 'string'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        // 2. Registry Security Check
        $seederConfig = $this->seeders[$request->seeder_key] ?? null;
        if (! $seederConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid or unauthorized seeder.'], 403);
        }

        // 3. Context Requirement Check
        if ($seederConfig['requires_company'] && empty($request->company_id)) {
            return response()->json(['success' => false, 'message' => 'Please select a Target Company first.'], 422);
        }

        try {
            DB::beginTransaction();

            // 4. Inject the Company ID securely into the request lifecycle
            // This allows your Seeder files to dynamically grab the targeted company
            if ($request->company_id) {
                request()->attributes->set('seeder_company_id', $request->company_id);
            }

            // 5. Instantiate and Run
            app()->make($seederConfig['class'])->run();

            DB::commit();

            Log::info('[Visual Seeder] Executed successfully', [
                'seeder' => $seederConfig['name'],
                'company_id' => $request->company_id,
                'admin_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$seederConfig['name']} deployed successfully!",
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[Visual Seeder] Execution Failed', [
                'seeder' => $seederConfig['name'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // 👈 ADD THIS
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // 👈 show real error for debugging
            ], 500);
        }
    }
}
