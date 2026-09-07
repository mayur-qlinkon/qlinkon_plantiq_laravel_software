<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $roles = $this->roleService->getRoles();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        // Pass the current tenant/company ID to fetch only licensed permissions
        $companyId = Auth::user()->company_id; 
        $permissions = $this->roleService->getLicensedGroupedPermissions($companyId);

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $this->roleService->storeRole($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        if (in_array($role->slug, ['owner', 'super_admin'])) {
            return redirect()->route('admin.roles.index')->with('error', 'System roles cannot be modified.');
        }

        // Pass the current tenant/company ID to fetch only licensed permissions
        $companyId = Auth::user()->company_id;
        $permissions = $this->roleService->getLicensedGroupedPermissions($companyId);

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if (in_array($role->slug, ['owner', 'super_admin'])) {
            return back()->with('error', 'System roles cannot be modified.');
        }

        $this->roleService->updateRole($role, $request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->slug, ['owner', 'super_admin'])) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete this role. Staff members are still assigned to it.');
        }

        $this->roleService->deleteRole($role);

        return back()->with('success', 'Role deleted successfully.');
    }
}