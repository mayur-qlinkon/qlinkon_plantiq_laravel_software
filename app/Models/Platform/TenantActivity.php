<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity log rows, stamped and scoped by company.
 *
 * Tenantable is not used here. That trait registers its scope only when a user
 * is authenticated, and it would also stamp company_id on rows written by
 * console commands and webhooks as null with no way to tell them apart from a
 * genuine platform event. This model states both halves explicitly instead.
 */
class TenantActivity extends SpatieActivity
{
    protected static function booted(): void
    {
        // Stamp on write, from the acting user. Console and webhook writes
        // legitimately land with a null company_id and read as platform events.
        static::creating(function (self $activity) {
            if (empty($activity->company_id) && Auth::check()) {
                $activity->company_id = Auth::user()->company_id;
            }
        });

        // Read scope. Super admins have no company_id and see everything;
        // every other user is pinned to their own company. Null rows stay
        // hidden from tenants — a cron write is not theirs to audit.
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $companyId = Auth::user()->company_id;

            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });
    }
}