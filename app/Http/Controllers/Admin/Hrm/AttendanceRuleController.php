<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendanceRule;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AttendanceRuleController extends Controller
{
    public const HOLIDAY_POLICY_KEY = 'attendance.holiday_policy';

    public const HOLIDAY_POLICY_OPTIONS = ['block', 'allow', 'approval'];

    public function index(Request $request)
    {
        $rules = AttendanceRule::ordered()
            ->paginate(25)
            ->withQueryString();

        $holidayPolicy = (string) get_setting(self::HOLIDAY_POLICY_KEY, 'block');

        if (! in_array($holidayPolicy, self::HOLIDAY_POLICY_OPTIONS, true)) {
            $holidayPolicy = 'block';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $rules,
                'holiday_policy' => $holidayPolicy,
            ]);
        }

        return view('admin.hrm.attendance-rules.index', compact('rules', 'holidayPolicy'));
    }

    public function updateHolidayPolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holiday_policy' => ['required', Rule::in(self::HOLIDAY_POLICY_OPTIONS)],
        ]);

        $companyId = Auth::user()->company_id;

        Setting::updateOrCreate(
            ['company_id' => $companyId, 'key' => self::HOLIDAY_POLICY_KEY],
            ['value' => $validated['holiday_policy'], 'group' => 'attendance', 'type' => 'string']
        );

        Cache::forget("company_settings_{$companyId}");

        return response()->json([
            'success' => true,
            'message' => 'Holiday attendance policy saved.',
            'data' => ['holiday_policy' => $validated['holiday_policy']],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'rule_type' => ['required', Rule::in(array_keys(AttendanceRule::TYPE_LABELS))],
            'threshold_count' => ['required', 'integer', 'min:1'],
            'threshold_period' => ['required', Rule::in(array_keys(AttendanceRule::PERIOD_LABELS))],
            'action' => ['required', Rule::in(array_keys(AttendanceRule::ACTION_LABELS))],
            'deduction_days' => ['nullable', 'numeric', 'min:0'],
            'leave_type_code' => ['nullable', 'string', 'max:20'],
            'auto_apply' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $rule = AttendanceRule::create($validated);

        return response()->json(['success' => true, 'message' => 'Attendance rule created.', 'data' => $rule]);
    }

    public function update(Request $request, AttendanceRule $attendanceRule)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'rule_type' => ['required', Rule::in(array_keys(AttendanceRule::TYPE_LABELS))],
            'threshold_count' => ['required', 'integer', 'min:1'],
            'threshold_period' => ['required', Rule::in(array_keys(AttendanceRule::PERIOD_LABELS))],
            'action' => ['required', Rule::in(array_keys(AttendanceRule::ACTION_LABELS))],
            'deduction_days' => ['nullable', 'numeric', 'min:0'],
            'leave_type_code' => ['nullable', 'string', 'max:20'],
            'auto_apply' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $attendanceRule->update($validated);

        return response()->json(['success' => true, 'message' => 'Attendance rule updated.', 'data' => $attendanceRule]);
    }

    public function destroy(AttendanceRule $attendanceRule)
    {
        $attendanceRule->delete();

        return response()->json(['success' => true, 'message' => 'Attendance rule deleted.']);
    }
}
