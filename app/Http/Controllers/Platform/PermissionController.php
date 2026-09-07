<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        $permissions = Permission::orderBy('module_group')->orderBy('name')->get();

        // Dynamically fetch unique module groups from the database for the dropdown
        $moduleGroups = Permission::select('module_group')
            ->distinct()
            ->pluck('module_group')
            ->toArray();

        return view('platform.permissions', compact('permissions', 'moduleGroups'));
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module_group' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:permissions,slug'],
        ]);

        // Auto-generate slug from module_group + name (e.g. 'pos_create_quick_product') if left empty
        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'], '_')
            : Str::slug($data['module_group'].'_'.$data['name'], '_');

        Permission::create($data);

        return redirect()->route('platform.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module_group' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('permissions', 'slug')->ignore($permission->id),
            ],
        ]);

        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'], '_')
            : Str::slug($data['module_group'].'_'.$data['name'], '_');

        $permission->update($data);

        return redirect()->route('platform.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return back()->with('success', 'Permission deleted successfully.');
    }

    /**
     * Helper to auto-generate all default permissions from the Seeder.
     */
    public function syncDefault()
    {
        // Programmatically call your central PermissionSeeder
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Platform\\PermissionSeeder',
        ]);

        return back()->with('success', 'Enterprise permissions synced successfully from Seeder!');
    }
}
