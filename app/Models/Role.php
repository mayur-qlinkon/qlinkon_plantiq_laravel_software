<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use Tenantable;

    protected $fillable = ['company_id', 'name', 'slug'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            $role->slug = Str::slug($role->name);
        });
    }

    /**
     * Permissions of role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * Users assigned to this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * Global "Owner" role — shared across ALL companies (company_id = NULL).
     * Not a per-tenant role; purely for display/auto-assignment at onboarding.
     *
     * Smart resolve: if it doesn't exist anywhere in the DB, create it once.
     * If it already exists, reuse the same row — never creates a duplicate.
     */
    public static function resolveGlobalOwnerRole(): self
    {
        return static::firstOrCreate(
            ['company_id' => null, 'slug' => 'owner'],
            ['name' => 'Owner']
        );
    }
}
