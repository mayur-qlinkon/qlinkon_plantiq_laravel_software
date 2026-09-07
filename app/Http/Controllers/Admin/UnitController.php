<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::latest()->get();

        return view('admin.products.units', compact('units'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units', 'name')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'short_name' => [
                'nullable', 'string', 'max:50',
                Rule::unique('units', 'short_name')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'A unit with this name already exists.',
            'short_name.unique' => "A unit with short name ':input' already exists.",
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Unit::create($validated);

        return back()->with('success', 'Unit created successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units', 'name')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->ignore($unit->id),
            ],
            'short_name' => [
                'nullable', 'string', 'max:50',
                Rule::unique('units', 'short_name')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->ignore($unit->id),
            ],
            'is_active' => ['boolean'],
        ], [
            'name.unique' => 'A unit with this name already exists.',
            'short_name.unique' => "A unit with short name ':input' already exists.",
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        $unit->update($validated);

        return back()->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }
}
