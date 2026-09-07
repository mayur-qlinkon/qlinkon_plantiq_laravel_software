<?php

use App\Http\Controllers\Admin\Hrm\SalarySlipController;
use App\Models\Company;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalarySlip;
use App\Models\Hrm\SalarySlipItem;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Hrm\SalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Build an approved slip ready to be marked as paid.
 *
 * @return array{0: Company, 1: User, 2: SalarySlip}
 */
function bootApprovedSlip(): array
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Auth::login($user);

    $empUser = User::factory()->create(['company_id' => $company->id]);
    $employee = Employee::create([
        'company_id' => $company->id,
        'user_id' => $empUser->id,
        'employee_code' => 'EMP-PAY-001',
        'date_of_joining' => '2025-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'basic_salary' => 50000,
        'salary_type' => 'monthly',
    ]);

    $slip = SalarySlip::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'month' => 4,
        'year' => 2026,
        'working_days' => 30,
        'present_days' => 30,
        'gross_earnings' => 50000,
        'total_deductions' => 3000,
        'net_salary' => 47000,
        'round_off' => 0,
        'status' => SalarySlip::STATUS_APPROVED,
        'generated_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    SalarySlipItem::create([
        'salary_slip_id' => $slip->id,
        'component_name' => 'Basic',
        'component_code' => 'BASIC',
        'type' => 'earning',
        'amount' => 50000,
        'sort_order' => 0,
    ]);

    return [$company, $user, $slip];
}

function makePaymentMethod(int $companyId, string $label = 'Bank Transfer', string $slug = 'bank_transfer'): PaymentMethod
{
    return PaymentMethod::create([
        'company_id' => $companyId,
        'label' => $label,
        'slug' => $slug,
        'gateway' => null,
        'is_online' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function callMarkPaid(SalarySlip $slip, array $payload): JsonResponse
{
    $controller = new SalarySlipController(app(SalaryService::class));
    $request = Request::create('/', 'PATCH', $payload);

    return $controller->markPaid($request, $slip);
}

// ── Accessor tests ─────────────────────────────────────────────────────────────

test('payment_label returns payment_method_name when set (new slips)', function () {
    $slip = new SalarySlip([
        'payment_method_name' => 'UPI',
        'payment_mode' => null,
    ]);

    expect($slip->payment_label)->toBe('UPI');
});

test('payment_label falls back to formatted payment_mode for legacy slips', function () {
    $slip = new SalarySlip([
        'payment_method_name' => null,
        'payment_mode' => 'bank_transfer',
    ]);

    expect($slip->payment_label)->toBe('Bank transfer');
});

test('payment_label is null when both fields are null', function () {
    $slip = new SalarySlip([
        'payment_method_name' => null,
        'payment_mode' => null,
    ]);

    expect($slip->payment_label)->toBeNull();
});

test('payment_label prefers payment_method_name over legacy payment_mode', function () {
    $slip = new SalarySlip([
        'payment_method_name' => 'UPI',
        'payment_mode' => 'bank_transfer', // old value still present
    ]);

    expect($slip->payment_label)->toBe('UPI');
});

// ── Service / Controller integration tests ─────────────────────────────────────

test('markPaid stores payment_method_id and payment_method_name snapshot', function () {
    [$company, , $slip] = bootApprovedSlip();
    $method = makePaymentMethod($company->id, 'Bank Transfer');

    $response = callMarkPaid($slip, [
        'payment_method_id' => $method->id,
        'payment_date' => '2026-04-30',
    ]);

    expect($response->getStatusCode())->toBe(200);

    $fresh = $slip->fresh();
    expect($fresh->status)->toBe(SalarySlip::STATUS_PAID)
        ->and($fresh->payment_method_id)->toBe($method->id)
        ->and($fresh->payment_method_name)->toBe('Bank Transfer')
        ->and($fresh->payment_label)->toBe('Bank Transfer');
});

test('markPaid controller validation rejects when payment_method_id is missing', function () {
    [, , $slip] = bootApprovedSlip();

    // Calling the controller directly throws ValidationException (no HTTP layer to convert it).
    expect(fn () => callMarkPaid($slip, ['payment_date' => '2026-04-30']))
        ->toThrow(ValidationException::class);

    expect($slip->fresh()->status)->toBe(SalarySlip::STATUS_APPROVED);
});

test('markPaid controller validation rejects non-existent payment_method_id', function () {
    [, , $slip] = bootApprovedSlip();

    expect(fn () => callMarkPaid($slip, [
        'payment_method_id' => 99999,
        'payment_date' => '2026-04-30',
    ]))->toThrow(ValidationException::class);

    expect($slip->fresh()->status)->toBe(SalarySlip::STATUS_APPROVED);
});

test('legacy slips with only payment_mode still display correctly via payment_label', function () {
    [$company, , $slip] = bootApprovedSlip();

    // Directly update as if this is an old slip saved before the new columns existed.
    $slip->update([
        'status' => SalarySlip::STATUS_PAID,
        'payment_mode' => 'cash',
        'payment_method_id' => null,
        'payment_method_name' => null,
    ]);

    $fresh = $slip->fresh();
    expect($fresh->payment_label)->toBe('Cash')
        ->and($fresh->payment_method_id)->toBeNull();
});

test('isEditable returns false for paid slip (regression)', function () {
    [$company, , $slip] = bootApprovedSlip();
    $method = makePaymentMethod($company->id, 'UPI', 'upi');

    callMarkPaid($slip, [
        'payment_method_id' => $method->id,
        'payment_date' => '2026-04-30',
    ]);

    expect($slip->fresh()->isEditable())->toBeFalse();
});
