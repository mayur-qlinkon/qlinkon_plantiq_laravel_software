<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileKit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'image',
        'description',
        'price',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }
     /**
     * Public URL for the kit image (null-safe).
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
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