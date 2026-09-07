<?php

use App\Http\Controllers\Admin\Hrm\SalarySlipController;
use App\Models\Company;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalarySlip;
use App\Models\Hrm\SalarySlipItem;
use App\Models\User;
use App\Services\Hrm\Payroll\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/**
 * Spin up tenant + employee + a salary slip with one earning and one deduction.
 *
 * @return array{0: Company, 1: User, 2: Employee, 3: SalarySlip}
 */
function bootSlipContext(string $status = SalarySlip::STATUS_GENERATED): array
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Auth::login($user);

    $empUser = User::factory()->create(['company_id' => $company->id]);
    $employee = Employee::create([
        'company_id' => $company->id,
        'user_id' => $empUser->id,
        'employee_code' => 'EMP-001',
        'date_of_joining' => '2025-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'basic_salary' => 30000,
        'salary_type' => 'monthly',
    ]);

    $slip = SalarySlip::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
        'working_days' => 30,
        'present_days' => 30,
        'gross_earnings' => 30000,
        'total_deductions' => 2000,
        'net_salary' => 28000,
        'round_off' => 0,
        'status' => $status,
        'generated_by' => $user->id,
    ]);

    SalarySlipItem::create([
        'salary_slip_id' => $slip->id,
        'component_name' => 'Basic',
        'component_code' => 'BASIC',
        'type' => 'earning',
        'amount' => 30000,
        'sort_order' => 0,
    ]);
    SalarySlipItem::create([
        'salary_slip_id' => $slip->id,
        'component_name' => 'PF',
        'component_code' => 'PF',
        'type' => 'deduction',
        'amount' => 2000,
        'sort_order' => 1,
    ]);

    return [$company, $user, $employee, $slip];
}

function callUpdate(SalarySlip $slip, array $payload): JsonResponse
{
    $controller = new SalarySlipController(app(PayrollService::class));
    $request = Request::create('/', 'PATCH', $payload);

    return $controller->update($request, $slip);
}

test('isEditable helper returns true only for draft and generated', function () {
    $s = new SalarySlip(['status' => SalarySlip::STATUS_DRAFT]);
    expect($s->isEditable())->toBeTrue();

    $s->status = SalarySlip::STATUS_GENERATED;
    expect($s->isEditable())->toBeTrue();

    $s->status = SalarySlip::STATUS_APPROVED;
    expect($s->isEditable())->toBeFalse();

    $s->status = SalarySlip::STATUS_PAID;
    expect($s->isEditable())->toBeFalse();

    $s->status = SalarySlip::STATUS_CANCELLED;
    expect($s->isEditable())->toBeFalse();
});

test('update recalculates totals when an existing earning amount changes', function () {
    [, , , $slip] = bootSlipContext();
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');
    $pfId = $slip->items()->where('component_code', 'PF')->value('id');

    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 35000],
            ['id' => $pfId, 'component_name' => 'PF', 'type' => 'deduction', 'amount' => 2000],
        ],
        'round_off' => 0,
    ]);

    expect($response->getStatusCode())->toBe(200);

    $fresh = $slip->fresh();
    expect((float) $fresh->gross_earnings)->toBe(35000.0)
        ->and((float) $fresh->total_deductions)->toBe(2000.0)
        ->and((float) $fresh->net_salary)->toBe(33000.0);
});

test('update inserts a newly added earning row with a manual code', function () {
    [, , , $slip] = bootSlipContext();
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');
    $pfId = $slip->items()->where('component_code', 'PF')->value('id');

    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 30000],
            ['id' => null, 'component_name' => 'Bonus', 'type' => 'earning', 'amount' => 5000],
            ['id' => $pfId, 'component_name' => 'PF', 'type' => 'deduction', 'amount' => 2000],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);

    $items = $slip->items()->orderBy('sort_order')->get();
    expect($items)->toHaveCount(3);

    $bonus = $items->firstWhere('component_name', 'Bonus');
    expect($bonus)->not->toBeNull()
        ->and($bonus->type)->toBe('earning')
        ->and((float) $bonus->amount)->toBe(5000.0)
        ->and($bonus->salary_component_id)->toBeNull()
        ->and($bonus->component_code)->toStartWith('MANUAL-');

    $fresh = $slip->fresh();
    expect((float) $fresh->gross_earnings)->toBe(35000.0)
        ->and((float) $fresh->net_salary)->toBe(33000.0);
});

test('update removes rows omitted from the payload', function () {
    [, , , $slip] = bootSlipContext();
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');

    // Only send the earning row — PF should be pruned.
    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 30000],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    expect($slip->items()->count())->toBe(1);

    $fresh = $slip->fresh();
    expect((float) $fresh->total_deductions)->toBe(0.0)
        ->and((float) $fresh->net_salary)->toBe(30000.0);
});

test('update is rejected when slip is approved', function () {
    [, , , $slip] = bootSlipContext(SalarySlip::STATUS_APPROVED);
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');

    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 99999],
        ],
    ]);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['message'])->toContain('locked');

    // Amount must not have changed.
    expect((float) $slip->fresh()->items()->where('id', $basicId)->value('amount'))
        ->toBe(30000.0);
});

test('update is rejected when slip is paid', function () {
    [, , , $slip] = bootSlipContext(SalarySlip::STATUS_PAID);
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');

    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 50000],
        ],
    ]);

    expect($response->getStatusCode())->toBe(422);
    expect((float) $slip->fresh()->gross_earnings)->toBe(30000.0);
});

test('update applies round_off correctly in net salary calculation', function () {
    [, , , $slip] = bootSlipContext();
    $basicId = $slip->items()->where('component_code', 'BASIC')->value('id');
    $pfId = $slip->items()->where('component_code', 'PF')->value('id');

    $response = callUpdate($slip, [
        'items' => [
            ['id' => $basicId, 'component_name' => 'Basic', 'type' => 'earning', 'amount' => 30000.40],
            ['id' => $pfId, 'component_name' => 'PF', 'type' => 'deduction', 'amount' => 2000],
        ],
        'round_off' => 0.60,
    ]);

    expect($response->getStatusCode())->toBe(200);

    $fresh = $slip->fresh();
    expect((float) $fresh->round_off)->toBe(0.60)
        ->and((float) $fresh->net_salary)->toBe(28001.0);
});
