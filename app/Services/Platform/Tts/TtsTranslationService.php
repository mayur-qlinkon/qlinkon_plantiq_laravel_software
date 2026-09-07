<?php

namespace App\Services\Platform\Tts;

use App\Models\Platform\TtsTranslation;
use App\Services\Admin\AiService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Translates source text into a target language, caching the result forever.
 *
 * Source content lives in the database in English. Playing a Gujarati voice
 * over English text produces gibberish, so the text must be translated before
 * synthesis. Because the translation of a given string never changes, it is
 * cached content-addressed and shared across every product and tenant.
 *
 * Cost note: translation is a rounding error next to synthesis. A 400-character
 * guide item costs a fraction of a cent to translate once, while the audio for
 * it is the recurring expense. Caching here mainly avoids latency, not spend.
 */
class TtsTranslationService
{
    public function __construct(
        protected AiService $ai,
        protected TtsLanguageResolver $resolver,
    ) {
    }

    /**
     * Return the text to be spoken in the target language.
     *
     * Never throws on translation failure: if the model is unavailable the
     * original text is returned so the user still hears something rather
     * than seeing an error.
     */
    public function translate(string $text, string $targetLanguage): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        // Source language needs no translation.
        if ($this->resolver->isSourceLanguage($targetLanguage)) {
            return $text;
        }

        if (! config('tts.translation.enabled', true)) {
            return $text;
        }

        $hash = TtsTranslation::makeHash($text);

        $cached = TtsTranslation::where('source_hash', $hash)
            ->where('target_language', $targetLanguage)
            ->first();

        if ($cached) {
            return $cached->translated_text;
        }

        try {
            $translated = $this->callModel($text, $targetLanguage);
        } catch (Exception $e) {
            Log::warning('TTS translation failed, falling back to source text', [
                'language' => $targetLanguage,
                'message'  => $e->getMessage(),
            ]);

            return $text;
        }

        $this->store($hash, $text, $translated, $targetLanguage);

        return $translated;
    }

    /**
     * Send the translation prompt to Gemini.
     *
     * @throws Exception
     */
    protected function callModel(string $text, string $targetLanguage): string
    {
        $languageName = $this->resolver->geminiName($targetLanguage);
        $model        = (string) config('tts.translation.model');

        /*
        | The system prompt is deliberately strict. The output is fed directly
        | into a speech synthesiser, so any preamble ("Here is the translation:")
        | would be read aloud to the customer.
        */
        $systemPrompt = <<<PROMPT
        You are a translation engine for a plant nursery's product care guide.

        Rules:
        - Translate the user's text into {$languageName}.
        - Output ONLY the translation. No preamble, no notes, no quotes, no
          explanation, no romanisation.
        - Use the target language's native script.
        - Keep the tone plain and instructional, as written for a customer
          caring for a plant at home.
        - Preserve numbers, measurements and units exactly.
        - Keep botanical or scientific plant names in their original Latin form.
        - The output will be read aloud by a speech synthesiser, so avoid
          brackets, asterisks, bullet characters and other symbols that do not
          belong in spoken language.
        PROMPT;

        $translated = $this->ai->ask($text, $systemPrompt, $model);

        $translated = trim($translated);

        if ($translated === '') {
            throw new Exception('Translation model returned empty output.');
        }

        return $translated;
    }

    /**
     * Persist the translation, tolerating a concurrent insert of the same row.
     */
    protected function store(
        string $hash,
        string $source,
        string $translated,
        string $targetLanguage
    ): void {
        try {
            TtsTranslation::create([
                'source_hash'     => $hash,
                'target_language' => $targetLanguage,
                'source_text'     => $source,
                'translated_text' => $translated,
                'provider'        => 'gemini',
                'model'           => (string) config('tts.translation.model'),
                'characters'      => mb_strlen($translated),
            ]);
        } catch (QueryException $e) {
            // Another request cached the same translation first. Harmless.
            Log::debug('TTS translation already cached by a concurrent request', [
                'language' => $targetLanguage,
            ]);
        }
    }
}