<?php

namespace App\Rules;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * A tenant-aware replacement for the plain `exists:table,id` rule.
 *
 * Laravel's `exists` runs a raw query builder, so it applies NEITHER the
 * Tenantable global scope NOR the SoftDeletes scope. A bare
 * `exists:clients,id` therefore accepts another company's client and an
 * already-deleted one — which is how a project ends up holding a reference
 * that resolves to null the moment anything tries to read it.
 *
 * Use this for every foreign key that points at a tenant-owned table.
 */
class TenantExists
{
    /**
     * @param  string  $table       Table the ID must exist in.
     * @param  bool    $softDeletes Whether the table has a deleted_at column.
     * @param  string  $column      Column to match on.
     */
    public static function make(string $table, bool $softDeletes = false, string $column = 'id'): Exists
    {
        $companyId = Auth::user()?->company_id;

        $rule = Rule::exists($table, $column)->where('company_id', $companyId);

        if ($softDeletes) {
            $rule->whereNull('deleted_at');
        }

        return $rule;
    }
}