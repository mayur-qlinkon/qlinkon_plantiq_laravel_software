<?php

namespace App\Models;

use App\Models\Platform\Addon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'depends_on',
        'price',
        'is_addon',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_addon' => 'boolean',
            'price' => 'decimal:2',
            'sort_order' => 'integer',
            'depends_on' => 'array',
        ];
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_modules');
    }
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'addon_modules')
                    ->withTimestamps();
    }
}
