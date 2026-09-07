<?php

namespace App\Services\Platform\Tts;

use App\Models\Platform\TtsAudio;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Synthesises text into an MP3 via Google Cloud Text-to-Speech and caches the
 * result on disk.
 *
 * Every clip is content-addressed: the cache key covers the text, language,
 * voice and all audio settings. Changing any audio setting in config therefore
 * produces new cache entries rather than serving audio generated under the old
 * settings — no manual cache clearing required.
 */
class GoogleTtsService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct(
        protected TtsLanguageResolver $resolver,
    ) {
        $this->apiKey  = (string) config('services.google_tts.api_key');
        $this->baseUrl = rtrim((string) config('services.google_tts.base_url'), '/');
    }

    /**
     * Return a cached or freshly generated audio clip for the given text.
     *
     * @throws Exception
     */
    public function synthesize(
        string  $text,
        string  $language,
        ?string $tier = null,
        ?int    $companyId = null
    ): TtsAudio {

        if (! config('tts.enabled', true)) {
            throw new Exception('Text-to-speech is currently disabled.');
        }

        if (empty($this->apiKey)) {
            throw new Exception('GOOGLE_TTS_API_KEY is missing. Please add it to your .env file.');
        }

        $text = $this->prepareText($text);

        if ($text === '') {
            throw new Exception('No text supplied for synthesis.');
        }

        $resolved = $this->resolver->resolveVoice($language, $tier);
        $cacheKey = $this->makeCacheKey($text, $resolved['language'], $resolved['voice']);

        /*
        | Cache lookup. An existing row whose file has gone missing (manual
        | storage wipe, failed deploy) is treated as a miss and regenerated in
        | place, so a broken row never permanently serves a 404.
        */
        $existing = TtsAudio::where('cache_key', $cacheKey)->first();

        if ($existing && $existing->fileExists()) {
            $existing->markUsed();

            return $existing;
        }

        $audioContent = $this->callApi($text, $resolved);

        $path = $this->storeFile($cacheKey, $audioContent);

        return $this->persist(
            existing:  $existing,
            cacheKey:  $cacheKey,
            text:      $text,
            resolved:  $resolved,
            path:      $path,
            bytes:     strlen($audioContent),
            companyId: $companyId,
        );
    }

    /**
     * Normalise and length-limit the text before anything else.
     *
     * Normalising here (rather than at the call site) guarantees that the same
     * logical text always produces the same cache key.
     */
    protected function prepareText(string $text): string
    {
        // Collapse all whitespace runs — newlines and double spaces would
        // otherwise create distinct cache entries for identical speech.
        $text = preg_replace('/\s+/u', ' ', trim($text));

        $max = (int) config('tts.limits.max_characters', 1500);

        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
        }

        return trim($text);
    }

    /**
     * Build the cache key.
     *
     * Every parameter that affects the produced audio is included. If a future
     * requirement adds, say, a volume gain setting, adding it here is enough to
     * correctly invalidate the old clips.
     */
    protected function makeCacheKey(string $text, string $language, string $voice): string
    {
        $audio = (array) config('tts.audio', []);

        return hash('sha256', implode('|', [
            $text,
            $language,
            $voice,
            $audio['encoding']      ?? 'MP3',
            $audio['speaking_rate'] ?? 1.0,
            $audio['pitch']         ?? 0.0,
        ]));
    }

    /**
     * Call the Google synthesis endpoint and return raw audio bytes.
     *
     * @param  array{language:string, voice:string, tier:string}  $resolved
     * @throws Exception
     */
    protected function callApi(string $text, array $resolved): string
    {
        $audio = (array) config('tts.audio', []);

        $audioConfig = [
            'audioEncoding' => $audio['encoding'] ?? 'MP3',
        ];

        /*
        | Not every voice family accepts every audio parameter, and sending an
        | unsupported one returns HTTP 400 rather than being ignored:
        |
        |   Standard / WaveNet / Neural2 — speakingRate and pitch both fine
        |   Chirp3-HD                    — speakingRate fine, pitch rejected
        |   Chirp-HD (classic)           — both rejected
        |
        | Detection is on the voice name rather than the resolved tier so that
        | a voice pinned by hand is still handled correctly.
        */
        $isChirp  = str_contains($resolved['voice'], 'Chirp');
        $isChirp3 = str_contains($resolved['voice'], 'Chirp3');

        if (! $isChirp || $isChirp3) {
            $audioConfig['speakingRate'] = (float) ($audio['speaking_rate'] ?? 1.0);
        }

        if (! $isChirp) {
            $audioConfig['pitch'] = (float) ($audio['pitch'] ?? 0.0);
        }

        $payload = [
            'input' => [
                'text' => $text,
            ],
            'voice' => [
                'languageCode' => $resolved['language'],
                'name'         => $resolved['voice'],
            ],
            'audioConfig' => $audioConfig,
        ];

        $url = $this->baseUrl . '/text:synthesize?key=' . $this->apiKey;

        $response = Http::timeout(60)->post($url, $payload);

        if ($response->failed()) {
            $body     = $response->json();
            $errorMsg = $body['error']['message'] ?? ('Google TTS Error: HTTP ' . $response->status());

            Log::error('Google TTS HTTP Error', [
                'status'   => $response->status(),
                'message'  => $errorMsg,
                'voice'    => $resolved['voice'],
                'language' => $resolved['language'],
            ]);

            throw new Exception($errorMsg);
        }

        $encoded = $response->json('audioContent');

        if (empty($encoded)) {
            Log::error('Google TTS returned no audioContent', [
                'voice' => $resolved['voice'],
            ]);

            throw new Exception('Google TTS returned an empty audio response.');
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false || $decoded === '') {
            throw new Exception('Google TTS returned undecodable audio data.');
        }

        return $decoded;
    }

    /**
     * Write the clip to disk using a two-level directory prefix.
     *
     * Shared hosting struggles with directories holding tens of thousands of
     * files — backups crawl and FTP listings time out. Splitting on the first
     * four hex characters spreads clips across up to 65,536 directories.
     */
    protected function storeFile(string $cacheKey, string $contents): string
    {
        $disk = (string) config('tts.storage.disk', 'public');
        $base = trim((string) config('tts.storage.path', 'tts'), '/');

        $path = sprintf(
            '%s/%s/%s/%s.mp3',
            $base,
            substr($cacheKey, 0, 2),
            substr($cacheKey, 2, 2),
            $cacheKey
        );

        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /**
     * Create or refresh the cache row.
     *
     * @param  array{language:string, voice:string, tier:string}  $resolved
     */
    protected function persist(
        ?TtsAudio $existing,
        string    $cacheKey,
        string    $text,
        array     $resolved,
        string    $path,
        int       $bytes,
        ?int      $companyId
    ): TtsAudio {

        $attributes = [
            'text_hash'     => hash('sha256', $text),
            'language_code' => $resolved['language'],
            'voice_name'    => $resolved['voice'],
            'voice_tier'    => $resolved['tier'],
            'text_preview'  => mb_substr($text, 0, 180),
            'disk'          => (string) config('tts.storage.disk', 'public'),
            'path'          => $path,
            'format'        => 'mp3',
            'characters'    => mb_strlen($text),
            'bytes'         => $bytes,
            'hit_count'     => 1,
            'last_used_at'  => now(),
        ];

        // Row existed but its file was missing — repair it rather than
        // inserting a duplicate that would violate the unique key.
        if ($existing) {
            $existing->update($attributes);

            return $existing;
        }

        try {
            return TtsAudio::create($attributes + [
                'cache_key'                => $cacheKey,
                'generated_for_company_id' => $companyId,
            ]);
        } catch (QueryException $e) {
            /*
            | A concurrent request generated the same clip first. Both wrote
            | identical bytes to the same path, so the file is correct — just
            | return the row that won the race.
            */
            $winner = TtsAudio::where('cache_key', $cacheKey)->first();

            if ($winner) {
                return $winner;
            }

            throw $e;
        }
    }
}