<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Designation;
use App\Services\Hrm\DesignationService;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function __construct(
        protected DesignationService $designationService
    ) {}

    public function index(Request $request)
    {
        $designations = Designation::withCount('employees')
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $designations]);
        }

        return view('admin.hrm.designations.index', compact('designations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        try {
            $designation = $this->designationService->create($validated);

            return response()->json(['success' => true, 'message' => 'Designation created.', 'data' => $designation]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        try {
            $designation = $this->designationService->update($designation, $validated);

            return response()->json(['success' => true, 'message' => 'Designation updated.', 'data' => $designation]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Designation $designation)
    {
        try {
            $this->designationService->delete($designation);

            return response()->json(['success' => true, 'message' => 'Designation deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
