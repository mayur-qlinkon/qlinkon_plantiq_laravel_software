<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantLibraryMedia extends Model
{
    use HasFactory;

    protected $table = 'plant_library_media';

    protected $fillable = [
        'plant_library_id',
        'media_type',
        'media_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function plantLibrary(): BelongsTo
    {
        return $this->belongsTo(PlantLibrary::class);
    }

    /**
     * Same shape as ProductMedia::getMediaUrlAttribute() —
     * keeps Phase 2 import mapping 1:1, zero transformation needed.
     */
    public function getMediaUrlAttribute(): string
    {
        if ($this->media_type === 'youtube') {
            return $this->media_path;
        }

        return asset('storage/'.$this->media_path);
    }
}