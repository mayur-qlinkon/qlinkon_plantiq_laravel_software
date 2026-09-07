<?php

namespace App\Enums\Auth;

/**
 * Single source of truth for user identity.
 *
 * Identity answers "who is this?" only. It never answers
 * "what can they do?" (roles/permissions) or
 * "what did the company buy for them?" (module seats).
 */
enum UserType: string
{
    /** Platform team. company_id is always null. */
    case SUPER_ADMIN = 'super_admin';

    /** Tenant owner. User management, settings, billing. */
    case COMPANY_ADMIN = 'company_admin';

    /** Anyone inside the company: manager, accountant, worker, labour. */
    case INTERNAL = 'internal';

    /** Storefront buyer. Has a Client profile. */
    case CUSTOMER = 'customer';

    /** Human readable label for UI badges and dropdowns. */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN   => 'Super Admin',
            self::COMPANY_ADMIN => 'Company Admin',
            self::INTERNAL      => 'Team Member',
            self::CUSTOMER      => 'Customer',
        };
    }

    /** True when this type belongs to a tenant company. */
    public function isTenantScoped(): bool
    {
        return $this !== self::SUPER_ADMIN;
    }

    /** True when this type may hold an HRM Employee profile. */
    public function canHaveEmployeeProfile(): bool
    {
        return in_array($this, [self::COMPANY_ADMIN, self::INTERNAL], true);
    }

    /** True when this type can reach the admin panel. */
    public function usesAdminPanel(): bool
    {
        return in_array($this, [self::COMPANY_ADMIN, self::INTERNAL], true);
    }

    /** Types selectable when creating a user from the Users screen. */
    public static function assignable(): array
    {
        return [self::INTERNAL, self::COMPANY_ADMIN];
    }
}