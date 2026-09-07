<?php

use App\Http\Middleware\CheckPendingAnnouncements;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureValidStoreSession;
use App\Models\Company;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\AttendanceLog;
use App\Models\Hrm\AttendanceRule;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Shift;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\Hrm\AnnouncementService;
use App\Services\Hrm\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $mock = Mockery::mock(AnnouncementService::class);
    $mock->shouldReceive('hasPendingMandatory')->andReturn(false);
    app()->instance(AnnouncementService::class, $mock);

    $this->travelTo(today()->setTimeFromTimeString('08:55:00'));
});

function seedAttendanceContext(array $overrides = []): array
{
    $company = Company::create(['name' => 'Test Company']);

    $store = Store::create(array_merge([
        'company_id' => $company->id,
        'name' => 'Mumbai Office',
        'office_lat' => 19.0760000,
        'office_lng' => 72.8777000,
        'gps_radius_meters' => 200,
    ], $overrides['store'] ?? []));

    $shift = Shift::create([
        'company_id' => $company->id,
        'name' => 'General Shift',
        'code' => 'GEN',
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
        'late_mark_after' => '09:15:00',
        'half_day_after' => '10:30:00',
        'early_leave_before' => '17:30:00',
        'min_working_hours_minutes' => 480,
        'overtime_after_minutes' => 540,
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $user->stores()->attach($store->id);

    $employee = Employee::create(array_merge([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'store_id' => $store->id,
        'shift_id' => $shift->id,
        'employee_code' => 'EMP-0001',
        'date_of_joining' => now()->subYear(),
        'status' => Employee::STATUS_ACTIVE,
    ], $overrides['employee'] ?? []));

    return compact('company', 'store', 'shift', 'user', 'employee');
}

function scanPayload(Store $store, array $overrides = []): array
{
    return array_merge([
        'store_id' => $store->id,
        'latitude' => $store->office_lat,
        'longitude' => $store->office_lng,
    ], $overrides);
}

function withoutAttendanceMiddleware($testCase): void
{
    $testCase->withoutMiddleware([
        CheckSubscription::class,
        EnsureValidStoreSession::class,
        CheckPendingAnnouncements::class,
    ]);
}

test('employee can check in via the single attendance scan endpoint', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('action', AttendanceLog::ACTION_CHECK_IN)
        ->assertJsonPath('message', 'Check-in recorded successfully.');

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'store_id' => $ctx['store']->id,
        'status' => Attendance::STATUS_PRESENT,
        'check_in_method' => Attendance::METHOD_QR,
    ]);
});

test('successful check-in creates an attendance log', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $this->assertDatabaseHas('attendance_logs', [
        'employee_id' => $ctx['employee']->id,
        'action' => AttendanceLog::ACTION_CHECK_IN,
        'is_valid' => true,
    ]);
});

test('check-in after late threshold marks employee as late', function () {
    $ctx = seedAttendanceContext();

    $this->travelTo(today()->setTimeFromTimeString('09:20:00'));
    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('message', 'Check-in recorded. You have been marked late.')
        ->assertJsonPath('type', 'warning');

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_LATE,
    ]);
});

test('check-in after half day threshold marks employee as half day', function () {
    $ctx = seedAttendanceContext();

    $this->travelTo(today()->setTimeFromTimeString('10:45:00'));
    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('message', 'Check-in recorded. You have been marked half day.')
        ->assertJsonPath('type', 'warning');

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_HALF_DAY,
    ]);
});

test('scan is rejected when no shift is assigned', function () {
    $ctx = seedAttendanceContext(['employee' => ['shift_id' => null]]);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'No shift is assigned to your employee profile.',
        ]);
});

test('second scan after shift end performs check out', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $this->travelTo(today()->setTimeFromTimeString('18:35:00'));

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('action', AttendanceLog::ACTION_CHECK_OUT)
        ->assertJsonPath('message', 'Check-out recorded successfully.');

    $attendance = Attendance::first();

    expect((float) $attendance->overtime_hours)->toBeGreaterThan(0.5);
    expect($attendance->check_out_time)->not->toBeNull();
});

test('early checkout requires confirmation and force checkout completes it', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $this->travelTo(today()->setTimeFromTimeString('16:00:00'));

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('requires_confirmation', true)
        ->assertJsonPath('action', AttendanceLog::ACTION_CHECK_OUT);

    $confirmed = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store'], [
            'force_checkout' => true,
        ]));

    $confirmed->assertOk()
        ->assertJsonPath('action', AttendanceLog::ACTION_CHECK_OUT)
        ->assertJsonPath('type', 'warning');

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_HALF_DAY,
        'check_out_method' => Attendance::METHOD_QR,
    ]);
});

test('third scan after completed attendance is rejected', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $this->travelTo(today()->setTimeFromTimeString('18:10:00'));

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Attendance already completed for this shift.',
        ]);
});

test('scan at unassigned store is rejected', function () {
    $ctx = seedAttendanceContext();

    $otherStore = Store::create([
        'company_id' => $ctx['company']->id,
        'name' => 'Delhi Office',
        'office_lat' => 28.6139000,
        'office_lng' => 77.2090000,
        'gps_radius_meters' => 200,
    ]);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($otherStore));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'You are not assigned to this store.',
        ]);

    $this->assertDatabaseHas('attendance_logs', [
        'employee_id' => $ctx['employee']->id,
        'is_valid' => false,
        'rejection_reason' => 'You are not assigned to this store.',
    ]);
});

test('scan outside store gps radius is rejected', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store'], [
            'latitude' => 28.6139000,
            'longitude' => 77.2090000,
        ]));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'You are outside the allowed attendance radius for this store.',
        ]);
});

test('scan is rejected when store gps is not configured', function () {
    $ctx = seedAttendanceContext(['store' => [
        'office_lat' => null,
        'office_lng' => null,
        'gps_radius_meters' => null,
    ]]);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), [
            'store_id' => $ctx['store']->id,
            'latitude' => 19.0760000,
            'longitude' => 72.8777000,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'This store does not have attendance GPS settings configured.',
        ]);
});

test('inactive employee cannot scan', function () {
    $ctx = seedAttendanceContext(['employee' => ['status' => Employee::STATUS_INACTIVE]]);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'You are not registered as an active employee.',
        ]);
});

test('user without employee record cannot scan', function () {
    $company = Company::create(['name' => 'Test Company']);
    $store = Store::create([
        'company_id' => $company->id,
        'name' => 'Test Store',
        'office_lat' => 19.0760000,
        'office_lng' => 72.8777000,
        'gps_radius_meters' => 200,
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $user->stores()->attach($store->id);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($user)
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($store));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'You are not registered as an active employee.',
        ]);
});

test('scan request requires store id latitude and longitude', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id', 'latitude', 'longitude']);
});

test('scan request rejects store from another company', function () {
    $ctx = seedAttendanceContext();

    $otherCompany = Company::create(['name' => 'Other Company']);
    $otherStore = Store::create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Store',
    ]);

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), [
            'store_id' => $otherStore->id,
            'latitude' => 19.0760000,
            'longitude' => 72.8777000,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id']);
});

test('attendance record is created with the correct company id', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']))
        ->assertOk();

    $this->assertDatabaseHas('attendances', [
        'company_id' => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
    ]);
});

function seedHolidayForToday(int $companyId): Holiday
{
    return Holiday::create([
        'company_id' => $companyId,
        'name' => 'Founders Day',
        'date' => today(),
        'type' => Holiday::TYPE_COMPANY,
        'is_paid' => true,
        'is_active' => true,
    ]);
}

function setHolidayPolicy(int $companyId, string $policy): void
{
    Setting::updateOrCreate(
        ['company_id' => $companyId, 'key' => 'attendance.holiday_policy'],
        ['value' => $policy, 'group' => 'attendance', 'type' => 'string']
    );
    Cache::forget("company_settings_{$companyId}");
}

test('holiday policy block rejects check-in on a holiday with friendly message', function () {
    $ctx = seedAttendanceContext();
    seedHolidayForToday($ctx['company']->id);
    setHolidayPolicy($ctx['company']->id, 'block');

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Today is a holiday. Attendance is not required.',
            'type' => 'info',
        ]);

    $this->assertDatabaseCount('attendances', 0);
    $this->assertDatabaseHas('attendance_logs', [
        'employee_id' => $ctx['employee']->id,
        'is_valid' => false,
        'rejection_reason' => 'Today is a holiday. Attendance is not allowed.',
    ]);
});

test('holiday policy allow records attendance flagged as working on holiday', function () {
    $ctx = seedAttendanceContext();
    seedHolidayForToday($ctx['company']->id);
    setHolidayPolicy($ctx['company']->id, 'allow');

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('action', AttendanceLog::ACTION_CHECK_IN);

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_PRESENT,
        'is_holiday' => true,
        'working_on_holiday' => true,
    ]);
});

test('holiday policy approval records attendance as pending approval', function () {
    $ctx = seedAttendanceContext();
    seedHolidayForToday($ctx['company']->id);
    setHolidayPolicy($ctx['company']->id, 'approval');

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('type', 'warning');

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_PENDING,
        'is_holiday' => true,
        'working_on_holiday' => true,
    ]);
});

test('holiday policy has no effect on non-holiday dates', function () {
    $ctx = seedAttendanceContext();
    setHolidayPolicy($ctx['company']->id, 'block');

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'status' => Attendance::STATUS_PRESENT,
        'is_holiday' => false,
        'working_on_holiday' => false,
    ]);
});

test('inactive holiday does not trigger the block policy', function () {
    $ctx = seedAttendanceContext();
    Holiday::create([
        'company_id' => $ctx['company']->id,
        'name' => 'Retired Holiday',
        'date' => today(),
        'type' => Holiday::TYPE_COMPANY,
        'is_paid' => true,
        'is_active' => false,
    ]);
    setHolidayPolicy($ctx['company']->id, 'block');

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance.scan'), scanPayload($ctx['store']));

    $response->assertOk();
    $this->assertDatabaseHas('attendances', [
        'employee_id' => $ctx['employee']->id,
        'is_holiday' => false,
    ]);
});

test('holiday policy endpoint persists the setting', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance-rules.holiday-policy'), [
            'holiday_policy' => 'approval',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.holiday_policy', 'approval');

    $this->assertDatabaseHas('settings', [
        'company_id' => $ctx['company']->id,
        'key' => 'attendance.holiday_policy',
        'value' => 'approval',
    ]);
});

test('holiday policy endpoint rejects invalid values', function () {
    $ctx = seedAttendanceContext();

    withoutAttendanceMiddleware($this);

    $response = $this->actingAs($ctx['user'])
        ->postJson(route('admin.hrm.attendance-rules.holiday-policy'), [
            'holiday_policy' => 'bogus',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['holiday_policy']);
});

test('evaluateHolidayPolicy detects a date inside a multi-day holiday range', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    Holiday::create([
        'company_id' => $companyId,
        'name' => 'Diwali Break',
        'date' => '2026-11-08',
        'end_date' => '2026-11-10',
        'type' => Holiday::TYPE_COMPANY,
        'is_paid' => true,
        'is_active' => true,
    ]);

    setHolidayPolicy($companyId, 'allow');

    $service = app(AttendanceService::class);

    expect($service->evaluateHolidayPolicy($ctx['employee'], '2026-11-08'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2026-11-09'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2026-11-10'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2026-11-07'))->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2026-11-11'))->toBeNull();
});

test('evaluateHolidayPolicy detects recurring single-day holiday across years', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    Holiday::create([
        'company_id' => $companyId,
        'name' => 'Republic Day',
        'date' => '2026-01-26',
        'type' => Holiday::TYPE_NATIONAL,
        'is_paid' => true,
        'is_active' => true,
        'is_recurring' => true,
    ]);

    setHolidayPolicy($companyId, 'allow');

    $service = app(AttendanceService::class);

    expect($service->evaluateHolidayPolicy($ctx['employee'], '2027-01-26'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2030-01-26'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2027-01-25'))->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2027-01-27'))->toBeNull();
});

test('evaluateHolidayPolicy detects recurring multi-day holiday across years', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    Holiday::create([
        'company_id' => $companyId,
        'name' => 'Diwali Week',
        'date' => '2026-11-08',
        'end_date' => '2026-11-10',
        'type' => Holiday::TYPE_COMPANY,
        'is_paid' => true,
        'is_active' => true,
        'is_recurring' => true,
    ]);

    setHolidayPolicy($companyId, 'allow');

    $service = app(AttendanceService::class);

    expect($service->evaluateHolidayPolicy($ctx['employee'], '2029-11-08'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2029-11-09'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2029-11-10'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2029-11-07'))->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2029-11-11'))->toBeNull();
});

test('evaluateHolidayPolicy ignores non-recurring holidays in other years', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    Holiday::create([
        'company_id' => $companyId,
        'name' => 'One-off Event',
        'date' => '2026-06-15',
        'type' => Holiday::TYPE_COMPANY,
        'is_paid' => true,
        'is_active' => true,
        'is_recurring' => false,
    ]);

    setHolidayPolicy($companyId, 'allow');

    $service = app(AttendanceService::class);

    expect($service->evaluateHolidayPolicy($ctx['employee'], '2026-06-15'))->not->toBeNull()
        ->and($service->evaluateHolidayPolicy($ctx['employee'], '2027-06-15'))->toBeNull();
});

function makeAttendanceRule(int $companyId, string $type, int $thresholdMinutes, string $action, array $overrides = []): AttendanceRule
{
    return AttendanceRule::create(array_merge([
        'company_id' => $companyId,
        'name' => 'Rule '.$type,
        'rule_type' => $type,
        'threshold_count' => $thresholdMinutes,
        'threshold_period' => 'monthly',
        'action' => $action,
        'auto_apply' => true,
        'is_active' => true,
    ], $overrides));
}

test('applyAttendanceRules upgrades late status to absent when threshold passed', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_ABSENT, 15, AttendanceRule::ACTION_MARK_ABSENT);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 16:00:00');
    $checkIn = Carbon::parse('2026-04-25 16:20:00');

    [$status, $message, $type] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default late.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_ABSENT)
        ->and($type)->toBe('error')
        ->and($message)->toContain('Absent')
        ->and($message)->toContain('20 min late');
});

test('applyAttendanceRules marks half day when only late_to_half_day rule matches', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_HALF_DAY, 30, AttendanceRule::ACTION_MARK_HALF_DAY);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:35:00');

    [$status] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_HALF_DAY);
});

test('applyAttendanceRules picks the most severe matching rule', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_HALF_DAY, 10, AttendanceRule::ACTION_MARK_HALF_DAY);
    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_ABSENT, 30, AttendanceRule::ACTION_MARK_ABSENT);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:35:00');

    [$status] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_ABSENT);
});

test('applyAttendanceRules leaves status unchanged when minutes-late below threshold', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_ABSENT, 30, AttendanceRule::ACTION_MARK_ABSENT);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:10:00');

    [$status, $message, $type] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default late message.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_LATE)
        ->and($message)->toBe('Default late message.')
        ->and($type)->toBe('warning');
});

test('applyAttendanceRules ignores rules with auto_apply disabled', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_ABSENT, 5, AttendanceRule::ACTION_MARK_ABSENT, [
        'auto_apply' => false,
    ]);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:30:00');

    [$status] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_LATE);
});

test('applyAttendanceRules ignores inactive rules', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_ABSENT, 5, AttendanceRule::ACTION_MARK_ABSENT, [
        'is_active' => false,
    ]);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:30:00');

    [$status] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_PRESENT,
        'Default.',
        'success',
    );

    expect($status)->toBe(Attendance::STATUS_PRESENT);
});

test('applyAttendanceRules does not downgrade an already-absent default status', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    makeAttendanceRule($companyId, AttendanceRule::TYPE_LATE_TO_HALF_DAY, 5, AttendanceRule::ACTION_MARK_HALF_DAY);

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:30:00');

    [$status] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_ABSENT,
        'Already absent.',
        'error',
    );

    expect($status)->toBe(Attendance::STATUS_ABSENT);
});

test('applyAttendanceRules returns default when no rules exist', function () {
    $ctx = seedAttendanceContext();
    $companyId = $ctx['company']->id;

    $service = app(AttendanceService::class);

    $shiftStart = Carbon::parse('2026-04-25 09:00:00');
    $checkIn = Carbon::parse('2026-04-25 09:30:00');

    [$status, $message, $type] = $service->applyAttendanceRules(
        $companyId,
        $checkIn,
        $shiftStart,
        Attendance::STATUS_LATE,
        'Default late message.',
        'warning',
    );

    expect($status)->toBe(Attendance::STATUS_LATE)
        ->and($message)->toBe('Default late message.')
        ->and($type)->toBe('warning');
});
