<?php

namespace App\Services\Platform\Tts;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live catalog of available Google Cloud TTS voices.
 *
 * Replaces a hardcoded language-to-voice map. Hardcoding was fragile for three
 * reasons: voice names do not follow one pattern across languages (French
 * Standard is -F, German is -G, Hindi is -A), Google adds and retires voices
 * over time, and every new tier meant editing every language entry.
 *
 * The catalog is fetched once and cached for weeks, so this costs one HTTP
 * request per cache period, not one per synthesis.
 */
class TtsVoiceCatalog
{
    protected const CACHE_KEY = 'tts:voice_catalog:v1';

    /**
     * Voice-name fragments mapped to the tier they represent.
     *
     * Anything not listed here is deliberately ignored. Google also exposes
     * bare-name Gemini-TTS voices ("Kore", "Puck") which are billed by audio
     * token rather than character — leaving them unclassified keeps them out
     * of selection entirely so they can never be picked by accident.
     */
    protected const TIER_MARKERS = [
        '-Standard-'  => 'standard',
        '-Wavenet-'   => 'wavenet',
        '-Neural2-'   => 'neural2',
        '-Chirp3-HD-' => 'chirp3',
        '-Chirp-HD-'  => 'chirp',
        '-Polyglot-'  => 'polyglot',
        '-Studio-'    => 'studio',
        '-News-'      => 'news',
        '-Casual-'    => 'casual',
    ];

    /**
     * Full catalog grouped by language code.
     *
     * @return array<string, array<int, array{name:string, tier:string, gender:string}>>
     */
    public function all(): array
    {
        $days = (int) config('tts.catalog.cache_days', 30);

        return Cache::remember(
            self::CACHE_KEY,
            now()->addDays($days),
            fn () => $this->fetch()
        );
    }

    /**
     * Voices available for one language code.
     *
     * @return array<int, array{name:string, tier:string, gender:string}>
     */
    public function forLanguage(string $language): array
    {
        return $this->all()[$language] ?? [];
    }

    /**
     * Every language code Google currently offers.
     *
     * @return array<int, string>
     */
    public function languages(): array
    {
        return array_keys($this->all());
    }

    /**
     * Pick the best voice for a language, walking the configured tier
     * preference in order.
     *
     * Selection is deterministic — the list is sorted before picking — because
     * a voice name feeds the audio cache key. A non-deterministic pick would
     * silently regenerate every clip on each catalog refresh.
     *
     * @return array{name:string, tier:string, gender:string}|null
     */
    public function pick(string $language, ?string $preferredTier = null): ?array
    {
        $voices = $this->forLanguage($language);

        if (empty($voices)) {
            return null;
        }

        $order = (array) config('tts.tier_preference', ['wavenet', 'standard']);

        // An explicitly requested tier is tried first, then the normal ladder.
        if ($preferredTier) {
            $order = array_values(array_unique(array_merge([$preferredTier], $order)));
        }

        $preferredGender = strtoupper((string) config('tts.preferred_gender', 'FEMALE'));

        // $preferredVoice = config('tts.preferred_voice',"en-IN-Wavenet-E");

        // if ($preferredVoice) {
        //     foreach ($voices as $voice) {
        //         if (
        //             $voice['name'] === $preferredVoice &&
        //             in_array($voice['tier'], $order, true)
        //         ) {
        //             return $voice;
        //         }
        //     }
        // }

        foreach ($order as $tier) {
            $matches = array_values(array_filter(
                $voices,
                fn (array $v) => $v['tier'] === $tier
            ));

            if (empty($matches)) {
                continue;
            }

            $gendered = array_values(array_filter(
                $matches,
                fn (array $v) => $v['gender'] === $preferredGender
            ));

            $pool = ! empty($gendered) ? $gendered : $matches;

            // Prefer a named voice when one is configured and available. Chirp3
            // names are shared across languages, so this yields the same voice
            // character in every language without a per-language map.
            foreach ((array) config('tts.voice_name_priority', []) as $preferred) {
                foreach ($pool as $voice) {
                    if (str_ends_with($voice['name'], '-' . $preferred)) {
                        return $voice;
                    }
                }
            }

            // Already sorted by name in fetch(); take the first for stability.
            return $pool[0];
        }

        return null;
    }

    /**
     * Discard the cached catalog. Used by the refresh command.
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Retrieve and normalise the voice list from Google.
     *
     * On failure an empty catalog is returned rather than throwing, and it is
     * NOT cached — so a transient outage does not lock in a broken catalog for
     * the whole cache period.
     *
     * @return array<string, array<int, array{name:string, tier:string, gender:string}>>
     */
    protected function fetch(): array
    {
        $apiKey  = (string) config('services.google_tts.api_key');
        $baseUrl = rtrim((string) config('services.google_tts.base_url'), '/');

        if (empty($apiKey)) {
            Log::warning('TTS voice catalog: GOOGLE_TTS_API_KEY is missing.');

            return [];
        }

        try {
            $response = Http::timeout(30)->get($baseUrl . '/voices', ['key' => $apiKey]);
        } catch (\Throwable $e) {
            Log::error('TTS voice catalog fetch failed', ['message' => $e->getMessage()]);

            return [];
        }

        if ($response->failed()) {
            Log::error('TTS voice catalog HTTP error', [
                'status'  => $response->status(),
                'message' => $response->json('error.message'),
            ]);

            return [];
        }

        $allowed = array_keys((array) config('tts.tier_costs', []));
        $grouped = [];

        foreach ((array) $response->json('voices', []) as $voice) {
            $name = $voice['name'] ?? null;

            if (! $name) {
                continue;
            }

            $tier = $this->classify($name);

            // Unclassified, or a tier this app has not opted into.
            if (! $tier || ! in_array($tier, $allowed, true)) {
                continue;
            }

            foreach ((array) ($voice['languageCodes'] ?? []) as $code) {
                $grouped[$code][] = [
                    'name'   => $name,
                    'tier'   => $tier,
                    'gender' => strtoupper((string) ($voice['ssmlGender'] ?? 'NEUTRAL')),
                ];
            }
        }

        // Sort for deterministic selection, then sort languages for readable
        // output in the diagnostic command.
        foreach ($grouped as &$voices) {
            usort($voices, fn ($a, $b) => strcmp($a['name'], $b['name']));
        }
        unset($voices);

        ksort($grouped);

        return $grouped;
    }

    /**
     * Map a voice name to its billing tier.
     */
    protected function classify(string $name): ?string
    {
        foreach (self::TIER_MARKERS as $marker => $tier) {
            if (str_contains($name, $marker)) {
                return $tier;
            }
        }

        return null;
    }
}