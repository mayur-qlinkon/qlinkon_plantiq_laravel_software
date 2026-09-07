<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

use App\Models\Platform\CoreFeature;

use Illuminate\Http\Request;

class CoreFeatureController extends Controller
{
    public function index()
    {
        $features = CoreFeature::ordered()->paginate(30);

        return view('platform.core-features.index', compact('features'));
    }

    public function create()
    {
        return view('platform.core-features.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'value'       => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        CoreFeature::create($validated);

        return redirect()->route('platform.core-features.index')
            ->with('success', 'Feature created successfully.');
    }

    public function edit(CoreFeature $coreFeature)
    {
        return view('platform.core-features.edit', compact('coreFeature'));
    }

    public function update(Request $request, CoreFeature $coreFeature)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'value'       => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $coreFeature->update($validated);

        return redirect()->route('platform.core-features.index')
            ->with('success', 'Feature updated successfully.');
    }

    public function destroy(CoreFeature $coreFeature)
    {
        $coreFeature->delete();

        return redirect()->route('platform.core-features.index')
            ->with('success', 'Feature deleted.');
    }
}