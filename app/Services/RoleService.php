<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\CompanyModuleLicense;
use Illuminate\Support\Str;

class RoleService
{
    public function getRoles()
    {
        return Role::with('permissions')
            ->whereNotIn('slug', ['owner', 'super_admin'])
            ->latest()
            ->get();
    }

    /**
     * Get permissions strictly for modules the company has an active license for.
     * Grouped by: Module Group -> Feature Sub-group
     */
    public function getLicensedGroupedPermissions($companyId)
    {
        // 1. Fetch all licenses for the company and filter valid ones using your model's logic
        $validModuleSlugs = CompanyModuleLicense::with('module')
            ->where('company_id', $companyId)
            ->get()
            ->filter(function ($license) {
                return $license->isCurrentlyValid();
            })
            ->pluck('module.slug')
            ->toArray();

        // 🌟 ALWAYS INCLUDE CORE BASE PERMISSIONS 🌟
        $validModuleSlugs[] = 'core';

        // 2. Fetch permissions ONLY for these valid modules
        // (If you have global/system permissions that don't need a license, 
        // you might want to add an orWhereNull('module_group') here)
        $permissions = Permission::whereIn('module_group', $validModuleSlugs)->get();

        // 3. Perform the 2-level grouping matching your UI requirements
        $groupedData = [];

        foreach ($permissions as $permission) {
            $moduleGroup = $permission->module_group; 
            
            // Extract the feature name (e.g., 'ocr_scanner' from 'ocr_scanner.view')
            $featureGroup = explode('.', $permission->slug)[0]; 

            // Initialize arrays if they don't exist
            if (!isset($groupedData[$moduleGroup])) {
                $groupedData[$moduleGroup] = [];
            }
            if (!isset($groupedData[$moduleGroup][$featureGroup])) {
                $groupedData[$moduleGroup][$featureGroup] = [];
            }

            // Push the permission object into its specific sub-group
            $groupedData[$moduleGroup][$featureGroup][] = $permission;
        }

        /* 
         * Output Structure will be:
         * [
         *   'tools' => [
         *       'ai_assistant' => [ PermissionObj(access) ],
         *       'ocr_scanner'  => [ PermissionObj(view), PermissionObj(history), ... ],
         *       'bulk_import'  => [ PermissionObj(products), ... ]
         *   ],
         *   'pos' => [ ... ]
         * ]
         */
        return $groupedData;
    }

    public function storeRole(array $data): Role
    {
        $data['slug'] = Str::slug($data['name']);

        $role = Role::create($data);

        if (! empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        unset($data['slug']);

        $role->update($data);
        $role->permissions()->sync($data['permissions'] ?? []);

        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        $role->permissions()->detach();
        return $role->delete();
    }
}