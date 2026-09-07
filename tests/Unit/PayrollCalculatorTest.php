<?php

use App\Services\Hrm\Payroll\Data\AttendanceSummary;
use App\Services\Hrm\Payroll\Data\ManualAdjustment;
use App\Services\Hrm\Payroll\Data\StructureLine;
use App\Services\Hrm\Payroll\Data\WageType;
use App\Services\Hrm\Payroll\PayrollCalculator;

/**
 * The payroll engine is pure — no database, no auth, no facades — so these
 * run without migrations or fixtures. That is the point of the split: the
 * arithmetic that decides what people are paid can be pinned down here,
 * cheaply, instead of by generating slips and reading them off a screen.
 */

// ── Builders ──────────────────────────────────────────────────────────────

function line(
    int $id,
    string $name,
    string $type = 'earning',
    string $calc = 'fixed',
    float $amount = 0,
    ?int $baseId = null,
    bool $onPayslip = true,
    int $sort = 0,
): StructureLine {
    return new StructureLine(
        componentId: $id,
        name: $name,
        code: strtoupper(str_replace(' ', '_', $name)),
        type: $type,
        role: 'other',
        calculationType: $calc,
        amount: $amount,
        percentageOfComponentId: $baseId,
        appearsOnPayslip: $onPayslip,
        sortOrder: $sort,
    );
}

function attendance(int $workingDays, float $presentDays, float $paidLeave = 0): AttendanceSummary
{
    return new AttendanceSummary(
        workingDays: $workingDays,
        presentDays: $presentDays,
        paidLeaveDays: $paidLeave,
        absentDays: max(0, $workingDays - $presentDays - $paidLeave),
        leaveDays: $paidLeave,
        overtimeHours: 0.0,
        attendanceTracked: true,
    );
}

function calculator(): PayrollCalculator
{
    return new PayrollCalculator;
}

// ── Monthly ───────────────────────────────────────────────────────────────

it('pays a monthly structure in full when nothing is missed', function () {
    $result = calculator()->calculate(
        [line(1, 'Basic Salary', amount: 7000)],
        attendance(26, 26),
    );

    expect($result->grossEarnings)->toBe(7000.0)
        ->and($result->netSalary)->toBe(7000.0)
        ->and($result->multiplier)->toBe(1.0);
});

it('pro-rates a monthly structure by the share of the roster worked', function () {
    // The live case: ₹7,000 over a 26-day roster with 25 days paid.
    $result = calculator()->calculate(
        [line(1, 'Basic Salary', amount: 7000)],
        attendance(26, 25),
    );

    expect($result->grossEarnings)->toBe(6730.77)
        ->and($result->netSalary)->toBe(6731.0)
        ->and($result->roundOff)->toBe(0.23);
});

it('counts paid leave as time worked', function () {
    $full = calculator()->calculate([line(1, 'Basic', amount: 2600)], attendance(26, 26));
    $withLeave = calculator()->calculate([line(1, 'Basic', amount: 2600)], attendance(26, 24, paidLeave: 2));

    expect($withLeave->grossEarnings)->toBe($full->grossEarnings);
});

it('never pays more than the full amount when extra days are worked', function () {
    // Days beyond the roster are overtime, not a salary multiplier.
    $result = calculator()->calculate([line(1, 'Basic', amount: 5000)], attendance(26, 30));

    expect($result->grossEarnings)->toBe(5000.0)
        ->and($result->multiplier)->toBe(1.0);
});

it('treats an absent roster as no reduction rather than no pay', function () {
    $result = calculator()->calculate([line(1, 'Basic', amount: 5000)], attendance(0, 0));

    expect($result->grossEarnings)->toBe(5000.0);
});

// ── Percentages ───────────────────────────────────────────────────────────

it('resolves a percentage against the component it names', function () {
    $result = calculator()->preview([
        line(1, 'Basic Salary', amount: 20000),
        line(2, 'HRA', calc: 'percentage', amount: 40, baseId: 1, sort: 1),
    ]);

    expect($result->grossEarnings)->toBe(28000.0);
});

it('pro-rates a percentage deduction along with the earning it is based on', function () {
    // PF at 12% of Basic must follow the basic actually paid, not the full one.
    $result = calculator()->calculate([
        line(1, 'Basic Salary', amount: 20000),
        line(2, 'Provident Fund', type: 'deduction', calc: 'percentage', amount: 12, baseId: 1, sort: 9),
    ], attendance(26, 13));

    expect($result->grossEarnings)->toBe(10000.0)
        ->and($result->totalDeductions)->toBe(1200.0)
        ->and($result->netSalary)->toBe(8800.0);
});

it('leaves a fixed deduction whole when the month is short', function () {
    // A loan instalment is owed in full regardless of days worked.
    $result = calculator()->calculate([
        line(1, 'Basic', amount: 26000),
        line(2, 'Loan EMI', type: 'deduction', amount: 2000, sort: 9),
    ], attendance(26, 13));

    expect($result->grossEarnings)->toBe(13000.0)
        ->and($result->totalDeductions)->toBe(2000.0);
});

it('rejects a percentage whose base is absent from the structure', function () {
    // This used to fall back to the employee's basic salary and produce a
    // plausible but wrong figure with no warning at all.
    expect(fn () => calculator()->preview([
        line(1, 'Basic', amount: 10000),
        line(2, 'Bonus', calc: 'percentage', amount: 10, baseId: 99),
    ]))->toThrow(InvalidArgumentException::class);
});

it('resolves a chain of percentages whatever order the rows arrive in', function () {
    // Basic 10,000 → DA 10% of Basic = 1,000 → HRA 50% of DA = 500.
    $chain = [
        line(1, 'Basic', amount: 10000),
        line(2, 'DA', calc: 'percentage', amount: 10, baseId: 1, sort: 1),
        line(3, 'HRA', calc: 'percentage', amount: 50, baseId: 2, sort: 2),
    ];

    $inOrder = calculator()->preview($chain);
    $reversed = calculator()->preview(array_reverse($chain));

    // Row order comes from the database, so a structure that calculated one
    // way today and another way tomorrow was a real possibility.
    expect($inOrder->grossEarnings)->toBe(11500.0)
        ->and($reversed->grossEarnings)->toBe(11500.0);
});

it('rejects components that are percentages of one another', function () {
    expect(fn () => calculator()->preview([
        line(1, 'A', calc: 'percentage', amount: 50, baseId: 2),
        line(2, 'B', calc: 'percentage', amount: 50, baseId: 1),
    ]))->toThrow(InvalidArgumentException::class);
});

it('uses a hidden component as a base without printing it', function () {
    $result = calculator()->preview([
        line(1, 'Notional Base', amount: 10000, onPayslip: false),
        line(2, 'Allowance', calc: 'percentage', amount: 25, baseId: 1, sort: 1),
    ]);

    expect($result->lines)->toHaveCount(1)
        ->and($result->grossEarnings)->toBe(2500.0);
});

// ── Daily ─────────────────────────────────────────────────────────────────

it('multiplies a daily rate by days worked and ignores the roster', function () {
    // ₹500 a day for 20 days is ₹10,000 whether the roster held 26 days or 22.
    $long = calculator()->calculate([line(1, 'Daily Wage', amount: 500)], attendance(26, 20), WageType::DAILY);
    $short = calculator()->calculate([line(1, 'Daily Wage', amount: 500)], attendance(22, 20), WageType::DAILY);

    expect($long->grossEarnings)->toBe(10000.0)
        ->and($short->grossEarnings)->toBe(10000.0);
});

it('previews a daily rate as a single day', function () {
    $result = calculator()->preview([line(1, 'Daily Wage', amount: 500)], WageType::DAILY);

    expect($result->grossEarnings)->toBe(500.0);
});

it('refuses hourly payroll while hours worked are unavailable', function () {
    expect(fn () => calculator()->calculate([line(1, 'Hourly Rate', amount: 80)], attendance(26, 20), WageType::HOURLY))
        ->toThrow(InvalidArgumentException::class);
});

// ── Totals ────────────────────────────────────────────────────────────────

it('derives round-off by default and honours an explicit one', function () {
    $lines = [line(1, 'Basic', amount: 7000)];
    $days = attendance(26, 25);

    $derived = calculator()->calculate($lines, $days, WageType::MONTHLY);
    $explicit = calculator()->calculate($lines, $days, WageType::MONTHLY, roundOffOverride: 0.0);

    // Saving a slip unchanged used to alter its net, because the manual path
    // always supplied an override of zero instead of leaving it derived.
    expect($derived->netSalary)->toBe(6731.0)
        ->and($explicit->netSalary)->toBe(6730.77);
});

it('reports a negative net rather than throwing on it', function () {
    // Preview has to show HR the problem; refusing to store it is the
    // orchestrator's job, not the calculator's.
    $result = calculator()->preview([
        line(1, 'Basic', amount: 1000),
        line(2, 'Advance Recovery', type: 'deduction', amount: 5000, sort: 9),
    ]);

    expect($result->netSalary)->toBe(-4000.0)
        ->and($result->isPayable())->toBeFalse();
});

it('adds manual adjustments to the totals without calculating them', function () {
    $result = calculator()->calculate(
        lines: [],
        attendance: AttendanceSummary::fullMonth(),
        wageType: WageType::MONTHLY,
        adjustments: [
            new ManualAdjustment('Festival Bonus', 'MANUAL-1', 'earning', 3000),
            new ManualAdjustment('Canteen', 'MANUAL-2', 'deduction', 250),
        ],
    );

    expect($result->grossEarnings)->toBe(3000.0)
        ->and($result->totalDeductions)->toBe(250.0)
        ->and($result->netSalary)->toBe(2750.0);
});

it('orders payslip lines by the component sort order', function () {
    $result = calculator()->preview([
        line(3, 'Special Allowance', amount: 1000, sort: 5),
        line(1, 'Basic Salary', amount: 5000, sort: 0),
        line(2, 'HRA', amount: 2000, sort: 1),
    ]);

    expect(collect($result->lines)->pluck('name')->all())
        ->toBe(['Basic Salary', 'HRA', 'Special Allowance']);
});

// ── Working shown on the payslip ──────────────────────────────────────────

it('shows the working behind a pro-rated figure', function () {
    $result = calculator()->calculate([line(1, 'Basic Salary', amount: 7000)], attendance(26, 25));

    expect($result->lines[0]->detail)->toBe('₹7,000.00 × 25/26 days');
});

it('shows both the percentage and the proration', function () {
    $result = calculator()->calculate([
        line(1, 'Basic Salary', amount: 7000),
        line(2, 'HRA', calc: 'percentage', amount: 50, baseId: 1, sort: 1),
    ], attendance(26, 25));

    expect($result->lines[1]->detail)->toBe('50% of Basic Salary · ₹3,500.00 × 25/26 days');
});

it('shows a day count rather than a fraction for daily pay', function () {
    $result = calculator()->calculate([line(1, 'Daily Wage', amount: 500)], attendance(26, 20), WageType::DAILY);

    expect($result->lines[0]->detail)->toBe('₹500.00 × 20 days');
});

it('explains nothing when nothing was adjusted', function () {
    $result = calculator()->calculate([line(1, 'Basic', amount: 7000)], attendance(26, 26));

    expect($result->lines[0]->detail)->toBeNull();
});