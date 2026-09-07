<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

/**
 * Cached machine translation of a source string.
 *
 * Platform-level: no company_id, no Tenantable. Translations are pure content
 * and are deliberately shared across every tenant so the same English guide
 * text is never paid for twice.
 */
class TtsTranslation extends Model
{
    protected $table = 'tts_translations';

    protected $fillable = [
        'source_hash',
        'target_language',
        'source_text',
        'translated_text',
        'provider',
        'model',
        'characters',
    ];

    protected function casts(): array
    {
        return [
            'characters' => 'integer',
        ];
    }

    /**
     * Normalise text before hashing so that trivial whitespace differences
     * do not create duplicate cache entries.
     */
    public static function makeHash(string $text): string
    {
        $normalised = preg_replace('/\s+/u', ' ', trim($text));

        return hash('sha256', $normalised);
    }
}