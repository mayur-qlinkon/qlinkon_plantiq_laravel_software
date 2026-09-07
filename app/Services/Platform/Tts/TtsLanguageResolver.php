<?php

namespace App\Services\Platform\Tts;

/**
 * Resolves an incoming language code into a Google-valid language code and a
 * concrete voice.
 *
 * Incoming codes cannot be trusted: the Google Translate widget emits short
 * codes ('hi', 'gu'), and two commonly used codes do not exist in Cloud TTS at
 * all — 'ar-SA' must become 'ar-XA' and 'zh-CN' must become 'cmn-CN'. Sending
 * either straight through returns HTTP 400.
 *
 * Voice selection is delegated to the live catalog, so no voice names are
 * maintained by hand.
 */
class TtsLanguageResolver
{
    public function __construct(
        protected TtsVoiceCatalog $catalog,
    ) {
    }

    /**
     * Normalise any incoming code to one Google actually offers.
     */
    public function normalise(?string $code): string
    {
        $default = (string) config('tts.default_language', 'en-IN');

        if (empty($code)) {
            return $default;
        }

        $code    = trim($code);
        $aliases = (array) config('tts.aliases', []);

        // Exact match against a language the catalog knows.
        if ($this->isSupported($code)) {
            return $code;
        }

        // Direct alias hit ('hi', 'ar-SA', 'zh-CN', ...).
        if (isset($aliases[$code]) && $this->isSupported($aliases[$code])) {
            return $aliases[$code];
        }

        // Base subtag: 'hi-Latn' -> 'hi' -> 'hi-IN'.
        $base = strtolower(explode('-', $code)[0]);

        if (isset($aliases[$base]) && $this->isSupported($aliases[$base])) {
            return $aliases[$base];
        }

        return $default;
    }

    /**
     * Resolve the voice to use.
     *
     * @return array{language:string, voice:string, tier:string}
     */
    public function resolveVoice(?string $code, ?string $tier = null): array
    {
        $language = $this->normalise($code);

        $picked = $this->catalog->pick($language, $tier);

        // Language exists but offers nothing in the permitted tiers — this is
        // real (some languages ship only premium voices). Fall back to the
        // default language rather than escalating into a costlier tier, so a
        // rare language can never quietly change the billing profile.
        if (! $picked) {
            $language = (string) config('tts.default_language', 'en-IN');
            $picked   = $this->catalog->pick($language);
        }

        if (! $picked) {
            // Catalog is empty — almost always a missing or blocked API key.
            throw new \RuntimeException(
                'No TTS voice could be resolved. The voice catalog is empty; '
                . 'check GOOGLE_TTS_API_KEY and run: php artisan tts:catalog --refresh'
            );
        }

        return [
            'language' => $language,
            'voice'    => $picked['name'],
            'tier'     => $picked['tier'],
        ];
    }

    /**
     * Whether the catalog has any usable voice for this exact code.
     */
    public function isSupported(string $code): bool
    {
        return ! empty($this->catalog->forLanguage($code));
    }

    /**
     * Plain-English language name for translation prompts.
     */
    public function geminiName(string $language): string
    {
        return (string) config("tts.language_names.{$language}", $language);
    }

    /**
     * Whether the resolved language is the source language, meaning no
     * translation step is needed.
     */
    public function isSourceLanguage(string $language): bool
    {
        $source = (string) config('tts.translation.source_language', 'en');

        return str_starts_with(strtolower($language), strtolower($source));
    }
}