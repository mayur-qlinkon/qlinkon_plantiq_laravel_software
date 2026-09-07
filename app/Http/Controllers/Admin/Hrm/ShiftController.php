<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = Shift::withCount('employees')
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $shifts]);
        }

        return view('admin.hrm.shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Mirrors unique(['company_id', 'name']) on the table — without it
            // the insert reaches MySQL and throws a raw 23000 at the user.
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('shifts', 'name')->where('company_id', $request->user()->company_id),
            ],
            // Carbon dayOfWeek numbers: 0 = Sunday ... 6 = Saturday.
            // Payroll divides pay by the roster these define, so a field that
            // silently fails validation is a field that silently changes salaries.
            'weekly_off_days' => ['nullable', 'array'],
            'weekly_off_days.*' => ['integer', 'between:0,6'],
            'alternate_saturday_offs' => ['nullable', 'array'],
            'alternate_saturday_offs.*' => ['integer', 'between:1,5'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_mark_after' => ['nullable', 'date_format:H:i'],
            'early_leave_before' => ['nullable', 'date_format:H:i'],
            'half_day_after' => ['nullable', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'min_working_hours_minutes' => ['nullable', 'integer', 'min:0'],
            'overtime_after_minutes' => ['nullable', 'integer', 'min:0'],
            'weekly_off_days' => ['nullable', 'array'],
            'weekly_off_days.*' => ['integer', 'between:0,6'],
            'alternate_saturday_offs' => ['nullable', 'array'],
            'alternate_saturday_offs.*' => ['integer', 'between:1,5'],
            'is_night_shift' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'A shift with this name already exists.',
        ]);

        // Demotion of the previous default is handled by Shift::booted(), so it
        // applies to every write path, not just this one.
        $shift = Shift::create($validated);

        return response()->json(['success' => true, 'message' => 'Shift created.', 'data' => $shift]);
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                                Rule::unique('shifts', 'name')->where('company_id', $request->user()->company_id)->ignore($shift->id),
            ],
            'weekly_off_days' => ['nullable', 'array'],
            'weekly_off_days.*' => ['integer', 'between:0,6'],
            'alternate_saturday_offs' => ['nullable', 'array'],
            'alternate_saturday_offs.*' => ['integer', 'between:1,5'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_mark_after' => ['nullable', 'date_format:H:i'],
            'early_leave_before' => ['nullable', 'date_format:H:i'],
            'half_day_after' => ['nullable', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'min_working_hours_minutes' => ['nullable', 'integer', 'min:0'],
            'overtime_after_minutes' => ['nullable', 'integer', 'min:0'],
            'is_night_shift' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'A shift with this name already exists.',
        ]);

        $shift->update($validated);

        return response()->json(['success' => true, 'message' => 'Shift updated.', 'data' => $shift]);
    }

    public function destroy(Shift $shift)
    {
        if ($shift->employees()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete shift with assigned employees.'], 422);
        }

        // Mirrors the warehouse rule: removing the last default leaves the
        // employee form with nothing preselected.
        if ($shift->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete the default shift. Please set another shift as default first.',
            ], 422);
        }

        $shift->delete();

        return response()->json(['success' => true, 'message' => 'Shift deleted.']);
    }
}
