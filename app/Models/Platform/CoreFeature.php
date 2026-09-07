<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreFeature extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'value',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}