<?php

namespace App\Services\Platform;

use App\Enums\Auth\UserType;
use App\Models\Category;
use App\Models\Client;
use App\Models\Company;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Shift;
use App\Models\Module;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * TenantBootstrapper
 *
 * Seeds the minimum a freshly onboarded tenant needs to start working, so
 * their first visit to a screen is never a blocked empty state (a product,
 * for instance, cannot be created at all without a unit, a category and a
 * warehouse already existing).
 *
 * Deliberately ONE row per table. These are starting points, not opinions:
 * the tenant renames, edits or deletes them immediately. Seeding five
 * payment methods because five are plausible just leaves four rows every
 * tenant has to clean up, multiplied across every company on the platform.
 *
 * Called from CompanyOnboardingService::onboard() INSIDE its
 * transaction, so a failure here rolls the whole onboarding back — a company
 * that cannot be seeded is not one we want half-made.
 *
 * Two entry points:
 *   bootstrapCore()    — always runs. Backs the 'core' permission group,
 *                        which every company gets regardless of plan.
 *   bootstrapModules() — runs per module the plan includes.
 *
 * Adding another module later = one private method plus one line in
 * bootstrapModules().
 */
class TenantBootstrapper
{
    // ──────────────────────────────────────────────────────────────
    // PUBLIC ENTRY POINTS
    // ──────────────────────────────────────────────────────────────

    /**
     * Data every company gets, plan or no plan.
     *
     * @return array|null Plaintext credentials of the starter user for
     *                    one-time display, or null when none was created.
     *                    Never persisted — read it immediately.
     */
    public function bootstrapCore(Company $company, Store $store, array $data): ?array
    {
        $role = $this->createRole($company);

        $this->createClient($company);
        $this->createPaymentMethod($company);

        return $this->createUser($company, $store, $role, $data);
    }

    /**
     * Starter data for each module the plan includes.
     */
    public function bootstrapModules(Company $company, Store $store, array $moduleIds): void
    {
        if (empty($moduleIds)) {
            return;
        }

        // The onboarding form submits module IDs; seeding keys on slugs
        // because IDs differ between environments.
        $slugs = Module::whereIn('id', $moduleIds)->pluck('slug')->all();

        if (in_array('inventory', $slugs, true)) {
            $this->bootstrapInventory($company, $store);
        }

        if (in_array('hrm', $slugs, true)) {
            $this->bootstrapHrm($company);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // CORE
    // ──────────────────────────────────────────────────────────────

    /**
     * The owner does not need a role — identity, not role, grants their
     * access — but every user the tenant creates later does, and an empty
     * Roles screen is a dead end.
     *
     * No permissions are attached on purpose: the tenant ticks what this
     * role should do. Attaching a guess here means they have to work out
     * what we already granted before they can trust the screen.
     */
    private function createRole(Company $company): Role
    {
        // Matching on (company_id, slug) mirrors the table's unique index, so
        // a re-run reuses the row instead of throwing. The model's creating()
        // hook derives slug from name, keeping both in sync.
        return Role::firstOrCreate(
            ['company_id' => $company->id, 'slug' => "staff"],
            ['name' => "Staff"]
        );
    }

    /**
     * A billing document always needs a party. Without this row the very
     * first invoice or POS sale stops the tenant to create a client.
     */
    private function createClient(Company $company): Client
    {
        return Client::create([
            'company_id'        => $company->id,
            'name'              => 'Walk-in Customer',
            // First client of a brand-new company, so the sequence starts at
            // 1. ClientService::generateClientCode() is deliberately not used:
            // it reads Client::latest('id'), which under a super admin session
            // carries no tenant scope and would return another company's last
            // client.
            'client_code'       => 'CLI-00001',
            'registration_type' => 'unregistered',
            'country'           => 'India',
            'state_id'          => $company->state_id,
            'is_active'         => true,
        ]);
    }

    /**
     * Cash only. It is the one method that needs no configuration and works
     * for every business type; anything online needs gateway credentials the
     * tenant has not supplied yet, and a method pointing at an unconfigured
     * gateway fails at checkout rather than at setup.
     */
    private function createPaymentMethod(Company $company): PaymentMethod
    {
        return $this->createForCompany(PaymentMethod::class, $company, [
            'slug'       => 'cash',
            'label'      => 'Cash',
            'is_online'  => false,
            'is_active'  => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * One usable staff login, so the tenant can see their RBAC working
     * without building a user first. Returns null when the plan has no room.
     */
    private function createUser(Company $company, Store $store, Role $role, array $data): ?array
    {
        // user_limit counts the owner created moments ago, so a second login
        // only fits when the limit exceeds 1.
        if ((int) ($data['user_limit'] ?? 1) <= 1) {
            return null;
        }

        $email = $data['default_user_email'] ?? 'staff@' . $company->slug . '.local';

        // users carries a unique index on (company_id, email) — bail rather
        // than blow up the whole onboarding transaction on a collision.
        if (User::where('company_id', $company->id)->where('email', $email)->exists()) {
            return null;
        }

        $password = Str::password(12, symbols: false, spaces: false);

        $user = User::create([
            'company_id' => $company->id,
            'name'       => $data['default_user_name'] ?? 'Staff User',
            'email'      => $email,
            'password'   => Hash::make($password),
            'state_id'   => $company->state_id,
            'status'     => 'active',
            'user_type'  => UserType::INTERNAL,
        ]);

        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        // No module seats are granted on purpose: seats are billable and the
        // tenant decides who consumes them. Until then this login sees the
        // launcher with core tiles only.

        return ['email' => $email, 'password' => $password];
    }

    // ──────────────────────────────────────────────────────────────
    // MODULE: INVENTORY
    // ──────────────────────────────────────────────────────────────

    private function bootstrapInventory(Company $company, Store $store): void
    {
        $unit = $this->createForCompany(Unit::class, $company, [
            'name'       => 'Piece',
            'short_name' => 'pcs',
            'is_active'  => true,
        ]);

        $category = $this->createForCompany(Category::class, $company, [
            'name'      => 'General',
            'is_active' => true,
        ]);

        $warehouse = $this->createForCompany(Warehouse::class, $company, [
            'store_id'   => $store->id,
            'name'       => 'Main Warehouse',
            'is_default' => true,
            'is_active'  => true,
        ]);

        // products.slug is unique per company and the model's slug hook is
        // commented out, so it has to be set by hand.
        $product = Product::create([
            'company_id'         => $company->id,
            'category_id'        => $category->id,
            'product_unit_id'    => $unit->id,   // NOT NULL in the schema
            'sale_unit_id'       => $unit->id,
            'purchase_unit_id'   => $unit->id,
            'name'               => 'Sample Product',
            'slug'               => 'sample-product',
            'type'               => 'single',
            'product_type'       => 'sellable',
            'barcode_symbology'  => 'CODE128',
            'is_active'          => true,
            // Kept off the public storefront on purpose — a demo row must
            // never reach a customer-facing page.
            'show_in_storefront' => false,
        ]);

        $product->categories()->attach($category->id);

        $sku = ProductSku::create([
            'company_id'  => $company->id,
            'product_id'  => $product->id,
            'unit_id'     => $unit->id,
            'sku'         => 'SAMPLE-001',
            'cost'        => 80,
            'price'       => 100,
            'mrp'         => 120,
            'order_tax'   => 0,
            'tax_type'    => 'exclusive',
            'stock_alert' => 0,
            'is_active'   => true,
        ]);

        // Zero qty, not a fake number: the row exists so the warehouse shows
        // up in stock screens, but no phantom inventory is implied.
        $this->createForCompany(ProductStock::class, $company, [
            'product_sku_id' => $sku->id,
            'warehouse_id'   => $warehouse->id,
            'qty'            => 0,
        ]);
    }

    /**
     * HRM cannot be used out of the box without these two rows.
     *
     * Every employee record requires a shift, and a leave request requires a
     * leave type — so an empty HRM module is a dead end in both directions:
     * the tenant cannot add their first employee, and that employee cannot
     * apply for anything.
     *
     * Deliberately minimal. One neutral shift and one unrestricted leave type,
     * so the tenant configures their real policy rather than discovering our
     * guesses after the fact.
     */
    private function bootstrapHrm(Company $company): void
    {
        // is_default matters: employee forms preselect it, so without one the
        // shift dropdown opens empty on every new employee.
        $this->createForCompany(Shift::class, $company, [
            'name'                      => 'General Shift',
            'code'                      => 'GEN',
            'start_time'                => '09:00:00',
            'end_time'                  => '18:00:00',
            'break_duration_minutes'    => 60,
            'min_working_hours_minutes' => 480,
            'is_night_shift'            => false,
            'is_default'                => true,
            'is_active'                 => true,
            'sort_order'                => 0,
        ]);

        // No advance-notice or consecutive-day limits, and no document
        // requirement: LeaveService enforces all three, and a seeded guess
        // would reject the tenant's very first leave request for a rule they
        // never set.
        $this->createForCompany(LeaveType::class, $company, [
            'name'                   => 'Casual Leave',
            'code'                   => 'CL',
            'description'            => 'General purpose leave.',
            'default_days_per_year'  => 12,
            'is_paid'                => true,
            'is_carry_forward'       => false,
            'max_carry_forward_days' => 0,
            'is_encashable'          => false,
            'requires_document'      => false,
            'min_days_before_apply'  => 0,
            'max_consecutive_days'   => 0,
            'applicable_gender'      => 'all',
            'is_active'              => true,
            'sort_order'             => 0,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    /**
     * Create a tenant-owned row with an explicit company_id.
     *
     * Tenantable only auto-fills company_id when the acting user belongs to a
     * company; onboarding runs as a super admin, so it never fires. And
     * PaymentMethod, Unit, Category, Warehouse and ProductStock all omit
     * company_id from $fillable, so handing it to create() would silently
     * drop it and write an orphan row. Assigning on the instance bypasses
     * mass assignment entirely.
     *
     * Client, Product and ProductSku do list company_id, so they use
     * create() directly.
     */
    private function createForCompany(string $modelClass, Company $company, array $attributes): Model
    {
        $model = new $modelClass($attributes);
        $model->company_id = $company->id;
        $model->save();

        return $model;
    }
}