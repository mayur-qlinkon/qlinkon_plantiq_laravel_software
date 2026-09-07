<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department;
use App\Models\User;
use App\Services\Hrm\DepartmentService;
use Closure;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    public function index(Request $request)
    {
        $departments = Department::with('head')
            ->withCount('employees')
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $departments]);
        }

        $headUsers = User::query()
            ->internal()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.hrm.departments.index', compact('departments', 'headUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'head_id' => $this->departmentHeadRules(),
            'is_active' => ['boolean'],
        ]);

        try {
            $department = $this->departmentService->create($validated);

            return response()->json(['success' => true, 'message' => 'Department created.', 'data' => $department]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'head_id' => $this->departmentHeadRules(),
            'is_active' => ['boolean'],
        ]);

        try {
            $department = $this->departmentService->update($department, $validated);

            return response()->json(['success' => true, 'message' => 'Department updated.', 'data' => $department]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Department $department)
    {
        try {
            $this->departmentService->delete($department);

            return response()->json(['success' => true, 'message' => 'Department deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function departmentHeadRules(): array
    {
        return [
            'nullable',
            'integer',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                $isValidHead = User::query()
                    ->internal()
                    ->where('status', 'active')
                    ->whereKey($value)
                    ->exists();

                if (! $isValidHead) {
                    $fail('Selected department head must be an active staff user.');
                }
            },
        ];
    }
}
