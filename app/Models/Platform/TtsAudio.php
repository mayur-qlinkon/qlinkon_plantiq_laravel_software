<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Cached synthesised audio clip.
 *
 * Platform-level and content-addressed: one row per unique combination of
 * text, language, voice and audio settings, shared across all tenants.
 */
class TtsAudio extends Model
{
    protected $table = 'tts_audios';

    protected $fillable = [
        'cache_key',
        'text_hash',
        'language_code',
        'voice_name',
        'voice_tier',
        'text_preview',
        'disk',
        'path',
        'format',
        'characters',
        'bytes',
        'generated_for_company_id',
        'hit_count',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'characters'   => 'integer',
            'bytes'        => 'integer',
            'hit_count'    => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Public URL for the stored clip, resolved against the disk recorded on
     * this row rather than the current default disk.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Whether the underlying file still exists. Guards against rows that
     * survive a manual storage wipe.
     */
    public function fileExists(): bool
    {
        return $this->path && Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Record a cache hit. Uses a direct query rather than save() to avoid
     * touching updated_at, which would defeat any future age-based cleanup.
     */
    public function markUsed(): void
    {
        static::withoutTimestamps(function () {
            $this->increment('hit_count');
            $this->forceFill(['last_used_at' => now()])->saveQuietly();
        });
    }
}