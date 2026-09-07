<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureStoreExists;
use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalUserFilteringTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Store $store;

    private User $owner;

    private User $staffUser;

    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscription::class,
            EnsureStoreExists::class,
        ]);

        $this->company = Company::create([
            'name' => 'Qlinkon Labs',
        ]);

        $this->store = Store::create([
            'company_id' => $this->company->id,
            'name' => 'Main Store',
        ]);

        $ownerRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'Owner',
        ]);

        $employeeRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'Employee',
        ]);

        $customerRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'Customer',
        ]);

        $this->owner = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Owner Admin',
            'email' => 'owner@example.test',
            'status' => 'active',
        ]);
        $this->owner->roles()->attach($ownerRole->id);
        $this->owner->stores()->attach($this->store->id);

        $this->staffUser = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Staff Agent',
            'email' => 'staff@example.test',
            'status' => 'active',
        ]);
        $this->staffUser->roles()->attach($employeeRole->id);
        $this->staffUser->stores()->attach($this->store->id);

        $this->customerUser = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Portal Client',
            'email' => 'client@example.test',
            'status' => 'active',
        ]);
        $this->customerUser->roles()->attach($customerRole->id);

        Client::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->customerUser->id,
            'name' => 'Portal Client',
            'email' => 'client@example.test',
            'registration_type' => 'registered',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner);
    }

    public function test_admin_users_page_hides_customer_logins_and_customer_role_option(): void
    {
        $response = $this->withSession(['store_id' => $this->store->id])
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Staff Agent');
        $response->assertDontSee('Portal Client');
        $response->assertDontSee('Customer');
    }

    public function test_crm_lead_create_page_excludes_customer_logins_from_assignee_lists(): void
    {
        $response = $this->withSession(['store_id' => $this->store->id])
            ->get(route('admin.crm.leads.create'));

        $response->assertOk();
        $response->assertSee('Staff Agent');
        $response->assertDontSee('Portal Client');
    }

    public function test_crm_lead_creation_rejects_customer_logins_as_assignees(): void
    {
        $response = $this->withSession(['store_id' => $this->store->id])
            ->postJson(route('admin.crm.leads.store'), [
                'name' => 'Test Lead',
                'assigned_to' => $this->customerUser->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assigned_to']);
        $response->assertJsonPath('errors.assigned_to.0', 'Selected assignee must be a staff user.');
    }

    public function test_hrm_user_selectors_exclude_customer_logins(): void
    {
        $employeeCreateResponse = $this->withSession(['store_id' => $this->store->id])
            ->get(route('admin.hrm.employees.create'));

        $employeeCreateResponse->assertOk();
        $employeeCreateResponse->assertSee('Staff Agent');
        $employeeCreateResponse->assertDontSee('Portal Client');

        $departmentIndexResponse = $this->withSession(['store_id' => $this->store->id])
            ->get(route('admin.hrm.departments.index'));

        $departmentIndexResponse->assertOk();
        $departmentIndexResponse->assertDontSee('Portal Client');
    }
}
