<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CrmTag extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'color',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmLead::class,
            'crm_lead_tags',
            'crm_tag_id',
            'crm_lead_id'
        );
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('name');
    }

    // ════════════════════════════════════════════════════
    //  STATIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Find or create tags by name for a company.
     * Used when saving leads with tag input.
     */
    public static function findOrCreateByNames(array $names, int $companyId): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim($name);
            if (! $name) {
                continue;
            }

            $tag = static::firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['color' => '#6b7280']
            );
            $ids[] = $tag->id;
        }

        return $ids;
    }
}
