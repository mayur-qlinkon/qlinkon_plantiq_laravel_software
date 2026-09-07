<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $leaveTypes = LeaveType::ordered()
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $leaveTypes]);
        }

        return view('admin.hrm.leave-types.index', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],

            // Mirrors the unique(['company_id', 'code']) index on the table.
            // Without it the request reaches the insert and MySQL throws a raw
            // 23000, which surfaces the table name and full INSERT statement
            // to the user.
            //
            // Soft-deleted rows are counted deliberately: the index does not
            // include deleted_at, so a trashed row still occupies its code.
            // Ignoring them here would let validation pass and the insert fail.
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')
                    ->where('company_id', $request->user()->company_id),
            ],

            'description' => ['nullable', 'string'],
            'default_days_per_year' => ['required', 'numeric', 'min:0'],
            'is_paid' => ['boolean'],
            'is_carry_forward' => ['boolean'],
            'max_carry_forward_days' => ['nullable', 'numeric', 'min:0'],
            'is_encashable' => ['boolean'],
            'requires_document' => ['boolean'],
            'min_days_before_apply' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'numeric', 'min:0'],
            'applicable_gender' => ['required', Rule::in(['all', 'male', 'female'])],
            'is_active' => ['boolean'],
        ], [
            'code.unique' => 'This code is already used by another leave type.',
        ]);

        $leaveType = LeaveType::create($validated);

        return response()->json(['success' => true, 'message' => 'Leave type created.', 'data' => $leaveType]);
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],

            // Same rule as store(), plus ignore() so a row does not collide
            // with itself when the code is left unchanged.
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')
                    ->ignore($leaveType->id)
                    ->where('company_id', $request->user()->company_id),
            ],

            'description' => ['nullable', 'string'],
            'default_days_per_year' => ['required', 'numeric', 'min:0'],
            'is_paid' => ['boolean'],
            'is_carry_forward' => ['boolean'],
            'max_carry_forward_days' => ['nullable', 'numeric', 'min:0'],
            'is_encashable' => ['boolean'],
            'requires_document' => ['boolean'],
            'min_days_before_apply' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'numeric', 'min:0'],
            'applicable_gender' => ['required', Rule::in(['all', 'male', 'female'])],
            'is_active' => ['boolean'],
        ], [
            'code.unique' => 'This code is already used by another leave type.',
        ]);

        $leaveType->update($validated);

        return response()->json(['success' => true, 'message' => 'Leave type updated.', 'data' => $leaveType]);
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->leaves()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete leave type with existing leave records.'], 422);
        }

        $leaveType->delete();

        return response()->json(['success' => true, 'message' => 'Leave type deleted.']);
    }
}
