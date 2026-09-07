<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::latest()->get();

        return view('platform.modules', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:modules,slug'],
        ]);

        // FIX: Added '_' as the second parameter to allow underscores
        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'], '_')
            : Str::slug($data['name'], '_');

        $data['is_active'] = $request->has('is_active');

        Module::create($data);

        return redirect()->route('platform.modules.index')
            ->with('success', 'Module created successfully.');
    }

    public function update(Request $request, Module $module)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('modules', 'slug')->ignore($module->id),
            ],
        ]);

        // FIX: Added '_' as the second parameter to allow underscores
        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'], '_')
            : Str::slug($data['name'], '_');

        $data['is_active'] = $request->has('is_active');

        $module->update($data);

        return redirect()->route('platform.modules.index')
            ->with('success', 'Module updated successfully.');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return back()->with('success', 'Module deleted successfully.');
    }
}
