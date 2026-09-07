<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Auto-assigns store_id on create, mirroring what Tenantable does for
 * company_id.
 *
 * Deliberately does NOT add a global scope. Filtering every read by the active
 * store would silently break cross-store reporting — a client's total
 * outstanding must span all stores. Screens that need store filtering opt in
 * with forActiveStore().
 */
trait StoreScoped
{
    protected static function bootStoreScoped(): void
    {
        static::creating(function ($model) {
            if (empty($model->store_id)) {
                $model->store_id = optional(active_store())->id;
            }
        });
    }

    /** Opt-in filter for list screens that should respect the store switcher. */
    public function scopeForActiveStore(Builder $query): Builder
    {
        $storeIds = active_store_ids();

        return empty($storeIds)
            ? $query
            : $query->whereIn($query->getModel()->getTable().'.store_id', $storeIds);
    }
}