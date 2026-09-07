<?php

namespace App\Models;

use App\Enums\Auth\UserType;
use App\Traits\Tenantable;
use App\Traits\HasSmartNotifications;

use App\Models\Hrm\Employee;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method bool hasRole(string $role)
 * @method bool hasAnyRole(array $roles)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, Tenantable, HasSmartNotifications, HasApiTokens;

    protected $fillable = [
        'company_id',
        'name',
        'user_type',
        'email',
        'phone',
        'password',
        'email_verified_at',        
        'image',
        'address',
        'state',
        'country',
        'zip_code',
        'status',                
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'  => 'datetime',        
        'password'           => 'hashed',
        'user_type'          => UserType::class,
    ];

    // ── Identity checks (source of truth) ──

    public function isSuperAdmin(): bool
    {
        return $this->user_type === UserType::SUPER_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->user_type === UserType::CUSTOMER;
    }

    /**
     * Any user who belongs to a tenant company and reaches the admin panel.
     * Covers both the owner and every team member.
     */
    public function isInternalUser(): bool
    {
        return $this->user_type->usesAdminPanel();
    }

    /** True when an HRM Employee profile is attached to this login. */
    public function hasEmployeeProfile(): bool
    {
        // Property, not the relation method: Eloquent caches the loaded
        // relation, so repeated calls within one request cost one query
        // instead of one exists() per call.
        return $this->employee !== null;
    }

    /** * Company relationship */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Customer profile linked to this login.
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Relationship to Role
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function crmLeads(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmLead::class,
            'crm_lead_assignees',
            'user_id',
            'crm_lead_id'
        );
    }

    /**
     * Relationship to Store
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')->withTimestamps();
    }

    /**
     * Modules explicitly assigned to this user (per-user module licensing).
     * Not used for the owner — owner bypasses this entirely (see has_module()).
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'user_module_access')->withTimestamps();
    }

    /**
     * Every user inside the company: owner plus all team members.
     * Excludes super admins and customers.
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->whereIn('user_type', [
            UserType::COMPANY_ADMIN,
            UserType::INTERNAL,
        ]);
    }

    /** Team members only, excluding the owner. */
    public function scopeTeamMembers(Builder $query): Builder
    {
        return $query->where('user_type', UserType::INTERNAL);
    }

    /**
     * True if this user is the company owner.
     * Grants administrative rights only — never module access.
     */
    public function isCompanyAdmin(): bool
    {
        return $this->user_type === UserType::COMPANY_ADMIN;
    }

    /**
     * Scope: only company admins within the tenant.
     */
    public function scopeCompanyAdmins(Builder $query): Builder
    {
        return $query->where('user_type', UserType::COMPANY_ADMIN);
    }

    public function hasRole($role)
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles)
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission($permission)
    {
        // $this, not the logged-in user — this method is called via
        // Gate::forUser() for other users during notification fan-out.
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Company admin bypasses permissions — identity is already loaded,
        // so this costs no extra query.
        if ($this->isCompanyAdmin()) {
            return true;
        }

        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->contains('slug', $permission);
    }

    /**
     * Alias for hasPermission to match standard policy syntax
     */
    public function hasPermissionTo($permission)
    {
        return $this->hasPermission($permission);
    }

    /**
     * Check if the user has ANY of the given permissions in an array
     */
    public function hasAnyPermission(array $permissions)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isCompanyAdmin()) {
            return true;
        }

        // Flatten the user's permissions and check if any intersect with the requested array
        $userPermissions = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->toArray();

        return ! empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * HRM Employee relationship
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Helper to get profile image URL
     */
    public function getAvatarUrlAttribute()
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Optimized for the Header Notification Dropdown
     */
    public function unreadNotificationsLimit()
    {
        return $this->unreadNotifications()->latest()->limit(5);
    }

    /**
     * Route notifications for the mail channel.
     * This strictly prevents Laravel from trying to send emails to blank/invalid addresses.
     */
    public function routeNotificationForMail($notification)
    {
        // Only allow the notification to send if the email is perfectly valid
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) ? $this->email : null;
    }
}
