<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master Switch
    |--------------------------------------------------------------------------
    */
    'enabled' => env('TTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tier Costs (USD per 1 million characters)
    |--------------------------------------------------------------------------
    | This list doubles as the allow-list: a voice tier NOT listed here can
    | never be selected, no matter what Google returns from voices:list. That
    | is the guard against a $160/1M Studio voice being picked by accident.
    |
    | Free allowances per month, which stack across tiers:
    |   standard 4M · wavenet 1M · neural2 1M · chirp3 1M
    |
    | To opt into a pricier tier, add it here AND to tier_preference.
    */
    'tier_costs' => [
        'wavenet'  => 4,
        'standard' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Preference
    |--------------------------------------------------------------------------
    | Tried in order; the first tier that has a voice for the language wins.
    |
    | Chirp3-HD leads on quality but costs $30/1M against WaveNet's $4/1M.
    | WaveNet and Standard remain in the ladder so that a language with no
    | Chirp3 voice still produces audio instead of failing.
    |
    | Set TTS_TIER_PREFERENCE=wavenet,standard in .env to drop to the cheap
    | tiers without editing this file — useful if spend needs cutting fast.
    */
    'tier_preference' => array_filter(
        array_map('trim', explode(',', (string) env('TTS_TIER_PREFERENCE', 'chirp3,wavenet,standard')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Voice Name Priority
    |--------------------------------------------------------------------------
    | Chirp3-HD voice names are language-independent — 'Kore' exists in Hindi,
    | Gujarati, Tamil and every other language. Listing preferred names here
    | therefore gives one consistent brand voice across the whole catalogue
    | without maintaining a per-language map.
    |
    | Names are matched as a suffix. Anything unmatched falls back to
    | alphabetical order, so this list never needs to be exhaustive.
    */
    'voice_name_priority' => [
        'Kore',      // warm, even-paced female — good default for instructions
        'Aoede',
        'Leda',
        'Callirrhoe',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preferred Gender
    |--------------------------------------------------------------------------
    | Keeps the brand voice consistent across languages. Falls back to any
    | available voice when the preferred gender is not offered.
    */
    'preferred_gender' => env('TTS_VOICE_GENDER', 'FEMALE'),
    'preferred_voice' => env('TTS_VOICE_NAME',"en-IN-Wavenet-E"),

    /*
    |--------------------------------------------------------------------------
    | Voice Catalog
    |--------------------------------------------------------------------------
    | The list of voices is fetched live from Google and cached, rather than
    | hardcoded. Refresh manually with: php artisan tts:catalog --refresh
    */
    'catalog' => [
        'cache_days' => env('TTS_CATALOG_CACHE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default / Fallback Language
    |--------------------------------------------------------------------------
    */
    'default_language' => 'en-IN',

    /*
    |--------------------------------------------------------------------------
    | Audio Output
    |--------------------------------------------------------------------------
    | Any change here produces different cache keys, so old audio is never
    | silently reused under new settings.
    */
    'audio' => [
        'encoding'      => 'MP3',
        // Google's valid range is 0.25 to 4.0, with 1.0 as the natural pace.
        // WaveNet reads instructional text noticeably slower than it looks on
        // screen, so a mild bump keeps it brisk without sounding rushed.
        // Env-driven so the pace can be tuned without a code change.
        'speaking_rate' => (float) env('TTS_SPEAKING_RATE', 1.15),
        'pitch'         => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('TTS_DISK', 'public'),
        'path' => 'tts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Limits
    |--------------------------------------------------------------------------
    | Abuse guards, not billing quotas.
    */
    'limits' => [
        'max_characters' => 1500,
        'rate_limit'     => env('TTS_RATE_LIMIT', '30,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation
    |--------------------------------------------------------------------------
    */
    'translation' => [
        'enabled'         => env('TTS_TRANSLATION_ENABLED', true),
        'model'           => env('TTS_TRANSLATION_MODEL', 'gemini-2.5-flash-lite'),
        'source_language' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Code Aliases
    |--------------------------------------------------------------------------
    | Browsers and the Google Translate widget emit codes that Cloud TTS does
    | not recognise. Two are outright wrong rather than merely short:
    |
    |   ar-SA -> ar-XA   (Cloud TTS uses a pan-Arabic code)
    |   zh-CN -> cmn-CN  (Mandarin is "cmn", not "zh")
    |
    | Short subtags are also mapped to the regional variant this app prefers,
    | e.g. 'en' resolves to Indian English rather than US English.
    */
    'aliases' => [
        'en'    => 'en-IN',
        'en-US' => 'en-IN',
        'en-GB' => 'en-IN',
        'hi'    => 'hi-IN',
        'gu'    => 'gu-IN',
        'mr'    => 'mr-IN',
        'ta'    => 'ta-IN',
        'te'    => 'te-IN',
        'bn'    => 'bn-IN',
        'kn'    => 'kn-IN',
        'ml'    => 'ml-IN',
        'pa'    => 'pa-IN',
        'ur'    => 'ur-IN',
        'ar'    => 'ar-XA',
        'ar-SA' => 'ar-XA',
        'zh'    => 'cmn-CN',
        'zh-CN' => 'cmn-CN',
        'zh-TW' => 'cmn-TW',
        'fr'    => 'fr-FR',
        'de'    => 'de-DE',
        'es'    => 'es-ES',
        'pt'    => 'pt-BR',
        'ru'    => 'ru-RU',
        'ja'    => 'ja-JP',
        'ko'    => 'ko-KR',
        'it'    => 'it-IT',
        'id'    => 'id-ID',
        'vi'    => 'vi-VN',
        'th'    => 'th-TH',
        'tr'    => 'tr-TR',
        'nl'    => 'nl-NL',
        'pl'    => 'pl-PL',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Names for Translation Prompts
    |--------------------------------------------------------------------------
    | Gemini handles "Gujarati" far more reliably than "gu-IN". Only languages
    | listed here get a specific name; anything else falls back to the code
    | itself, which the model usually still understands.
    |
    | This is the ONLY per-language data the app maintains. Voices are
    | discovered from Google at runtime.
    */
    'language_names' => [
        'en-IN'  => 'Indian English',
        'hi-IN'  => 'Hindi',
        'gu-IN'  => 'Gujarati',
        'mr-IN'  => 'Marathi',
        'ta-IN'  => 'Tamil',
        'te-IN'  => 'Telugu',
        'bn-IN'  => 'Bengali',
        'kn-IN'  => 'Kannada',
        'ml-IN'  => 'Malayalam',
        'pa-IN'  => 'Punjabi',
        'ur-IN'  => 'Urdu',
        'ar-XA'  => 'Arabic',
        'cmn-CN' => 'Simplified Chinese (Mandarin)',
        'cmn-TW' => 'Traditional Chinese (Mandarin)',
        'fr-FR'  => 'French',
        'de-DE'  => 'German',
        'es-ES'  => 'Spanish',
        'pt-BR'  => 'Brazilian Portuguese',
        'ru-RU'  => 'Russian',
        'ja-JP'  => 'Japanese',
        'ko-KR'  => 'Korean',
        'it-IT'  => 'Italian',
        'id-ID'  => 'Indonesian',
        'vi-VN'  => 'Vietnamese',
        'th-TH'  => 'Thai',
        'tr-TR'  => 'Turkish',
        'nl-NL'  => 'Dutch',
        'pl-PL'  => 'Polish',
    ],
];