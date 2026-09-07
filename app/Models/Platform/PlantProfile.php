<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'image',
        'plant_limit',
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
            'plant_limit' => 'integer',
            'price'       => 'decimal:2',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ];
    }
     /**
     * Public URL for the profile image (null-safe).
     * Falls back to the platform-wide default plant profile image
     * (System Settings → Visual Branding) when this profile has none.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        $default = get_system_setting('default_plant_profile_image');

        return $default ? asset('storage/' . $default) : null;
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