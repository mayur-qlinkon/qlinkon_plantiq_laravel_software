<?php

namespace Database\Seeders\Audit;

use App\Enums\Auth\UserType;
use App\Models\Category;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyModuleLicense;
use App\Models\CompanySubscription;
use App\Models\Module;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\State;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserModuleAccess;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Audit fixture: exactly two fully provisioned tenants.
 *
 * Runs from the console, where Auth::check() is false. That means the
 * Tenantable global scope is NOT registered and company_id is NOT auto
 * assigned, so every insert below sets company_id explicitly. Do not
 * "simplify" that away.
 *
 * Idempotent: safe to re-run on top of an existing database.
 */
class TwoTenantAuditSeeder extends Seeder
{
    public const PASSWORD = 'Audit@2026';

    private array $tenants = [
        [
            'name' => 'Alpha Nursery',
            'slug' => 'alpha-nursery',
            'subdomain' => 'alpha',
            'email' => 'owner@alpha-nursery.test',
            'phone' => '9000000001',
            'admin' => ['name' => 'Alpha Admin', 'email' => 'admin@alpha-nursery.test', 'phone' => '9000001001'],
            'prefix' => 'ALP',
            'gst_seed' => '24AAAAA0000A1Z',
        ],
        [
            'name' => 'Beta Greens',
            'slug' => 'beta-greens',
            'subdomain' => 'beta',
            'email' => 'owner@beta-greens.test',
            'phone' => '9000000002',
            'admin' => ['name' => 'Beta Admin', 'email' => 'admin@beta-greens.test', 'phone' => '9000002001'],
            'prefix' => 'BET',
            'gst_seed' => '27BBBBB0000B1Z',
        ],
    ];

    public function run(): void
    {
        $stateId = State::query()->where('code', '24')->value('id');

        $plan = $this->fullAccessPlan();
        $moduleIds = Module::query()->where('is_active', true)->pluck('id')->all();
        $permIds = Permission::query()->pluck('id')->all();

        foreach ($this->tenants as $spec) {
            DB::transaction(function () use ($spec, $plan, $moduleIds, $permIds, $stateId) {
                $company = $this->company($spec, $stateId);

                $this->subscription($company, $plan);
                $this->moduleLicenses($company, $moduleIds);

                $admin = $this->admin($company, $spec, $stateId);
                $this->roleFor($company, $admin, $permIds);
                $this->userModules($company, $admin, $moduleIds);

                $store = $this->store($company, $spec, $stateId);
                $warehouses = $this->warehouses($company, $store, $spec, $stateId);
                $units = $this->units($company);
                $category = $this->category($company);
                $suppliers = $this->suppliers($company, $spec, $stateId);

                $this->paymentMethods($company);
                $this->products($company, $spec, $units, $category, $suppliers[0], $warehouses);
                $this->clients($company, $spec, $stateId);

                if (isset($this->command)) {
                    $this->command->info("  seeded: {$company->name}  (slug: {$company->slug}, company_id: {$company->id})");
                }
            });
        }

        if (isset($this->command)) {
            $this->command->info('Audit tenants ready. Password for both admins: '.self::PASSWORD);
        }
    }

    // -- Platform-level plan shared by both tenants -------------------------

    private function fullAccessPlan(): Plan
    {
        $plan = Plan::withTrashed()->updateOrCreate(
            ['slug' => 'audit-full-access'],
            [
                'company_id' => null,
                'name' => 'Audit Full Access',
                'description' => 'Every module enabled. Audit fixture only.',
                'price' => 0,
                'billing_cycle' => 'yearly',
                'user_limit' => 999,
                'store_limit' => 999,
                'product_limit' => 9999,
                'employee_limit' => 999,
                'ocr_scan_limit' => 9999,
                'ai_chat_daily_limit' => 9999,
                'ai_token_daily_limit' => 9999999,
                'is_active' => true,
                'deleted_at' => null,
            ]
        );

        $plan->modules()->sync(Module::query()->where('is_active', true)->pluck('id')->all());

        return $plan;
    }

    // -- Tenant pieces ------------------------------------------------------

    private function company(array $spec, ?int $stateId): Company
    {
        return Company::withTrashed()->updateOrCreate(
            ['slug' => $spec['slug']],
            [
                'name' => $spec['name'],
                'subdomain' => $spec['subdomain'],
                'email' => $spec['email'],
                'phone' => $spec['phone'],
                'currency' => 'INR',
                'address' => $spec['name'].' Head Office',
                'city' => 'Ahmedabad',
                'state_id' => $stateId,
                'zip_code' => '380001',
                'country' => 'India',
                'is_active' => true,
                'deleted_at' => null,
            ]
        );
    }

    private function subscription(Company $company, Plan $plan): void
    {
        CompanySubscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_id' => $plan->id,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ]
        );

        CompanySubscription::forgetCache($company->id);
    }

    private function moduleLicenses(Company $company, array $moduleIds): void
    {
        foreach ($moduleIds as $moduleId) {
            CompanyModuleLicense::updateOrCreate(
                ['company_id' => $company->id, 'module_id' => $moduleId],
                [
                    'seat_limit' => null,
                    'is_active' => true,
                    'starts_at' => now()->subDay(),
                    'expires_at' => now()->addYear(),
                ]
            );
        }
    }

    private function admin(Company $company, array $spec, ?int $stateId): User
    {
        $user = User::withTrashed()->updateOrCreate(
            ['company_id' => $company->id, 'email' => $spec['admin']['email']],
            [
                'name' => $spec['admin']['name'],
                'user_type' => UserType::COMPANY_ADMIN,
                'phone' => $spec['admin']['phone'],
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'status' => 'active',
                'country' => 'India',
                'deleted_at' => null,
            ]
        );

        $user->state_id = $stateId;
        $user->save();

        return $user;
    }

    private function roleFor(Company $company, User $admin, array $permIds): void
    {
        $role = Role::updateOrCreate(
            ['company_id' => $company->id, 'slug' => 'owner'],
            ['name' => 'Owner']
        );

        $role->permissions()->sync($permIds);
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }

    private function userModules(Company $company, User $admin, array $moduleIds): void
    {
        foreach ($moduleIds as $moduleId) {
            UserModuleAccess::updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $admin->id, 'module_id' => $moduleId],
                ['assigned_by' => $admin->id, 'assigned_at' => now()]
            );
        }
    }

    private function store(Company $company, array $spec, ?int $stateId): Store
    {
        $store = Store::withTrashed()->updateOrCreate(
            ['company_id' => $company->id, 'slug' => $spec['slug'].'-main'],
            [
                'name' => $spec['name'].' Main Store',
                'email' => $spec['email'],
                'phone' => $spec['phone'],
                'gst_number' => $spec['gst_seed'].'1',
                'currency' => 'INR',
                'address' => $spec['name'].' Main Store',
                'city' => 'Ahmedabad',
                'state_id' => $stateId,
                'zip_code' => '380001',
                'country' => 'India',
                'invoice_prefix' => $spec['prefix'].'-INV',
                'quotation_prefix' => $spec['prefix'].'-QTN',
                'purchase_prefix' => $spec['prefix'].'-PUR',
                'next_invoice_number' => 1,
                'storefront_enabled' => true,
                'is_primary' => true,
                'is_active' => true,
                'deleted_at' => null,
            ]
        );

        $store->users()->syncWithoutDetaching(
            User::withTrashed()->where('company_id', $company->id)->pluck('id')->all()
        );

        return $store;
    }

    /** @return Warehouse[] */
    private function warehouses(Company $company, Store $store, array $spec, ?int $stateId): array
    {
        $out = [];

        foreach ([['Main Godown', true], ['Overflow Godown', false]] as $i => $row) {
            [$name, $isDefault] = $row;

            $w = Warehouse::withTrashed()->firstOrNew([
                'company_id' => $company->id,
                'name' => $name,
            ]);

            $w->company_id = $company->id;
            $w->store_id = $store->id;
            $w->name = $name;
            $w->code = $spec['prefix'].'-WH-'.($i + 1);
            $w->city = 'Ahmedabad';
            $w->state_id = $stateId;
            $w->country = 'India';
            $w->is_default = $isDefault;
            $w->is_active = true;
            $w->deleted_at = null;
            $w->save();

            $out[] = $w;
        }

        return $out;
    }

    /** @return array<string,Unit> */
    private function units(Company $company): array
    {
        $out = [];

        foreach ([['Piece', 'pcs'], ['Kilogram', 'kg']] as $row) {
            [$name, $short] = $row;

            $u = Unit::withTrashed()->firstOrNew(['company_id' => $company->id, 'name' => $name]);
            $u->company_id = $company->id;
            $u->name = $name;
            $u->short_name = $short;
            $u->is_active = true;
            $u->deleted_at = null;
            $u->save();

            $out[$short] = $u;
        }

        return $out;
    }

    private function category(Company $company): Category
    {
        $c = Category::withTrashed()->firstOrNew(['company_id' => $company->id, 'slug' => 'live-plants']);
        $c->company_id = $company->id;
        $c->name = 'Live Plants';
        $c->slug = 'live-plants';
        $c->is_active = true;
        $c->deleted_at = null;
        $c->save();

        return $c;
    }

    /** @return Supplier[] */
    private function suppliers(Company $company, array $spec, ?int $stateId): array
    {
        $out = [];

        foreach (['Green Roots Supply', 'Terra Pots and Media'] as $i => $name) {
            $s = Supplier::withTrashed()->firstOrNew(['company_id' => $company->id, 'name' => $name]);
            $s->company_id = $company->id;
            $s->name = $name;
            $s->email = strtolower($spec['prefix']).'.sup'.($i + 1).'@'.$spec['slug'].'.test';
            $s->phone = '98'.substr($spec['phone'], -4).'00'.($i + 1);
            $s->city = 'Ahmedabad';
            $s->state_id = $stateId;
            // suppliers.gstin carries a GLOBAL unique index, not one scoped to
            // company_id, so these must be distinct across tenants for the
            // fixture to seed at all.
            $s->gstin = $spec['gst_seed'].($i + 2);
            $s->registration_type = 'regular';
            $s->is_active = true;
            $s->deleted_at = null;
            $s->save();

            $out[] = $s;
        }

        return $out;
    }

    private function paymentMethods(Company $company): void
    {
        $methods = [
            ['cash', 'Cash', null, false, 1],
            ['upi', 'UPI / QR Code', null, false, 2],
            ['bank_transfer', 'Bank Transfer (NEFT/IMPS)', null, false, 3],
        ];

        foreach ($methods as $row) {
            [$slug, $label, $gateway, $isOnline, $sort] = $row;

            $m = PaymentMethod::withTrashed()->firstOrNew(['company_id' => $company->id, 'slug' => $slug]);
            $m->company_id = $company->id;
            $m->slug = $slug;
            $m->label = $label;
            $m->gateway = $gateway;
            $m->is_online = $isOnline;
            $m->is_active = true;
            $m->sort_order = $sort;
            $m->deleted_at = null;
            $m->save();
        }
    }

    /**
     * @param  array<string,Unit>  $units
     * @param  Warehouse[]  $warehouses
     */
    private function products(
        Company $company,
        array $spec,
        array $units,
        Category $category,
        Supplier $supplier,
        array $warehouses
    ): void {
        $catalogue = [
            ['Snake Plant', 249.00, 149.00, 299.00],
            ['Areca Palm', 399.00, 260.00, 449.00],
            ['Money Plant', 149.00, 80.00, 179.00],
            ['Peace Lily', 329.00, 210.00, 379.00],
            ['Jade Plant', 199.00, 120.00, 229.00],
        ];

        foreach ($catalogue as $i => $row) {
            [$name, $price, $cost, $mrp] = $row;

            $slug = Str::slug($name);

            $product = Product::withTrashed()->firstOrNew(['company_id' => $company->id, 'slug' => $slug]);
            $product->company_id = $company->id;
            $product->category_id = $category->id;
            $product->supplier_id = $supplier->id;
            $product->name = $name;
            $product->slug = $slug;
            $product->type = 'single';
            $product->product_type = 'sellable';
            $product->barcode_symbology = 'CODE128';
            $product->hsn_code = '0602';
            $product->product_unit_id = $units['pcs']->id;
            $product->sale_unit_id = $units['pcs']->id;
            $product->purchase_unit_id = $units['pcs']->id;
            $product->is_active = true;
            $product->show_in_storefront = true;
            $product->deleted_at = null;
            $product->save();

            $skuCode = $spec['prefix'].'-SKU-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $sku = ProductSku::withTrashed()->firstOrNew(['company_id' => $company->id, 'sku' => $skuCode]);
            $sku->company_id = $company->id;
            $sku->product_id = $product->id;
            $sku->unit_id = $units['pcs']->id;
            $sku->sku = $skuCode;
            $sku->barcode = $spec['prefix'].'BC'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
            $sku->cost = $cost;
            $sku->price = $price;
            $sku->mrp = $mrp;
            $sku->hsn_code = '0602';
            $sku->gst_rate = 5.00;
            $sku->order_tax = 5.00;
            $sku->tax_type = 'exclusive';
            $sku->stock_alert = 5;
            $sku->is_active = true;
            $sku->deleted_at = null;
            $sku->save();

            foreach ($warehouses as $w) {
                $stock = ProductStock::firstOrNew([
                    'product_sku_id' => $sku->id,
                    'warehouse_id' => $w->id,
                ]);
                // company_id is NOT in ProductStock::$fillable and there is no
                // Auth here to auto-fill it, so it must be set by hand or the
                // insert dies on the NOT NULL constraint.
                $stock->company_id = $company->id;
                $stock->qty = $w->is_default ? 100 : 50;
                $stock->save();
            }
        }
    }

    private function clients(Company $company, array $spec, ?int $stateId): void
    {
        $names = ['Riverside Resorts', 'Nimbus Interiors', 'Kavya Sharma'];

        foreach ($names as $i => $name) {
            $code = $spec['prefix'].'-CL-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $c = Client::withTrashed()->firstOrNew(['company_id' => $company->id, 'client_code' => $code]);
            $c->company_id = $company->id;
            $c->name = $name;
            $c->client_code = $code;
            $c->company_name = $i < 2 ? $name.' Pvt Ltd' : null;
            $c->email = strtolower($spec['prefix']).'.client'.($i + 1).'@'.$spec['slug'].'.test';
            $c->phone = '97'.substr($spec['phone'], -4).'00'.($i + 1);
            $c->registration_type = 'unregistered';
            $c->city = 'Ahmedabad';
            $c->state_id = $stateId;
            $c->country = 'India';
            $c->is_active = true;
            $c->deleted_at = null;
            $c->save();
        }
    }
}
